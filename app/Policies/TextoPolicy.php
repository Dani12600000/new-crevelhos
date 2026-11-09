<?php

namespace App\Policies;

use App\Models\Texto;
use App\Models\User;

/** As chaves dos textos são fixas (config/crevelhos.php): só se edita o conteúdo. */
class TextoPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Texto $texto): bool
    {
        return false;
    }
}
