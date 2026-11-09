<?php

namespace App\Filament\Resources\PedidosRemocao;

use App\Filament\Resources\PedidosRemocao\Pages\CreatePedidoRemocao;
use App\Filament\Resources\PedidosRemocao\Pages\EditPedidoRemocao;
use App\Filament\Resources\PedidosRemocao\Pages\ListPedidosRemocao;
use App\Filament\Resources\PedidosRemocao\Schemas\PedidoRemocaoForm;
use App\Filament\Resources\PedidosRemocao\Tables\PedidosRemocaoTable;
use App\Models\PedidoRemocao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** RGPD: registo dos pedidos para retirar alguém do arquivo e do que se fez. */
class PedidoRemocaoResource extends Resource
{
    protected static ?string $model = PedidoRemocao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $modelLabel = 'pedido de remoção';

    protected static ?string $pluralModelLabel = 'pedidos de remoção';

    protected static ?string $slug = 'pedidos-remocao';

    public static function getNavigationBadge(): ?string
    {
        $porResolver = PedidoRemocao::whereNull('data_resolucao')->count();

        return $porResolver > 0 ? (string) $porResolver : null;
    }

    public static function form(Schema $schema): Schema
    {
        return PedidoRemocaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PedidosRemocaoTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPedidosRemocao::route('/'),
            'create' => CreatePedidoRemocao::route('/create'),
            'edit' => EditPedidoRemocao::route('/{record}/edit'),
        ];
    }
}
