<?php

namespace St693ava\FilamentEventsManager\Contracts;

use St693ava\FilamentEventsManager\Support\EventContext;

interface ActionContract
{
    /**
     * Execute the action with the provided data
     */
    public function execute(array $config, array $data, EventContext $context): array;

    /**
     * Validate the action configuration
     */
    public function validateConfig(array $config): array;

    /**
     * Get the configuration fields for this action type
     */
    public static function getConfigFields(): array;
}