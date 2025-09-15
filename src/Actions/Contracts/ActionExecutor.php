<?php

namespace St693ava\FilamentEventsManager\Actions\Contracts;

use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Support\EventContext;

interface ActionExecutor
{
    /**
     * Executar a ação com os dados fornecidos
     *
     * @param EventRuleAction $action A ação a executar
     * @param array $data Os dados do evento (modelos, etc.)
     * @param EventContext $context O contexto do evento
     * @return array Resultado da execução com detalhes
     * @throws \Exception Se a execução falhar
     */
    public function execute(EventRuleAction $action, array $data, EventContext $context): array;

    /**
     * Validar se a configuração da ação está correta
     *
     * @param array $config A configuração da ação
     * @return array Array vazio se válido, ou array com erros
     */
    public function validateConfig(array $config): array;
}