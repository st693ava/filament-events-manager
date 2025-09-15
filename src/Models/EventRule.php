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
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
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

    public function logs(): HasMany
    {
        return $this->hasMany(EventLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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
}