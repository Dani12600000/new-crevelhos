<?php

namespace App\Importacao;

use App\Enums\TipoPeca;
use App\Models\Peca;
use App\Models\Tema;
use App\Support\Limpeza;
use Illuminate\Support\Str;

/**
 * Junta tudo o que o importador encontrou e escreve o relatório em Markdown
 * (storage/app/importacao/relatorio.md) para revisão manual.
 */
class Relatorio
{
    public string $pasta = '';

    public bool $comFicheiros = true;

    public int $totalTemasAntigos = 0;

    public int $totalTemasNovos = 0;

    /** @var list<string> */
    public array $temasNovosSemPecas = [];

    private array $porTipo = [];

    private array $tabelas = [];

    private array $ignorados = [];

    private array $tabelasIgnoradas = [];

    private array $temasUnidos = [];

    private array $temasCorrigidos = [];

    private array $temasUsados = [];

    private array $temasDeIgnorados = [];

    private array $temasSemUso = [];

    private array $temasInexistentes = [];

    private array $textos = [];

    private array $emails = [];

    private array $datasSemDecada = [];

    private array $datasFalsas = [];

    private array $datasAjustadas = [];

    private array $descricoesVazias = [];

    private array $nomes = [];

    private array $duplicados = [];

    private array $ficheirosEmFalta = [];

    private array $ficheirosComErro = [];

    private array $ficheirosSemRegisto = [];

    // ------------------------------------------------------------ registo

    public function registosNaTabela(string $tabela, int $total): void
    {
        $this->tabelas[$tabela] = $total;
    }

    public function importado(Peca $peca): void
    {
        $this->porTipo[$peca->tipo->value] = ($this->porTipo[$peca->tipo->value] ?? 0) + 1;
    }

    public function ignorado(string $tabela, int $id, string $motivo): void
    {
        $this->ignorados[] = compact('tabela', 'id', 'motivo');
    }

    public function ignorarTabela(string $tabela, string $motivo): void
    {
        $this->tabelasIgnoradas[$tabela] = $motivo;
    }

    public function temaUnido(Tema $tema, array $membros): void
    {
        $this->temasUnidos[] = ['tema' => $tema->nome, 'membros' => $membros];
    }

    public function temaCorrigido(int $id, string $original, string $limpo): void
    {
        $this->temasCorrigidos[] = compact('id', 'original', 'limpo');
    }

    public function temaUsado(int $id): void
    {
        $this->temasUsados[$id] = true;
    }

    public function temaFoiUsado(int $id): bool
    {
        return isset($this->temasUsados[$id]);
    }

    public function temasDeIgnorado(array $ids): void
    {
        foreach ($ids as $id) {
            $this->temasDeIgnorados[$id] = true;
        }
    }

    public function temaSemUso(object $antigo, ?Tema $tema): void
    {
        $this->temasSemUso[] = [
            'id' => (int) $antigo->legado_id,
            'nome' => $tema?->nome ?? $antigo->nome_original,
            'destino' => (int) $antigo->destino,
            'so_em_ignorados' => isset($this->temasDeIgnorados[(int) $antigo->legado_id]),
            'unido_com_pecas' => $tema && $tema->pecas()->exists(),
        ];
    }

    public function temaInexistente(string $tabela, int $id, int $tema): void
    {
        $this->temasInexistentes[] = compact('tabela', 'id', 'tema');
    }

    public function textoImportado(string $chave): void
    {
        $this->textos[$chave] = 'importado';
    }

    public function textoMantido(string $chave): void
    {
        $this->textos[$chave] = 'mantido (já tinha texto; use --forcar-textos para substituir)';
    }

    public function emailEncontrado(string $email, string $chave): void
    {
        $this->emails[$email][] = $chave;
    }

    public function dataSemDecada(Peca $peca, string $tabela, int $id, ?string $original): void
    {
        $this->datasSemDecada[] = ['ref' => $peca->ref, 'tabela' => $tabela, 'id' => $id, 'data' => $original];
    }

    public function dataFalsaImprensa(string $tabela, int $id, string $data): void
    {
        $this->datasFalsas[] = compact('tabela', 'id', 'data');
    }

    public function dataAjustada(string $tabela, int $id, string $original, ?string $novo): void
    {
        $this->datasAjustadas[] = compact('tabela', 'id', 'original', 'novo');
    }

    public function descricaoVazia(Peca $peca, string $tabela, int $id): void
    {
        $this->descricoesVazias[] = ['ref' => $peca->ref, 'tabela' => $tabela, 'id' => $id];
    }

