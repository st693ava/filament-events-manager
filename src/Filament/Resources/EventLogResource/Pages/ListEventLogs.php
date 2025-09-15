<?php

namespace St693ava\FilamentEventsManager\Filament\Resources\EventLogResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use St693ava\FilamentEventsManager\Filament\Resources\EventLogResource;

class ListEventLogs extends ListRecords
{
    protected static string $resource = EventLogResource::class;

    // disable heading
    //protected string|null $heading = ' ';


    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clear_old_logs')
                ->label('Limpar Logs Antigos')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Limpar Logs Antigos')
                ->modalDescription('Esta ação irá eliminar todos os logs com mais de 30 dias. Esta ação não pode ser desfeita.')
                ->action(function () {
                    $deletedCount = static::getModel()::where('triggered_at', '<', now()->subDays(30))->delete();

                    $this->notify('success', "Eliminados {$deletedCount} logs antigos com sucesso.");
                }),
        ];
    }
}
