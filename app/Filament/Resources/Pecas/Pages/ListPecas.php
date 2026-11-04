<?php

namespace App\Filament\Resources\Pecas\Pages;

use App\Enums\TipoPeca;
use App\Filament\Resources\Pecas\PecaResource;
use App\Models\Acervo;
use App\Models\Peca;
use App\Models\Tema;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ListPecas extends ListRecords
{
    protected static string $resource = PecaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->enviarVariasAction(),
            CreateAction::make(),
        ];
    }

    /** Upload em lote: uma peça nova por imagem, com os mesmos dados para todas. */
    protected function enviarVariasAction(): Action
    {
        return Action::make('enviarVarias')
            ->label('Enviar várias imagens')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalDescription('Cria uma peça por cada imagem. Depois pode completar cada uma (título, data, descrição).')
            ->schema([
                FileUpload::make('ficheiros')
                    ->label('Imagens')
                    ->image()
                    ->multiple()
                    ->maxFiles(50)
                    ->maxSize(30 * 1024)
                    ->disk('public')
                    ->directory('pecas/envios')
                    ->required(),
                Select::make('tipo')->label('Tipo')->options(TipoPeca::class)->default(TipoPeca::Foto)->required(),
                Select::make('temas')->label('Temas')->options(fn () => Tema::orderBy('nome')->pluck('nome', 'id'))->multiple()->searchable(),
                Select::make('acervo_id')->label('Acervo')->options(fn () => Acervo::orderBy('nome')->pluck('nome', 'id')),
                TextInput::make('cedido_por')->label('Cedido por')->maxLength(255),
                Toggle::make('publicado')->label('Publicar já')->default(false)
                    ->helperText('Normalmente deixa-se por publicar até ter título e descrição.'),
            ])
            ->action(function (array $data) {
                $criadas = DB::transaction(function () use ($data) {
                    $criadas = 0;
                    foreach ($data['ficheiros'] as $ficheiro) {
                        $peca = Peca::create([
                            'tipo' => $data['tipo'],
                            'ficheiro' => $ficheiro,
                            'acervo_id' => $data['acervo_id'] ?? null,
                            'cedido_por' => $data['cedido_por'] ?? null,
                            'publicado' => (bool) $data['publicado'],
                        ]);
                        $peca->temas()->sync($data['temas'] ?? []);
                        $criadas++;
                    }

                    return $criadas;
                });

                Notification::make()->success()->title("{$criadas} peças criadas")->send();
            });
    }
}
