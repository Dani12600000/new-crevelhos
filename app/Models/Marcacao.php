<?php

namespace App\Models;

use App\Enums\EstadoSugestao;
use Database\Factories\MarcacaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Um número (①②③) por cima de uma fotografia, numa posição em percentagem. */
#[Fillable(['peca_id', 'numero', 'x', 'y', 'raio', 'pessoa_id', 'oculta'])]
class Marcacao extends Model
{
    /** @use HasFactory<MarcacaoFactory> */
    use HasFactory;

    protected $table = 'marcacoes';

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'x' => 'float',
            'y' => 'float',
            'raio' => 'float',
            'oculta' => 'boolean',
        ];
    }

    public function peca(): BelongsTo
    {
        return $this->belongsTo(Peca::class);
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function sugestoes(): HasMany
    {
        return $this->hasMany(Sugestao::class);
    }

    /**
     * O que o site público pode mostrar: a marcação não está oculta e,
     * se tiver pessoa, a pessoa também não está oculta (RGPD).
     */
    #[Scope]
    protected function publicas(Builder $query): void
    {
        $query->where('oculta', false)
            ->where(fn ($q) => $q->whereNull('pessoa_id')->orWhereHas('pessoa', fn ($p) => $p->where('oculta', false)));
    }

    public static function proximoNumero(Peca $peca): int
    {
        return (int) $peca->marcacoes()->max('numero') + 1;
    }

    /** Há sugestões pendentes com nomes diferentes para este número? */
    public function temSugestoesEmConflito(): bool
    {
        return $this->sugestoes()
            ->where('estado', EstadoSugestao::Pendente)
            ->pluck('nome_sugerido')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->unique()
            ->count() > 1;
    }

    /**
     * Estilo CSS de um recorte quadrado à volta da cara.
     * O contentor é quadrado; a imagem é ampliada e deslocada para centrar (x, y).
     */
    public function estiloRecorte(): string
    {
        $lado = max(1.0, $this->raio * 2) / 100; // fração da largura
        $proporcao = $this->peca->proporcao();
        $escala = 1 / $lado;
        $esquerda = -(($this->x / 100) / $lado - 0.5) * 100;
        $topo = -(($this->y / 100) * $proporcao / $lado - 0.5) * 100;

        return sprintf('width:%.3f%%;left:%.3f%%;top:%.3f%%', $escala * 100, $esquerda, $topo);
    }
}
