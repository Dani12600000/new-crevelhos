<?php

namespace App\Filament\Resources\Pecas\Tables;

use App\Enums\TipoPeca;
use App\Filament\Resources\Pecas\PecaResource;
use App\Models\Peca;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PecasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('ref')
            ->columns([
                ImageColumn::make('miniatura')
                    ->label('')
                    ->state(fn (Peca $record) => $record->urlMiniatura())
                    ->imageHeight(56),
                TextColumn::make('ref')
                    ->label('Ref.')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('titulo_exibicao')
                    ->label('Título')
                    ->limit(60)
                    ->wrap()
                    ->description(fn (Peca $record) => blank($record->titulo) ? 'sem título' : null)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(
                        fn ($q) => $q->where('titulo', 'like', "%{$search}%")->orWhere('descricao', 'like', "%{$search}%")
                    )),
                TextColumn::make('tipo')->label('Tipo')->badge()->sortable(),
                TextColumn::make('data_texto')->label('Data')->toggleable(),
                TextColumn::make('decada')->label('Década')->sortable()->toggleable(),
                TextColumn::make('temas.nome')->label('Temas')->badge()->limitList(2)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('marcacoes_count')->label('Números')->counts('marcacoes')->toggleable(),
                ToggleColumn::make('publicado')->label('Publicada'),
                ToggleColumn::make('destaque')->label('Destaque')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('tipo')->label('Tipo')->options(TipoPeca::class)->multiple(),
                SelectFilter::make('temas')->label('Tema')->relationship('temas', 'nome')->multiple()->searchable()->preload(),
                SelectFilter::make('decada')->label('Década')
                    ->options(fn () => Peca::query()->whereNotNull('decada')->distinct()->orderBy('decada')->pluck('decada', 'decada')->map(fn ($d) => "Anos {$d}")->all()),
                TernaryFilter::make('publicado')->label('Estado')->trueLabel('Publicadas')->falseLabel('Por publicar'),
                TernaryFilter::make('destaque')->label('Destaque'),
                TernaryFilter::make('sem_titulo')->label('Título')
                    ->trueLabel('Sem título')->falseLabel('Com título')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('titulo'),
                        false: fn (Builder $q) => $q->whereNotNull('titulo'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('marcacoes')
                        ->label('Marcar pessoas')
                        ->icon(Heroicon::OutlinedUserGroup)
                        ->visible(fn (Peca $record) => $record->tipo->permiteMarcacoes() && $record->temImagem())
                        ->url(fn (Peca $record) => PecaResource::getUrl('marcacoes', ['record' => $record])),
                    Action::make('site')
                        ->label('Ver no site')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->url(fn (Peca $record) => route('peca', $record), shouldOpenInNewTab: true),
                    Action::make('folha')
                        ->label('Folha para o café')
                        ->icon(Heroicon::OutlinedPrinter)
                        ->url(fn (Peca $record) => route('folha-cafe', $record), shouldOpenInNewTab: true),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publicar')
                        ->label('Publicar')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['publicado' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('despublicar')
                        ->label('Despublicar')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['publicado' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
