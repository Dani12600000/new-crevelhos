<?php

namespace App\Filament\Resources\Sugestoes\Tables;

use App\Enums\EstadoSugestao;
use App\Enums\Origem;
use App\Filament\Resources\Pecas\PecaResource;
use App\Models\Pessoa;
use App\Models\Sugestao;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SugestoesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['marcacao.peca', 'marcacao.pessoa']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ViewColumn::make('recorte')->label('')->view('filament.colunas.recorte'),
                TextColumn::make('marcacao.peca.ref')->label('Peça')->fontFamily(FontFamily::Mono)->searchable(),
                TextColumn::make('marcacao.numero')->label('N.º'),
                TextColumn::make('nome_sugerido')->label('Nome sugerido')->searchable()->weight('bold'),
                TextColumn::make('marcacao.pessoa.nome')->label('Já ligado a')->placeholder('ninguém')->toggleable(),
                IconColumn::make('conflito')
                    ->label('Conflito')
                    ->state(fn (Sugestao $record) => $record->estado === EstadoSugestao::Pendente && $record->marcacao->temSugestoesEmConflito())
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)
                    ->trueColor('warning')
                    ->falseIcon(null)
                    ->tooltip(fn (bool $state) => $state ? 'Há sugestões diferentes para este número' : null),
                TextColumn::make('fonte')->label('Fonte')->badge()->color('gray'),
                TextColumn::make('data')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(EstadoSugestao::class)->default(EstadoSugestao::Pendente->value),
                SelectFilter::make('fonte')->label('Fonte')->options(Origem::class),
            ])
            ->recordActions([
                Action::make('aceitar')
                    ->label('Aceitar')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Sugestao $record) => $record->estado === EstadoSugestao::Pendente)
                    ->modalDescription('O número fica ligado a esta pessoa e as outras sugestões pendentes passam a rejeitadas.')
                    ->fillForm(fn (Sugestao $record) => ['pessoa_id' => Pessoa::where('nome', $record->nome_sugerido)->value('id')])
                    ->schema([
                        Select::make('pessoa_id')
                            ->label('Pessoa já existente')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Pessoa::where('nome', 'like', "%{$search}%")->orderBy('nome')->limit(30)->pluck('nome', 'id'))
                            ->getOptionLabelUsing(fn ($value) => Pessoa::find($value)?->nome)
                            ->helperText('Deixe vazio para criar uma pessoa nova com o nome sugerido.'),
                    ])
                    ->action(function (Sugestao $record, array $data) {
                        $pessoa = $record->aceitar(filled($data['pessoa_id']) ? Pessoa::find($data['pessoa_id']) : null);
                        Notification::make()->success()->title("{$record->marcacao->peca->ref}, n.º {$record->marcacao->numero}: {$pessoa->nome}")->send();
                    }),
                Action::make('rejeitar')
                    ->label('Rejeitar')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('gray')
                    ->visible(fn (Sugestao $record) => $record->estado === EstadoSugestao::Pendente)
                    ->action(fn (Sugestao $record) => $record->rejeitar()),
                Action::make('abrir')
                    ->label('Abrir a foto')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->color('gray')
                    ->url(fn (Sugestao $record) => PecaResource::getUrl('marcacoes', ['record' => $record->marcacao->peca])),
                EditAction::make()->iconButton(),
            ])
            ->emptyStateHeading('Sem sugestões')
            ->emptyStateDescription('As sugestões registam-se na página "Marcar pessoas" de cada fotografia.');
    }
}
