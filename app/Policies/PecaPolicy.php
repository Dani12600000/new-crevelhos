<?php

namespace App\Policies;

use App\Models\Peca;
use App\Models\User;

/** O Filament consulta as policies; o que não estiver aqui fica permitido a quem tem sessão. */
class PecaPolicy
{
    public function delete(User $user, Peca $peca): bool
    {
        return $user->ehAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->ehAdmin();
    }
}
