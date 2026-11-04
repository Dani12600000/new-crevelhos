<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome'])]
class Acervo extends Model
{
    protected $table = 'acervos';

    public function pecas(): HasMany
    {
        return $this->hasMany(Peca::class);
    }
}
