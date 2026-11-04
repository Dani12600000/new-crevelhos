<?php

namespace App\Filament\Resources\Comentarios;

use App\Filament\Resources\Comentarios\Pages\CreateComentario;
use App\Filament\Resources\Comentarios\Pages\EditComentario;
use App\Filament\Resources\Comentarios\Pages\ListComentarios;
use App\Filament\Resources\Comentarios\Schemas\ComentarioForm;
use App\Filament\Resources\Comentarios\Tables\ComentariosTable;
use App\Models\Comentario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Memórias transcritas da folha do café. Só aparecem no site depois de publicadas. */
class ComentarioResource extends Resource
{
    protected static ?string $model = Comentario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Identificação';

    protected static ?string $modelLabel = 'memória';

    protected static ?string $pluralModelLabel = 'memórias';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $porPublicar = Comentario::where('publicado', false)->count();

        return $porPublicar > 0 ? (string) $porPublicar : null;
    }

    public static function form(Schema $schema): Schema
    {
        return ComentarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComentariosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComentarios::route('/'),
            'create' => CreateComentario::route('/create'),
            'edit' => EditComentario::route('/{record}/edit'),
        ];
    }
}
