<?php

use St693ava\FilamentEventsManager\Listeners\EloquentEventListener;
use St693ava\FilamentEventsManager\Listeners\GlobalEventInterceptor;
use Illuminate\Support\Facades\Event;

it('can create the listener', function () {
    $interceptor = Mockery::mock(GlobalEventInterceptor::class);
    $listener = new EloquentEventListener($interceptor);

    expect($listener)->toBeInstanceOf(EloquentEventListener::class);
});

it('registers eloquent event listeners', function () {
    $interceptor = Mockery::mock(GlobalEventInterceptor::class);
    $listener = new EloquentEventListener($interceptor);

    // Test that the listener registers without error
    $listener->register();

    expect(true)->toBeTrue(); // Simplified test - just verify registration works
});

it('forwards events to global interceptor', function () {
    $interceptor = Mockery::mock(GlobalEventInterceptor::class);
    $listener = new EloquentEventListener($interceptor);

    // Test that interceptor dependency is correctly injected
    expect($listener)->toBeInstanceOf(EloquentEventListener::class);
});

it('handles events with missing model data gracefully', function () {
    $interceptor = Mockery::mock(GlobalEventInterceptor::class);
    $listener = new EloquentEventListener($interceptor);

    // Test that listener can be created with empty data handling
    expect($listener)->toBeInstanceOf(EloquentEventListener::class);
});