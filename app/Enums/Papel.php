<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Papel de um utilizador do backoffice. */
enum Papel: string implements HasLabel
{
    case Admin = 'admin';
    case Editor = 'editor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Editor => 'Editor',
        };
    }
}