    public function nomesNaDescricao(Peca $peca, string $tabela, int $id, array $nomes): void
    {
        $this->nomes[] = [
            'ref' => $peca->ref, 'tabela' => $tabela, 'id' => $id, 'nomes' => $nomes,
            'texto' => Limpeza::semHtml($peca->descricao),
        ];
    }

    public function duplicado(string $hash, array $ficheiros): void
    {
        $this->duplicados[] = compact('hash', 'ficheiros');
    }

    public function ficheiroEmFalta(string $tabela, int $id, string $nome): void
    {
        $this->ficheirosEmFalta[] = compact('tabela', 'id', 'nome');
    }

    public function ficheiroComErro(string $tabela, int $id, string $nome, string $erro): void
    {
        $this->ficheirosComErro[] = compact('tabela', 'id', 'nome', 'erro');
    }

    public function ficheiroSemRegisto(string $nome): void
    {
        $this->ficheirosSemRegisto[] = $nome;
    }

    // ------------------------------------------------------------ leitura

    public function totalImportado(): int
    {
        return array_sum($this->porTipo);
    }

    /** Números para o resumo na consola. */
    public function resumo(): array
    {
        return [
            ['Peças importadas', $this->totalImportado()],
            ['Registos ignorados', count($this->ignorados)],
            ['Temas antigos → novos', "{$this->totalTemasAntigos} → {$this->totalTemasNovos}"],
            ['Temas antigos sem uso', count($this->temasSemUso)],
            ['Datas sem década', count($this->datasSemDecada)],
            ['Datas falsas (imprensa)', count($this->datasFalsas)],
            ['Grupos de ficheiros duplicados', count($this->duplicados)],
            ['Descrições vazias', count($this->descricoesVazias)],
            ['Peças com nomes na descrição', count($this->nomes)],
            ['Ficheiros em falta ou com erro', count($this->ficheirosEmFalta) + count($this->ficheirosComErro)],
        ];
    }

    public function guardar(string $caminho): void
    {
        if (! is_dir(dirname($caminho))) {
            mkdir(dirname($caminho), 0775, true);
        }

        file_put_contents($caminho, $this->markdown());
    }

    // ------------------------------------------------------------ markdown

