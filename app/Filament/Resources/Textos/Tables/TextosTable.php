<?php

namespace App\Filament\Resources\Textos\Tables;

use App\Models\Texto;
use App\Support\Limpeza;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TextosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('rotulo')->label('Texto')->state(fn (Texto $record) => $record->rotulo())->weight('bold'),
                TextColumn::make('valor')->label('Conteúdo')
                    ->formatStateUsing(fn (?string $state) => Limpeza::semHtml($state))
                    ->limit(90)
                    ->placeholder('(vazio)')
                    ->wrap(),
                TextColumn::make('updated_at')->label('Alterado')->since()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
