<?php

namespace St693ava\FilamentEventsManager\Filament\Resources\SimpleEventRuleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use St693ava\FilamentEventsManager\Filament\Resources\SimpleEventRuleResource;

class EditSimpleEventRule extends EditRecord
{
    protected static string $resource = SimpleEventRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}