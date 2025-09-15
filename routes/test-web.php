<?php

use Illuminate\Support\Facades\Route;
use St693ava\FilamentEventsManager\Models\EventRule;

Route::get('/test-event-rules', function () {
    $rules = EventRule::all();

    return view('filament-events-manager::test-list', compact('rules'));
});