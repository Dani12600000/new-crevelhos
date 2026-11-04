<?php

namespace App\Importacao;

use App\Enums\TipoPeca;
use App\Models\Acervo;
use App\Models\Peca;
use App\Models\Tema;
use App\Models\Texto;
use App\Services\ImagensPeca;
use App\Support\Limpeza;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importa a base de dados antiga (conexão `legado`) para a estrutura nova.
 *
 * É idempotente: cada peça é encontrada pelo par legado_tabela + legado_id e
 * atualizada. Os campos que só existem no site novo (título, acervo, publicado,
 * destaque) não são mexidos depois da primeira importação.
 */
class Importador
{
    /** Registos de teste do site antigo: não se importam. */
    public const IGNORAR = [
        'imprensa' => [1 => 'registo de teste (descrição "ddfdsfsdfsdf")'],
        'videoteca' => [1 => 'registo de teste (fonte, autor e descrição "aa")'],
    ];

    /** Data por omissão que o site antigo gravava quando não se sabia a data. */
    public const DATA_FALSA_IMPRENSA = '1999-04-13';

    /** Variantes de temas que não se resolvem só tirando acentos: chave → nome final. */
    public const TEMAS_EQUIVALENTES = [
        'rancho etnografico e folclorico do casais' => 'Rancho Folclórico e Etnográfico de Casais de Revelhos',
        'rancho folclorico e etnografico de casais de revelhos' => 'Rancho Folclórico e Etnográfico de Casais de Revelhos',
        'sociedade recreativa pro casais de revelhos' => 'Sociedade Recreativa Pró Casais de Revelhos',
    ];

    public const GRALHAS = ['Vellhice' => 'Velhice'];

    public const ACERVOS = [
        'Sociedade Recreativa Pró Casais de Revelhos',
        'Arquivo Municipal de Abrantes',
        'Biblioteca Municipal António Botto',
        'Comunidade dos Casais de Revelhos',
    ];

    /** Página antiga → chave do texto novo. */
    public const PAGINAS = [
        'inicio' => 'inicio',
        'ficha_tecnica' => 'ficha_tecnica',
        'fototeca' => 'seccao_fototeca',
        'imprensa' => 'seccao_imprensa',
        'cartazes' => 'seccao_cartazes',
        'mapas_e_plantas' => 'seccao_mapas_e_plantas',
        'videoteca' => 'seccao_videoteca',
        'biblioteca' => 'seccao_biblioteca',
    ];

    private ConnectionInterface $legado;

    /** @var array<int, int> id do tema antigo → id do tema novo */
    private array $mapaTemas = [];

    private ?Closure $progresso = null;

    public function __construct(
        private readonly ImagensPeca $imagens,
        private readonly Relatorio $relatorio,
    ) {
        $this->legado = DB::connection('legado');
    }

    public function aoProgredir(Closure $callback): self
    {
        $this->progresso = $callback;

        return $this;
    }

    public function executar(string $pastaFicheiros, bool $comFicheiros = true, bool $forcarTextos = false): Relatorio
    {
        $this->relatorio->pasta = $pastaFicheiros;
        $this->relatorio->comFicheiros = $comFicheiros;

        DB::transaction(function () use ($pastaFicheiros, $comFicheiros, $forcarTextos) {
            $this->importarAcervos();
            $this->importarTemas();
            $this->importarTextos($forcarTextos);

            foreach ($this->tabelas() as $tabela => $mapear) {
                $this->importarTabela($tabela, $mapear, $pastaFicheiros, $comFicheiros);
            }
        });

        $this->analisarTemasSemUso();
        $this->analisarDuplicados($pastaFicheiros);
        $this->relatorio->ignorarTabela('menu', 'menu de um template, sem conteúdo do arquivo');
        $this->relatorio->ignorarTabela('tipo_foto', 'convertida no campo `tipo` (1 → foto, 2 → postal)');
        $this->relatorio->ignorarTabela('paginas.pesquisa', 'página sem texto');

        return $this->relatorio;
    }

