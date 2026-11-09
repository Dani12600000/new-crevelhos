<?php

namespace App\Policies;

use App\Models\PedidoRemocao;
use App\Models\User;

class PedidoRemocaoPolicy
{
    /** O registo serve de prova do que foi feito: só um administrador o pode apagar. */
    public function delete(User $user, PedidoRemocao $pedido): bool
    {
        return $user->ehAdmin();
    }
}
