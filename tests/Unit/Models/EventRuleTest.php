<?php

namespace St693ava\FilamentEventsManager\Tests\Unit\Models;

use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Models\EventRuleCondition;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Tests\TestCase;

class EventRuleTest extends TestCase
{
    public function test_can_create_event_rule(): void
    {
        $rule = EventRule::create([
            'name' => 'Teste Rule',
            'description' => 'Uma regra de teste',
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => 'App\\Models\\User',
                'events' => ['created', 'updated'],
            ],
            'is_active' => true,
            'priority' => 10,
        ]);

        $this->assertInstanceOf(EventRule::class, $rule);
        $this->assertEquals('Teste Rule', $rule->name);
        $this->assertEquals('eloquent', $rule->trigger_type);
        $this->assertTrue($rule->is_active);
        $this->assertEquals(10, $rule->priority);
        $this->assertEquals(['model' => 'App\\Models\\User', 'events' => ['created', 'updated']], $rule->trigger_config);
    }

    public function test_has_conditions_relationship(): void
    {
        $rule = EventRule::create([
            'name' => 'Teste Rule',
            'trigger_type' => 'eloquent',
            'trigger_config' => ['model' => 'App\\Models\\User', 'events' => ['created']],
        ]);

        $condition = $rule->conditions()->create([
            'field_path' => 'email',
            'operator' => 'contains',
            'value' => '@test.com',
        ]);

        $this->assertInstanceOf(EventRuleCondition::class, $condition);
        $this->assertEquals($rule->id, $condition->event_rule_id);
        $this->assertTrue($rule->hasConditions());
    }

    public function test_has_actions_relationship(): void
    {
        $rule = EventRule::create([
            'name' => 'Teste Rule',
            'trigger_type' => 'eloquent',
            'trigger_config' => ['model' => 'App\\Models\\User', 'events' => ['created']],
        ]);

        $action = $rule->actions()->create([
            'action_type' => 'email',
            'action_config' => [
                'to' => 'test@test.com',
                'subject' => 'Test',
                'body' => 'Test body',
            ],
        ]);

        $this->assertInstanceOf(EventRuleAction::class, $action);
        $this->assertEquals($rule->id, $action->event_rule_id);
        $this->assertTrue($rule->hasActions());
    }

    public function test_active_scope(): void
    {
        EventRule::create([
            'name' => 'Active Rule',
            'trigger_type' => 'eloquent',
            'trigger_config' => ['model' => 'App\\Models\\User', 'events' => ['created']],
            'is_active' => true,
        ]);

        EventRule::create([
            'name' => 'Inactive Rule',
            'trigger_type' => 'eloquent',
            'trigger_config' => ['model' => 'App\\Models\\User', 'events' => ['created']],
            'is_active' => false,
        ]);

        $activeRules = EventRule::active()->get();

        $this->assertCount(1, $activeRules);
        $this->assertEquals('Active Rule', $activeRules->first()->name);
    }

    public function test_helper_methods(): void
    {
        $rule = EventRule::create([
            'name' => 'Teste Rule',
            'trigger_type' => 'eloquent',
            'trigger_config' => [
                'model' => 'App\\Models\\User',
                'events' => ['created', 'updated'],
            ],
            'is_active' => true,
        ]);

        $this->assertTrue($rule->isActive());
        $this->assertEquals('App\\Models\\User', $rule->getTriggerModelClass());
        $this->assertEquals(['created', 'updated'], $rule->getTriggerEvents());
    }
}