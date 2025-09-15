<?php

namespace St693ava\FilamentEventsManager\Actions;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Contracts\ActionContract;
use St693ava\FilamentEventsManager\Services\TemplateRenderer;
use St693ava\FilamentEventsManager\Support\EventContext;

class WebhookAction implements ActionContract
{
    private TemplateRenderer $templateRenderer;

    public function __construct()
    {
        $this->templateRenderer = new TemplateRenderer();
    }

    public function execute(array $config, array $data, EventContext $context): array
    {
        $startTime = microtime(true);

        try {
            // Validar configuração obrigatória
            $url = $this->templateRenderer->render($config['url'] ?? '', $data, $context);
            if (empty($url)) {
                throw new Exception('URL do webhook é obrigatória');
            }

            // Configurar método HTTP
            $method = strtoupper($config['method'] ?? 'POST');
            if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                $method = 'POST';
            }

            // Preparar headers
            $headers = $this->prepareHeaders($config['headers'] ?? [], $data, $context);

            // Preparar payload
            $payload = $this->preparePayload($config['payload'] ?? [], $data, $context);

            // Configurar timeout e retry
            $timeout = (int) ($config['timeout'] ?? 30);
            $retries = (int) ($config['retries'] ?? 3);

            // Executar webhook com retry
            $response = $this->executeWithRetry($method, $url, $payload, $headers, $timeout, $retries);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'success',
                'action_type' => 'webhook',
                'url' => $url,
                'method' => $method,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'execution_time_ms' => $executionTime,
                'executed_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Webhook action failed', [
                'url' => $url ?? 'unknown',
                'method' => $method ?? 'unknown',
                'error' => $e->getMessage(),
                'context' => $context->all(),
            ]);

            return [
                'status' => 'failed',
                'action_type' => 'webhook',
                'url' => $url ?? 'unknown',
                'method' => $method ?? 'unknown',
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
                'executed_at' => now()->toISOString(),
            ];
        }
    }

    private function prepareHeaders(array $headerConfig, array $data, EventContext $context): array
    {
        $headers = [];

        foreach ($headerConfig as $name => $value) {
            $renderedName = $this->templateRenderer->render($name, $data, $context);
            $renderedValue = $this->templateRenderer->render($value, $data, $context);

            if ($renderedName && $renderedValue) {
                $headers[$renderedName] = $renderedValue;
            }
        }

        // Headers padrão
        if (! isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        if (! isset($headers['User-Agent'])) {
            $headers['User-Agent'] = 'FilamentEventsManager/1.2.0';
        }

        return $headers;
    }

    private function preparePayload(array $payloadConfig, array $data, EventContext $context): array
    {
        if (empty($payloadConfig)) {
            // Payload padrão com todos os dados disponíveis
            return [
                'event' => $context->get('event_name'),
                'triggered_at' => now()->toISOString(),
                'data' => $data,
                'context' => $context->all(),
            ];
        }

        $payload = [];
        foreach ($payloadConfig as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->preparePayload($value, $data, $context);
            } else {
                $payload[$key] = $this->templateRenderer->render($value, $data, $context);
            }
        }

        return $payload;
    }

    private function executeWithRetry(string $method, string $url, array $payload, array $headers, int $timeout, int $retries): Response
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $retries) {
            try {
                $client = Http::timeout($timeout)->withHeaders($headers);

                $response = match ($method) {
                    'GET' => $client->get($url, $payload),
                    'POST' => $client->post($url, $payload),
                    'PUT' => $client->put($url, $payload),
                    'PATCH' => $client->patch($url, $payload),
                    'DELETE' => $client->delete($url, $payload),
                    default => $client->post($url, $payload),
                };

                // Considerar sucesso se status code 2xx ou 3xx
                if ($response->successful() || $response->redirect()) {
                    return $response;
                }

                // Se não for bem-sucedido mas não houver exceção, lançar uma
                if ($attempt >= $retries) {
                    throw new Exception("HTTP {$response->status()}: {$response->body()}");
                }

            } catch (Exception $e) {
                $lastException = $e;

                if ($attempt >= $retries) {
                    throw $e;
                }
            }

            $attempt++;

            // Backoff exponencial: 1s, 2s, 4s...
            if ($attempt <= $retries) {
                sleep(pow(2, $attempt - 1));
            }
        }

        throw $lastException ?? new Exception('Falha desconhecida no webhook');
    }

    public function validateConfig(array $config): array
    {
        $errors = [];

        if (empty($config['url'])) {
            $errors[] = 'URL do webhook é obrigatória';
        }

        if (isset($config['method'])) {
            $method = strtoupper($config['method']);
            if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                $errors[] = 'Método HTTP deve ser GET, POST, PUT, PATCH ou DELETE';
            }
        }

        if (isset($config['timeout']) && (! is_numeric($config['timeout']) || $config['timeout'] < 1)) {
            $errors[] = 'Timeout deve ser um número positivo';
        }

        if (isset($config['retries']) && (! is_numeric($config['retries']) || $config['retries'] < 0)) {
            $errors[] = 'Número de tentativas deve ser um número não negativo';
        }

        return $errors;
    }

    public static function getConfigFields(): array
    {
        return [
            'url' => [
                'type' => 'text',
                'label' => 'URL do Webhook',
                'required' => true,
                'placeholder' => 'https://api.exemplo.com/webhook',
                'helper' => 'Suporta placeholders: {{model.campo}}, {{user.name}}',
            ],
            'method' => [
                'type' => 'select',
                'label' => 'Método HTTP',
                'options' => [
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                ],
                'default' => 'POST',
            ],
            'headers' => [
                'type' => 'key_value',
                'label' => 'Headers HTTP',
                'helper' => 'Headers personalizados. Suporta placeholders.',
            ],
            'payload' => [
                'type' => 'key_value',
                'label' => 'Payload Personalizado',
                'helper' => 'Se vazio, enviará dados do evento automaticamente.',
            ],
            'timeout' => [
                'type' => 'number',
                'label' => 'Timeout (segundos)',
                'default' => 30,
                'min' => 1,
                'max' => 300,
            ],
            'retries' => [
                'type' => 'number',
                'label' => 'Número de Tentativas',
                'default' => 3,
                'min' => 0,
                'max' => 10,
            ],
        ];
    }
}