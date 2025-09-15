<?php

namespace St693ava\FilamentEventsManager\Database\Seeders;

use Illuminate\Database\Seeder;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleAction;

class DefaultEventRulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUserLoginRule();
        $this->createUserLoginFailedRule();
        $this->createUserRegistrationRule();
        $this->createPasswordResetRule();
        $this->createSuspiciousActivityRule();
        $this->createDataExportRule();
        $this->createCriticalErrorRule();
    }

    private function createUserLoginRule(): void
    {
        $rule = EventRule::create([
            'name' => 'User Login Success - Activity Log',
            'description' => 'Regista todos os logins bem-sucedidos no activity log para auditoria e segurança',
            'trigger_type' => 'custom',
            'trigger_config' => [
                'event_class' => \Illuminate\Auth\Events\Login::class,
            ],
            'is_active' => true,
            'priority' => 100,
        ]);

        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => 'Utilizador {user.name} fez login com sucesso desde {ip_address}',
                'log_name' => 'authentication',
                'event' => 'login',
                'properties' => [
                    'user_agent' => '{user_agent}',
                    'login_time' => '{triggered_at}',
                    'ip_address' => '{ip_address}',
                ],
            ],
            'sort_order' => 1,
        ]);
    }

    private function createUserLoginFailedRule(): void
    {
        $rule = EventRule::create([
            'name' => 'Failed Login Attempts - Security Alert',
            'description' => 'Monitoriza tentativas de login falhadas e envia alertas de segurança',
            'trigger_type' => 'custom',
            'trigger_config' => [
                'event_class' => \Illuminate\Auth\Events\Failed::class,
            ],
            'is_active' => true,
            'priority' => 200,
        ]);

        // Activity Log Action
        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => 'Tentativa de login falhada para {credentials.email} desde {ip_address}',
                'log_name' => 'security',
                'event' => 'login_failed',
                'properties' => [
                    'attempted_email' => '{credentials.email}',
                    'ip_address' => '{ip_address}',
                    'user_agent' => '{user_agent}',
                    'failure_reason' => 'Invalid credentials',
                ],
            ],
            'sort_order' => 1,
        ]);

        // Email Alert Action (opcional - pode ser desativada)
        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'email',
            'action_config' => [
                'to' => config('mail.from.address'),
                'subject' => '[SECURITY] Tentativa de login falhada - {credentials.email}',
                'body' => "Alerta de Segurança\n\nTentativa de login falhada detectada:\n\nEmail: {credentials.email}\nIP: {ip_address}\nUser Agent: {user_agent}\nHora: {triggered_at}\n\nPor favor, verifique se esta atividade é legítima.",
            ],
            'sort_order' => 2,
        ]);
    }

    private function createUserRegistrationRule(): void
    {
        $rule = EventRule::create([
            'name' => 'User Registration - Welcome & Audit',
            'description' => 'Regista novos utilizadores e pode enviar emails de boas-vindas',
            'trigger_type' => 'custom',
            'trigger_config' => [
                'event_class' => \Illuminate\Auth\Events\Registered::class,
            ],
            'is_active' => true,
            'priority' => 100,
        ]);

        // Activity Log
        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => 'Novo utilizador registado: {user.name} ({user.email})',
                'log_name' => 'user_management',
                'event' => 'user_registered',
                'properties' => [
                    'user_name' => '{user.name}',
                    'user_email' => '{user.email}',
                    'registration_ip' => '{ip_address}',
                    'registration_time' => '{triggered_at}',
                ],
            ],
            'sort_order' => 1,
        ]);

        // Welcome Email (pode ser personalizada)
        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'email',
            'action_config' => [
                'to' => '{user.email}',
                'subject' => 'Bem-vindo à nossa plataforma, {user.name}!',
                'body' => "Olá {user.name},\n\nBem-vindo à nossa plataforma!\n\nA sua conta foi criada com sucesso. Pode agora fazer login e explorar todas as funcionalidades disponíveis.\n\nSe tiver alguma dúvida, não hesite em contactar-nos.\n\nCumprimentos,\nA Equipa",
            ],
            'sort_order' => 2,
        ]);
    }

    private function createPasswordResetRule(): void
    {
        $rule = EventRule::create([
            'name' => 'Password Reset - Security Audit',
            'description' => 'Regista todos os resets de password para auditoria de segurança',
            'trigger_type' => 'custom',
            'trigger_config' => [
                'event_class' => \Illuminate\Auth\Events\PasswordReset::class,
            ],
            'is_active' => true,
            'priority' => 150,
        ]);

        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => 'Password alterada para utilizador {user.name} ({user.email})',
                'log_name' => 'security',
                'event' => 'password_reset',
                'properties' => [
                    'user_name' => '{user.name}',
                    'user_email' => '{user.email}',
                    'reset_ip' => '{ip_address}',
                    'reset_time' => '{triggered_at}',
                ],
            ],
            'sort_order' => 1,
        ]);

        // Security notification
        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'email',
            'action_config' => [
                'to' => '{user.email}',
                'subject' => 'Password alterada com sucesso',
                'body' => "Olá {user.name},\n\nA sua password foi alterada com sucesso.\n\nSe não foi você que fez esta alteração, por favor contacte-nos imediatamente.\n\nDetalhes:\nData: {triggered_at}\nIP: {ip_address}\n\nCumprimentos,\nEquipa de Segurança",
            ],
            'sort_order' => 2,
        ]);
    }

    private function createSuspiciousActivityRule(): void
    {
        $rule = EventRule::create([
            'name' => 'Suspicious Activity - Multiple Failed Logins',
            'description' => 'Detecta atividade suspeita baseada em múltiplas tentativas de login falhadas',
            'trigger_type' => 'custom',
            'trigger_config' => [
                'event_class' => \Illuminate\Auth\Events\Failed::class,
            ],
            'is_active' => true,
            'priority' => 300,
        ]);

        // Nota: Esta regra precisa de condições personalizadas para detectar múltiplas tentativas
        // Por agora, vamos registar todas as tentativas e deixar que seja refinada depois

        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => 'ALERTA DE SEGURANÇA: Possível atividade suspeita detectada para {credentials.email}',
                'log_name' => 'security_alerts',
                'event' => 'suspicious_activity',
                'properties' => [
                    'attempted_email' => '{credentials.email}',
                    'ip_address' => '{ip_address}',
                    'alert_level' => 'high',
                    'detection_time' => '{triggered_at}',
                ],
            ],
            'sort_order' => 1,
        ]);
    }

    private function createDataExportRule(): void
    {
        $rule = EventRule::create([
            'name' => 'Data Export Audit',
            'description' => 'Regista todas as exportações de dados para compliance e auditoria',
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => null, // Será configurado conforme os modelos da aplicação
                'events' => ['retrieved'], // Quando dados são recuperados em bulk
            ],
            'is_active' => false, // Inativa por default - precisa de configuração específica
            'priority' => 50,
        ]);

        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => 'Exportação de dados realizada por {user_name}',
                'log_name' => 'data_compliance',
                'event' => 'data_export',
                'properties' => [
                    'exported_model' => '{model_type}',
                    'record_count' => '{record_count}',
                    'export_time' => '{triggered_at}',
                    'user_id' => '{user_id}',
                ],
            ],
            'sort_order' => 1,
        ]);
    }

    private function createCriticalErrorRule(): void
    {
        $rule = EventRule::create([
            'name' => 'Critical Application Errors',
            'description' => 'Monitoriza e alerta sobre erros críticos na aplicação',
            'trigger_type' => 'custom',
            'trigger_config' => [
                'event_class' => \Illuminate\Log\Events\MessageLogged::class,
            ],
            'is_active' => false, // Inativa por default para evitar spam
            'priority' => 400,
        ]);

        // Esta regra precisará de condições para filtrar apenas erros críticos
        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'email',
            'action_config' => [
                'to' => config('mail.from.address'),
                'subject' => '[CRITICAL ERROR] Erro crítico na aplicação',
                'body' => "ERRO CRÍTICO DETECTADO\n\nUm erro crítico foi detectado na aplicação:\n\nMensagem: {message}\nNível: {level}\nContexto: {context}\nHora: {triggered_at}\n\nPor favor, verifique os logs imediatamente.",
            ],
            'sort_order' => 1,
        ]);

        EventRuleAction::create([
            'event_rule_id' => $rule->id,
            'action_type' => 'activity_log',
            'action_config' => [
                'description' => 'ERRO CRÍTICO: {message}',
                'log_name' => 'critical_errors',
                'event' => 'critical_error',
                'properties' => [
                    'error_message' => '{message}',
                    'error_level' => '{level}',
                    'error_context' => '{context}',
                    'detection_time' => '{triggered_at}',
                ],
            ],
            'sort_order' => 2,
        ]);
    }
}