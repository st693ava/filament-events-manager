<?php

namespace St693ava\FilamentEventsManager\Filament\Resources\SimpleEventRuleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use St693ava\FilamentEventsManager\Filament\Resources\SimpleEventRuleResource;

class ListSimpleEventRules extends ListRecords
{
    protected static string $resource = SimpleEventRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}