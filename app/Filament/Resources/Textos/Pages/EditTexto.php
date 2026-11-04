<?php

namespace App\Filament\Resources\Textos\Pages;

use App\Filament\Resources\Textos\TextoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTexto extends EditRecord
{
    protected static string $resource = TextoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
