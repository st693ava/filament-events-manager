<?php

namespace St693ava\FilamentEventsManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRuleCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_rule_id',
        'field_path',
        'operator',
        'value',
        'value_type',
        'logical_operator',
        'group_id',
        'sort_order',
    ];

    protected $attributes = [
        'logical_operator' => 'AND',
        'priority' => 0,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'event_rule_id' => 'integer',
            'priority' => 'integer',
        ];
    }

    // Relationships
    public function eventRule(): BelongsTo
    {
        return $this->belongsTo(EventRule::class);
    }

    // Scopes
    public function scopeForRule(Builder $query, int $ruleId): Builder
    {
        return $query->where('event_rule_id', $ruleId);
    }

    public function scopeByGroup(Builder $query, ?string $groupId): Builder
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    // Helper methods
    public function getFieldSegments(): array
    {
        return explode('.', $this->field_path);
    }

    public function isRelationshipField(): bool
    {
        return str_contains($this->field_path, '.');
    }

    public function getDecodedValue(): mixed
    {
        if (empty($this->value)) {
            return null;
        }

        // Tentar decodificar como JSON, se falhar usar como string
        $decoded = json_decode($this->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->value;
    }

    public function getParsedValue(): mixed
    {
        return $this->getDecodedValue();
    }

    public function isDynamic(): bool
    {
        return in_array($this->value_type, ['dynamic', 'model_field']);
    }

    public function requiresValue(): bool
    {
        return !in_array($this->operator, ['changed', 'was']);
    }

    public function isValidOperatorValueCombination(): bool
    {
        if ($this->isArrayOperator()) {
            $value = $this->getParsedValue();
            return is_array($value);
        }

        return true;
    }

    public function requiresModelData(): bool
    {
        return in_array($this->operator, ['changed', 'was']);
    }

    public function isComparisonOperator(): bool
    {
        return in_array($this->operator, ['=', '!=', '>', '<', '>=', '<=']);
    }

    public function isTextOperator(): bool
    {
        return in_array($this->operator, ['contains', 'starts_with', 'ends_with']);
    }

    public function isArrayOperator(): bool
    {
        return in_array($this->operator, ['in', 'not_in']);
    }

    public function hasGroupStart(): bool
    {
        return ! empty($this->group_start);
    }

    public function hasGroupEnd(): bool
    {
        return ! empty($this->group_end);
    }

    public function getGroupLevel(): int
    {
        if ($this->hasGroupStart()) {
            return strlen($this->group_start);
        }
        if ($this->hasGroupEnd()) {
            return strlen($this->group_end);
        }

        return 0;
    }

    // Helper method para construir a expressão completa da condição
    public function toConditionString(): string
    {
        $condition = '';

        if ($this->hasGroupStart()) {
            $condition .= $this->group_start.' ';
        }

        $condition .= $this->field_path.' '.$this->operator;

        if (! in_array($this->operator, ['changed'])) {
            $value = $this->getDecodedValue();
            if (is_array($value)) {
                $condition .= ' ['.implode(', ', $value).']';
            } else {
                $condition .= ' "'.$value.'"';
            }
        }

        if ($this->hasGroupEnd()) {
            $condition .= ' '.$this->group_end;
        }

        return $condition;
    }
}
