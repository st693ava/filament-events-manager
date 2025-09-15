<?php

namespace St693ava\FilamentEventsManager\Actions\Executors;

use St693ava\FilamentEventsManager\Actions\Contracts\ActionExecutor;
use St693ava\FilamentEventsManager\Actions\WebhookAction;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Support\EventContext;

class WebhookActionExecutor implements ActionExecutor
{
    private WebhookAction $webhookAction;

    public function __construct()
    {
        $this->webhookAction = new WebhookAction();
    }

    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        return $this->webhookAction->execute($action->action_config, $data, $context);
    }

    public function validateConfig(array $config): array
    {
        return $this->webhookAction->validateConfig($config);
    }
}