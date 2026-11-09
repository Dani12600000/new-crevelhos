<?php

namespace App\Models;

use Database\Factories\PessoaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable(['nome', 'slug', 'alcunha', 'notas', 'oculta'])]
class Pessoa extends Model
{
    /** @use HasFactory<PessoaFactory> */
    use HasFactory;

    protected $table = 'pessoas';

    protected function casts(): array
    {
        return ['oculta' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Pessoa $pessoa) {
            if (blank($pessoa->slug) || $pessoa->isDirty('nome')) {
                $pessoa->slug = self::slugLivre($pessoa->nome, $pessoa->id);
            }
        });
    }

    /** /pessoa/maria-rosa, /pessoa/maria-rosa-2... */
    public static function slugLivre(string $nome, ?int $ignorarId = null): string
    {
        $base = Str::slug($nome) ?: 'pessoa';
        $slug = $base;
        $n = 2;
        while (self::where('slug', $slug)->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function marcacoes(): HasMany
    {
        return $this->hasMany(Marcacao::class);
    }

    public function pedidosRemocao(): HasMany
    {
        return $this->hasMany(PedidoRemocao::class);
    }

    /**
     * Junta esta pessoa (a duplicada) a $destino e apaga-a. Não se perde nada: as marcações
     * passam para $destino, a alcunha e as notas somam-se e o nome antigo fica nas notas.
     * Se uma das duas estiver escondida, $destino fica escondida (RGPD).
     */
    public function juntarEm(Pessoa $destino): void
    {
        if ($destino->is($this)) {
            throw new InvalidArgumentException('Não se pode juntar uma pessoa consigo própria.');
        }

        DB::transaction(function () use ($destino) {
            $this->marcacoes()->update(['pessoa_id' => $destino->id]);
            $this->pedidosRemocao()->update(['pessoa_id' => $destino->id]);

            $alcunhas = collect([$destino->alcunha, $this->alcunha])
                ->filter()
                ->unique(fn (string $alcunha) => mb_strtolower(trim($alcunha)))
                ->implode(' / ');
            $alcunhaCabe = mb_strlen($alcunhas) <= 255;

            $destino->alcunha = $alcunhaCabe ? ($alcunhas ?: null) : $destino->alcunha;
            $destino->notas = collect([
                $destino->notas,
                'Nome antigo: '.$this->nome.' (ficha duplicada, junta a esta em '.now()->format('d/m/Y').').',
                $alcunhaCabe ? null : 'Alcunha da ficha duplicada: '.$this->alcunha,
                $this->notas,
            ])->filter()->implode("\n");
            $destino->oculta = $destino->oculta || $this->oculta;
            $destino->save();

            $this->delete();
        });
    }

    #[Scope]
    protected function visiveis(Builder $query): void
    {
        $query->where('oculta', false);
    }
}
