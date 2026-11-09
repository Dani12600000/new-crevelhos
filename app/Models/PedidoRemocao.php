<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** RGPD: um pedido para retirar alguém do arquivo. Nunca aparece no site. */
#[Fillable(['requerente', 'contacto', 'data_pedido', 'pessoa_id', 'marcacao_id', 'pedido', 'acao_tomada', 'data_resolucao'])]
class PedidoRemocao extends Model
{
    protected $table = 'pedidos_remocao';

    protected function casts(): array
    {
        return [
            'data_pedido' => 'date',
            'data_resolucao' => 'date',
        ];
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function marcacao(): BelongsTo
    {
        return $this->belongsTo(Marcacao::class);
    }
}
