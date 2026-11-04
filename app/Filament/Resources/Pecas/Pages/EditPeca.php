<?php

namespace App\Filament\Resources\Pecas\Pages;

use App\Filament\Resources\Pecas\PecaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPeca extends EditRecord
{
    protected static string $resource = PecaResource::class;

    public function getTitle(): string
    {
        return $this->record->ref;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('marcacoes')
                ->label('Marcar pessoas')
                ->icon(Heroicon::OutlinedUserGroup)
                ->visible(fn () => $this->record->tipo->permiteMarcacoes() && $this->record->temImagem())
                ->url(fn () => PecaResource::getUrl('marcacoes', ['record' => $this->record])),
            Action::make('site')
                ->label('Ver no site')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->color('gray')
                ->url(fn () => route('peca', $this->record), shouldOpenInNewTab: true),
            Action::make('folha')
                ->label('Folha para o café')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(fn () => route('folha-cafe', $this->record), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
