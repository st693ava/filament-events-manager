<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use St693ava\FilamentEventsManager\Support\EventContext;

class ContextCollector
{
    public function collect(string $eventName, array $data): EventContext
    {
        return new EventContext([
            'event_name' => $eventName,
            'triggered_at' => now(),
            'user' => $this->getUserContext(),
            'request' => $this->getRequestContext(),
            'session' => $this->getSessionContext(),
            'data' => $data,
        ]);
    }

    private function getUserContext(): array
    {
        $user = Auth::user();

        if (!$user) {
            return ['authenticated' => false];
        }

        return [
            'authenticated' => true,
            'id' => $user->id,
            'name' => $user->name ?? null,
            'email' => $user->email ?? null,
            'class' => get_class($user),
        ];
    }

    private function getRequestContext(): array
    {
        if (!app()->has('request') || app()->runningInConsole()) {
            return ['source' => 'console'];
        }

        $request = request();

        if (!$request instanceof Request) {
            return ['source' => 'unknown'];
        }

        return [
            'source' => 'web',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'route_name' => optional($request->route())->getName(),
        ];
    }

    private function getSessionContext(): array
    {
        if (!app()->has('request') || app()->runningInConsole()) {
            return [];
        }

        $request = request();

        if (!$request instanceof Request || !$request->hasSession()) {
            return [];
        }

        return [
            'id' => $request->session()->getId(),
            'csrf_token' => $request->session()->token(),
        ];
    }
}