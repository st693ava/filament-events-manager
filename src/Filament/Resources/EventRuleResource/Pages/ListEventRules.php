<?php

namespace St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use St693ava\FilamentEventsManager\Filament\Resources\EventRuleResource;

class ListEventRules extends ListRecords
{
    protected static string $resource = EventRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Criar Regra'),
        ];
    }
}