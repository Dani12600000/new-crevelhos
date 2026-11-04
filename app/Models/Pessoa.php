<?php

namespace App\Models;

use Database\Factories\PessoaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    #[Scope]
    protected function visiveis(Builder $query): void
    {
        $query->where('oculta', false);
    }
}
