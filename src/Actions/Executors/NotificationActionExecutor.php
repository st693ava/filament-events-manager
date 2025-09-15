<?php

namespace St693ava\FilamentEventsManager\Actions\Executors;

use St693ava\FilamentEventsManager\Actions\Contracts\ActionExecutor;
use St693ava\FilamentEventsManager\Actions\NotificationAction;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Support\EventContext;

class NotificationActionExecutor implements ActionExecutor
{
    private NotificationAction $notificationAction;

    public function __construct()
    {
        $this->notificationAction = new NotificationAction();
    }

    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        return $this->notificationAction->execute($action->action_config, $data, $context);
    }

    public function validateConfig(array $config): array
    {
        return $this->notificationAction->validateConfig($config);
    }
}