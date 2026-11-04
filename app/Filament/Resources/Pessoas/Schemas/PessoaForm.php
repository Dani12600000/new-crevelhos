<?php

namespace App\Filament\Resources\Pessoas\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PessoaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->columns(2)->schema([
                    TextInput::make('nome')->label('Nome')->required()->maxLength(255),
                    TextInput::make('alcunha')->label('Alcunha')->maxLength(255),
                    Textarea::make('notas')->label('Notas internas')->rows(4)->columnSpanFull()
                        ->helperText('Não aparecem no site (ex.: "filha de...", quem a identificou).'),
                    Toggle::make('oculta')->label('Esconder no site')->columnSpanFull()
                        ->helperText('RGPD: a pessoa e os seus números deixam de aparecer no site, sem se apagar nada.'),
                ]),
            ]);
    }
}
