<?php

namespace St693ava\FilamentEventsManager\Actions\Executors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Facades\LogActivity;
use St693ava\FilamentEventsManager\Actions\Contracts\ActionExecutor;
use St693ava\FilamentEventsManager\Models\EventRuleAction;
use St693ava\FilamentEventsManager\Services\TemplateRenderer;
use St693ava\FilamentEventsManager\Support\EventContext;

class ActivityLogAction implements ActionExecutor
{
    public function __construct(
        private TemplateRenderer $templateRenderer
    ) {}

    public function execute(EventRuleAction $action, array $data, EventContext $context): array
    {
        $config = $action->action_config;

        // Encontrar o modelo para associar ao log
        $model = $this->findModelInData($data);

        if (!$model) {
            throw new \InvalidArgumentException('Activity log requer pelo menos um modelo nos dados');
        }

        // Renderizar descrição
        $description = $this->templateRenderer->render($config['description'], $data, $context);

        // Preparar propriedades personalizadas
        $customProperties = $config['properties'] ?? [];
        $renderedProperties = [];

        foreach ($customProperties as $key => $value) {
            $renderedProperties[$key] = $this->templateRenderer->render($value, $data, $context);
        }

        // Criar o log de atividade
        $logName = $config['log_name'] ?? 'events_manager';
        $causerId = $context->getUserId();

        $logBuilder = activity($logName)
            ->performedOn($model)
            ->withProperties(array_merge([
                'rule_id' => $action->event_rule_id,
                'event_name' => $context->getEventName(),
                'triggered_at' => $context->getTriggeredAt()->toISOString(),
                'request_source' => $context->getRequestSource(),
                'ip_address' => $context->getIpAddress(),
            ], $renderedProperties));

        // Adicionar causer se disponível
        if ($causerId) {
            $logBuilder->causedBy($causerId);
        }

        // Adicionar evento específico se configurado
        if (!empty($config['event'])) {
            $logBuilder->event($config['event']);
        }

        // Criar o log
        $log = $logBuilder->log($description);

        return [
            'log_id' => $log->id,
            'description' => $description,
            'log_name' => $logName,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'causer_id' => $causerId,
            'properties_count' => count($renderedProperties),
            'created_at' => $log->created_at->toISOString(),
        ];
    }

    public function validateConfig(array $config): array
    {
        $validator = Validator::make($config, [
            'description' => 'required|string',
            'log_name' => 'nullable|string',
            'event' => 'nullable|string',
            'properties' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

    private function findModelInData(array $data): ?Model
    {
        foreach ($data as $item) {
            if ($item instanceof Model) {
                return $item;
            }
        }

        return null;
    }
}