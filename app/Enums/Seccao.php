<?php

namespace App\Enums;

/**
 * As secções do museu. Cada uma agrupa um ou mais tipos de peça
 * e tem um texto de introdução editável no backoffice.
 */
enum Seccao: string
{
    case Fototeca = 'fototeca';
    case Imprensa = 'imprensa';
    case Cartazes = 'cartazes';
    case Mapas = 'mapas-e-plantas';
    case Videoteca = 'videoteca';
    case Biblioteca = 'biblioteca';

    public function nome(): string
    {
        return match ($this) {
            self::Fototeca => 'Fototeca',
            self::Imprensa => 'Imprensa',
            self::Cartazes => 'Cartazes',
            self::Mapas => 'Mapas e plantas',
            self::Videoteca => 'Videoteca',
            self::Biblioteca => 'Biblioteca',
        };
    }

    public function resumo(): string
    {
        return match ($this) {
            self::Fototeca => 'Fotografias e postais da aldeia e das suas gentes.',
            self::Imprensa => 'Recortes de jornais e notícias sobre os Casais.',
            self::Cartazes => 'Cartazes de festas, bailes e eventos.',
            self::Mapas => 'Mapas e plantas da aldeia e arredores.',
            self::Videoteca => 'Registos em vídeo da vida da aldeia.',
            self::Biblioteca => 'Livros e obras sobre Abrantes e os Casais.',
        };
    }

    /** @return list<TipoPeca> */
    public function tipos(): array
    {
        return array_values(array_filter(TipoPeca::cases(), fn (TipoPeca $t) => $t->seccao() === $this));
    }

    /** Chave do texto de introdução na tabela `textos`. */
    public function chaveTexto(): string
    {
        return 'seccao_'.str_replace('-', '_', $this->value);
    }

    /** Valor de ?pagina= no site antigo. */
    public function paginaLegado(): string
    {
        return $this === self::Mapas ? 'mapas_e_plantas' : $this->value;
    }

    public static function daPaginaLegado(string $pagina): ?self
    {
        foreach (self::cases() as $seccao) {
            if ($seccao->paginaLegado() === $pagina) {
                return $seccao;
            }
        }

        return null;
    }
}
