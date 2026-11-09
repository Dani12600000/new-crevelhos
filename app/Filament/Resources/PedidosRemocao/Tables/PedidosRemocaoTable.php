<?php

namespace App\Filament\Resources\PedidosRemocao\Tables;

use App\Models\PedidoRemocao;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PedidosRemocaoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_pedido', 'desc')
            ->columns([
                TextColumn::make('data_pedido')->label('Pedido em')->date('d/m/Y')->sortable(),
                TextColumn::make('requerente')->label('Quem pediu')->searchable(),
                TextColumn::make('pessoa.nome')->label('Pessoa')->placeholder('—')->searchable(),
                TextColumn::make('marcacao_id')
                    ->label('Número')
                    ->state(fn (PedidoRemocao $record) => $record->marcacao ? $record->marcacao->peca->ref.' · n.º '.$record->marcacao->numero : null)
                    ->placeholder('—'),
                TextColumn::make('pedido')->label('O que foi pedido')->limit(80)->wrap()->searchable(),
                TextColumn::make('data_resolucao')->label('Resolvido em')->date('d/m/Y')->placeholder('Por resolver')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('resolvido')->label('Estado')
                    ->trueLabel('Resolvidos')->falseLabel('Por resolver')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('data_resolucao'),
                        false: fn (Builder $q) => $q->whereNull('data_resolucao'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
