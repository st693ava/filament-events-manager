<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Database\Eloquent\Model;
use St693ava\FilamentEventsManager\Support\EventContext;

class TemplateRenderer
{
    private FieldPathResolver $fieldPathResolver;

    public function __construct()
    {
        $this->fieldPathResolver = new FieldPathResolver;
    }

    /**
     * Renderiza um template substituindo placeholders por valores reais
     */
    public function render(string $template, array $data, EventContext $context): string
    {
        if (empty($template)) {
            return '';
        }

        // Suportar ambos os formatos: {placeholder} e {{placeholder}}
        $rendered = $template;

        // Processar placeholders de chaves duplas {{placeholder}}
        $doublePlaceholders = $this->extractDoublePlaceholders($template);
        foreach ($doublePlaceholders as $placeholder) {
            $value = $this->resolvePlaceholder($placeholder, $data, $context);
            $rendered = str_replace("{{{$placeholder}}}", $this->formatValue($value), $rendered);
        }

        // Processar placeholders de chaves simples {placeholder}
        $singlePlaceholders = $this->extractSinglePlaceholders($rendered);
        foreach ($singlePlaceholders as $placeholder) {
            $value = $this->resolvePlaceholder($placeholder, $data, $context);
            $rendered = str_replace("{{$placeholder}}", $this->formatValue($value), $rendered);
        }

        return $rendered;
    }

    /**
     * Renderiza um array de templates recursivamente
     */
    public function renderArray(array $templates, array $data, EventContext $context): array
    {
        $rendered = [];

        foreach ($templates as $key => $value) {
            if (is_array($value)) {
                $rendered[$key] = $this->renderArray($value, $data, $context);
            } elseif (is_string($value)) {
                $rendered[$key] = $this->render($value, $data, $context);
            } else {
                $rendered[$key] = $value;
            }
        }

        return $rendered;
    }

    /**
     * Extrai todos os placeholders de um template (backwards compatibility)
     */
    private function extractPlaceholders(string $template): array
    {
        return $this->extractDoublePlaceholders($template);
    }

