<?php

namespace St693ava\FilamentEventsManager\Support;

use Carbon\Carbon;

class EventContext
{
    public function __construct(
        private array $context = []
    ) {}

    /**
     * Create EventContext from array data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return $this->context;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->context, $key, $default);
    }

    public function has(string $key): bool
    {
        return data_get($this->context, $key) !== null;
    }

    public function getEventName(): string
    {
        return $this->context['event_name'];
    }

    public function getTriggeredAt(): Carbon
    {
        return $this->context['triggered_at'];
    }

    public function getUserId(): ?int
    {
        return $this->context['user']['id'] ?? null;
    }

    public function getUserName(): ?string
    {
        return $this->context['user']['name'] ?? null;
    }

    public function getUserEmail(): ?string
    {
        return $this->context['user']['email'] ?? null;
    }

    public function isAuthenticated(): bool
    {
        return $this->context['user']['authenticated'] ?? false;
    }

    public function getRequestUrl(): ?string
    {
        return $this->context['request']['url'] ?? null;
    }

    public function getRequestMethod(): ?string
    {
        return $this->context['request']['method'] ?? null;
    }

    public function getIpAddress(): ?string
    {
        return $this->context['request']['ip'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->context['request']['user_agent'] ?? null;
    }

    public function getSessionId(): ?string
    {
        return $this->context['session']['id'] ?? null;
    }

    public function getRequestSource(): string
    {
        return $this->context['request']['source'] ?? 'unknown';
    }

    public function getData(): array
    {
        return $this->context['data'] ?? [];
    }

    public function isConsoleRequest(): bool
    {
        return $this->getRequestSource() === 'console';
    }

    public function isWebRequest(): bool
    {
        return $this->getRequestSource() === 'web';
    }

    /**
     * Set a context value
     */
    public function set(string $key, mixed $value): void
    {
        data_set($this->context, $key, $value);
    }

    /**
     * Get all context data
     */
    public function all(): array
    {
        return $this->context;
    }

    /**
     * Merge additional context data
     */
    public function merge(array $data): void
    {
        $this->context = array_merge($this->context, $data);
    }
}