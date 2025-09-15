<?php

namespace St693ava\FilamentEventsManager\Actions;

use St693ava\FilamentEventsManager\Actions\Contracts\ActionExecutor;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Support\EventContext;

class ActionManager
{
    private array $executors = [];

    public function register(string $type, string $executorClass): void
    {
        if (!is_subclass_of($executorClass, ActionExecutor::class)) {
            throw new \InvalidArgumentException("Executor deve implementar ActionExecutor interface");
        }

        $this->executors[$type] = $executorClass;
    }

    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        $executor = $this->getExecutor($action->action_type);

        if (!$executor) {
            throw new \InvalidArgumentException("Executor não encontrado para ação tipo: {$action->action_type}");
        }

        return $executor->execute($action, $data, $context);
    }

    public function validateConfig(string $actionType, array $config): array
    {
        $executor = $this->getExecutor($actionType);

        if (!$executor) {
            return ["Tipo de ação não suportado: {$actionType}"];
        }

        return $executor->validateConfig($config);
    }

    public function getRegisteredTypes(): array
    {
        return array_keys($this->executors);
    }

    public function hasExecutor(string $type): bool
    {
        return isset($this->executors[$type]);
    }

    private function getExecutor(string $type): ?ActionExecutor
    {
        if (!isset($this->executors[$type])) {
            return null;
        }

        $executorClass = $this->executors[$type];
        return app($executorClass);
    }

    public function getAvailableActions(): array
    {
        return [
            'email' => [
                'name' => 'Enviar Email',
                'description' => 'Enviar email personalizado com templates',
                'icon' => 'envelope',
                'config_fields' => [
                    'to' => 'Email de destino',
                    'subject' => 'Assunto',
                    'body' => 'Corpo do email',
                    'cc' => 'Cópia (opcional)',
                    'bcc' => 'Cópia oculta (opcional)',
                ],
            ],
            'activity_log' => [
                'name' => 'Registo de Atividade',
                'description' => 'Criar entrada no registo de atividade',
                'icon' => 'document-text',
                'config_fields' => [
                    'description' => 'Descrição da atividade',
                    'log_name' => 'Nome do log (opcional)',
                    'properties' => 'Propriedades personalizadas (opcional)',
                ],
            ],
            'webhook' => [
                'name' => 'Webhook HTTP',
                'description' => 'Enviar dados via webhook HTTP com retry automático',
                'icon' => 'globe-alt',
                'config_fields' => [
                    'url' => 'URL do webhook',
                    'method' => 'Método HTTP',
                    'headers' => 'Headers personalizados (opcional)',
                    'payload' => 'Payload personalizado (opcional)',
                    'timeout' => 'Timeout em segundos',
                    'retries' => 'Número de tentativas',
                ],
            ],
            'notification' => [
                'name' => 'Notificação do Sistema',
                'description' => 'Enviar notificação através de múltiplos canais',
                'icon' => 'bell',
                'config_fields' => [
                    'title' => 'Título da notificação',
                    'message' => 'Mensagem',
                    'action_url' => 'URL da ação (opcional)',
                    'channels' => 'Canais de notificação',
                    'recipient_type' => 'Tipo de destinatário',
                ],
            ],
        ];
    }
}