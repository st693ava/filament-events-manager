<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Support\EventContext;

class ConditionEvaluator
{
    protected FieldPathResolver $fieldPathResolver;

    public function __construct()
    {
        $this->fieldPathResolver = new FieldPathResolver;
    }

    public function evaluate(Collection $conditions, array $data, EventContext $context): bool
    {
        if ($conditions->isEmpty()) {
            return true;
        }

        // Ordenar por prioridade e depois por order
        $sortedConditions = $conditions->sortByDesc('priority')->sortBy('sort_order');

        // Construir expressão com parêntesis e avaliar
        return $this->evaluateExpression($sortedConditions, $data, $context);
    }

    /**
     * Avalia uma expressão completa com suporte a parêntesis
     */
    private function evaluateExpression(Collection $conditions, array $data, EventContext $context): bool
    {
        if ($conditions->isEmpty()) {
            return true;
        }

        // Converter condições para uma expressão avaliável
        $expression = $this->buildExpression($conditions, $data, $context);

        // Avaliar a expressão
        return $this->evaluateExpressionString($expression);
    }

    /**
     * Constrói uma string de expressão booleana com os resultados das condições
     */
    private function buildExpression(Collection $conditions, array $data, EventContext $context): string
    {
        $expression = '';
        $previousLogicalOperator = null;

        foreach ($conditions as $index => $condition) {
            // Adicionar operador lógico da condição anterior (exceto para a primeira condição)
            if ($index > 0 && $previousLogicalOperator !== null) {
                $expression .= ' '.$previousLogicalOperator.' ';
            }

            // Adicionar parêntesis de abertura
            if ($condition->hasGroupStart()) {
                $expression .= $condition->group_start.' ';
            }

            // Avaliar a condição individual e adicionar o resultado
            $conditionResult = $this->evaluateCondition($condition, $data, $context);
            $expression .= $conditionResult ? 'true' : 'false';

            // Adicionar parêntesis de fecho
            if ($condition->hasGroupEnd()) {
                $expression .= ' '.$condition->group_end;
            }

            // Guardar o operador lógico desta condição para a próxima iteração
            $previousLogicalOperator = $condition->logical_operator;
        }

        return $expression;
    }

    /**
     * Avalia uma string de expressão booleana
     */
    private function evaluateExpressionString(string $expression): bool
    {
        // Limpar a expressão
        $expression = trim($expression);

        if (empty($expression)) {
            return true;
        }

        // Substituir operadores por equivalentes PHP
        $expression = str_replace(['AND', 'OR'], ['&&', '||'], $expression);

        // Avaliar a expressão de forma segura
        try {
            return eval("return $expression;");
        } catch (\Throwable $e) {
            // Em caso de erro, usar fallback
            return $this->evaluateExpressionFallback($expression);
        }
    }

    /**
     * Fallback para avaliação de expressão sem eval()
     */
    private function evaluateExpressionFallback(string $expression): bool
    {
        // Implementação simplificada para casos básicos
        $expression = str_replace(['&&', '||'], ['AND', 'OR'], $expression);

        // Se contém apenas 'true' ou 'false', avaliar diretamente
        if (preg_match('/^(true|false)(\s+(AND|OR)\s+(true|false))*$/i', $expression)) {
            $tokens = preg_split('/\s+(AND|OR)\s+/i', $expression, -1, PREG_SPLIT_DELIM_CAPTURE);

            $result = $tokens[0] === 'true';

            for ($i = 1; $i < count($tokens); $i += 2) {
                $operator = strtoupper($tokens[$i]);
                $value = $tokens[$i + 1] === 'true';

                if ($operator === 'AND') {
                    $result = $result && $value;
                } else {
                    $result = $result || $value;
                }
            }

            return $result;
        }

        // Para expressões complexas, retornar true como fallback seguro
        return true;
    }

