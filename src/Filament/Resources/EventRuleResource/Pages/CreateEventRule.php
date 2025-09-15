<?php

namespace St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource;

class CreateEventRule extends CreateRecord
{
    protected static string $resource = EventRuleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}