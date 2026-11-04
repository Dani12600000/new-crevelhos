<?php

namespace App\Filament\Resources\Pessoas\Tables;

use App\Models\Pessoa;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PessoasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('alcunha')->label('Alcunha')->searchable()->toggleable(),
                TextColumn::make('marcacoes_count')->label('Aparições')->counts('marcacoes')->sortable(),
                ToggleColumn::make('oculta')->label('Escondida'),
            ])
            ->filters([
                TernaryFilter::make('oculta')->label('No site')->trueLabel('Escondidas')->falseLabel('Visíveis'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('site')->label('Ver no site')->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn (Pessoa $record) => route('pessoa', $record), shouldOpenInNewTab: true)
                    ->visible(fn (Pessoa $record) => ! $record->oculta),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