    private function evaluateCondition(EventRuleCondition $condition, array $data, EventContext $context): bool
    {
        $fieldValue = $this->extractFieldValue($condition->field_path, $data, $context);
        $conditionValue = $condition->getDecodedValue();

        return match ($condition->operator) {
            '=' => $this->compareEquals($fieldValue, $conditionValue),
            '!=' => ! $this->compareEquals($fieldValue, $conditionValue),
            '>' => $this->compareGreater($fieldValue, $conditionValue),
            '<' => $this->compareLess($fieldValue, $conditionValue),
            '>=' => $this->compareGreaterOrEqual($fieldValue, $conditionValue),
            '<=' => $this->compareLessOrEqual($fieldValue, $conditionValue),
            'contains' => $this->compareContains($fieldValue, $conditionValue),
            'starts_with' => $this->compareStartsWith($fieldValue, $conditionValue),
            'ends_with' => $this->compareEndsWith($fieldValue, $conditionValue),
            'in' => $this->compareIn($fieldValue, $conditionValue),
            'not_in' => ! $this->compareIn($fieldValue, $conditionValue),
            'changed' => $this->compareChanged($condition->field_path, $data),
            'was' => $this->compareWas($condition->field_path, $conditionValue, $data),
            default => false,
        };
    }

    private function extractFieldValue(string $fieldPath, array $data, EventContext $context): mixed
    {
        // Primeiro, tentar em todos os items dos dados usando o FieldPathResolver
        foreach ($data as $item) {
            $value = $this->fieldPathResolver->resolve($fieldPath, $item);
            if ($value !== null) {
                return $value;
            }
        }

        // Tentar no contexto
        $contextValue = $context->get($fieldPath);
        if ($contextValue !== null) {
            return $contextValue;
        }

        // Tentar resolver o field path como array no contexto
        if (str_contains($fieldPath, '.')) {
            $contextData = $context->all();
            $value = $this->fieldPathResolver->resolve($fieldPath, $contextData);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function compareEquals(mixed $fieldValue, mixed $conditionValue): bool
    {
        return $fieldValue == $conditionValue;
    }

    private function compareGreater(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_numeric($fieldValue) || ! is_numeric($conditionValue)) {
            return false;
        }

        return $fieldValue > $conditionValue;
    }

    private function compareLess(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_numeric($fieldValue) || ! is_numeric($conditionValue)) {
            return false;
        }

        return $fieldValue < $conditionValue;
    }

    private function compareGreaterOrEqual(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_numeric($fieldValue) || ! is_numeric($conditionValue)) {
            return false;
        }

        return $fieldValue >= $conditionValue;
    }

    private function compareLessOrEqual(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_numeric($fieldValue) || ! is_numeric($conditionValue)) {
            return false;
        }

        return $fieldValue <= $conditionValue;
    }

    private function compareContains(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_string($fieldValue) || ! is_string($conditionValue)) {
            return false;
        }

        return str_contains($fieldValue, $conditionValue);
    }

    private function compareStartsWith(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_string($fieldValue) || ! is_string($conditionValue)) {
            return false;
        }

        return str_starts_with($fieldValue, $conditionValue);
    }

    private function compareEndsWith(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_string($fieldValue) || ! is_string($conditionValue)) {
            return false;
        }

        return str_ends_with($fieldValue, $conditionValue);
    }

    private function compareIn(mixed $fieldValue, mixed $conditionValue): bool
    {
        if (! is_array($conditionValue)) {
            return false;
        }

        return in_array($fieldValue, $conditionValue);
    }

    private function compareChanged(string $fieldPath, array $data): bool
    {
        foreach ($data as $item) {
            if ($item instanceof Model && $item->wasChanged($fieldPath)) {
                return true;
            }
        }

        return false;
    }

    private function compareWas(string $fieldPath, mixed $conditionValue, array $data): bool
    {
        foreach ($data as $item) {
            if ($item instanceof Model && $item->getOriginal($fieldPath) == $conditionValue) {
                return true;
            }
        }

        return false;
    }
}
