<?php

namespace St693ava\FilamentEventsManager\Console\Commands;

use Illuminate\Console\Command;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleAction;

class InstallDefaultRulesCommand extends Command
{
    protected $signature = 'events-manager:install-defaults
                            {--force : Overwrite existing rules with the same name}
                            {--dry-run : Show what would be installed without actually installing}
                            {--only= : Install only specific rule types (comma separated): auth,security,audit,errors}';

    protected $description = 'Install default event rules for common use cases (login tracking, security alerts, etc.)';

    public function handle(): int
    {
        $this->info('🚀 Installing default event rules for Filament Events Manager...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $only = $this->option('only');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->showAvailableRules();

        if ($only) {
            $selectedTypes = array_map('trim', explode(',', $only));
            $this->info("Installing only: " . implode(', ', $selectedTypes));
            $this->newLine();
        }

        if (!$force && !$dryRun) {
            $existingRules = $this->getExistingDefaultRules();
            if ($existingRules->isNotEmpty()) {
                $this->warn('Some default rules already exist:');
                foreach ($existingRules as $rule) {
                    $this->line("  - {$rule->name}");
                }
                $this->newLine();

                if (!$this->confirm('Do you want to continue? (Use --force to overwrite existing rules)')) {
                    $this->info('Installation cancelled.');
                    return self::SUCCESS;
                }
            }
        }

        $installed = $this->installRules($dryRun, $force, $only ? explode(',', $only) : null);

        $this->newLine();
        $this->info("✅ Installation completed! {$installed} rules processed.");

        if (!$dryRun) {
            $this->newLine();
            $this->info('💡 Next steps:');
            $this->line('  1. Review the installed rules in Filament Admin');
            $this->line('  2. Customize email addresses and messages as needed');
            $this->line('  3. Test the rules using: php artisan events:test-rule');
            $this->line('  4. Enable/disable rules as appropriate for your application');
        }

        return self::SUCCESS;
    }

    private function showAvailableRules(): void
    {
        $rules = [
            'auth' => [
                'User Login Success - Activity Log' => 'Tracks successful logins with spatie/activity-log',
                'User Registration - Welcome & Audit' => 'Logs new user registrations and sends welcome emails',
                'Password Reset - Security Audit' => 'Tracks password resets for security auditing',
            ],
            'security' => [
                'Failed Login Attempts - Security Alert' => 'Monitors and alerts on failed login attempts',
                'Suspicious Activity - Multiple Failed Logins' => 'Detects suspicious login patterns',
            ],
            'audit' => [
                'Data Export Audit' => 'Tracks data exports for compliance (requires configuration)',
            ],
            'errors' => [
                'Critical Application Errors' => 'Alerts on critical application errors (disabled by default)',
            ],
        ];

        $this->info('📋 Available default rules:');
        $this->newLine();

        foreach ($rules as $category => $categoryRules) {
            $this->line("<fg=yellow>🔸 {$category}</>");
            foreach ($categoryRules as $name => $description) {
                $this->line("  • {$name}");
                $this->line("    <fg=gray>{$description}</>");
            }
            $this->newLine();
        }
    }

    private function getExistingDefaultRules()
    {
        $defaultRuleNames = [
            'User Login Success - Activity Log',
            'Failed Login Attempts - Security Alert',
            'User Registration - Welcome & Audit',
            'Password Reset - Security Audit',
            'Suspicious Activity - Multiple Failed Logins',
            'Data Export Audit',
            'Critical Application Errors',
        ];

        return EventRule::whereIn('name', $defaultRuleNames)->get();
    }

    private function installRules(bool $dryRun, bool $force, ?array $onlyTypes): int
    {
        $installed = 0;
        $skipped = 0;

        $rulesToInstall = $this->getRulesToInstall($onlyTypes);

        foreach ($rulesToInstall as $ruleData) {
            $existing = EventRule::where('name', $ruleData['name'])->first();

            if ($existing && !$force) {
                $this->line("⏭️  Skipping: {$ruleData['name']} (already exists)");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $action = $existing ? 'Would overwrite' : 'Would install';
                $this->line("🔍 {$action}: {$ruleData['name']}");
                $installed++;
                continue;
            }

            if ($existing) {
                $existing->delete(); // Remove existing to recreate with new config
                $this->line("🔄 Overwriting: {$ruleData['name']}");
            } else {
                $this->line("➕ Installing: {$ruleData['name']}");
            }

            // Install the specific rule
            $this->installSpecificRule($ruleData['method']);
            $installed++;
        }

        if ($skipped > 0) {
            $this->line("⏭️  Skipped {$skipped} existing rules (use --force to overwrite)");
        }

        return $installed;
    }

    private function getRulesToInstall(?array $onlyTypes): array
    {
        $allRules = [
            [
                'name' => 'User Login Success - Activity Log',
                'method' => 'createUserLoginRule',
                'category' => 'auth',
            ],
            [
                'name' => 'Failed Login Attempts - Security Alert',
                'method' => 'createUserLoginFailedRule',
                'category' => 'security',
            ],
            [
                'name' => 'User Registration - Welcome & Audit',
                'method' => 'createUserRegistrationRule',
                'category' => 'auth',
            ],
            [
                'name' => 'Password Reset - Security Audit',
                'method' => 'createPasswordResetRule',
                'category' => 'auth',
            ],
            [
                'name' => 'Suspicious Activity - Multiple Failed Logins',
                'method' => 'createSuspiciousActivityRule',
                'category' => 'security',
            ],
            [
                'name' => 'Data Export Audit',
                'method' => 'createDataExportRule',
                'category' => 'audit',
            ],
            [
                'name' => 'Critical Application Errors',
                'method' => 'createCriticalErrorRule',
                'category' => 'errors',
            ],
        ];

        if ($onlyTypes) {
            return array_filter($allRules, function ($rule) use ($onlyTypes) {
                return in_array($rule['category'], array_map('trim', $onlyTypes));
            });
        }

        return $allRules;
    }

    private function installSpecificRule(string $method): void
    {
        // Create rules directly instead of using seeder methods
        switch ($method) {
            case 'createUserLoginRule':
                $this->createUserLoginRule();
                break;
            case 'createUserLoginFailedRule':
                $this->createUserLoginFailedRule();
                break;
            case 'createUserRegistrationRule':
                $this->createUserRegistrationRule();
                break;
            case 'createPasswordResetRule':
                $this->createPasswordResetRule();
                break;
            case 'createSuspiciousActivityRule':
                $this->createSuspiciousActivityRule();
                break;
            case 'createDataExportRule':
                $this->createDataExportRule();
                break;
            case 'createCriticalErrorRule':
                $this->createCriticalErrorRule();
                break;
        }
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