    public function markdown(): string
    {
        $md = [];
        $md[] = '# Relatório da importação';
        $md[] = '';
        $md[] = 'Gerado por `php artisan crevelhos:importar` em '.now()->format('d/m/Y \à\s H:i').'.';
        $md[] = 'Ficheiros lidos de `'.$this->pasta.'`'.($this->comFicheiros ? '.' : ' (**sem copiar ficheiros**: `--sem-ficheiros`).');
        $md[] = '';
        $md[] = 'Cada secção lista o que precisa de revisão manual. Pode voltar a correr o comando as vezes que quiser: atualiza as peças pelo par `legado_tabela` + `legado_id` e não mexe no título, acervo, publicado e destaque depois da primeira vez.';

        $md[] = '';
        $md[] = '## 1. Totais importados por tipo';
        $md[] = '';
        $md[] = '| Tipo | Peças |';
        $md[] = '| --- | ---: |';
        foreach (TipoPeca::cases() as $tipo) {
            $md[] = "| {$tipo->getLabel()} | ".($this->porTipo[$tipo->value] ?? 0).' |';
        }
        $md[] = '| **Total** | **'.$this->totalImportado().'** |';
        $md[] = '';
        $md[] = '| Tabela antiga | Registos |';
        $md[] = '| --- | ---: |';
        foreach ($this->tabelas as $tabela => $total) {
            $md[] = "| `{$tabela}` | {$total} |";
        }

        $md[] = '';
        $md[] = '## 2. Registos ignorados';
        $md[] = '';
        foreach ($this->ignorados as $i) {
            $md[] = "- `{$i['tabela']}` #{$i['id']}: {$i['motivo']}.";
        }
        foreach ($this->tabelasIgnoradas as $tabela => $motivo) {
            $md[] = "- Tabela `{$tabela}`: {$motivo}.";
        }

        $md[] = '';
        $md[] = '## 3. Temas unidos (antigo → novo)';
        $md[] = '';
        $md[] = "Havia **{$this->totalTemasAntigos}** temas antigos, divididos por secção. Ficam **{$this->totalTemasNovos}** temas num conjunto único.";
        $md[] = '';
        $md[] = '| Tema novo | Temas antigos (id: nome original, secção) |';
        $md[] = '| --- | --- |';
        foreach ($this->temasUnidos as $u) {
            $antigos = collect($u['membros'])
                ->map(fn ($m) => "{$m['id']}: ".$this->celula($m['original']).' ('.$this->destino($m['destino']).')')
                ->implode('<br>');
            $md[] = '| '.$this->celula($u['tema'])." | {$antigos} |";
        }
        if ($this->temasCorrigidos) {
            $md[] = '';
            $md[] = 'Nomes corrigidos (gralhas e espaços):';
            $md[] = '';
            foreach ($this->temasCorrigidos as $c) {
                $md[] = "- {$c['id']}: \"".$this->celula($c['original']).'" → "'.$this->celula($c['limpo']).'"';
            }
        }

        $md[] = '';
        $md[] = '## 4. Temas sem uso';
        $md[] = '';
        $md[] = 'Temas antigos que nenhuma peça importada usa. Foram importados na mesma: **a Sociedade decide** se ficam.';
        $md[] = '';
        $md[] = '| Id antigo | Tema | Secção antiga | Nota |';
        $md[] = '| ---: | --- | --- | --- |';
        foreach ($this->temasSemUso as $t) {
            $nota = implode('; ', array_filter([
                $t['so_em_ignorados'] ? 'só usado num registo de teste' : null,
                $t['unido_com_pecas'] ? 'unido a um tema que tem peças' : null,
            ]));
            $md[] = "| {$t['id']} | ".$this->celula($t['nome']).' | '.$this->destino($t['destino'])." | {$nota} |";
        }
        $md[] = '';
        $md[] = 'Total: **'.count($this->temasSemUso).'** de '.$this->totalTemasAntigos.' temas antigos.';
        if ($this->temasNovosSemPecas) {
            $md[] = '';
            $md[] = 'Depois de unidos, ficam **'.count($this->temasNovosSemPecas).'** temas novos sem nenhuma peça (é esta a lista a decidir):';
            $md[] = '';
            foreach ($this->temasNovosSemPecas as $nome) {
                $md[] = '- '.$nome;
            }
        }
        if ($this->temasInexistentes) {
            $md[] = '';
            $md[] = 'Peças que referem temas que não existem na tabela antiga:';
            foreach ($this->temasInexistentes as $t) {
                $md[] = "- `{$t['tabela']}` #{$t['id']}: tema {$t['tema']}";
            }
        }

        $md[] = '';
        $md[] = '## 5. Datas sem década';
        $md[] = '';
        $md[] = 'Sem ano de 4 dígitos (1850 a 2030), por isso não entram na linha do tempo.';
        $md[] = '';
        $md[] = '| Ref | Origem | Data original |';
        $md[] = '| --- | --- | --- |';
        foreach ($this->datasSemDecada as $d) {
            $md[] = "| {$d['ref']} | `{$d['tabela']}` #{$d['id']} | ".($d['data'] === null ? '*(vazia)*' : '`'.$this->celula($d['data']).'`').' |';
        }
        if ($this->datasAjustadas) {
            $md[] = '';
            $md[] = 'Datas em que se tirou o local do fim do texto:';
            foreach ($this->datasAjustadas as $d) {
                $md[] = "- `{$d['tabela']}` #{$d['id']}: \"{$d['original']}\" → \"{$d['novo']}\"";
            }
        }

        $md[] = '';
        $md[] = '## 6. Datas falsas da imprensa';
        $md[] = '';
        $md[] = 'O site antigo gravava `'.Importador::DATA_FALSA_IMPRENSA.'` quando não se sabia a data. Ficam sem data (a confirmar no recorte).';
        $md[] = '';
        foreach ($this->datasFalsas as $d) {
            $md[] = "- `{$d['tabela']}` #{$d['id']} → ".$this->ref($d['tabela'], $d['id']);
        }

        $md[] = '';
        $md[] = '## 7. Ficheiros duplicados (SHA-1)';
        $md[] = '';
        if (! $this->duplicados) {
            $md[] = 'Nenhum.';
        }
        foreach ($this->duplicados as $d) {
            $md[] = '- `'.substr($d['hash'], 0, 12).'…`: '.collect($d['ficheiros'])->map(
                fn ($f) => "`{$f['tabela']}` #{$f['id']}".($f['ref'] ? " ({$f['ref']})" : '').($f['ignorado'] ? ' *(ignorado)*' : '')
            )->implode(' = ');
        }
        $md[] = '';
        $md[] = 'Os ficheiros são iguais byte a byte. Decidir se são a mesma peça (e despublicar uma) ou se o registo está errado.';
        if ($this->ficheirosEmFalta || $this->ficheirosComErro || $this->ficheirosSemRegisto) {
            $md[] = '';
            $md[] = '### Ficheiros em falta, com erro ou sem registo';
            $md[] = '';
            foreach ($this->ficheirosEmFalta as $f) {
                $md[] = "- Em falta: `{$f['tabela']}` #{$f['id']}: `{$f['nome']}`";
            }
            foreach ($this->ficheirosComErro as $f) {
                $md[] = "- Erro: `{$f['tabela']}` #{$f['id']}: `{$f['nome']}`: {$f['erro']}";
            }
            foreach ($this->ficheirosSemRegisto as $f) {
                $md[] = "- Na pasta mas sem registo: `{$f}`";
            }
        }

        $md[] = '';
        $md[] = '## 8. Descrições vazias';
        $md[] = '';
        $md[] = count($this->descricoesVazias).' peças sem descrição: '.(collect($this->descricoesVazias)->pluck('ref')->implode(', ') ?: 'nenhuma').'.';

        $md[] = '';
        $md[] = '## 9. Peças com nomes nas descrições';
        $md[] = '';
        $md[] = 'Possíveis nomes de pessoas, detetados automaticamente **só para revisão**. Não se criou nenhuma pessoa: um editor cria as pessoas e as marcações no backoffice. Alguns são de quem cedeu a foto e não de quem aparece nela.';
        $md[] = '';
        foreach ($this->nomes as $n) {
            $md[] = "### {$n['ref']} (`{$n['tabela']}` #{$n['id']})";
            $md[] = '';
            $md[] = '> '.Str::limit($n['texto'], 500);
            $md[] = '';
            $md[] = 'Possíveis nomes: '.implode(', ', array_map(fn ($x) => "**{$x}**", $n['nomes'])).'.';
            $md[] = '';
        }

        $md[] = '## 10. Outras notas';
        $md[] = '';
        $semTitulo = Peca::whereNull('titulo')->count();
        $md[] = "- **Títulos:** o site antigo não tinha títulos. {$semTitulo} peças estão sem título; o site mostra o início da descrição ou o tipo e o local. Convém escrever títulos no backoffice.";
        $md[] = '- **Publicação:** as peças novas ficam publicadas, porque já estavam públicas no site antigo. Pode despublicar no backoffice.';
        $comAcervo = Peca::whereNotNull('acervo_id')->count();
        $md[] = "- **Acervo:** só se atribuiu a Sociedade Recreativa quando a fonte é claramente a Sociedade ({$comAcervo} peças). As restantes ficam por atribuir.";
        $md[] = '- **Notas internas:** o campo `editor` da fototeca ficou nas notas internas de cada peça.';
        foreach ($this->textos as $chave => $estado) {
            $md[] = "- Texto `{$chave}`: {$estado}.";
        }
        if (count($this->emails) > 1) {
            $md[] = '- **Emails de contacto diferentes** nos textos antigos: '.collect($this->emails)->map(fn ($chaves, $email) => "`{$email}` (".implode(', ', array_unique($chaves)).')')->implode(', ').'. Confirmar qual usar.';
        }

        $cedidos = Peca::query()->whereNotNull('cedido_por')->selectRaw('cedido_por, count(*) as total')
            ->groupBy('cedido_por')->orderBy('cedido_por')->get();
        if ($cedidos->isNotEmpty()) {
            $md[] = '';
            $md[] = '### Valores de "cedido por" (para uniformizar)';
            $md[] = '';
            foreach ($cedidos as $c) {
                $md[] = '- '.$this->celula($c->cedido_por)." ({$c->total})";
            }
        }

        $md[] = '';

        return implode("\n", $md);
    }

    private function destino(int $destino): string
    {
        return [1 => 'imprensa', 2 => 'fototeca', 3 => 'cartazes', 4 => 'mapas', 5 => 'videoteca', 6 => 'biblioteca'][$destino] ?? "destino {$destino}";
    }

    private function ref(string $tabela, int $id): string
    {
        return Peca::where('legado_tabela', $tabela)->where('legado_id', $id)->value('ref') ?? '?';
    }

    private function celula(?string $texto): string
    {
        return str_replace('|', '\|', (string) $texto);
    }
}
