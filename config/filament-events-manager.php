<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache de Regras Ativas
    |--------------------------------------------------------------------------
    |
    | Tempo em segundos para cache das regras ativas. Isso melhora
    | significativamente a performance quando há muitas regras.
    |
    */
    'cache_duration' => 300, // 5 minutos

    /*
    |--------------------------------------------------------------------------
    | Tabelas da Base de Dados
    |--------------------------------------------------------------------------
    |
    | Nomes das tabelas utilizadas pelo sistema de eventos.
    |
    */
    'tables' => [
        'event_rules' => 'event_rules',
        'event_rule_conditions' => 'event_rule_conditions',
        'event_rule_actions' => 'event_rule_actions',
        'event_logs' => 'event_logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ações Registadas
    |--------------------------------------------------------------------------
    |
    | Lista de ações disponíveis no sistema. Podem ser adicionadas
    | ações personalizadas através do ActionManager.
    |
    */
    'actions' => [
        'email' => \St693ava\FilamentEventsManager\Actions\Executors\EmailAction::class,
        'activity_log' => \St693ava\FilamentEventsManager\Actions\Executors\ActivityLogAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Performance
    |--------------------------------------------------------------------------
    |
    | Configurações para otimizar a performance do sistema.
    |
    */
    'performance' => [
        'max_execution_time' => 30, // segundos
        'batch_size' => 100,
        'enable_query_cache' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs e Debugging
    |--------------------------------------------------------------------------
    |
    | Configurações para logging e debugging do sistema.
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
        'level' => 'info',
        'include_context' => true,
        'max_log_entries' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Segurança
    |--------------------------------------------------------------------------
    |
    | Configurações de segurança para o sistema de eventos.
    |
    */
    'security' => [
        'sanitize_templates' => true,
        'max_template_size' => 10240, // bytes
        'allowed_template_tags' => [],
        'rate_limit_per_minute' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Funcionalidades da Release 2.0.0
    |--------------------------------------------------------------------------
    |
    | Configurações para as funcionalidades avançadas da versão 2.0.0.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | SQL Query Events
    |--------------------------------------------------------------------------
    |
    | Configurações para interceptar e processar queries SQL.
    |
    */
    'sql_events' => [
        'enabled' => false, // Ativar listener de queries SQL
        'operations' => ['INSERT', 'UPDATE', 'DELETE'], // Operações a interceptar
        'exclude_tables' => [
            'migrations',
            'failed_jobs',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
            'cache',
            'jobs',
            'event_rules',
            'event_rule_conditions',
            'event_rule_actions',
            'event_logs',
        ],
        'max_query_length' => 5000, // Máximo tamanho da query a processar
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule-based Triggers
    |--------------------------------------------------------------------------
    |
    | Configurações para triggers baseados em horários/cron.
    |
    */
    'schedule' => [
        'enabled' => true,
        'default_timezone' => 'Europe/Lisbon',
        'max_execution_time' => 300, // 5 minutos
        'overlap_protection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Events
    |--------------------------------------------------------------------------
    |
    | Configurações para eventos personalizados da aplicação.
    |
    */
    'custom_events' => [
        'enabled' => true,
        'auto_discovery' => true, // Descobrir eventos automaticamente
        'discovery_paths' => [
            'app/Events',
            'app/Domain',
            'app/Modules',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Import/Export
    |--------------------------------------------------------------------------
    |
    | Configurações para importar e exportar regras.
    |
    */
    'import_export' => [
        'enabled' => true,
        'max_file_size' => '10M', // Máximo tamanho do arquivo
        'backup_before_import' => true,
        'validate_on_import' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Avançado
    |--------------------------------------------------------------------------
    |
    | Configurações de cache inteligente para otimização de performance.
    |
    */
    'cache' => [
        'enabled' => true,
        'default_ttl' => 3600, // 1 hora
        'stores' => [
            'rules' => env('CACHE_STORE', 'redis'), // Cache de regras
            'conditions' => env('CACHE_STORE', 'redis'), // Cache de condições
            'actions' => env('CACHE_STORE', 'redis'), // Cache de ações
        ],
        'prefix' => 'filament_events_manager',
        'auto_refresh' => true, // Renovar cache automaticamente
    ],

    /*
    |--------------------------------------------------------------------------
    | Processamento Assíncrono
    |--------------------------------------------------------------------------
    |
    | Configurações para processamento de regras em background.
    |
    */
    'async_processing' => false, // Processar regras assincronamente
    'queue_name' => 'events', // Nome da queue para jobs
    'job_timeout' => 300, // Timeout para jobs em segundos
    'job_retries' => 3, // Número de tentativas para jobs

    /*
    |--------------------------------------------------------------------------
    | Interface Filament
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para a interface Filament.
    |
    */
    'filament' => [
        'navigation_group' => 'Gestão de Eventos',
        'navigation_sort' => null,
        'navigation_badge' => true,
        'cluster' => null,
    ],
];