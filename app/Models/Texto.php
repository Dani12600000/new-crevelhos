<?php

namespace App\Models;

use App\Support\Limpeza;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Textos editáveis do site, por chave (ver config/crevelhos.php). */
#[Fillable(['chave', 'valor'])]
class Texto extends Model
{
    protected $table = 'textos';

    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    protected static function booted(): void
    {
        static::saving(function (Texto $texto) {
            $texto->valor = $texto->ehHtml() ? Limpeza::html($texto->valor) : Limpeza::texto($texto->valor);
        });
        static::saved(fn () => self::$cache = null);
        static::deleted(fn () => self::$cache = null);
    }

    public static function valor(string $chave, ?string $omissao = null): ?string
    {
        self::$cache ??= self::query()->pluck('valor', 'chave')->all();

        $valor = self::$cache[$chave] ?? null;

        return filled($valor) ? $valor : $omissao;
    }

    public static function limparCache(): void
    {
        self::$cache = null;
    }

    public function rotulo(): string
    {
        return config("crevelhos.textos.{$this->chave}.0", $this->chave);
    }

    public function ehHtml(): bool
    {
        return config("crevelhos.textos.{$this->chave}.1", 'html') === 'html';
    }
}
