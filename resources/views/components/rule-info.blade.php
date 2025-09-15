<div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
    <div class="grid md:grid-cols-2 gap-4">
        <!-- Basic Info -->
        <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Informações Básicas</h4>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Nome:</dt>
                    <dd class="font-medium">{{ $rule->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Tipo de Trigger:</dt>
                    <dd>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                            {{ match($rule->trigger_type) {
                                'eloquent' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'query' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'custom' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'schedule' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                            } }}">
                            {{ match($rule->trigger_type) {
                                'eloquent' => 'Eloquent',
                                'query' => 'Consulta BD',
                                'custom' => 'Personalizado',
                                'schedule' => 'Horário',
                                default => $rule->trigger_type
                            } }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Estado:</dt>
                    <dd>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                            {{ $rule->is_active
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $rule->is_active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 dark:text-gray-400">Prioridade:</dt>
                    <dd class="font-medium">{{ $rule->priority }}</dd>
                </div>
            </dl>
        </div>

        <!-- Trigger Config -->
        <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Configuração do Trigger</h4>
            @if($rule->trigger_type === 'eloquent' && isset($rule->trigger_config['model']))
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">Modelo:</dt>
                        <dd class="font-mono text-xs">{{ class_basename($rule->trigger_config['model']) }}</dd>
                    </div>
                    @if(isset($rule->trigger_config['events']))
                        <div>
                            <dt class="text-gray-600 dark:text-gray-400 mb-1">Eventos:</dt>
                            <dd class="flex flex-wrap gap-1">
                                @foreach($rule->trigger_config['events'] as $event)
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $event }}
                                    </span>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ json_encode($rule->trigger_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                </p>
            @endif
        </div>
    </div>

    <!-- Conditions -->
    @if($rule->conditions->isNotEmpty())
        <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                Condições ({{ $rule->conditions->count() }})
            </h4>
            <div class="space-y-2">
                @foreach($rule->conditions->sortBy('priority')->sortBy('sort_order') as $condition)
                    <div class="flex items-center space-x-2 text-sm">
                        @if($condition->group_start)
                            <span class="text-gray-500">{{ $condition->group_start }}</span>
                        @endif

                        <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                            {{ $condition->field_path }}
                        </code>

                        <span class="text-gray-600 dark:text-gray-400">{{ $condition->operator }}</span>

                        @if($condition->operator !== 'changed')
                            <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                                {{ is_array($condition->value) ? json_encode($condition->value) : $condition->value }}
                            </code>
                        @endif

                        @if($condition->group_end)
                            <span class="text-gray-500">{{ $condition->group_end }}</span>
                        @endif

                        @if($condition->logical_operator && !$loop->last)
                            <span class="font-medium text-blue-600 dark:text-blue-400">
                                {{ $condition->logical_operator }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Condições</h4>
            <p class="text-sm text-amber-600 dark:text-amber-400">
                ⚠️ Nenhuma condição definida - a regra será sempre executada
            </p>
        </div>
    @endif

    <!-- Actions -->
    @if($rule->actions->isNotEmpty())
        <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                Ações ({{ $rule->actions->count() }})
            </h4>
            <div class="space-y-2">
                @foreach($rule->actions as $action)
                    <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded border">
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded
                                {{ match($action->action_type) {
                                    'email' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'webhook' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'notification' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                    'activity_log' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                } }}">
                                {{ match($action->action_type) {
                                    'email' => 'Enviar Email',
                                    'webhook' => 'Webhook HTTP',
                                    'notification' => 'Notificação',
                                    'activity_log' => 'Registo de Atividade',
                                    default => $action->action_type
                                } }}
                            </span>

                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                @if($action->action_type === 'email' && isset($action->action_config['to']))
                                    → {{ $action->action_config['to'] }}
                                @elseif($action->action_type === 'webhook' && isset($action->action_config['url']))
                                    → {{ $action->action_config['url'] }}
                                @elseif($action->action_type === 'notification' && isset($action->action_config['title']))
                                    → {{ $action->action_config['title'] }}
                                @endif
                            </span>
                        </div>

                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                            {{ $action->is_active
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ $action->is_active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Ações</h4>
            <p class="text-sm text-red-600 dark:text-red-400">
                ❌ Nenhuma ação definida - a regra não fará nada quando executada
            </p>
        </div>
    @endif

    @if($rule->description)
        <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Descrição</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $rule->description }}</p>
        </div>
    @endif
</div>