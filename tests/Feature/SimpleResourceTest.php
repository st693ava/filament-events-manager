<?php

use Workbench\App\Models\User;

it('can access event rules test page', function () {
    // Criar algumas regras de exemplo
    createEventRule(['name' => 'User Registration Rule', 'description' => 'Sends welcome email when user registers']);
    createEventRule(['name' => 'Order Completed Rule', 'description' => 'Notifies admin when order is completed', 'is_active' => false]);
    createEventRule(['name' => 'Low Stock Alert', 'description' => 'Alerts when product stock is low', 'priority' => 5]);

    $page = visit('/test-event-rules');
    $page->assertSee('Event Rules');
    $page->assertSee('User Registration Rule');
    $page->assertSee('Order Completed Rule');
    $page->assertSee('Low Stock Alert');
    $page->assertSee('Manage your event rules and conditions');
    $page->screenshot(filename: 'event-rules-interface');
})->group('screenshots');