<?php

namespace Tests\Feature;

use App\Enums\TipoPeca;
use App\Models\Peca;
use App\Models\Tema;
use App\Models\Texto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O importador corre sobre uma base antiga de EXEMPLO (SQLite em memória)
 * com a mesma estrutura do dump de 2017 e os mesmos problemas: entidades HTML,
 * "desconhecido", temas repetidos, datas falsas, registos de teste e duplicados.
 */
class ImportadorTest extends TestCase
{
    use RefreshDatabase;

    private string $pasta;

    private string $relatorio;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->pasta = sys_get_temp_dir().'/crevelhos-teste-'.uniqid();
        mkdir($this->pasta);
        $this->relatorio = $this->pasta.'/relatorio.md';

        $this->criarBaseAntiga();
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->pasta.'/*'));
        rmdir($this->pasta);

        parent::tearDown();
    }

    private function importar(array $opcoes = []): void
    {
        $this->artisan('crevelhos:importar', ['--pasta' => $this->pasta, '--relatorio' => $this->relatorio] + $opcoes)
            ->assertSuccessful();
    }

    public function test_importa_pecas_e_ignora_registos_de_teste(): void
    {
        $this->importar();

        $this->assertSame(3, Peca::where('tipo', TipoPeca::Foto)->count());
        $this->assertSame(1, Peca::where('tipo', TipoPeca::Postal)->count());
        $this->assertSame(1, Peca::where('tipo', TipoPeca::Cartaz)->count());
        $this->assertSame(1, Peca::where('tipo', TipoPeca::Imprensa)->count()); // o id 1 é teste
        $this->assertSame(0, Peca::where('tipo', TipoPeca::Video)->count());   // só tinha o teste

        $this->assertSame(['CRV-F-0001', 'CRV-F-0002', 'CRV-F-0003', 'CRV-F-0004'],
            Peca::where('legado_tabela', 'fototeca')->orderBy('legado_id')->pluck('ref')->all());
    }

    public function test_limpa_campos_e_normaliza_desconhecido(): void
    {
        $this->importar();

        $peca = Peca::where('legado_tabela', 'fototeca')->where('legado_id', 1)->first();
        $this->assertNull($peca->autor);                       // "Descolnhecido"
        $this->assertSame('Cedente de Exemplo', $peca->cedido_por);
        $this->assertSame('<p>Descrição com acentuação e história.</p>', $peca->descricao);
        $this->assertSame('1955', $peca->data_texto);          // sem "- Casais de Revelhos"
        $this->assertSame(1950, $peca->decada);
        $this->assertStringContainsString('Editor no site antigo: Editor de Exemplo', $peca->notas_internas);
        $this->assertSame('2017-10-08 16:54:05', $peca->created_at->format('Y-m-d H:i:s'));
        $this->assertTrue($peca->publicado);
    }

    public function test_une_temas_e_guarda_o_mapa_antigo(): void
    {
        $this->importar();

        // Aldeia (x2), Sociedade Pró/Pro (x2) e Rancho (x2) passam a um só cada.
        $this->assertSame(1, Tema::where('nome', 'Aldeia')->count());
        $this->assertSame(1, Tema::where('nome', 'Sociedade Recreativa Pró Casais de Revelhos')->count());
        $this->assertSame(1, Tema::where('nome', 'Rancho Folclórico e Etnográfico de Casais de Revelhos')->count());
        $this->assertSame(1, Tema::where('nome', 'Crianças, Jovens e Velhice')->count()); // gralha corrigida
        $this->assertSame(6, Tema::count());

        $aldeia = Tema::where('nome', 'Aldeia')->value('id');
        $this->assertSame($aldeia, DB::table('temas_legado')->where('legado_id', 48)->value('tema_id'));
        $this->assertSame($aldeia, DB::table('temas_legado')->where('legado_id', 61)->value('tema_id'));

        $peca = Peca::where('legado_tabela', 'fototeca')->where('legado_id', 1)->first();
        $this->assertEqualsCanonicalizing(['Aldeia', 'Gentes e pessoas'], $peca->temas->pluck('nome')->all());
    }

    public function test_data_falsa_da_imprensa_fica_desconhecida(): void
    {
        $this->importar();

        $recorte = Peca::where('legado_tabela', 'imprensa')->first();
        $this->assertNull($recorte->data_publicacao);
        $this->assertNull($recorte->decada);
        $this->assertSame('https://exemplo.pt/noticia', $recorte->url_original);
    }

    public function test_copia_imagens_e_gera_webp_e_miniatura(): void
    {
        $this->importar();

        $peca = Peca::where('legado_tabela', 'fototeca')->where('legado_id', 1)->first();
        Storage::disk('public')->assertExists('pecas/CRV-F-0001/original.jpg');
        Storage::disk('public')->assertExists('pecas/CRV-F-0001/grande.webp');
        Storage::disk('public')->assertExists('pecas/CRV-F-0001/miniatura.webp');
        $this->assertSame(sha1_file($this->pasta.'/fototeca.a.jpg'), $peca->ficheiro_hash);
        $this->assertSame(64, $peca->largura);
    }

    public function test_pode_correr_varias_vezes(): void
    {
        $this->importar();
        $refs = Peca::orderBy('id')->pluck('ref', 'id')->all();

        // Um editor dá um título e despublica uma peça; isso não se perde.
        $peca = Peca::where('legado_tabela', 'fototeca')->where('legado_id', 1)->first();
        $peca->update(['titulo' => 'Título escrito no backoffice', 'publicado' => false]);

        $this->importar();

        $this->assertSame($refs, Peca::orderBy('id')->pluck('ref', 'id')->all());
        $this->assertSame(6, Tema::count());
        $peca->refresh();
        $this->assertSame('Título escrito no backoffice', $peca->titulo);
        $this->assertFalse($peca->publicado);
    }

    public function test_importa_os_textos_das_paginas(): void
    {
        $this->importar();

        $this->assertSame('<p>Texto de início de exemplo.</p>', Texto::valor('inicio'));
        $this->assertSame('<p>Introdução da fototeca de exemplo.</p>', Texto::valor('seccao_fototeca'));
    }

    public function test_relatorio_tem_tudo_o_que_precisa_de_revisao(): void
    {
        $this->importar();

        $md = file_get_contents($this->relatorio);
        $this->assertStringContainsString('## 1. Totais importados por tipo', $md);
        $this->assertStringContainsString('`imprensa` #1: registo de teste', $md);
        $this->assertStringContainsString('`videoteca` #1: registo de teste', $md);
        $this->assertStringContainsString('| Aldeia | 48: Aldeia (fototeca)<br>61: Aldeia (mapas) |', $md);
        $this->assertStringContainsString('## 4. Temas sem uso', $md);
        $this->assertStringContainsString('| 61 | Aldeia | mapas | só usado num registo de teste; unido a um tema que tem peças |', $md);
        $this->assertStringContainsString('`11-12-13-14-15`', $md);        // datas sem década
        $this->assertStringContainsString('`imprensa` #2 → CRV-I-0001', $md); // data falsa
        $this->assertStringContainsString('`fototeca` #2 (CRV-F-0002) = `fototeca` #3 (CRV-F-0003)', $md); // duplicado
        $this->assertStringContainsString('## 8. Descrições vazias', $md);
        $this->assertStringContainsString('**Ana Exemplo**', $md);          // nomes na descrição
    }

    // ------------------------------------------------------------ base antiga de exemplo

    private function criarBaseAntiga(): void
    {
        $legado = Schema::connection('legado');

        $legado->create('temas', function (Blueprint $t) {
            $t->integer('id_tema')->primary();
            $t->string('nome')->nullable();
            $t->integer('destino')->default(1);
            $t->tinyInteger('del')->default(0);
        });
        $legado->create('fototeca', function (Blueprint $t) {
            $t->integer('id')->primary();
            foreach (['temas', 'fotografo', 'editor', 'fonte', 'localizacao', 'data', 'descricao', 'nome_ficheiro'] as $c) {
                $t->text($c)->nullable();
            }
            $t->integer('id_tipo_foto')->nullable();
            $t->timestamp('data_submissao')->nullable();
        });
        foreach (['cartazes', 'videoteca', 'mapaplanta'] as $tabela) {
            $legado->create($tabela, function (Blueprint $t) use ($tabela) {
                $t->integer('id')->primary();
                foreach (['data', 'fonte', 'autor', 'descricao', 'temas', 'nome_ficheiro'] as $c) {
                    $t->text($c)->nullable();
                }
                if ($tabela === 'mapaplanta') {
                    $t->text('localizacao')->nullable();
                }
            });
        }
        $legado->create('imprensa', function (Blueprint $t) {
            $t->integer('id')->primary();
            foreach (['temas', 'jornal', 'descricao', 'nome_ficheiro', 'fonte'] as $c) {
                $t->text($c)->nullable();
            }
            $t->date('data_publicacao')->nullable();
        });
        $legado->create('biblioteca', function (Blueprint $t) {
            $t->integer('id')->primary();
            foreach (['temas', 'nome', 'titulo', 'edicao', 'editora', 'data_edicao', 'autor', 'pag_original', 'nome_ficheiro', 'fonte'] as $c) {
                $t->text($c)->nullable();
            }
        });
        $legado->create('paginas', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('pagina');
            $t->text('texto')->nullable();
        });

        $db = DB::connection('legado');
        $db->table('temas')->insert([
            ['id_tema' => 41, 'nome' => 'Crianças, Jovens e Vellhice ', 'destino' => 1],
            ['id_tema' => 48, 'nome' => 'Aldeia', 'destino' => 2],
            ['id_tema' => 51, 'nome' => 'Gentes e pessoas', 'destino' => 2],
            ['id_tema' => 61, 'nome' => 'Aldeia', 'destino' => 4],
            ['id_tema' => 6, 'nome' => 'Sociedade Recreativa Pró Casais de Revelhos', 'destino' => 1],
            ['id_tema' => 89, 'nome' => 'Sociedade Recreativa Pro Casais de Revelhos', 'destino' => 6],
            ['id_tema' => 34, 'nome' => 'Rancho Etnográfico e Folclórico do Casais', 'destino' => 1],
            ['id_tema' => 94, 'nome' => 'Rancho Folclórico  e Etnográfico de Casais de Revelhos', 'destino' => 2],
            ['id_tema' => 77, 'nome' => 'Festas', 'destino' => 3],
        ]);

        $this->imagem('fototeca.a.jpg', 10);
        $this->imagem('fototeca.b.jpg', 200);
        copy($this->pasta.'/fototeca.b.jpg', $this->pasta.'/fototeca.c.jpg'); // duplicado byte a byte
        $this->imagem('fototeca.d.jpg', 90);
        $this->imagem('cartaz.a.jpg', 30);
        $this->imagem('imprensa.a.jpg', 50);
        $this->imagem('imprensa.b.jpg', 70);
        $this->imagem('videoteca.a.jpg', 120);

        $db->table('fototeca')->insert([
            ['id' => 1, 'temas' => '|48|51|', 'id_tipo_foto' => 1, 'fotografo' => 'Descolnhecido', 'editor' => 'Editor de Exemplo',
                'fonte' => 'Cedente de Exemplo', 'localizacao' => 'Casais de Revelhos', 'data' => '1955 - Casais de Revelhos',
                'descricao' => '<p style="margin: 0pt;">Descri&ccedil;&atilde;o com acentua&ccedil;&atilde;o e hist&oacute;ria.</p><p>&nbsp;</p>',
                'nome_ficheiro' => 'fototeca.a.jpg', 'data_submissao' => '2017-10-08 16:54:05'],
            ['id' => 2, 'temas' => '|94|', 'id_tipo_foto' => 1, 'fotografo' => 'Desconhecido', 'editor' => null, 'fonte' => 'Desconhecido ',
                'localizacao' => null, 'data' => null, 'descricao' => null, 'nome_ficheiro' => 'fototeca.b.jpg', 'data_submissao' => null],
            ['id' => 3, 'temas' => '|51|', 'id_tipo_foto' => 1, 'fotografo' => null, 'editor' => null, 'fonte' => null,
                'localizacao' => null, 'data' => null, 'descricao' => null, 'nome_ficheiro' => 'fototeca.c.jpg', 'data_submissao' => null],
            ['id' => 5, 'temas' => '|41|', 'id_tipo_foto' => 2, 'fotografo' => null, 'editor' => null, 'fonte' => null,
                'localizacao' => null, 'data' => 'Outubro de 1967',
                'descricao' => '<p>Postal de exemplo, Ana Exemplo ,Beatriz Teste.</p>', 'nome_ficheiro' => 'fototeca.d.jpg', 'data_submissao' => null],
        ]);
        $db->table('cartazes')->insert([
            ['id' => 1, 'data' => '11-12-13-14-15', 'fonte' => 'SRPCasais de revelhos', 'autor' => 'desconhecido ', 'descricao' => null,
                'temas' => '|77|', 'nome_ficheiro' => 'cartaz.a.jpg'],
        ]);
        $db->table('imprensa')->insert([
            ['id' => 1, 'temas' => '|6|', 'jornal' => 'Teste', 'data_publicacao' => '2017-10-02', 'descricao' => '<p>ddfdsfsdfsdf</p>',
                'nome_ficheiro' => 'imprensa.a.jpg', 'fonte' => null],
            ['id' => 2, 'temas' => '|34|', 'jornal' => 'Jornal de Exemplo ', 'data_publicacao' => '1999-04-13', 'descricao' => null,
                'nome_ficheiro' => 'imprensa.b.jpg', 'fonte' => 'https://exemplo.pt/noticia'],
        ]);
        $db->table('videoteca')->insert([
            ['id' => 1, 'data' => '1962', 'fonte' => 'aa', 'autor' => 'aa', 'descricao' => '<p>aa</p>', 'temas' => '|61|', 'nome_ficheiro' => 'videoteca.a.jpg'],
        ]);
        $db->table('paginas')->insert([
            ['id' => 1, 'pagina' => 'inicio', 'texto' => '<p style="color:red">Texto de in&iacute;cio de exemplo.</p>'],
            ['id' => 2, 'pagina' => 'fototeca', 'texto' => '<p>Introdu&ccedil;&atilde;o da fototeca de exemplo.</p>'],
            ['id' => 3, 'pagina' => 'pesquisa', 'texto' => null],
        ]);
    }

    private function imagem(string $nome, int $cinzento): void
    {
        $img = imagecreatetruecolor(64, 48);
        imagefill($img, 0, 0, imagecolorallocate($img, $cinzento, $cinzento, $cinzento));
        imagejpeg($img, $this->pasta.'/'.$nome);
        imagedestroy($img);
    }
}