    /**
     * Extrai placeholders de chaves duplas {{placeholder}}
     */
    private function extractDoublePlaceholders(string $template): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);

        return array_unique($matches[1]);
    }

    /**
     * Extrai placeholders de chaves simples {placeholder}
     */
    private function extractSinglePlaceholders(string $template): array
    {
        // Evitar placeholders que já são duplos {{}}
        preg_match_all('/(?<!\{)\{([^{}]+)\}(?!\})/', $template, $matches);

        return array_unique($matches[1]);
    }

    /**
     * Resolve um placeholder específico
     */
    private function resolvePlaceholder(string $placeholder, array $data, EventContext $context): mixed
    {
        $placeholder = trim($placeholder);

        \Illuminate\Support\Facades\Log::info('TemplateRenderer: Resolving placeholder', [
            'placeholder' => $placeholder,
            'data_types' => array_map('get_class', array_filter($data, 'is_object')),
            'data_count' => count($data),
            'context_keys' => array_keys($context->all()),
        ]);

        // Primeiro, tentar resolver no contexto
        if (str_contains($placeholder, '.')) {
            $contextValue = $this->fieldPathResolver->resolve($placeholder, $context->all());
            if ($contextValue !== null) {
                \Illuminate\Support\Facades\Log::info('TemplateRenderer: Resolved from context (dotted)', [
                    'placeholder' => $placeholder,
                    'value' => $contextValue,
                ]);
                return $contextValue;
            }
        } else {
            $contextValue = $context->get($placeholder);
            if ($contextValue !== null) {
                \Illuminate\Support\Facades\Log::info('TemplateRenderer: Resolved from context (simple)', [
                    'placeholder' => $placeholder,
                    'value' => $contextValue,
                ]);
                return $contextValue;
            }
        }

        // Depois, tentar resolver nos dados dos modelos
        foreach ($data as $key => $item) {
            \Illuminate\Support\Facades\Log::info('TemplateRenderer: Trying to resolve from data item', [
                'placeholder' => $placeholder,
                'item_type' => get_class($item),
                'item_key' => $key,
            ]);

            $value = $this->fieldPathResolver->resolve($placeholder, $item);
            if ($value !== null) {
                \Illuminate\Support\Facades\Log::info('TemplateRenderer: Resolved from model data', [
                    'placeholder' => $placeholder,
                    'value' => $value,
                    'item_type' => get_class($item),
                ]);
                return $value;
            }
        }

        // Tentar placeholders especiais
        $specialValue = $this->resolveSpecialPlaceholder($placeholder, $data, $context);
        if ($specialValue !== null) {
            \Illuminate\Support\Facades\Log::info('TemplateRenderer: Resolved as special placeholder', [
                'placeholder' => $placeholder,
                'value' => $specialValue,
            ]);
            return $specialValue;
        }

        // Tentar placeholder simples no primeiro item dos dados
        if (! empty($data)) {
            $firstItem = reset($data);
            if (is_array($firstItem) && isset($firstItem[$placeholder])) {
                \Illuminate\Support\Facades\Log::info('TemplateRenderer: Resolved from first array item', [
                    'placeholder' => $placeholder,
                    'value' => $firstItem[$placeholder],
                ]);
                return $firstItem[$placeholder];
            }
        }

        \Illuminate\Support\Facades\Log::warning('TemplateRenderer: Placeholder not resolved', [
            'placeholder' => $placeholder,
        ]);

        return null;
    }

    /**
     * Resolve placeholders especiais do sistema
     */
    private function resolveSpecialPlaceholder(string $placeholder, array $data, EventContext $context): mixed
    {
        return match ($placeholder) {
            'now' => now()->toISOString(),
            'today' => now()->toDateString(),
            'timestamp' => now()->timestamp,
            'app.name' => config('app.name'),
            'app.url' => config('app.url'),
            'event.name' => $context->get('event_name'),
            'event.model' => $context->get('model_type'),
            'user.id' => $context->get('user_id'),
            'user.name' => $context->get('user_name'),
            'user.email' => $context->get('user_email'),
            'ip' => $context->get('ip_address'),
            'url' => $context->get('request_url'),
            'method' => $context->get('request_method'),
            default => null,
        };
    }

    /**
     * Formata um valor para exibição, garantindo segurança
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return json_encode($value->toArray(), JSON_UNESCAPED_UNICODE);
            }
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $stringValue = (string) $value;

        // Escape básico para prevenir injeção
        $stringValue = $this->escapeValue($stringValue);

        return $stringValue;
    }

    /**
     * Escape básico de dados sensíveis
     */
    private function escapeValue(string $value): string
    {
        // Lista de campos que devem ser mascarados
        $sensitivePatterns = [
            '/password/i',
            '/token/i',
            '/secret/i',
            '/key/i',
            '/api_key/i',
            '/access_token/i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return '***MASKED***';
            }
        }

        // Escape HTML básico
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Gera um preview de como um template será renderizado
     */
    public function preview(string $template, array $sampleData = []): string
    {
        $placeholders = $this->extractPlaceholders($template);
        $preview = $template;

        foreach ($placeholders as $placeholder) {
            $sampleValue = $sampleData[$placeholder] ?? $this->generateSampleValue($placeholder);
            $preview = str_replace("{{{$placeholder}}}", "[{$sampleValue}]", $preview);
        }

        return $preview;
    }

    /**
     * Gera um valor de exemplo para um placeholder
     */
    private function generateSampleValue(string $placeholder): string
    {
        return match (true) {
            str_contains($placeholder, 'email') => 'utilizador@exemplo.com',
            str_contains($placeholder, 'name') => 'João Silva',
            str_contains($placeholder, 'id') => '123',
            str_contains($placeholder, 'url') => 'https://exemplo.com',
            str_contains($placeholder, 'date') => '2025-09-15',
            str_contains($placeholder, 'time') => '14:30:00',
            default => "exemplo_{$placeholder}",
        };
    }

    /**
     * Valida se um template está bem formado
     */
    public function validateTemplate(string $template): array
    {
        $errors = [];

        // Verificar se há placeholders malformados
        if (preg_match_all('/\{[^{]*\{[^}]*\}[^}]*\}/', $template, $matches)) {
            $errors[] = 'Placeholders aninhados não são suportados: '.implode(', ', $matches[0]);
        }

        // Verificar se há chaves desbalanceadas
        $openBraces = substr_count($template, '{{');
        $closeBraces = substr_count($template, '}}');

        if ($openBraces !== $closeBraces) {
            $errors[] = 'Chaves desbalanceadas: '.$openBraces.' aberturas, '.$closeBraces.' fechos';
        }

        // Verificar placeholders vazios
        if (preg_match('/\{\{\s*\}\}/', $template)) {
            $errors[] = 'Placeholders vazios encontrados';
        }

        return $errors;
    }

    /**
     * Lista todos os placeholders disponíveis para um conjunto de dados
     */
    public function getAvailablePlaceholders(array $data, ?string $modelClass = null): array
    {
        $placeholders = [
            // Placeholders especiais do sistema
            'now' => 'Data/hora atual (ISO)',
            'today' => 'Data atual',
            'timestamp' => 'Timestamp Unix',
            'app.name' => 'Nome da aplicação',
            'app.url' => 'URL da aplicação',
            'event.name' => 'Nome do evento',
            'event.model' => 'Tipo do modelo',
            'user.id' => 'ID do utilizador',
            'user.name' => 'Nome do utilizador',
            'user.email' => 'Email do utilizador',
            'ip' => 'Endereço IP',
            'url' => 'URL do request',
            'method' => 'Método HTTP',
        ];

        // Adicionar placeholders do modelo, se fornecido
        if ($modelClass) {
            $suggestions = $this->fieldPathResolver->getFieldPathSuggestions($modelClass, 2);
            foreach ($suggestions as $path) {
                $placeholders[$path] = "Campo do modelo: {$path}";
            }
        }

        // Adicionar placeholders dos dados fornecidos
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                foreach (array_keys($item) as $field) {
                    $placeholders[$field] = "Campo dos dados: {$field}";
                }
            } elseif ($item instanceof Model) {
                $attributes = array_keys($item->getAttributes());
                foreach ($attributes as $attr) {
                    $placeholders[$attr] = "Atributo do modelo: {$attr}";
                }
            }
        }

        return $placeholders;
    }
}
