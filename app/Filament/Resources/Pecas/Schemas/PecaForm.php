<?php

namespace App\Filament\Resources\Pecas\Schemas;

use App\Enums\TipoPeca;
use App\Models\Peca;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PecaForm
{
    /** Botões do editor de texto: só o que o site aceita (p, br, strong, em, listas, ligações). */
    public const BOTOES_TEXTO = [['bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()->columnSpan(['lg' => 2])->schema([
                    Section::make('Identificação')->columns(2)->schema([
                        Select::make('tipo')
                            ->label('Tipo')
                            ->options(TipoPeca::class)
                            ->default(TipoPeca::Foto)
                            ->required()
                            ->live()
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Mudar o tipo não muda a referência.' : 'A referência (ex.: CRV-F-0046) é criada ao gravar.'),
                        TextInput::make('ref')
                            ->label('Referência')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->extraInputAttributes(['class' => 'font-mono']),
                        TextInput::make('titulo')
                            ->label('Título')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Sem título, o site mostra o início da descrição.'),
                        TextInput::make('data_texto')
                            ->label('Data')
                            ->maxLength(255)
                            ->helperText('Texto livre, como está no original: "1962-05-01", "Outubro de 1967"...'),
                        TextInput::make('decada')
                            ->label('Década')
                            ->numeric()
                            ->minValue(1850)
                            ->maxValue(2030)
                            ->step(10)
                            ->helperText('Se ficar vazia, é calculada a partir do ano da data.'),
                        TextInput::make('local')->label('Local')->maxLength(255),
                        TextInput::make('autor')->label('Autor / fotógrafo')->maxLength(255)->placeholder('Desconhecido'),
                        TextInput::make('cedido_por')
                            ->label('Cedido por')
                            ->maxLength(255)
                            ->datalist(fn () => Peca::valoresCedidoPor())
                            ->helperText('Quem partilhou a peça. Escolha um nome sugerido, se já existir.'),
                        Select::make('acervo_id')
                            ->label('Acervo')
                            ->relationship('acervo', 'nome')
                            ->createOptionForm([TextInput::make('nome')->label('Nome')->required()->maxLength(255)]),
                        Select::make('temas')
                            ->label('Temas')
                            ->relationship('temas', 'nome')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull()
                            ->createOptionForm([TextInput::make('nome')->label('Nome')->required()->maxLength(255)]),
                    ]),

                    Section::make('Descrição')->schema([
                        RichEditor::make('descricao')
                            ->hiddenLabel()
                            ->toolbarButtons(self::BOTOES_TEXTO),
                    ]),

                    Section::make('Recorte de imprensa')->columns(2)
                        ->visible(fn (Get $get) => self::tipo($get) === TipoPeca::Imprensa)
                        ->schema([
                            TextInput::make('jornal')->label('Jornal')->maxLength(255),
                            DatePicker::make('data_publicacao')->label('Data de publicação')->native(false)->displayFormat('d/m/Y'),
                            TextInput::make('url_original')->label('Notícia original (URL)')->url()->maxLength(500)->columnSpanFull(),
                        ]),

                    Section::make('Livro')->columns(2)
                        ->visible(fn (Get $get) => self::tipo($get) === TipoPeca::Livro)
                        ->schema([
                            TextInput::make('editora')->label('Editora')->maxLength(255),
                            TextInput::make('edicao')->label('Edição')->maxLength(255),
                            TextInput::make('data_edicao')->label('Data da edição')->maxLength(255),
                            TextInput::make('paginas')->label('Páginas')->maxLength(255),
                        ]),
                ]),

                Group::make()->columnSpan(['lg' => 1])->schema([
                    Section::make('Publicação')->schema([
                        Toggle::make('publicado')
                            ->label('Publicada no site')
                            ->helperText('Só as peças publicadas aparecem no site.'),
                        Toggle::make('destaque')
                            ->label('Em destaque no início'),
                    ]),

                    Section::make('Imagem')->schema([
                        FileUpload::make('ficheiro')
                            ->hiddenLabel()
                            ->image()
                            ->disk('public')
                            ->directory('pecas/envios')
                            ->maxSize(30 * 1024)
                            ->imagePreviewHeight('220')
                            ->downloadable()
                            ->helperText('Guarda-se o original; a versão WebP e a miniatura são geradas sozinhas.'),
                        TextInput::make('video_url')
                            ->label('Endereço do vídeo (YouTube ou Vimeo)')
                            ->url()
                            ->maxLength(255)
                            ->visible(fn (Get $get) => self::tipo($get) === TipoPeca::Video),
                    ]),

                    Section::make('Notas internas')->collapsible()->schema([
                        Textarea::make('notas_internas')
                            ->hiddenLabel()
                            ->rows(4)
                            ->helperText('Nunca aparecem no site.'),
                    ]),
                ]),
            ]);
    }

    private static function tipo(Get $get): ?TipoPeca
    {
        $tipo = $get('tipo');

        return $tipo instanceof TipoPeca ? $tipo : TipoPeca::tryFrom((string) $tipo);
    }
}
