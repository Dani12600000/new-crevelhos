<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** De onde veio uma sugestão de nome ou uma memória. */
enum Origem: string implements HasLabel
{
    case Folha = 'folha';
    case Email = 'email';
    case Conversa = 'conversa';
    case Outra = 'outra';

    public function getLabel(): string
    {
        return match ($this) {
            self::Folha => 'Folha do café',
            self::Email => 'Email',
            self::Conversa => 'Conversa',
            self::Outra => 'Outra',
        };
    }
}
