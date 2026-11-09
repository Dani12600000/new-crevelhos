<?php

namespace App\Filament\Resources\Textos;

use App\Filament\Resources\Textos\Pages\EditTexto;
use App\Filament\Resources\Textos\Pages\ListTextos;
use App\Filament\Resources\Textos\Schemas\TextoForm;
use App\Filament\Resources\Textos\Tables\TextosTable;
use App\Models\Texto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Textos do site. As chaves são fixas (config/crevelhos.php): só se edita o conteúdo. */
class TextoResource extends Resource
{
    protected static ?string $model = Texto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $modelLabel = 'texto do site';

    protected static ?string $pluralModelLabel = 'textos do site';

    public static function form(Schema $schema): Schema
    {
        return TextoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TextosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTextos::route('/'),
            'edit' => EditTexto::route('/{record}/edit'),
        ];
    }
}
