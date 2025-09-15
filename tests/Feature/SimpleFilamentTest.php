<?php

use Workbench\App\Models\User;

it('can access filament login page', function () {
    $user = createUser([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);

    $page = visit('/admin/login');
    $page->screenshot(filename: 'login-page');
})->group('screenshots');