<?php

namespace St693ava\FilamentEventsManager\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventTriggeredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private array $channels = ['database'];

    public function __construct(
        private string $title,
        private string $message,
        private ?string $actionUrl = null,
        private array $context = []
    ) {
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return $this->channels;
    }

    public function setChannels(array $channels): self
    {
        $this->channels = $channels;
        return $this;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Olá!')
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action('Ver Detalhes', $this->actionUrl);
        }

        $mail->line('Esta notificação foi gerada automaticamente pelo sistema de eventos.');

        return $mail;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'context' => $this->context,
            'event_name' => $this->context['event_name'] ?? null,
            'model_type' => $this->context['model_type'] ?? null,
            'triggered_at' => $this->context['triggered_at'] ?? now()->toISOString(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'type' => 'event_triggered',
            'timestamp' => now()->toISOString(),
        ]))->onQueue('broadcast');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'context' => $this->context,
        ];
    }
}