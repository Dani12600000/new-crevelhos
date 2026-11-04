<?php

namespace App\Filament\Resources\Comentarios\Schemas;

use App\Enums\Origem;
use App\Models\Peca;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComentarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Memória')->columnSpan(['lg' => 2])->columns(2)->schema([
                    Select::make('peca_id')
                        ->label('Peça')
                        ->relationship('peca', 'ref')
                        ->getOptionLabelFromRecordUsing(fn (Peca $record) => $record->ref.' · '.$record->titulo_exibicao)
                        ->searchable(['ref', 'titulo'])
                        ->required()
                        ->columnSpanFull(),
                    Textarea::make('texto')
                        ->label('Texto')
                        ->helperText('Transcreva como está na folha. Corrija só a ortografia evidente.')
                        ->rows(6)
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('autor')->label('Quem escreveu')->maxLength(255)->placeholder('Anónimo'),
                    Select::make('origem')->label('De onde veio')->options(Origem::class)->default(Origem::Folha)->required(),
                    DatePicker::make('data')->label('Data')->native(false)->displayFormat('d/m/Y')->default(now()),
                ]),
                Section::make()->columnSpan(['lg' => 1])->schema([
                    Toggle::make('publicado')
                        ->label('Publicada no site')
                        ->helperText('Nada é público sem ser publicado aqui.'),
                    FileUpload::make('foto_folha')
                        ->label('Fotografia da folha (opcional)')
                        ->image()
                        ->disk('local')
                        ->directory('folhas')
                        ->visibility('private')
                        ->helperText('Fica guardada só no backoffice, nunca no site.'),
                ]),
            ]);
    }
}
