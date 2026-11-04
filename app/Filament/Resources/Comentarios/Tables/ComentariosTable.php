<?php

namespace App\Filament\Resources\Comentarios\Tables;

use App\Enums\Origem;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ComentariosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('peca.ref')->label('Peça')->fontFamily(FontFamily::Mono)->searchable(),
                TextColumn::make('texto')->label('Texto')->limit(80)->wrap()->searchable(),
                TextColumn::make('autor')->label('Autor')->placeholder('Anónimo')->searchable(),
                TextColumn::make('origem')->label('Origem')->badge()->color('gray'),
                TextColumn::make('data')->label('Data')->date('d/m/Y')->sortable(),
                ToggleColumn::make('publicado')->label('Publicada'),
            ])
            ->filters([
                TernaryFilter::make('publicado')->label('Estado')
                    ->trueLabel('Publicadas')->falseLabel('Por publicar')
                    ->default(false),
                SelectFilter::make('origem')->label('Origem')->options(Origem::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publicar')->label('Publicar')->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['publicado' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
