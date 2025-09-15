# Filament Events Manager

🚀 **Sistema Avançado de Automação de Eventos para Filament v4**

Crie regras de automação complexas através de uma interface visual, sem necessidade de código. O "Zapier/IFTTT" da sua aplicação Laravel/Filament.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-4.x-orange.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)

---

## 🎯 Visão Geral

O **Filament Events Manager** é um package que permite configurar dinamicamente triggers de eventos e ações automáticas através de uma interface gráfica intuitiva. Elimina a necessidade de criar observers, listeners e jobs específicos para cada regra de negócio.

### Problemas que Resolve

- ✅ **Automação Manual**: Elimina observers e listeners hardcoded
- ✅ **Flexibilidade**: Configure regras complexas via UI
- ✅ **Auditoria**: Rastreamento completo de ações e utilizadores
- ✅ **Manutenção**: Centraliza lógica de eventos numa interface
- ✅ **Compliance**: Facilita requisitos de auditoria e GDPR

### Valor Proposição

> *"Configure triggers e ações complexas através de interface visual, sem código"*

---

## ✨ Funcionalidades Principais

### 🎛️ Sistema de Regras Avançado
- **Condition Builder Visual**: Interface drag-and-drop para condições complexas
- **Operadores Completos**: =, !=, >, <, contains, starts_with, changed, was
- **Lógica Avançada**: Suporte a AND/OR com agrupamento por parêntesis
- **Field Paths**: Acesso a relações complexas (`user.email`, `order.customer.name`)

### 🔥 Tipos de Triggers
- **Eloquent Events**: created, updated, deleted, restored, retrieved
- **SQL Query Events**: Intercepção de operações INSERT, UPDATE, DELETE
- **Custom Events**: Eventos específicos da aplicação com auto-discovery
- **Schedule-based**: Triggers baseados em tempo/cron com configurações avançadas

### ⚡ Ações Automáticas
- **Email**: Templates dinâmicos com variáveis e conditional logic
- **Webhooks**: HTTP requests com retry automático e configuração completa
- **Notificações**: Database, broadcast, Slack com múltiplos canais
- **Activity Log**: Integração nativa com Spatie Activity Log
- **Custom Actions**: Interface pluggável para ações personalizadas

### 📊 Dashboard e Monitorização
- **Widgets em Tempo Real**: Estatísticas, triggers recentes, regras ativas
- **Performance Metrics**: Tempos de execução, taxa de sucesso, horários de pico
- **Filtros Avançados**: Por regra, utilizador, período, estado
- **Export de Logs**: Relatórios completos para auditoria

### 🧪 Ferramentas de Teste
- **Rule Tester**: Simulação de eventos com dry-run mode
- **Mock Data Generator**: Cenários predefinidos e dados personalizados
- **Debug Mode**: Logging verboso e stack trace detalhado
- **CLI Commands**: Teste via Artisan com múltiplos formatos de output

### 🔄 Funcionalidades Avançadas (v2.0+)
- **Import/Export**: Gestão de regras entre ambientes
- **Cache Inteligente**: Otimização de performance com multiple stores
- **Processamento Assíncrono**: Execução em background com queues
- **Auto-discovery**: Descoberta automática de eventos e modelos

---

## 🚀 Instalação Rápida

### Requisitos
- PHP 8.3+
- Laravel 12+
- Filament v4
- MySQL 8.0+ ou PostgreSQL 13+

### Passo 1: Instalar Package
```bash
composer require st693ava/filament-events-manager
```

### Passo 2: Executar Migrações
```bash
php artisan vendor:publish --tag="filament-events-manager-migrations"
php artisan migrate
```

### Passo 3: Registar Plugin
```php
// Em AdminPanelProvider.php
use St693ava\FilamentEventsManager\FilamentEventsManagerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentEventsManagerPlugin::make(),
        ]);
}
```

### Passo 4: Configurar (Opcional)
```bash
php artisan vendor:publish --tag="filament-events-manager-config"
```

