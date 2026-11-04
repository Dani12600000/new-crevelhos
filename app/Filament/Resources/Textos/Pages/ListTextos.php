<?php

namespace App\Filament\Resources\Textos\Pages;

use App\Filament\Resources\Textos\TextoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTextos extends ListRecords
{
    protected static string $resource = TextoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
