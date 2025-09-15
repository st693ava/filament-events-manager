<?php

namespace St693ava\FilamentEventsManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EventRuleAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_rule_id',
        'action_type',
        'action_config',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'action_config' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function eventRule(): BelongsTo
    {
        return $this->belongsTo(EventRule::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('action_type', $type);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->action_config[$key] ?? $default;
    }

    public function hasConfig(string $key): bool
    {
        return isset($this->action_config[$key]);
    }

    public function isEmailAction(): bool
    {
        return $this->action_type === 'email';
    }

    public function isWebhookAction(): bool
    {
        return $this->action_type === 'webhook';
    }

    public function isActivityLogAction(): bool
    {
        return $this->action_type === 'activity_log';
    }

    public function isNotificationAction(): bool
    {
        return $this->action_type === 'notification';
    }

    public function requiresTemplate(): bool
    {
        return in_array($this->action_type, ['email', 'notification']);
    }
}