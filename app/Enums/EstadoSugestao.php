<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoSugestao: string implements HasColor, HasLabel
{
    case Pendente = 'pendente';
    case Aceite = 'aceite';
    case Rejeitada = 'rejeitada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aceite => 'Aceite',
            self::Rejeitada => 'Rejeitada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendente => 'warning',
            self::Aceite => 'success',
            self::Rejeitada => 'gray',
        };
    }
}
