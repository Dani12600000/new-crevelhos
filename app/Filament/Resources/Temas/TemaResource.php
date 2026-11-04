<?php

namespace App\Filament\Resources\Temas;

use App\Filament\Resources\Temas\Pages\ManageTemas;
use App\Models\Tema;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TemaResource extends Resource
{
    protected static ?string $model = Tema::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Arquivo';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')->label('Nome')->required()->maxLength(255),
                TextInput::make('slug')->label('Endereço')->maxLength(255)->unique(ignoreRecord: true)
                    ->helperText('Usado em /arquivo?tema=... Deixe vazio para gerar a partir do nome.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('slug')->label('Endereço')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pecas_count')->label('Peças')->counts('pecas')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('sem_pecas')->label('Uso')
                    ->trueLabel('Sem peças')->falseLabel('Com peças')
                    ->queries(
                        true: fn (Builder $q) => $q->doesntHave('pecas'),
                        false: fn (Builder $q) => $q->has('pecas'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTemas::route('/'),
        ];
    }
}