---

## 📖 Guia de Uso

### Exemplo 1: Email de Boas-vindas
```php
// Configuração via Interface Filament:
// Trigger: Eloquent Model Events → User → created
// Ação: Send Email
//   - To: {model.email}
//   - Subject: Bem-vindo {model.name}!
//   - Body: Obrigado por se registar na nossa plataforma.
```

### Exemplo 2: Alertas de Vendas Elevadas
```php
// Trigger: Eloquent Model Events → Order → created
// Condições:
//   - total > 1000 AND
//   - status = 'confirmed'
// Ações:
//   1. Email para admin@empresa.com
//   2. Slack notification para #vendas
//   3. Activity Log: "Venda elevada criada"
```

### Exemplo 3: Monitorização de Stock
```php
// Trigger: Eloquent Model Events → Product → updated
// Condições:
//   - stock_quantity <= 10 AND
//   - stock_quantity changed
// Ações:
//   1. Email para equipa de inventário
//   2. Webhook para sistema externo
//   3. Notificação urgente para gestores
```

---

## 🏗️ Arquitetura Técnica

### Componentes Principais

#### 1. Event Interceptor
Captura todos os eventos do sistema com performance otimizada:
```php
class GlobalEventInterceptor
{
    public function handle($eventName, $data): void
    {
        if (!$this->hasActiveRules($eventName)) {
            return; // Early exit para performance
        }

        $context = $this->contextCollector->collect($eventName, $data);
        $this->ruleEngine->processEvent($eventName, $data, $context);
    }
}
```

#### 2. Rule Engine
Motor de avaliação de condições e execução de ações:
```php
class RuleEngine
{
    public function processEvent(string $eventName, array $data, EventContext $context): void
    {
        $rules = $this->getMatchingRules($eventName);

        foreach ($rules as $rule) {
            if ($this->evaluateConditions($rule, $data, $context)) {
                $this->executeActions($rule, $data, $context);
                $this->logExecution($rule, $data, $context);
            }
        }
    }
}
```

#### 3. Condition Evaluator
Sistema avançado de avaliação de condições com suporte a expressões complexas:
```php
class ConditionEvaluator
{
    public function evaluate(Collection $conditions, array $data, EventContext $context): bool
    {
        // Suporte a AND/OR com parêntesis
        $expression = $this->buildExpression($conditions, $data, $context);
        return $this->evaluateExpressionString($expression);
    }
}
```

#### 4. Action Manager
Sistema pluggável de ações com interface extensível:
```php
interface ActionExecutor
{
    public function execute(EventRuleAction $action, array $data, EventContext $context): array;
    public function validateConfig(array $config): array;
}
```

### Schema da Base de Dados

#### Tabelas Principais
- **`event_rules`**: Regras principais com configuração de triggers
- **`event_rule_conditions`**: Condições com suporte a agrupamento
- **`event_rule_actions`**: Ações com configuração específica
- **`event_logs`**: Logs completos de execução com contexto

#### Índices Otimizados
```sql
-- Performance crítica para consultas frequentes
INDEX idx_active_rules_by_trigger (is_active, trigger_type, priority);
INDEX idx_logs_recent (triggered_at DESC, event_rule_id);
INDEX idx_user_activity (user_id, triggered_at DESC);
```

---

## 🔧 Configuração Avançada

### Configuração de Performance
```php
// config/filament-events-manager.php
'performance' => [
    'max_execution_time' => 30,
    'batch_size' => 100,
    'enable_query_cache' => true,
],

'cache' => [
    'enabled' => true,
    'default_ttl' => 3600,
    'stores' => [
        'rules' => env('CACHE_STORE', 'redis'),
        'conditions' => env('CACHE_STORE', 'redis'),
    ],
],
```

### Configuração de SQL Events
```php
'sql_events' => [
    'enabled' => true,
    'operations' => ['INSERT', 'UPDATE', 'DELETE'],
    'exclude_tables' => [
        'migrations', 'failed_jobs', 'sessions',
        'event_rules', 'event_logs', // Evitar loops
    ],
],
```

