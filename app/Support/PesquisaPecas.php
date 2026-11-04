<?php

namespace App\Support;

use App\Enums\Seccao;
use App\Enums\TipoPeca;
use App\Models\Peca;
use App\Models\Tema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Filtros do arquivo: texto livre, tipo, década e tema, opcionalmente
 * dentro de uma secção. A pesquisa procura também nos nomes das pessoas
 * identificadas nas fotografias (só as visíveis no site).
 */
class PesquisaPecas
{
    public ?string $texto = null;

    public ?TipoPeca $tipo = null;

    /** Década (ex.: 1950), 'sem' para peças sem data, ou null. */
    public int|string|null $decada = null;

    public ?Tema $tema = null;

    public ?Seccao $seccao = null;

    public static function doPedido(Request $request, ?Seccao $seccao = null): self
    {
        $pesquisa = new self;
        $pesquisa->seccao = $seccao;
        $pesquisa->texto = Limpeza::texto($request->string('q')->limit(120, '')->toString());
        $pesquisa->tipo = TipoPeca::tryFrom((string) $request->query('tipo'));

        if ($seccao && $pesquisa->tipo && $pesquisa->tipo->seccao() !== $seccao) {
            $pesquisa->tipo = null;
        }

        $decada = (string) $request->query('decada');
        $pesquisa->decada = $decada === 'sem' ? 'sem' : (preg_match('/^(18|19|20)\d0$/', $decada) ? (int) $decada : null);

        if ($slug = $request->query('tema')) {
            $pesquisa->tema = Tema::where('slug', (string) $slug)->first();
        }

        return $pesquisa;
    }

    public function temFiltros(): bool
    {
        return $this->texto || $this->tipo || $this->decada || $this->tema;
    }

    /** Peças publicadas que cumprem os filtros. */
    public function consulta(bool $comDecada = true, bool $comTema = true): Builder
    {
        $query = Peca::query()->publicadas();

        if ($this->seccao) {
            $query->dosTipos($this->seccao->tipos());
        }

        if ($this->tipo) {
            $query->where('tipo', $this->tipo);
        }

        if ($comDecada && $this->decada === 'sem') {
            $query->whereNull('decada');
        } elseif ($comDecada && $this->decada) {
            $query->where('decada', $this->decada);
        }

        if ($comTema && $this->tema) {
            $query->whereHas('temas', fn ($q) => $q->whereKey($this->tema->id));
        }

        if ($this->texto) {
            foreach (array_slice(preg_split('/\s+/u', $this->texto), 0, 6) as $palavra) {
                $termo = '%'.addcslashes($palavra, '%_\\').'%';
                $query->where(function (Builder $q) use ($termo) {
                    foreach (['ref', 'titulo', 'descricao', 'autor', 'cedido_por', 'local', 'jornal', 'data_texto'] as $campo) {
                        $q->orWhere($campo, 'like', $termo);
                    }
                    $q->orWhereHas('temas', fn ($t) => $t->where('nome', 'like', $termo));
                    $q->orWhereHas('marcacoes', fn ($m) => $m->publicas()
                        ->whereHas('pessoa', fn ($p) => $p->where('nome', 'like', $termo)->orWhere('alcunha', 'like', $termo)));
                });
            }
        }

        return $query;
    }

    public function resultados(int $porPagina = 24)
    {
        return $this->consulta()
            ->with('temas')
            ->orderByRaw('decada is null')
            ->orderBy('decada')
            ->orderBy('ref')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /** @return array<int|string, int> década => número de peças (com os outros filtros aplicados) */
    public function decadas(): array
    {
        return $this->consulta(comDecada: false)
            ->reorder()
            ->select('decada', DB::raw('count(*) as total'))
            ->groupBy('decada')
            ->orderBy('decada')
            ->pluck('total', 'decada')
            ->mapWithKeys(fn ($total, $decada) => [$decada === '' || $decada === null ? 'sem' : (int) $decada => $total])
            ->all();
    }

    /** Temas com peças, com os outros filtros aplicados. */
    public function temas()
    {
        $ids = $this->consulta(comTema: false)->reorder()->select('pecas.id');

        $soEstas = fn ($q) => $q->whereIn('pecas.id', $ids);

        return Tema::query()
            ->whereHas('pecas', $soEstas)
            ->withCount(['pecas' => $soEstas])
            ->orderBy('nome')
            ->get();
    }

    /** Parâmetros atuais, para construir ligações que mudam só um filtro. */
    public function parametros(array $alterar = []): array
    {
        $atuais = array_filter([
            'q' => $this->texto,
            'tipo' => $this->tipo?->value,
            'decada' => $this->decada,
            'tema' => $this->tema?->slug,
        ]);

        return array_filter(array_merge($atuais, $alterar), fn ($v) => $v !== null && $v !== '');
    }
}
