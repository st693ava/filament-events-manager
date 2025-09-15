<?php

namespace St693ava\FilamentEventsManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EventLog extends Model
{
    use HasFactory;

    // Não usa timestamps padrão pois temos triggered_at
    public $timestamps = false;

    protected $fillable = [
        'event_rule_id',
        'trigger_type',
        'model_type',
        'model_id',
        'event_name',
        'context',
        'actions_executed',
        'execution_time_ms',
        'triggered_at',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'request_url',
        'request_method',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'actions_executed' => 'array',
            'execution_time_ms' => 'integer',
            'triggered_at' => 'datetime',
            'model_id' => 'integer',
            'event_rule_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    // Relationships
    public function eventRule(): BelongsTo
    {
        return $this->belongsTo(EventRule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    // Scopes
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }

    public function scopeByModel(Builder $query, string $modelType, ?int $modelId = null): Builder
    {
        $query = $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereJsonDoesntContain('actions_executed', ['status' => 'failed']);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereJsonContains('actions_executed', ['status' => 'failed']);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('triggered_at', 'desc');
    }

    // Helper methods
    public function getExecutionTimeInSeconds(): float
    {
        return $this->execution_time_ms / 1000;
    }

    public function wasSuccessful(): bool
    {
        foreach ($this->actions_executed as $action) {
            if (($action['status'] ?? 'success') === 'failed') {
                return false;
            }
        }
        return true;
    }

    public function getFailedActions(): array
    {
        return array_filter($this->actions_executed, function ($action) {
            return ($action['status'] ?? 'success') === 'failed';
        });
    }

    public function getSuccessfulActions(): array
    {
        return array_filter($this->actions_executed, function ($action) {
            return ($action['status'] ?? 'success') === 'success';
        });
    }

    public function getActionsCount(): int
    {
        return count($this->actions_executed);
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->context, $key, $default);
    }

    public function getModelClass(): ?string
    {
        return $this->model_type;
    }

    public function hasModel(): bool
    {
        return !empty($this->model_type) && !empty($this->model_id);
    }
}