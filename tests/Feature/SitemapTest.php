<?php

namespace Tests\Feature;

use App\Enums\Seccao;
use App\Models\Marcacao;
use App\Models\Peca;
use App\Models\Pessoa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private function pessoaEm(Peca $peca, array $atributos = [], array $marcacao = []): Pessoa
    {
        $pessoa = Pessoa::factory()->create($atributos);
        Marcacao::factory()->create(['peca_id' => $peca->id, 'numero' => $pessoa->id, 'pessoa_id' => $pessoa->id] + $marcacao);

        return $pessoa;
    }

    public function test_sitemap_e_xml_valido_com_paginas_e_seccoes(): void
    {
        $resposta = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');

        $xml = simplexml_load_string($resposta->getContent());
        $this->assertNotFalse($xml);

        $urls = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
        foreach (['inicio', 'arquivo', 'linha-do-tempo', 'quem-e-quem', 'sobre', 'privacidade'] as $rota) {
            $this->assertContains(route($rota), $urls);
        }
        foreach (Seccao::cases() as $seccao) {
            $this->assertContains(route('seccao', $seccao->value), $urls);
        }
    }

    public function test_sitemap_so_tem_pecas_publicadas(): void
    {
        $publicada = Peca::factory()->create();
        $porPublicar = Peca::factory()->porPublicar()->create();

        $this->get('/sitemap.xml')
            ->assertSee(route('peca', $publicada), false)
            ->assertDontSee(route('peca', $porPublicar), false);
    }

    public function test_sitemap_so_tem_pessoas_com_pagina_publica(): void
    {
        $publicada = Peca::factory()->create();
        $porPublicar = Peca::factory()->porPublicar()->create();

        $visivel = $this->pessoaEm($publicada);
        $escondida = $this->pessoaEm($publicada, ['oculta' => true]);
        $soPorPublicar = $this->pessoaEm($porPublicar);
        $marcacaoOculta = $this->pessoaEm($publicada, [], ['oculta' => true]);
        $semMarcacoes = Pessoa::factory()->create();

        $resposta = $this->get('/sitemap.xml')->assertSee(route('pessoa', $visivel), false);

        foreach ([$escondida, $soPorPublicar, $marcacaoOculta, $semMarcacoes] as $pessoa) {
            $resposta->assertDontSee(route('pessoa', $pessoa).'<', false);
        }
    }

    public function test_robots_aponta_para_o_sitemap_e_protege_o_backoffice(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('sitemap'), false);
    }
}
