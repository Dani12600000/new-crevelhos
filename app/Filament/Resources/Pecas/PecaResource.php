<?php

namespace App\Filament\Resources\Pecas;

use App\Filament\Resources\Pecas\Pages\CreatePeca;
use App\Filament\Resources\Pecas\Pages\EditPeca;
use App\Filament\Resources\Pecas\Pages\ListPecas;
use App\Filament\Resources\Pecas\Pages\MarcacoesPeca;
use App\Filament\Resources\Pecas\Schemas\PecaForm;
use App\Filament\Resources\Pecas\Tables\PecasTable;
use App\Models\Peca;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PecaResource extends Resource
{
    protected static ?string $model = Peca::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $modelLabel = 'peça';

    protected static ?string $pluralModelLabel = 'peças';

    protected static ?string $recordTitleAttribute = 'ref';

    protected static string|UnitEnum|null $navigationGroup = 'Arquivo';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PecaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PecasTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ref', 'titulo', 'descricao', 'cedido_por', 'local'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->ref.' · '.$record->titulo_exibicao;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPecas::route('/'),
            'create' => CreatePeca::route('/create'),
            'edit' => EditPeca::route('/{record}/edit'),
            'marcacoes' => MarcacoesPeca::route('/{record}/marcacoes'),
        ];
    }
}
