<?php

namespace St693ava\FilamentEventsManager\Actions\Executors;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use St693ava\FilamentEventsManager\Actions\Contracts\ActionExecutor;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Services\TemplateRenderer;
use St693ava\FilamentEventsManager\Support\EventContext;

class EmailAction implements ActionExecutor
{
    public function __construct(
        private TemplateRenderer $templateRenderer
    ) {}

    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        $config = $action->action_config;

        // Renderizar templates
        $to = $this->templateRenderer->render($config['to'], $data, $context);
        $subject = $this->templateRenderer->render($config['subject'], $data, $context);
        $body = $this->templateRenderer->render($config['body'], $data, $context);

        // Preparar dados adicionais
        $cc = !empty($config['cc']) ? $this->templateRenderer->render($config['cc'], $data, $context) : null;
        $bcc = !empty($config['bcc']) ? $this->templateRenderer->render($config['bcc'], $data, $context) : null;
        $fromEmail = $config['from_email'] ?? config('mail.from.address');
        $fromName = $config['from_name'] ?? config('mail.from.name');

        // Enviar email
        Mail::html($body, function ($message) use ($to, $subject, $cc, $bcc, $fromEmail, $fromName) {
            $message->to($to)
                ->subject($subject);

            if ($fromEmail) {
                $message->from($fromEmail, $fromName);
            }

            if ($cc) {
                $message->cc($cc);
            }

            if ($bcc) {
                $message->bcc($bcc);
            }
        });

        return [
            'to' => $to,
            'subject' => $subject,
            'cc' => $cc,
            'bcc' => $bcc,
            'body_length' => strlen($body),
            'sent_at' => now()->toISOString(),
        ];
    }

    public function validateConfig(array $config): array
    {
        $validator = Validator::make($config, [
            'to' => 'required|string',
            'subject' => 'required|string',
            'body' => 'required|string',
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'from_email' => 'nullable|email',
            'from_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }
}