    private function informar(string $mensagem): void
    {
        if ($this->progresso) {
            ($this->progresso)($mensagem);
        }
    }

    private function importarAcervos(): void
    {
        foreach (self::ACERVOS as $nome) {
            Acervo::firstOrCreate(['nome' => $nome]);
        }
    }

    // ---------------------------------------------------------------- temas

    private function importarTemas(): void
    {
        $antigos = $this->legado->table('temas')->orderBy('id_tema')->get();

        // Agrupa pelo nome normalizado.
        $grupos = [];
        foreach ($antigos as $antigo) {
            $nome = Limpeza::texto($antigo->nome) ?? 'Sem nome';
            $nome = strtr($nome, self::GRALHAS);
            $chave = Limpeza::chaveTema($nome);
            $final = self::TEMAS_EQUIVALENTES[$chave] ?? null;
            $chave = $final ? Limpeza::chaveTema($final) : $chave;

            $grupos[$chave]['final'] ??= $final;
            $grupos[$chave]['membros'][] = [
                'id' => (int) $antigo->id_tema,
                'original' => (string) $antigo->nome,
                'limpo' => $nome,
                'destino' => (int) $antigo->destino,
            ];
        }

        foreach ($grupos as $grupo) {
            $nomeFinal = $grupo['final'] ?? $this->melhorVariante(array_column($grupo['membros'], 'limpo'));
            $ids = array_column($grupo['membros'], 'id');

            // Se algum tema antigo já foi importado, reaproveita o tema novo (e o nome que lá estiver).
            $temaId = DB::table('temas_legado')->whereIn('legado_id', $ids)->value('tema_id');
            $tema = $temaId ? Tema::find($temaId) : null;
            $tema ??= Tema::firstOrCreate(['slug' => Str::slug($nomeFinal)], ['nome' => $nomeFinal]);

            foreach ($grupo['membros'] as $membro) {
                DB::table('temas_legado')->updateOrInsert(
                    ['legado_id' => $membro['id']],
                    ['tema_id' => $tema->id, 'nome_original' => $membro['original'], 'destino' => $membro['destino']],
                );
                $this->mapaTemas[$membro['id']] = $tema->id;

                if ($membro['limpo'] !== trim(html_entity_decode($membro['original'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
                    $this->relatorio->temaCorrigido($membro['id'], $membro['original'], $membro['limpo']);
                }
            }

            if (count($grupo['membros']) > 1) {
                $this->relatorio->temaUnido($tema, $grupo['membros']);
            }
        }

        $this->relatorio->totalTemasAntigos = $antigos->count();
        $this->relatorio->totalTemasNovos = count($grupos);
    }

    /** Entre variantes do mesmo nome, prefere a que tem acentos ("Pró" em vez de "Pro"). */
    private function melhorVariante(array $nomes): string
    {
        usort($nomes, fn ($a, $b) => (strlen($b) - mb_strlen($b)) <=> (strlen($a) - mb_strlen($a)));

        return $nomes[0];
    }

    /** '|48|84|51|' → ids novos (sem repetidos). */
    private function temasDe(?string $campo, string $tabela, int $id): array
    {
        $novos = [];
        foreach (array_filter(explode('|', (string) $campo), 'strlen') as $antigo) {
            $antigo = (int) $antigo;
            $this->relatorio->temaUsado($antigo);
            if (isset($this->mapaTemas[$antigo])) {
                $novos[] = $this->mapaTemas[$antigo];
            } else {
                $this->relatorio->temaInexistente($tabela, $id, $antigo);
            }
        }

        return array_values(array_unique($novos));
    }

    private function analisarTemasSemUso(): void
    {
        $antigos = DB::table('temas_legado')->orderBy('legado_id')->get();
        foreach ($antigos as $antigo) {
            if (! $this->relatorio->temaFoiUsado((int) $antigo->legado_id)) {
                $this->relatorio->temaSemUso($antigo, Tema::find($antigo->tema_id));
            }
        }

        $this->relatorio->temasNovosSemPecas = Tema::doesntHave('pecas')->orderBy('nome')->pluck('nome')->all();
    }

    // ---------------------------------------------------------------- textos

    private function importarTextos(bool $forcar): void
    {
        foreach ($this->legado->table('paginas')->get() as $pagina) {
            $chave = self::PAGINAS[$pagina->pagina] ?? null;
            if (! $chave) {
                continue;
            }

            $valor = Limpeza::html($pagina->texto);
            $existente = Texto::where('chave', $chave)->first();

            if ($existente && filled($existente->valor) && ! $forcar) {
                $this->relatorio->textoMantido($chave);

                continue;
            }

            Texto::updateOrCreate(['chave' => $chave], ['valor' => $valor]);
            $this->relatorio->textoImportado($chave);

            foreach ($this->emails($pagina->texto) as $email) {
                $this->relatorio->emailEncontrado($email, $chave);
            }
        }

        Texto::limparCache();
    }

    private function emails(?string $texto): array
    {
        preg_match_all('/[\w.+-]+@[\w-]+\.[\w.]+/u', strip_tags((string) $texto), $m);

        return array_unique($m[0]);
    }

    // ---------------------------------------------------------------- peças

    /** @return array<string, Closure(object): array> tabela antiga → função que devolve os campos novos */
    private function tabelas(): array
    {
        return [
            'fototeca' => fn (object $r) => [
                'tipo' => (int) $r->id_tipo_foto === 2 ? TipoPeca::Postal : TipoPeca::Foto,
                'autor' => Limpeza::nome($r->fotografo),
                'cedido_por' => Limpeza::nome($r->fonte),
                'local' => Limpeza::texto($r->localizacao),
                'data' => $r->data,
                'descricao' => $r->descricao,
                'editor' => Limpeza::nome($r->editor),
                'created_at' => $r->data_submissao,
            ],
            'cartazes' => fn (object $r) => [
                'tipo' => TipoPeca::Cartaz,
                'autor' => Limpeza::nome($r->autor),
                'cedido_por' => Limpeza::nome($r->fonte),
                'data' => $r->data,
                'descricao' => $r->descricao,
            ],
            'imprensa' => function (object $r) {
                $fonte = Limpeza::texto($r->fonte);
                $ehUrl = $fonte && filter_var($fonte, FILTER_VALIDATE_URL);

                return [
                    'tipo' => TipoPeca::Imprensa,
                    'jornal' => Limpeza::texto($r->jornal),
                    'data_publicacao' => $r->data_publicacao,
                    'url_original' => $ehUrl ? $fonte : null,
                    'cedido_por' => $ehUrl ? null : Limpeza::nome($fonte),
                    'descricao' => $r->descricao,
                ];
            },
            'mapaplanta' => fn (object $r) => [
                'tipo' => TipoPeca::Mapa,
                'autor' => Limpeza::nome($r->autor),
                'cedido_por' => Limpeza::nome($r->fonte),
                'local' => Limpeza::texto($r->localizacao),
                'data' => $r->data,
                'descricao' => $r->descricao,
            ],
            'videoteca' => fn (object $r) => [
                'tipo' => TipoPeca::Video,
                'autor' => Limpeza::nome($r->autor),
                'cedido_por' => Limpeza::nome($r->fonte),
                'data' => $r->data,
                'descricao' => $r->descricao,
            ],
            'biblioteca' => fn (object $r) => [
                'tipo' => TipoPeca::Livro,
                'titulo' => Limpeza::texto($r->titulo),
                'autor' => Limpeza::nome($r->autor),
                'cedido_por' => Limpeza::nome($r->fonte),
                'edicao' => Limpeza::texto($r->edicao),
                'editora' => Limpeza::texto($r->editora),
                'data_edicao' => $r->data_edicao,
                'paginas' => Limpeza::texto($r->pag_original),
                'data' => $r->data_edicao,
                'notas' => Limpeza::texto($r->nome),
            ],
        ];
    }

    private function importarTabela(string $tabela, Closure $mapear, string $pasta, bool $comFicheiros): void
    {
        $registos = $this->legado->table($tabela)->orderBy('id')->get();
        $this->relatorio->registosNaTabela($tabela, $registos->count());

        foreach ($registos as $registo) {
            $id = (int) $registo->id;

            if (isset(self::IGNORAR[$tabela][$id])) {
                $this->relatorio->ignorado($tabela, $id, self::IGNORAR[$tabela][$id]);
                // Os temas destes registos não contam como "usados".
                $this->relatorio->temasDeIgnorado(array_map('intval', array_filter(explode('|', (string) ($registo->temas ?? '')), 'strlen')));
                continue;
            }

            $campos = $mapear($registo);
            $peca = $this->gravarPeca($tabela, $id, $registo, $campos);

            if ($comFicheiros) {
                $this->copiarFicheiro($peca, $tabela, $id, $registo->nome_ficheiro ?? null, $pasta);
            }

            $peca->temas()->sync($this->temasDe($registo->temas ?? null, $tabela, $id));
            $this->analisarPeca($peca, $tabela, $id, $campos);
            $this->informar("{$tabela} {$id} → {$peca->ref}");
        }
    }

    private function gravarPeca(string $tabela, int $id, object $registo, array $campos): Peca
    {
        $peca = Peca::firstOrNew(['legado_tabela' => $tabela, 'legado_id' => $id]);
        $nova = ! $peca->exists;

        $dataOriginal = $campos['data'] ?? null;
        $dataTexto = Limpeza::dataTexto($dataOriginal);

        if ($tabela === 'imprensa') {
            $data = $campos['data_publicacao'];
            if ($data === self::DATA_FALSA_IMPRENSA) {
                $this->relatorio->dataFalsaImprensa($tabela, $id, $data);
                $data = null;
            }
            $campos['data_publicacao'] = $data;
            $dataTexto = $data;
        }

        $peca->fill([
            'tipo' => $campos['tipo'],
            'descricao' => Limpeza::html($campos['descricao'] ?? null),
            'data_texto' => $dataTexto,
            'decada' => Limpeza::decada($dataTexto),
            'autor' => $campos['autor'] ?? null,
            'cedido_por' => $campos['cedido_por'] ?? null,
            'local' => $campos['local'] ?? null,
            'jornal' => $campos['jornal'] ?? null,
            'data_publicacao' => $campos['data_publicacao'] ?? null,
            'url_original' => $campos['url_original'] ?? null,
            'edicao' => $campos['edicao'] ?? null,
            'editora' => $campos['editora'] ?? null,
            'data_edicao' => $campos['data_edicao'] ?? null,
            'paginas' => $campos['paginas'] ?? null,
        ]);

        if ($nova) {
            $peca->titulo = $campos['titulo'] ?? null;
            $peca->publicado = true; // já estava público no site antigo
            $peca->acervo_id = $this->acervoProvavel($campos['cedido_por'] ?? null);
            $notas = array_filter([
                isset($campos['editor']) ? 'Editor no site antigo: '.$campos['editor'] : null,
                isset($campos['notas']) ? 'Campo "nome" no site antigo: '.$campos['notas'] : null,
                "Importado de {$tabela} #{$id}.",
            ]);
            $peca->notas_internas = implode("\n", $notas);
        }

        if (! empty($campos['created_at'])) {
            $peca->created_at = $campos['created_at'];
        }

        if ($dataOriginal !== null && $dataTexto !== Limpeza::texto($dataOriginal)) {
            $this->relatorio->dataAjustada($tabela, $id, $dataOriginal, $dataTexto);
        }

        $peca->save();

        return $peca;
    }

    /** Só se atribui acervo quando a fonte é claramente a Sociedade Recreativa. */
    private function acervoProvavel(?string $cedidoPor): ?int
    {
        if ($cedidoPor && preg_match('/^(SRP\s*Casais|SR\s*Casais|Sociedade Recreativa)/iu', $cedidoPor)) {
            return Acervo::where('nome', self::ACERVOS[0])->value('id');
        }

        return null;
    }

    private function copiarFicheiro(Peca $peca, string $tabela, int $id, ?string $nome, string $pasta): void
    {
        if (blank($nome)) {
            $this->relatorio->ficheiroEmFalta($tabela, $id, '(sem nome de ficheiro)');

            return;
        }

        $origem = rtrim($pasta, '\\/').DIRECTORY_SEPARATOR.$nome;
        if (! is_file($origem)) {
            $this->relatorio->ficheiroEmFalta($tabela, $id, $nome);

            return;
        }

        // Mesmo ficheiro já importado e versões geradas: não repete o trabalho.
        $hash = sha1_file($origem);
        $grande = $peca->ficheiro ? dirname($peca->ficheiro).'/grande.webp' : null;
        if ($peca->ficheiro_hash === $hash && $grande && Storage::disk('public')->exists($grande)) {
            return;
        }

        try {
            $this->imagens->guardar($peca, $origem);
            $peca->saveQuietly();
        } catch (\Throwable $e) {
            $this->relatorio->ficheiroComErro($tabela, $id, $nome, $e->getMessage());
        }
    }

    private function analisarPeca(Peca $peca, string $tabela, int $id, array $campos): void
    {
        $this->relatorio->importado($peca);

        if ($peca->decada === null) {
            $this->relatorio->dataSemDecada($peca, $tabela, $id, $campos['data'] ?? $campos['data_publicacao'] ?? null);
        }

        if ($peca->descricao === null) {
            $this->relatorio->descricaoVazia($peca, $tabela, $id);
        }

        $nomes = DetetorNomes::candidatos(Limpeza::semHtml($peca->descricao));
        if ($nomes) {
            $this->relatorio->nomesNaDescricao($peca, $tabela, $id, $nomes);
        }
    }

    // ---------------------------------------------------------------- duplicados

    /** Compara por SHA-1 todos os ficheiros referidos no site antigo (incluindo os ignorados). */
    private function analisarDuplicados(string $pasta): void
    {
        $porHash = [];
        foreach (array_keys($this->tabelas()) as $tabela) {
            foreach ($this->legado->table($tabela)->orderBy('id')->get(['id', 'nome_ficheiro']) as $r) {
                $caminho = rtrim($pasta, '\\/').DIRECTORY_SEPARATOR.$r->nome_ficheiro;
                if ($r->nome_ficheiro && is_file($caminho)) {
                    $porHash[sha1_file($caminho)][] = [
                        'tabela' => $tabela,
                        'id' => (int) $r->id,
                        'ficheiro' => $r->nome_ficheiro,
                        'ignorado' => isset(self::IGNORAR[$tabela][(int) $r->id]),
                        'ref' => Peca::where('legado_tabela', $tabela)->where('legado_id', $r->id)->value('ref'),
                    ];
                }
            }
        }

        foreach ($porHash as $hash => $ficheiros) {
            if (count($ficheiros) > 1) {
                $this->relatorio->duplicado($hash, $ficheiros);
            }
        }

        // Ficheiros na pasta antiga que nenhum registo usa.
        $usados = array_merge(...array_values(array_map(fn ($f) => array_column($f, 'ficheiro'), $porHash ?: [[]])));
        foreach (glob(rtrim($pasta, '\\/').DIRECTORY_SEPARATOR.'*.{jpg,JPG,jpeg,png,PNG,gif}', GLOB_BRACE) ?: [] as $ficheiro) {
            if (! in_array(basename($ficheiro), $usados, true)) {
                $this->relatorio->ficheiroSemRegisto(basename($ficheiro));
            }
        }
    }
}
