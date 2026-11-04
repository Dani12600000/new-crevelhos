<?php

namespace App\Models;

use App\Enums\Origem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma memória sobre uma peça. Nada é público sem ser publicado no backoffice. */
#[Fillable(['peca_id', 'autor', 'texto', 'origem', 'data', 'foto_folha', 'publicado'])]
class Comentario extends Model
{
    protected $table = 'comentarios';

    protected function casts(): array
    {
        return [
            'origem' => Origem::class,
            'data' => 'date',
            'publicado' => 'boolean',
        ];
    }

    public function peca(): BelongsTo
    {
        return $this->belongsTo(Peca::class);
    }

    #[Scope]
    protected function publicados(Builder $query): void
    {
        $query->where('publicado', true);
    }
}
