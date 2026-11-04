<?php

namespace App\Filament\Resources\Acervos\Pages;

use App\Filament\Resources\Acervos\AcervoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAcervos extends ManageRecords
{
    protected static string $resource = AcervoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
