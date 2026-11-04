<?php

namespace App\Models;

use App\Enums\EstadoSugestao;
use App\Enums\Origem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/** Um nome sugerido para um número (ex.: escrito na folha do café). */
#[Fillable(['marcacao_id', 'nome_sugerido', 'fonte', 'data', 'estado', 'notas'])]
class Sugestao extends Model
{
    protected $table = 'sugestoes';

    protected $attributes = [
        'estado' => 'pendente',
        'fonte' => 'folha',
    ];

    protected function casts(): array
    {
        return [
            'fonte' => Origem::class,
            'estado' => EstadoSugestao::class,
            'data' => 'date',
        ];
    }

    public function marcacao(): BelongsTo
    {
        return $this->belongsTo(Marcacao::class);
    }

    /**
     * Aceita esta sugestão: liga o número a uma pessoa (existente ou nova)
     * e marca as outras sugestões pendentes do mesmo número como rejeitadas.
     */
    public function aceitar(?Pessoa $pessoa = null): Pessoa
    {
        return DB::transaction(function () use ($pessoa) {
            $pessoa ??= Pessoa::create(['nome' => trim($this->nome_sugerido)]);

            $this->marcacao->update(['pessoa_id' => $pessoa->id]);
            $this->update(['estado' => EstadoSugestao::Aceite]);

            Sugestao::where('marcacao_id', $this->marcacao_id)
                ->whereKeyNot($this->id)
                ->where('estado', EstadoSugestao::Pendente)
                ->update(['estado' => EstadoSugestao::Rejeitada]);

            return $pessoa;
        });
    }

    public function rejeitar(): void
    {
        $this->update(['estado' => EstadoSugestao::Rejeitada]);
    }
}
