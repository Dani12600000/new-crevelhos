<?php

namespace App\Filament\Resources\PedidosRemocao\Schemas;

use App\Models\Marcacao;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PedidoRemocaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Pedido')->columns(2)->schema([
                    TextInput::make('requerente')->label('Quem pediu')->required()->maxLength(255),
                    TextInput::make('contacto')->label('Contacto')->maxLength(255),
                    DatePicker::make('data_pedido')->label('Data do pedido')->native(false)->displayFormat('d/m/Y')->default(now())->required(),
                    Select::make('pessoa_id')
                        ->label('Pessoa')
                        ->relationship('pessoa', 'nome')
                        ->searchable()
                        ->preload(),
                    Select::make('marcacao_id')
                        ->label('Número numa fotografia')
                        ->options(fn () => Marcacao::with(['peca', 'pessoa'])->get()
                            ->sortBy(fn (Marcacao $m) => [$m->peca->ref, $m->numero])
                            ->mapWithKeys(fn (Marcacao $m) => [$m->id => $m->peca->ref.' · n.º '.$m->numero.($m->pessoa ? ' · '.$m->pessoa->nome : '')]))
                        ->searchable()
                        ->columnSpanFull(),
                    Textarea::make('pedido')
                        ->label('O que foi pedido')
                        ->helperText('Escreva também o nome da pessoa e a referência da peça: fica registado mesmo que a ficha ou o número sejam apagados.')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ]),
                Section::make('Resolução')->schema([
                    Textarea::make('acao_tomada')->label('O que se fez')->rows(4),
                    DatePicker::make('data_resolucao')
                        ->label('Quando')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('data_pedido')
                        ->helperText('Enquanto estiver vazia, o pedido conta como por resolver.'),
                ]),
            ]);
    }
}
