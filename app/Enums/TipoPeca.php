<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoPeca: string implements HasLabel
{
    case Foto = 'foto';
    case Postal = 'postal';
    case Cartaz = 'cartaz';
    case Imprensa = 'imprensa';
    case Video = 'video';
    case Mapa = 'mapa';
    case Livro = 'livro';

    public function getLabel(): string
    {
        return match ($this) {
            self::Foto => 'Fotografia',
            self::Postal => 'Postal',
            self::Cartaz => 'Cartaz',
            self::Imprensa => 'Recorte de imprensa',
            self::Video => 'Vídeo',
            self::Mapa => 'Mapa ou planta',
            self::Livro => 'Livro',
        };
    }

    /** Letra usada na referência (CRV-F-0001). Fotografias e postais partilham a numeração. */
    public function prefixo(): string
    {
        return match ($this) {
            self::Foto, self::Postal => 'F',
            self::Cartaz => 'C',
            self::Imprensa => 'I',
            self::Video => 'V',
            self::Mapa => 'M',
            self::Livro => 'B',
        };
    }

    /** @return list<self> tipos que partilham o mesmo prefixo */
    public function tiposComMesmoPrefixo(): array
    {
        return array_values(array_filter(self::cases(), fn (self $t) => $t->prefixo() === $this->prefixo()));
    }

    public function seccao(): Seccao
    {
        return match ($this) {
            self::Foto, self::Postal => Seccao::Fototeca,
            self::Cartaz => Seccao::Cartazes,
            self::Imprensa => Seccao::Imprensa,
            self::Video => Seccao::Videoteca,
            self::Mapa => Seccao::Mapas,
            self::Livro => Seccao::Biblioteca,
        };
    }

    /** As pessoas só se marcam em fotografias e postais. */
    public function permiteMarcacoes(): bool
    {
        return in_array($this, [self::Foto, self::Postal], true);
    }
}
