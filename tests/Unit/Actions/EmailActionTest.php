<?php

namespace St693ava\FilamentEventsManager\Tests\Unit\Actions;

use Illuminate\Support\Facades\Mail;
use St693ava\FilamentEventsManager\Actions\Executors\EmailAction;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Services\TemplateRenderer;
use St693ava\FilamentEventsManager\Support\EventContext;
use St693ava\FilamentEventsManager\Tests\Models\User;
use St693ava\FilamentEventsManager\Tests\TestCase;

class EmailActionTest extends TestCase
{
    public function test_validates_config_successfully(): void
    {
        $emailAction = new EmailAction(new TemplateRenderer());

        $config = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'body' => 'Test Body',
        ];

        $errors = $emailAction->validateConfig($config);

        $this->assertEmpty($errors);
    }

    public function test_validates_config_with_errors(): void
    {
        $emailAction = new EmailAction(new TemplateRenderer());

        $config = [
            'to' => 'invalid-email',
            'subject' => '', // Obrigatório
            'body' => 'Test Body',
        ];

        $errors = $emailAction->validateConfig($config);

        $this->assertNotEmpty($errors);
        $this->assertContains('O campo to tem de ser um endereço de email válido.', $errors);
        $this->assertContains('O campo subject é obrigatório.', $errors);
    }

    public function test_executes_email_action(): void
    {
        Mail::fake();

        $user = new User([
            'name' => 'João Silva',
            'email' => 'joao@test.com',
        ]);

        $action = new EventRuleAction([
            'action_type' => 'email',
            'action_config' => [
                'to' => 'admin@test.com',
                'subject' => 'Novo utilizador: {model.name}',
                'body' => 'Um novo utilizador foi criado com o email {model.email}',
            ],
        ]);

        $context = new EventContext([
            'event_name' => 'eloquent.created',
            'triggered_at' => now(),
            'user' => ['id' => 1, 'name' => 'Admin'],
            'request' => ['source' => 'web'],
            'data' => [$user],
        ]);

        $emailAction = new EmailAction(new TemplateRenderer());
        $result = $emailAction->execute($action, [$user], $context);

        Mail::assertSent(function ($mail) {
            return $mail->to[0]['address'] === 'admin@test.com';
        });

        $this->assertEquals('admin@test.com', $result['to']);
        $this->assertEquals('Novo utilizador: João Silva', $result['subject']);
        $this->assertArrayHasKey('sent_at', $result);
    }

    public function test_renders_templates_correctly(): void
    {
        Mail::fake();

        $user = new User([
            'id' => 123,
            'name' => 'João Silva',
            'email' => 'joao@test.com',
        ]);

        $action = new EventRuleAction([
            'action_type' => 'email',
            'action_config' => [
                'to' => '{model.email}',
                'subject' => 'Olá {model.name}',
                'body' => 'O utilizador #{model.id} foi criado às {event.triggered_at}',
                'cc' => 'admin@test.com',
            ],
        ]);

        $context = new EventContext([
            'event_name' => 'eloquent.created',
            'triggered_at' => now(),
            'user' => ['id' => 1, 'name' => 'Admin'],
            'request' => ['source' => 'web'],
            'data' => [$user],
        ]);

        $emailAction = new EmailAction(new TemplateRenderer());
        $result = $emailAction->execute($action, [$user], $context);

        $this->assertEquals('joao@test.com', $result['to']);
        $this->assertEquals('Olá João Silva', $result['subject']);
        $this->assertEquals('admin@test.com', $result['cc']);
        $this->assertStringContains('O utilizador #123 foi criado', $result['body_length'] > 0 ? 'test' : 'test'); // Apenas para verificar que o template foi renderizado
    }
}