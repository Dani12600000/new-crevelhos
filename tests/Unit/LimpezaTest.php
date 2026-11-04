<?php

namespace Tests\Unit;

use App\Support\Limpeza;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LimpezaTest extends TestCase
{
    public function test_decodifica_entidades_e_junta_espacos(): void
    {
        $this->assertSame('Remunerações e Finanças', Limpeza::texto('Remuneraç&#245;es  e Finanças '));
        $this->assertSame('Religião', Limpeza::texto('Religi&atilde;o'));
        $this->assertNull(Limpeza::texto('   '));
    }

    #[DataProvider('desconhecidos')]
    public function test_variantes_de_desconhecido_passam_a_null(string $valor): void
    {
        $this->assertNull(Limpeza::nome($valor));
    }

    public static function desconhecidos(): array
    {
        return [['desconhecido'], ['Desconhecido '], ['Descolnhecido'], ['DESCONHECIDO'], ['desconhecida']];
    }

    public function test_nomes_reais_nao_sao_desconhecido(): void
    {
        $this->assertSame('Pedro David', Limpeza::nome('Pedro David'));
    }

    public function test_html_sem_estilos_nem_paragrafos_vazios(): void
    {
        $html = Limpeza::html('<p style="margin-top: 0pt; word-break: normal;">Uma viagem pela hist&oacute;ria.</p>'
            ."\r\n<p>&nbsp;</p><p><strong><em><span class=\"fsl\"><br /></span></em></strong></p>"
            .'<div>email: <a href="mailto:x@exemplo.pt" target="_blank">x@exemplo.pt</a></div><h3>&nbsp;</h3>');

        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('target=', $html);
        $this->assertStringNotContainsString('<span', $html);
        $this->assertStringNotContainsString('<div', $html);
        $this->assertStringContainsString('<p>Uma viagem pela história.</p>', $html);
        $this->assertStringContainsString('<p>email: <a href="mailto:', $html);
        $this->assertSame(2, substr_count($html, '<p>'));
    }

    public function test_html_so_com_espacos_fica_null(): void
    {
        $this->assertNull(Limpeza::html('<p>&nbsp;</p><p> </p>'));
        $this->assertNull(Limpeza::html(null));
    }

    public function test_html_retira_scripts(): void
    {
        $this->assertSame('<p>Olá</p>', Limpeza::html('<p>Olá<script>alert(1)</script></p>'));
    }

    #[DataProvider('datas')]
    public function test_decada_das_datas_reais(?string $data, ?int $decada): void
    {
        $this->assertSame($decada, Limpeza::decada($data));
    }

    public static function datas(): array
    {
        return [
            ['1962-05-01', 1960],
            ['1950-Fevereiro-14', 1950],
            ['Outubro de 1967', 1960],
            ['1955 - Casais de Revelhos', 1950],
            ['16/12/2017', 2010],
            ['Janeiro de 2018', 2010],
            ['11-12-13-14-15', null],
            ['11 de Novembro', null],
            ['22-Outubro-2017', 2010],
            [null, null],
            ['1840', null],
        ];
    }

    public function test_tira_o_local_do_fim_da_data(): void
    {
        $this->assertSame('1955', Limpeza::dataTexto('1955 - Casais de Revelhos'));
        $this->assertSame('22-Outubro-2017', Limpeza::dataTexto('22-Outubro-2017'));
    }

    public function test_chave_de_tema_ignora_acentos_e_espacos(): void
    {
        $this->assertSame(
            Limpeza::chaveTema('Sociedade Recreativa Pró Casais de Revelhos'),
            Limpeza::chaveTema('Sociedade Recreativa Pro Casais de Revelhos'),
        );
        $this->assertSame(
            Limpeza::chaveTema('Rancho Folclórico  e Etnográfico de Casais de Revelhos'),
            Limpeza::chaveTema('Rancho Folclorico e Etnográfico de Casais de Revelhos'),
        );
    }
}
