<?php

namespace St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource;

class EditEventRule extends EditRecord
{
    protected static string $resource = EventRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}