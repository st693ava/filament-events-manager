<?php

namespace St693ava\FilamentEventsManager\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FieldPathResolver
{
    /**
     * Resolve um field path em um modelo ou array de dados
     */
    public function resolve(string $fieldPath, mixed $data): mixed
    {
        if (empty($fieldPath) || $data === null) {
            return null;
        }

        // Se for um modelo Eloquent
        if ($data instanceof Model) {
            return $this->resolveFromModel($fieldPath, $data);
        }

        // Se for um array
        if (is_array($data)) {
            return Arr::get($data, $fieldPath);
        }

        // Se for um objeto
        if (is_object($data)) {
            return $this->resolveFromObject($fieldPath, $data);
        }

        return null;
    }

    /**
     * Resolve um field path em um modelo Eloquent
     */
    protected function resolveFromModel(string $fieldPath, Model $model): mixed
    {
        $segments = explode('.', $fieldPath);
        $current = $model;

        foreach ($segments as $segment) {
            if ($current === null) {
                return null;
            }

            // Se for o último segmento, tentar getAttribute
            if ($segment === end($segments)) {
                if ($current instanceof Model) {
                    return $current->getAttribute($segment);
                }

                if (is_array($current)) {
                    return $current[$segment] ?? null;
                }

                if (is_object($current)) {
                    return $current->{$segment} ?? null;
                }

                return null;
            }

            // Para segmentos intermediários, carregar relacionamentos
            if ($current instanceof Model) {
                // Verificar se é um relacionamento
                if (method_exists($current, $segment)) {
                    try {
                        $relation = $current->$segment();

                        // Se o relacionamento não está carregado, carregá-lo
                        if (! $current->relationLoaded($segment)) {
                            $current->load($segment);
                        }

                        $current = $current->getRelation($segment);
                    } catch (Exception $e) {
                        // Se não for um relacionamento válido, tentar como atributo
                        $current = $current->getAttribute($segment);
                    }
                } else {
                    // Tentar como atributo direto
                    $current = $current->getAttribute($segment);
                }
            } else {
                // Se não for um modelo, tentar como array/objeto
                if (is_array($current)) {
                    $current = $current[$segment] ?? null;
                } elseif (is_object($current)) {
                    $current = $current->{$segment} ?? null;
                } else {
                    return null;
                }
            }
        }

        return $current;
    }

    /**
     * Resolve um field path em um objeto
     */
    protected function resolveFromObject(string $fieldPath, object $object): mixed
    {
        $segments = explode('.', $fieldPath);
        $current = $object;

        foreach ($segments as $segment) {
            if ($current === null || ! is_object($current)) {
                return null;
            }

            if (property_exists($current, $segment)) {
                $current = $current->{$segment};
            } elseif (method_exists($current, $segment)) {
                $current = $current->{$segment}();
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Verifica se um field path é válido para um modelo
     */
    public function isValidFieldPath(string $fieldPath, string $modelClass): bool
    {
        try {
            $model = new $modelClass;
            $segments = explode('.', $fieldPath);
            $current = $model;

            foreach ($segments as $index => $segment) {
                if (! $current instanceof Model) {
                    return false;
                }

                // Para o último segmento, verificar se existe como atributo ou relacionamento
                if ($index === count($segments) - 1) {
                    return $this->hasAttributeOrRelation($current, $segment);
                }

                // Para segmentos intermediários, deve ser um relacionamento
                if (! method_exists($current, $segment)) {
                    return false;
                }

                // Tentar obter o modelo relacionado
                try {
                    $relation = $current->$segment();
                    $relatedModel = $relation->getRelated();
                    $current = $relatedModel;
                } catch (Exception $e) {
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Verifica se um modelo tem um atributo ou relacionamento
     */
    protected function hasAttributeOrRelation(Model $model, string $name): bool
    {
        // Verificar se é um relacionamento
        if (method_exists($model, $name)) {
            return true;
        }

        // Verificar se está no fillable
        if (in_array($name, $model->getFillable())) {
            return true;
        }

        // Verificar se está no array de atributos do modelo
        if (array_key_exists($name, $model->getAttributes())) {
            return true;
        }

        // Verificar campos de timestamps
        if (in_array($name, ['created_at', 'updated_at', 'deleted_at'])) {
            return true;
        }

        // Verificar chave primária
        if ($name === $model->getKeyName()) {
            return true;
        }

        return false;
    }

    /**
     * Obter sugestões de field paths para um modelo
     */
    public function getFieldPathSuggestions(string $modelClass, int $maxDepth = 2): array
    {
        try {
            $model = new $modelClass;
            $suggestions = [];

            // Adicionar campos diretos
            $suggestions = array_merge($suggestions, $this->getDirectFields($model));

            // Adicionar relacionamentos (até a profundidade máxima)
            if ($maxDepth > 0) {
                $suggestions = array_merge($suggestions, $this->getRelationshipFields($model, $maxDepth));
            }

            return array_unique($suggestions);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obter campos diretos de um modelo
     */
    protected function getDirectFields(Model $model): array
    {
        $fields = [];

        // Fillable fields
        $fields = array_merge($fields, $model->getFillable());

        // Common timestamp fields
        $fields[] = 'id';
        $fields[] = 'created_at';
        $fields[] = 'updated_at';

        // Se usar soft deletes
        if (method_exists($model, 'getDeletedAtColumn')) {
            $fields[] = $model->getDeletedAtColumn();
        }

        return array_filter($fields);
    }

    /**
     * Obter campos de relacionamentos
     */
    protected function getRelationshipFields(Model $model, int $depth, string $prefix = ''): array
    {
        if ($depth <= 0) {
            return [];
        }

        $fields = [];
        $methods = get_class_methods($model);

        foreach ($methods as $method) {
            if ($this->isRelationshipMethod($model, $method)) {
                try {
                    $relation = $model->$method();
                    $relatedModel = $relation->getRelated();

                    $relationPrefix = $prefix ? $prefix.'.'.$method : $method;

                    // Adicionar campos diretos do relacionamento
                    foreach ($this->getDirectFields($relatedModel) as $field) {
                        $fields[] = $relationPrefix.'.'.$field;
                    }

                    // Recursivamente adicionar relacionamentos do modelo relacionado
                    if ($depth > 1) {
                        $fields = array_merge(
                            $fields,
                            $this->getRelationshipFields($relatedModel, $depth - 1, $relationPrefix)
                        );
                    }
                } catch (Exception $e) {
                    // Ignorar relacionamentos que não conseguimos resolver
                    continue;
                }
            }
        }

        return $fields;
    }

    /**
     * Verifica se um método é um relacionamento
     */
    protected function isRelationshipMethod(Model $model, string $method): bool
    {
        // Ignorar métodos mágicos e métodos do framework
        if (str_starts_with($method, '__') ||
            str_starts_with($method, 'get') ||
            str_starts_with($method, 'set') ||
            in_array($method, ['save', 'delete', 'update', 'create', 'find', 'where', 'query'])) {
            return false;
        }

        try {
            $result = $model->$method();

            return $result instanceof \Illuminate\Database\Eloquent\Relations\Relation;
        } catch (Exception $e) {
            return false;
        }
    }
}
