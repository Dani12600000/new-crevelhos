<?php

namespace Tests\Unit;

use App\Importacao\DetetorNomes;
use PHPUnit\Framework\TestCase;

class DetetorNomesTest extends TestCase
{
    public function test_encontra_nomes_numa_lista(): void
    {
        $nomes = DetetorNomes::candidatos('Fotos de exemplo, Ana Exemplo ,Beatriz Teste, Carla dos Santos e Dora Fictícia.');

        $this->assertSame(['Ana Exemplo', 'Beatriz Teste', 'Carla dos Santos', 'Dora Fictícia'], $nomes);
    }

    public function test_ignora_lugares_e_a_primeira_palavra_da_frase(): void
    {
        $this->assertSame([], DetetorNomes::candidatos('Visita de grupo de amigos dos Casais de Revelhos à Serra da Estrela.'));
        $this->assertSame([], DetetorNomes::candidatos('Equipa de futebol'));
    }

    public function test_aceita_nome_composto_no_inicio(): void
    {
        $this->assertSame(['Ana Exemplo Teste'], DetetorNomes::candidatos('Ana Exemplo Teste, filha de alguém'));
    }
}
