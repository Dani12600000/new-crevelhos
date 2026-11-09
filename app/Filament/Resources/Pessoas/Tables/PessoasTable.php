<?php

namespace App\Filament\Resources\Pessoas\Tables;

use App\Models\Pessoa;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
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
                Action::make('juntar')
                    ->label('Juntar com…')
                    ->icon(Heroicon::OutlinedArrowsPointingIn)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Pessoa $record) => "Juntar «{$record->nome}» com outra pessoa")
                    ->modalDescription('As marcações, a alcunha e as notas passam para a pessoa escolhida. Esta ficha é apagada e o nome dela fica registado nas notas. Se uma das duas estiver escondida, a pessoa escolhida fica escondida. Não é possível desfazer.')
                    ->modalSubmitActionLabel('Juntar e apagar esta ficha')
                    ->schema([
                        Select::make('pessoa_id')
                            ->label('Pessoa que fica')
                            ->options(fn (Pessoa $record) => Pessoa::whereKeyNot($record->id)->orderBy('nome')->pluck('nome', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Pessoa $record, array $data) {
                        $destino = Pessoa::findOrFail($data['pessoa_id']);
                        $record->juntarEm($destino);

                        Notification::make()->success()->title("Fichas juntas em «{$destino->nome}»")->send();
                    }),
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
