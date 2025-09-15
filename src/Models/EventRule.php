<?php

namespace St693ava\FilamentEventsManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class EventRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'is_active',
        'priority',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (EventRule $eventRule) {
            // Manually delete related records to ensure cascade deletion works
            // even if migration hasn't been run yet
            $eventRule->eventLogs()->delete();
            $eventRule->conditions()->delete();
            $eventRule->actions()->delete();
        });
    }

    // Relationships
    public function conditions(): HasMany
    {
        return $this->hasMany(EventRuleCondition::class)->orderBy('sort_order');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(EventRuleAction::class)->orderBy('sort_order');
    }

    public function eventLogs(): HasMany
    {
        return $this->hasMany(EventLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_user_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeByTriggerType(Builder $query, string $type): Builder
    {
        return $query->where('trigger_type', $type);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc');
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getTriggerModelClass(): ?string
    {
        return $this->trigger_config['model'] ?? null;
    }

    public function getTriggerEvents(): array
    {
        if ($this->trigger_type !== 'eloquent') {
            return [];
        }

        return $this->trigger_config['events'] ?? [];
    }

    public function hasConditions(): bool
    {
        return $this->conditions()->exists();
    }

    public function hasActions(): bool
    {
        return $this->actions()->exists();
    }

    public function matchesEvent(string $triggerType, ?string $modelClass = null, ?string $eventName = null): bool
    {
        if (!$this->is_active || $this->trigger_type !== $triggerType) {
            return false;
        }

        if ($triggerType === 'eloquent') {
            $configModel = $this->getTriggerModelClass();
            $configEvents = $this->getTriggerEvents();

            return $configModel === $modelClass && in_array($eventName, $configEvents);
        }

        // Add logic for other trigger types as needed
        return false;
    }
}