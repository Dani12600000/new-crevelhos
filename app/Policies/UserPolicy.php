<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Um administrador não apaga a própria conta, para o backoffice nunca ficar sem ninguém. */
    public function delete(User $user, User $model): bool
    {
        return $user->ehAdmin() && ! $user->is($model);
    }
}
