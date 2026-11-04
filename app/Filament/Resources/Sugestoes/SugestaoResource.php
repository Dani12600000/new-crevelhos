<?php

namespace App\Filament\Resources\Sugestoes;

use App\Enums\EstadoSugestao;
use App\Filament\Resources\Sugestoes\Pages\EditSugestao;
use App\Filament\Resources\Sugestoes\Pages\ListSugestoes;
use App\Filament\Resources\Sugestoes\Schemas\SugestaoForm;
use App\Filament\Resources\Sugestoes\Tables\SugestoesTable;
use App\Models\Sugestao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Fila de todas as sugestões de nomes. Criam-se no editor de marcações de cada peça. */
class SugestaoResource extends Resource
{
    protected static ?string $model = Sugestao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static string|UnitEnum|null $navigationGroup = 'Identificação';

    protected static ?string $modelLabel = 'sugestão';

    protected static ?string $pluralModelLabel = 'sugestões';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'sugestoes';

    public static function getNavigationBadge(): ?string
    {
        $pendentes = Sugestao::where('estado', EstadoSugestao::Pendente)->count();

        return $pendentes > 0 ? (string) $pendentes : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return SugestaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SugestoesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSugestoes::route('/'),
            'edit' => EditSugestao::route('/{record}/edit'),
        ];
    }
}
