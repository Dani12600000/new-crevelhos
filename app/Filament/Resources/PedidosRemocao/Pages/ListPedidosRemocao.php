<?php

namespace App\Filament\Resources\PedidosRemocao\Pages;

use App\Filament\Resources\PedidosRemocao\PedidoRemocaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPedidosRemocao extends ListRecords
{
    protected static string $resource = PedidoRemocaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
