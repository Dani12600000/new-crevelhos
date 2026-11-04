<?php

namespace App\Filament\Resources\Sugestoes\Pages;

use App\Filament\Resources\Sugestoes\SugestaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSugestao extends EditRecord
{
    protected static string $resource = SugestaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
