<?php

namespace App\Filament\Resources\Textos\Schemas;

use App\Filament\Resources\Pecas\Schemas\PecaForm;
use App\Models\Texto;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TextoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(fn (?Texto $record) => [
                Section::make($record?->rotulo())->schema([
                    $record?->ehHtml()
                        ? RichEditor::make('valor')->hiddenLabel()->toolbarButtons(PecaForm::BOTOES_TEXTO)
                        : TextInput::make('valor')->hiddenLabel()->maxLength(500),
                ]),
            ]);
    }
}
