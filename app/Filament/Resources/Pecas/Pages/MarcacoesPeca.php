<?php

namespace App\Filament\Resources\Pecas\Pages;

use App\Enums\EstadoSugestao;
use App\Enums\Origem;
use App\Filament\Resources\Pecas\PecaResource;
use App\Models\Marcacao;
use App\Models\Pessoa;
use App\Models\Sugestao;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Editor de marcações: clicar na imagem cria o próximo número nessa posição
 * (x/y em percentagem). Para cada número: ligar a uma pessoa, registar sugestões
 * escritas na folha do café, aceitar ou rejeitar, esconder (RGPD) ou remover.
 */
class MarcacoesPeca extends Page
{
    use InteractsWithRecord;

    protected static string $resource = PecaResource::class;

    protected string $view = 'filament.resources.pecas.pages.marcacoes-peca';

    public ?int $selecionada = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless($this->record->tipo->permiteMarcacoes() && $this->record->temImagem(), 404);
    }

    public function getTitle(): string
    {
        return 'Pessoas na '.$this->record->ref;
    }

    public function getBreadcrumbs(): array
    {
        return [
            PecaResource::getUrl() => 'Peças',
            PecaResource::getUrl('edit', ['record' => $this->record]) => $this->record->ref,
            'Marcar pessoas',
        ];
    }

    /** @return Collection<int, Marcacao> */
    #[Computed]
    public function marcacoes(): Collection
    {
        return $this->record->marcacoes()
            ->with(['pessoa', 'sugestoes' => fn ($q) => $q->orderBy('estado')->orderBy('created_at'), 'peca'])
            ->get();
    }

    // ------------------------------------------------------------ chamadas do Alpine

    public function adicionar(float $x, float $y): void
    {
        $marcacao = $this->record->marcacoes()->create([
            'numero' => Marcacao::proximoNumero($this->record),
            'x' => $this->limitar($x),
            'y' => $this->limitar($y),
        ]);

        $this->selecionada = $marcacao->id;
        unset($this->marcacoes);
    }

    public function mover(int $id, float $x, float $y): void
    {
        $this->marcacao($id)->update(['x' => $this->limitar($x), 'y' => $this->limitar($y)]);
        $this->selecionada = $id;
        unset($this->marcacoes);
    }

    public function selecionar(int $id): void
    {
        $this->selecionada = $this->selecionada === $id ? null : $id;
    }

    public function alterarRaio(int $id, float $raio): void
    {
        $this->marcacao($id)->update(['raio' => max(2, min(30, $raio))]);
        unset($this->marcacoes);
    }

    private function limitar(float $valor): float
    {
        return round(max(0, min(100, $valor)), 3);
    }

    private function marcacao(int $id): Marcacao
    {
        return $this->record->marcacoes()->findOrFail($id);
    }

    // ------------------------------------------------------------ ações (modais)

    public function ligarPessoaAction(): Action
    {
        return Action::make('ligarPessoa')
            ->label('Escolher pessoa')
            ->icon(Heroicon::OutlinedUser)
            ->size('sm')
            ->modalHeading(fn (array $arguments) => 'Quem é o número '.$this->marcacao($arguments['marcacao'])->numero.'?')
            ->fillForm(fn (array $arguments) => ['pessoa_id' => $this->marcacao($arguments['marcacao'])->pessoa_id])
            ->schema([
                Select::make('pessoa_id')
                    ->label('Pessoa')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search) => Pessoa::where('nome', 'like', "%{$search}%")
                        ->orWhere('alcunha', 'like', "%{$search}%")->orderBy('nome')->limit(30)
                        ->get()->mapWithKeys(fn (Pessoa $p) => [$p->id => $p->nome.($p->alcunha ? " ({$p->alcunha})" : '')]))
                    ->getOptionLabelUsing(fn ($value) => Pessoa::find($value)?->nome)
                    ->createOptionForm([
                        TextInput::make('nome')->label('Nome')->required()->maxLength(255),
                        TextInput::make('alcunha')->label('Alcunha')->maxLength(255),
                    ])
                    ->createOptionUsing(fn (array $data) => Pessoa::create($data)->id)
                    ->helperText('Procure pelo nome. Se ainda não existe, use o + para criar.'),
            ])
            ->action(function (array $data, array $arguments) {
                $this->marcacao($arguments['marcacao'])->update(['pessoa_id' => $data['pessoa_id']]);
                unset($this->marcacoes);
            });
    }

    public function desligarPessoaAction(): Action
    {
        return Action::make('desligarPessoa')
            ->label('Tirar pessoa')
            ->icon(Heroicon::OutlinedXMark)
            ->color('gray')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription('O número fica por identificar. A pessoa não é apagada.')
            ->action(function (array $arguments) {
                $this->marcacao($arguments['marcacao'])->update(['pessoa_id' => null]);
                unset($this->marcacoes);
            });
    }

    public function sugestaoAction(): Action
    {
        return Action::make('sugestao')
            ->label('Registar sugestão')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->size('sm')
            ->modalHeading(fn (array $arguments) => 'Sugestão para o número '.$this->marcacao($arguments['marcacao'])->numero)
            ->modalDescription('O nome que alguém escreveu na folha do café (ou disse numa conversa, ou mandou por email).')
            ->schema([
                TextInput::make('nome_sugerido')->label('Nome sugerido')->required()->maxLength(255),
                Select::make('fonte')->label('De onde veio')->options(Origem::class)->default(Origem::Folha)->required(),
                DatePicker::make('data')->label('Data')->default(now())->native(false)->displayFormat('d/m/Y'),
                Textarea::make('notas')->label('Notas')->rows(2),
            ])
            ->action(function (array $data, array $arguments) {
                $this->marcacao($arguments['marcacao'])->sugestoes()->create($data);
                unset($this->marcacoes);
            });
    }

    public function aceitarSugestaoAction(): Action
    {
        return Action::make('aceitarSugestao')
            ->label('Aceitar')
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->size('xs')
            ->modalHeading(fn (array $arguments) => 'Aceitar "'.Sugestao::find($arguments['sugestao'])?->nome_sugerido.'"')
            ->modalDescription('O número fica ligado a esta pessoa e as outras sugestões pendentes passam a rejeitadas.')
            ->fillForm(fn (array $arguments) => [
                'pessoa_id' => Pessoa::where('nome', Sugestao::find($arguments['sugestao'])?->nome_sugerido)->value('id'),
            ])
            ->schema([
                Select::make('pessoa_id')
                    ->label('Pessoa já existente')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Pessoa::where('nome', 'like', "%{$search}%")->orderBy('nome')->limit(30)->pluck('nome', 'id'))
                    ->getOptionLabelUsing(fn ($value) => Pessoa::find($value)?->nome)
                    ->helperText('Deixe vazio para criar uma pessoa nova com o nome sugerido.'),
            ])
            ->action(function (array $data, array $arguments) {
                $sugestao = $this->sugestaoDestaPeca($arguments['sugestao']);
                $pessoa = $sugestao->aceitar(filled($data['pessoa_id']) ? Pessoa::find($data['pessoa_id']) : null);
                Notification::make()->success()->title("Número {$sugestao->marcacao->numero}: {$pessoa->nome}")->send();
                unset($this->marcacoes);
            });
    }

    public function rejeitarSugestaoAction(): Action
    {
        return Action::make('rejeitarSugestao')
            ->label('Rejeitar')
            ->icon(Heroicon::OutlinedXMark)
            ->color('gray')
            ->size('xs')
            ->action(function (array $arguments) {
                $this->sugestaoDestaPeca($arguments['sugestao'])->rejeitar();
                unset($this->marcacoes);
            });
    }

    public function alternarOcultaAction(): Action
    {
        return Action::make('alternarOculta')
            ->label(fn (array $arguments) => $this->marcacao($arguments['marcacao'])->oculta ? 'Mostrar no site' : 'Esconder no site')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('warning')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription('Uma marcação escondida não aparece no site, mas não se apaga (por exemplo, a pedido da pessoa).')
            ->action(function (array $arguments) {
                $m = $this->marcacao($arguments['marcacao']);
                $m->update(['oculta' => ! $m->oculta]);
                unset($this->marcacoes);
            });
    }

    public function removerAction(): Action
    {
        return Action::make('remover')
            ->label('Remover número')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalDescription('Apaga o número e as suas sugestões. Os outros números não mudam (a folha do café pode já estar impressa).')
            ->action(function (array $arguments) {
                $this->marcacao($arguments['marcacao'])->delete();
                $this->selecionada = null;
                unset($this->marcacoes);
            });
    }

    private function sugestaoDestaPeca(int $id): Sugestao
    {
        return Sugestao::whereKey($id)
            ->whereHas('marcacao', fn ($q) => $q->where('peca_id', $this->record->id))
            ->with('marcacao')
            ->firstOrFail();
    }

    public function estadoPendente(): EstadoSugestao
    {
        return EstadoSugestao::Pendente;
    }
}