### Configuração de Schedule Triggers
```php
'schedule' => [
    'enabled' => true,
    'default_timezone' => 'Europe/Lisbon',
    'overlap_protection' => true,
],
```

### Processamento Assíncrono
```php
'async_processing' => true,
'queue_name' => 'events',
'job_timeout' => 300,
'job_retries' => 3,
```

---

## 🔌 Extensibilidade

### Criando Ações Personalizadas

#### 1. Implementar ActionExecutor
```php
use St693ava\FilamentEventsManager\Actions\Contracts\ActionExecutor;

class CustomSlackAction implements ActionExecutor
{
    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        $config = $action->action_config;

        // Renderizar template
        $message = app(TemplateRenderer::class)->render(
            $config['message'],
            $data,
            $context
        );

        // Enviar para Slack
        Http::post($config['webhook_url'], [
            'text' => $message,
            'channel' => $config['channel'],
        ]);

        return [
            'status' => 'sent',
            'channel' => $config['channel'],
            'message' => $message,
        ];
    }

    public function validateConfig(array $config): array
    {
        $errors = [];

        if (empty($config['webhook_url'])) {
            $errors[] = 'Webhook URL é obrigatório';
        }

        if (empty($config['message'])) {
            $errors[] = 'Mensagem é obrigatória';
        }

        return $errors;
    }
}
```

#### 2. Registar no Service Provider
```php
use St693ava\FilamentEventsManager\Actions\ActionManager;

public function boot(): void
{
    app(ActionManager::class)->register('custom_slack', CustomSlackAction::class);
}
```

#### 3. Configurar na Interface
A nova ação aparecerá automaticamente na lista de ações disponíveis no Filament.

### Criando Triggers Personalizados

#### 1. Registar Event Listener
```php
// Em EventServiceProvider ou AppServiceProvider
Event::listen('custom.event.triggered', function ($event) {
    app(GlobalEventInterceptor::class)->handle('custom.event.triggered', [$event]);
});
```

#### 2. Disparar Evento Customizado
```php
// Na sua aplicação
Event::dispatch('custom.event.triggered', $customData);
```

---

## 🧪 Testing

### Testando Regras Programaticamente
```php
use St693ava\FilamentEventsManager\Services\RuleTestRunner;

// Testar regra específica
$tester = app(RuleTestRunner::class);
$result = $tester->testRule($rule, [
    'user' => User::factory()->make(['email' => 'test@company.com']),
    'order' => Order::factory()->make(['total' => 1500]),
]);

// Verificar resultados
$this->assertTrue($result['conditions_met']);
$this->assertCount(2, $result['actions_executed']);
```

### CLI Testing
```bash
# Testar regra específica
php artisan events:test-rule 1 --scenario=user_registration

# Testar todas as regras ativas
php artisan events:test-rule --all --dry-run

# Exportar regras
php artisan events:export-rules --format=json

# Importar regras
php artisan events:import-rules rules.json --mode=merge
```

### Rule Tester Interface
Acesse via Filament: **Events Manager → Rule Tester**
- Simulação com dados mock
- Múltiplos cenários predefinidos
- Dry-run mode (não executa ações)
- Resultados detalhados com timing

---

## 📊 Monitorização e Performance

### Widgets Disponíveis
- **EventsOverviewWidget**: Estatísticas gerais
- **RecentTriggersWidget**: Atividade recente
- **ActiveRulesWidget**: Regras ativas por tipo
- **PerformanceWidget**: Métricas de performance

### Métricas Importantes
```php
// Verificar saúde do sistema
$healthCheck = app(EventsManagerHealthCheck::class)->check();

// Retorna:
[
    'active_rules' => 23,
    'avg_execution_time' => 45.2, // ms
    'failed_actions_last_hour' => 0,
    'queue_size' => 5,
]
```

### Otimizações de Performance

