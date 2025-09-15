<?php

namespace St693ava\FilamentEventsManager\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use St693ava\FilamentEventsManager\Contracts\ActionContract;
use St693ava\FilamentEventsManager\Notifications\EventTriggeredNotification;
use St693ava\FilamentEventsManager\Services\TemplateRenderer;
use St693ava\FilamentEventsManager\Support\EventContext;

class NotificationAction implements ActionContract
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
            // Preparar o conteúdo da notificação
            $title = $this->templateRenderer->render($config['title'] ?? 'Evento Disparado', $data, $context);
            $message = $this->templateRenderer->render($config['message'] ?? '', $data, $context);
            $actionUrl = isset($config['action_url']) ?
                $this->templateRenderer->render($config['action_url'], $data, $context) : null;

            // Determinar canais
            $channels = $config['channels'] ?? ['database'];
            if (! is_array($channels)) {
                $channels = [$channels];
            }

            // Criar a notificação
            $notification = new EventTriggeredNotification($title, $message, $actionUrl, $context->all());

            // Determinar destinatários
            $recipients = $this->resolveRecipients($config, $data, $context);

            $sentCount = 0;
            $errors = [];

            foreach ($recipients as $recipient) {
                try {
                    if ($recipient instanceof Authenticatable || $recipient instanceof Model) {
                        $recipient->notify($notification->via($channels));
                    } elseif (is_string($recipient)) {
                        // Notificação para email
                        NotificationFacade::route('mail', $recipient)
                            ->notify($notification->via($channels));
                    } else {
                        // Usar AnonymousNotifiable
                        $anonymous = new AnonymousNotifiable();
                        if (isset($recipient['email'])) {
                            $anonymous->route('mail', $recipient['email']);
                        }
                        if (isset($recipient['phone'])) {
                            $anonymous->route('sms', $recipient['phone']);
                        }
                        $anonymous->notify($notification->via($channels));
                    }
                    $sentCount++;
                } catch (Exception $e) {
                    $errors[] = "Erro ao enviar para {$this->getRecipientIdentifier($recipient)}: {$e->getMessage()}";
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            if (! empty($errors) && $sentCount === 0) {
                throw new Exception('Todas as notificações falharam: ' . implode('; ', $errors));
            }

            return [
                'status' => empty($errors) ? 'success' : 'partial_success',
                'action_type' => 'notification',
                'title' => $title,
                'message' => $message,
                'channels' => $channels,
                'recipients_count' => count($recipients),
                'sent_count' => $sentCount,
                'errors' => $errors,
                'execution_time_ms' => $executionTime,
                'executed_at' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Notification action failed', [
                'config' => $config,
                'error' => $e->getMessage(),
                'context' => $context->all(),
            ]);

            return [
                'status' => 'failed',
                'action_type' => 'notification',
                'error' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
                'executed_at' => now()->toISOString(),
            ];
        }
    }

    private function resolveRecipients(array $config, array $data, EventContext $context): array
    {
        $recipients = [];

        // Recipient types: users, emails, dynamic
        if (isset($config['recipient_type'])) {
            switch ($config['recipient_type']) {
                case 'users':
                    $recipients = $this->resolveUserRecipients($config, $data, $context);
                    break;

                case 'emails':
                    $recipients = $this->resolveEmailRecipients($config, $data, $context);
                    break;

                case 'dynamic':
                    $recipients = $this->resolveDynamicRecipients($config, $data, $context);
                    break;

                case 'event_user':
                    $recipients = $this->resolveEventUserRecipient($data, $context);
                    break;
            }
        }

        // Fallback: tentar extrair emails da configuração
        if (empty($recipients) && isset($config['emails'])) {
            $emails = is_array($config['emails']) ? $config['emails'] : [$config['emails']];
            foreach ($emails as $email) {
                $renderedEmail = $this->templateRenderer->render($email, $data, $context);
                if (filter_var($renderedEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $renderedEmail;
                }
            }
        }

        return array_filter($recipients);
    }

    private function resolveUserRecipients(array $config, array $data, EventContext $context): array
    {
        $userIds = $config['user_ids'] ?? [];
        if (empty($userIds)) {
            return [];
        }

        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        return $userModel::whereIn('id', $userIds)->get()->toArray();
    }

    private function resolveEmailRecipients(array $config, array $data, EventContext $context): array
    {
        $emails = $config['emails'] ?? [];
        if (! is_array($emails)) {
            $emails = [$emails];
        }

        $recipients = [];
        foreach ($emails as $email) {
            $renderedEmail = $this->templateRenderer->render($email, $data, $context);
            if (filter_var($renderedEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $renderedEmail;
            }
        }

        return $recipients;
    }

    private function resolveDynamicRecipients(array $config, array $data, EventContext $context): array
    {
        $fieldPath = $config['field_path'] ?? '';
        if (empty($fieldPath)) {
            return [];
        }

        $recipients = [];
        foreach ($data as $item) {
            if ($item instanceof Model) {
                $value = data_get($item, $fieldPath);
                if ($value) {
                    if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $value;
                    } elseif ($value instanceof Model) {
                        $recipients[] = $value;
                    }
                }
            }
        }

        return $recipients;
    }

    private function resolveEventUserRecipient(array $data, EventContext $context): array
    {
        $userId = $context->get('user_id');
        if (! $userId) {
            return [];
        }

        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        $user = $userModel::find($userId);

        return $user ? [$user] : [];
    }

    private function getRecipientIdentifier(mixed $recipient): string
    {
        if (is_string($recipient)) {
            return $recipient;
        }

        if ($recipient instanceof Model) {
            return $recipient->email ?? $recipient->getKey();
        }

        if (is_array($recipient) && isset($recipient['email'])) {
            return $recipient['email'];
        }

        return 'Destinatário desconhecido';
    }

    public function validateConfig(array $config): array
    {
        $errors = [];

        if (empty($config['title'])) {
            $errors[] = 'Título da notificação é obrigatório';
        }

        if (empty($config['message'])) {
            $errors[] = 'Mensagem da notificação é obrigatória';
        }

        $recipientType = $config['recipient_type'] ?? null;
        if (! $recipientType) {
            $errors[] = 'Tipo de destinatário é obrigatório';
        }

        if ($recipientType === 'users' && empty($config['user_ids'])) {
            $errors[] = 'IDs de utilizadores são obrigatórios quando tipo é "users"';
        }

        if ($recipientType === 'emails' && empty($config['emails'])) {
            $errors[] = 'Lista de emails é obrigatória quando tipo é "emails"';
        }

        if ($recipientType === 'dynamic' && empty($config['field_path'])) {
            $errors[] = 'Campo dinâmico é obrigatório quando tipo é "dynamic"';
        }

        return $errors;
    }

    public static function getConfigFields(): array
    {
        return [
            'title' => [
                'type' => 'text',
                'label' => 'Título da Notificação',
                'required' => true,
                'placeholder' => 'Evento {{event_name}} disparado',
            ],
            'message' => [
                'type' => 'textarea',
                'label' => 'Mensagem',
                'required' => true,
                'placeholder' => 'O evento foi disparado com sucesso...',
            ],
            'action_url' => [
                'type' => 'text',
                'label' => 'URL da Ação (opcional)',
                'placeholder' => 'https://app.exemplo.com/model/{{model.id}}',
            ],
            'channels' => [
                'type' => 'checkbox_list',
                'label' => 'Canais de Notificação',
                'options' => [
                    'database' => 'Base de Dados',
                    'mail' => 'Email',
                    'broadcast' => 'Broadcasting',
                ],
                'default' => ['database'],
            ],
            'recipient_type' => [
                'type' => 'select',
                'label' => 'Tipo de Destinatário',
                'required' => true,
                'options' => [
                    'users' => 'Utilizadores Específicos',
                    'emails' => 'Lista de Emails',
                    'dynamic' => 'Campo Dinâmico',
                    'event_user' => 'Utilizador do Evento',
                ],
            ],
            'user_ids' => [
                'type' => 'tags_input',
                'label' => 'IDs dos Utilizadores',
                'visible_when' => ['recipient_type' => 'users'],
            ],
            'emails' => [
                'type' => 'tags_input',
                'label' => 'Lista de Emails',
                'visible_when' => ['recipient_type' => 'emails'],
            ],
            'field_path' => [
                'type' => 'text',
                'label' => 'Campo para Email',
                'placeholder' => 'user.email',
                'visible_when' => ['recipient_type' => 'dynamic'],
            ],
        ];
    }
}