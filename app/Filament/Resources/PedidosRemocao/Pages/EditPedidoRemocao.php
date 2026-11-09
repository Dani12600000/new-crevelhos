<?php

namespace App\Filament\Resources\PedidosRemocao\Pages;

use App\Filament\Resources\PedidosRemocao\PedidoRemocaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPedidoRemocao extends EditRecord
{
    protected static string $resource = PedidoRemocaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
