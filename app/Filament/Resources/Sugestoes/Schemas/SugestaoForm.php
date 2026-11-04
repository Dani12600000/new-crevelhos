<?php

namespace App\Filament\Resources\Sugestoes\Schemas;

use App\Enums\EstadoSugestao;
use App\Enums\Origem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SugestaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->columns(2)->schema([
                    TextInput::make('nome_sugerido')->label('Nome sugerido')->required()->maxLength(255),
                    Select::make('fonte')->label('De onde veio')->options(Origem::class)->required(),
                    DatePicker::make('data')->label('Data')->native(false)->displayFormat('d/m/Y'),
                    Select::make('estado')->label('Estado')->options(EstadoSugestao::class)->required()
                        ->helperText('Para aceitar e ligar a uma pessoa, use o botão "Aceitar" na lista.'),
                    Textarea::make('notas')->label('Notas')->rows(3)->columnSpanFull(),
                ]),
            ]);
    }
}