#### Cache de Regras Ativas
```php
// Cache automático de regras por tipo de evento
Cache::remember("active_rules_eloquent", 300, function () {
    return EventRule::active()
        ->where('trigger_type', 'eloquent')
        ->with(['conditions', 'actions'])
        ->get();
});
```

#### Processamento Assíncrono
```php
// Para alto volume, processar ações em background
class ProcessRuleActionsJob implements ShouldQueue
{
    public function handle(): void
    {
        foreach ($this->rule->actions as $action) {
            app(ActionManager::class)->execute($action, $this->data, $this->context);
        }
    }
}
```

---

## 🛡️ Segurança

### Rate Limiting
```php
// Configuração por ação
'security' => [
    'rate_limit_per_minute' => 60,
    'sanitize_templates' => true,
    'max_template_size' => 10240,
],
```

### Sanitização de Templates
```php
// Prevenção de code injection
class TemplateRenderer
{
    private function sanitizeValue($value): mixed
    {
        if (is_string($value)) {
            return strip_tags($value);
        }
        return $value;
    }
}
```

### Permissões Filament
```php
// Controlo de acesso granular
public static function canCreate(): bool
{
    return auth()->user()->can('create_event_rules');
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->can('edit_event_rules', $record);
}
```

---

## 🚀 Roadmap e Versões

### Versão Atual: 2.0.0
- ✅ Funcionalidades core completas
- ✅ SQL Events e Schedule triggers
- ✅ Import/Export de regras
- ✅ Cache inteligente e otimizações

### Próximas Versões

#### v2.1.0 - Multi-tenancy
- Suporte a multi-tenancy
- Isolamento de regras por tenant
- Dashboards específicos por tenant

#### v2.2.0 - API Externa
- REST API para gestão de regras
- Webhooks bidirecionais
- Integração com sistemas externos

#### v2.3.0 - Machine Learning
- Pattern detection automático
- Anomaly detection
- Sugestões de regras baseadas em histórico

---

## 🤝 Contribuição

### Processo de Desenvolvimento
1. Fork do repositório
2. Criar branch para feature/bugfix
3. Seguir Conventional Commits
4. Testes obrigatórios para novas funcionalidades
5. Pull request com descrição detalhada

### Estrutura de Commits
```
feat: adiciona suporte a triggers de GraphQL
fix: corrige memory leak em rules caching
docs: atualiza exemplos de custom actions
test: adiciona testes para SQL parser
```

### Executar Testes
```bash
# Testes unitários
php artisan test --testsuite=Unit

# Testes de integração
php artisan test --testsuite=Feature

# Testes de performance
php artisan test --group=performance
```

---

## 📚 Documentação Adicional

- [**Guia de Configuração**](plano-desenvolvimento.md) - Configuração avançada e arquitetura
- [**Release Notes**](releases.md) - Histórico de versões e funcionalidades
- [**API Reference**](https://docs.example.com/api) - Documentação completa da API
- [**Video Tutorials**](https://youtube.com/playlist) - Tutoriais em vídeo

---

## 📄 Licença

Este package é open source licenciado sob [MIT License](LICENSE).

---

## 🆘 Suporte

### Problemas e Bugs
- [GitHub Issues](https://github.com/st693ava/filament-events-manager/issues)
- [Documentação](https://docs.example.com)

### Comunidade
- [Discord](https://discord.gg/filament)
- [GitHub Discussions](https://github.com/st693ava/filament-events-manager/discussions)

---

## 🙏 Agradecimentos

- [Filament Team](https://filamentphp.com) - Pela framework incrível
- [Spatie](https://spatie.be) - Pelas integrações de Activity Log
- [Laravel Community](https://laravel.com) - Pelo ecosystem fantástico

---

<div align="center">

**Feito com ❤️ para a comunidade Laravel/Filament**

[⭐ Star no GitHub](https://github.com/st693ava/filament-events-manager) | [📖 Documentação](https://docs.example.com) | [🚀 Demo](https://demo.example.com)

</div>
