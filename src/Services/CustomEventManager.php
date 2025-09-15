<?php

namespace St693ava\FilamentEventsManager\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use St693ava\FilamentEventsManager\Models\EventRule;
use St693ava\FilamentEventsManager\Support\EventContext;
use ReflectionClass;

class CustomEventManager
{
    private RuleEngine $ruleEngine;
    private array $registeredEvents = [];

    public function __construct(RuleEngine $ruleEngine)
    {
        $this->ruleEngine = $ruleEngine;
    }

    /**
     * Register listeners for all active custom event rules
     */
    public function registerCustomEventListeners(): void
    {
        try {
            $rules = EventRule::where('is_active', true)
                ->where('trigger_type', 'custom')
                ->get();

            foreach ($rules as $rule) {
                $this->registerEventListener($rule);
            }

        } catch (\Exception $e) {
            Log::error('Failed to register custom event listeners', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Register listener for a specific rule
     */
    public function registerEventListener(EventRule $rule): void
    {
        $config = $rule->trigger_config ?? [];

        if (empty($config['event_class'])) {
            Log::warning('Custom event rule missing event class', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
            ]);
            return;
        }

        $eventClass = $config['event_class'];

        // Check if event class exists
        if (!class_exists($eventClass)) {
            Log::warning('Custom event class not found', [
                'rule_id' => $rule->id,
                'event_class' => $eventClass,
            ]);
            return;
        }

        try {
            // Register the listener
            Event::listen($eventClass, function ($event) use ($rule) {
                $this->handleCustomEvent($event, $rule);
            });

            $this->registeredEvents[] = [
                'rule_id' => $rule->id,
                'event_class' => $eventClass,
            ];

            Log::info('Custom event listener registered', [
                'rule_id' => $rule->id,
                'event_class' => $eventClass,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to register custom event listener', [
                'rule_id' => $rule->id,
                'event_class' => $eventClass,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a custom event
     */
    public function handleCustomEvent($event, EventRule $rule): void
    {
        try {
            Log::info('Processing custom event', [
                'rule_id' => $rule->id,
                'event_class' => get_class($event),
            ]);

            // Extract data from the event
            $data = $this->extractEventData($event, $rule);

            // Create event context
            $context = $this->createCustomEventContext($event, $rule);

            // Process the rule
            $this->ruleEngine->processRule($rule, $data, $context);

        } catch (\Exception $e) {
            Log::error('Custom event processing failed', [
                'rule_id' => $rule->id,
                'event_class' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Extract data from the event object
     */
    private function extractEventData($event, EventRule $rule): array
    {
        $config = $rule->trigger_config ?? [];
        $data = [];

        try {
            // If specific properties are configured, extract only those
            if (isset($config['properties']) && is_array($config['properties'])) {
                foreach ($config['properties'] as $property) {
                    if (property_exists($event, $property)) {
                        $data[$property] = $event->{$property};
                    }
                }
            } else {
                // Otherwise, extract all public properties
                $data = $this->extractAllEventProperties($event);
            }

            // Also include the entire event object as a reference
            $data['_event_object'] = $event;

        } catch (\Exception $e) {
            Log::warning('Failed to extract event data', [
                'event_class' => get_class($event),
                'error' => $e->getMessage(),
            ]);

            // Fallback: just include the event object
            $data = ['_event_object' => $event];
        }

        return $data;
    }

    /**
     * Extract all public properties from an event object
     */
    private function extractAllEventProperties($event): array
    {
        $data = [];

        try {
            $reflection = new ReflectionClass($event);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $name = $property->getName();
                $data[$name] = $property->getValue($event);
            }

            // Also try to get properties via get_object_vars (in case of dynamic properties)
            $objectVars = get_object_vars($event);
            $data = array_merge($data, $objectVars);

        } catch (\Exception $e) {
            Log::debug('Failed to extract event properties via reflection', [
                'event_class' => get_class($event),
                'error' => $e->getMessage(),
            ]);

            // Fallback to get_object_vars only
            $data = get_object_vars($event);
        }

        return $data;
    }

    /**
     * Create event context for custom events
     */
    private function createCustomEventContext($event, EventRule $rule): EventContext
    {
        $context = new EventContext();

        $context->set('event_type', 'custom');
        $context->set('event_class', get_class($event));
        $context->set('rule_id', $rule->id);
        $context->set('rule_name', $rule->name);
        $context->set('triggered_at', now()->toISOString());

        // Add event-specific context if available
        if (method_exists($event, 'getContext')) {
            $eventContext = $event->getContext();
            if (is_array($eventContext)) {
                foreach ($eventContext as $key => $value) {
                    $context->set("event_{$key}", $value);
                }
            }
        }

        // User context (if available)
        if (auth()->check()) {
            $context->set('user_id', auth()->id());
            $context->set('user_name', auth()->user()->name ?? 'Unknown');
            $context->set('user_email', auth()->user()->email ?? 'unknown@example.com');
        }

        // Request context (if available)
        if (request()) {
            $context->set('ip_address', request()->ip());
            $context->set('user_agent', request()->userAgent());
            $context->set('request_url', request()->fullUrl());
            $context->set('request_method', request()->method());
        }

        return $context;
    }

    /**
     * Discover available events in the application
     */
    public function discoverEvents(): array
    {
        $events = [];

        try {
            // Discover events in app/Events directory
            $eventsPath = app_path('Events');
            if (is_dir($eventsPath)) {
                $events = array_merge($events, $this->scanDirectoryForEvents($eventsPath, 'App\\Events\\'));
            }

            // Discover events in other common locations
            $commonPaths = [
                [app_path('Domain'), 'App\\Domain\\'],
                [app_path('Modules'), 'App\\Modules\\'],
            ];

            foreach ($commonPaths as [$path, $namespace]) {
                if (is_dir($path)) {
                    $events = array_merge($events, $this->scanForEventsRecursively($path, $namespace));
                }
            }

            // Sort events by name
            sort($events);

        } catch (\Exception $e) {
            Log::error('Failed to discover events', [
                'error' => $e->getMessage(),
            ]);
        }

        return $events;
    }

    /**
     * Scan directory for event classes
     */
    private function scanDirectoryForEvents(string $path, string $namespace): array
    {
        $events = [];

        if (!is_dir($path)) {
            return $events;
        }

        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = $namespace . pathinfo($file, PATHINFO_FILENAME);

                if (class_exists($className) && $this->isEventClass($className)) {
                    $events[] = $className;
                }
            }
        }

        return $events;
    }

    /**
     * Recursively scan for events
     */
    private function scanForEventsRecursively(string $path, string $baseNamespace): array
    {
        $events = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
            $className = $baseNamespace . pathinfo($relativePath, PATHINFO_FILENAME);

            if (class_exists($className) && $this->isEventClass($className)) {
                $events[] = $className;
            }
        }

        return $events;
    }

    /**
     * Check if a class is an event class
     */
    private function isEventClass(string $className): bool
    {
        try {
            $reflection = new ReflectionClass($className);

            // Check if it's in an Events directory or namespace
            if (strpos($className, 'Events\\') !== false) {
                return true;
            }

            // Check if it ends with 'Event'
            if (str_ends_with($className, 'Event')) {
                return true;
            }

            // Check if it implements common event interfaces (basic check)
            $interfaces = $reflection->getInterfaceNames();
            foreach ($interfaces as $interface) {
                if (strpos($interface, 'Event') !== false) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get registered events for this manager
     */
    public function getRegisteredEvents(): array
    {
        return $this->registeredEvents;
    }

    /**
     * Unregister all custom event listeners
     */
    public function unregisterAllListeners(): void
    {
        // Note: Laravel doesn't provide a direct way to unregister specific listeners
        // This would need to be handled at the application level
        $this->registeredEvents = [];
    }
}