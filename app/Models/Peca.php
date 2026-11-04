<?php

namespace App\Models;

use App\Enums\TipoPeca;
use App\Services\ImagensPeca;
use Database\Factories\PecaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'ref', 'tipo', 'titulo', 'descricao', 'data_texto', 'decada', 'autor', 'cedido_por', 'local', 'acervo_id',
    'ficheiro', 'ficheiro_hash', 'largura', 'altura', 'video_url',
    'jornal', 'data_publicacao', 'url_original',
    'edicao', 'editora', 'data_edicao', 'paginas',
    'notas_internas', 'publicado', 'destaque', 'legado_tabela', 'legado_id',
])]
class Peca extends Model
{
    /** @use HasFactory<PecaFactory> */
    use HasFactory;

    protected $table = 'pecas';

    protected function casts(): array
    {
        return [
            'tipo' => TipoPeca::class,
            'decada' => 'integer',
            'data_publicacao' => 'date',
            'publicado' => 'boolean',
            'destaque' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Peca $peca) {
            $peca->ref ??= self::proximaRef($peca->tipo);
        });

        // Ficheiro novo enviado pelo backoffice: mover para pecas/{ref}/ e gerar WebP + miniatura.
        static::saved(function (Peca $peca) {
            if ($peca->wasChanged('ficheiro') || $peca->wasRecentlyCreated) {
                app(ImagensPeca::class)->processarEnvio($peca);
            }
        });
    }

    /** Os URLs usam a ref: /peca/CRV-F-0001 */
    public function getRouteKeyName(): string
    {
        return 'ref';
    }

    /** Próxima ref livre para o tipo (numeração sequencial por prefixo). */
    public static function proximaRef(TipoPeca $tipo): string
    {
        $prefixo = 'CRV-'.$tipo->prefixo().'-';

        $ultima = self::query()
            ->where('ref', 'like', $prefixo.'%')
            ->pluck('ref')
            ->map(fn (string $ref) => (int) Str::after($ref, $prefixo))
            ->max() ?? 0;

        return $prefixo.str_pad((string) ($ultima + 1), 4, '0', STR_PAD_LEFT);
    }

    public function temas(): BelongsToMany
    {
        return $this->belongsToMany(Tema::class, 'peca_tema');
    }

    public function acervo(): BelongsTo
    {
        return $this->belongsTo(Acervo::class);
    }

    public function marcacoes(): HasMany
    {
        return $this->hasMany(Marcacao::class)->orderBy('numero');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class);
    }

    #[Scope]
    protected function publicadas(Builder $query): void
    {
        $query->where('publicado', true);
    }

    /** @param  TipoPeca[]  $tipos */
    #[Scope]
    protected function dosTipos(Builder $query, array $tipos): void
    {
        $query->whereIn('tipo', array_map(fn (TipoPeca $t) => $t->value, $tipos));
    }

    /**
     * Título para mostrar. As peças antigas não têm título: usa-se o início
     * da descrição ou, em último caso, o tipo e o local. Nada é inventado.
     */
    protected function tituloExibicao(): Attribute
    {
        return Attribute::get(function () {
            if (filled($this->titulo)) {
                return $this->titulo;
            }

            $texto = trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br />'], ' ', (string) $this->descricao))));
            $texto = preg_replace('/\s+/u', ' ', $texto);
            if (mb_strlen($texto) >= 8) {
                $frase = preg_split('/(?<=[.!?])\s/u', $texto)[0];

                return Str::limit($frase, 70, '…', preserveWords: true);
            }

            $partes = array_filter([$this->tipo->getLabel(), $this->jornal, $this->local]);

            return implode(' · ', $partes);
        });
    }

    public function pasta(): string
    {
        return 'pecas/'.$this->ref;
    }

    public function temImagem(): bool
    {
        return filled($this->ficheiro);
    }

    public function urlOriginal(): ?string
    {
        return $this->ficheiro ? Storage::disk('public')->url($this->ficheiro) : null;
    }

    public function urlImagem(): ?string
    {
        return $this->urlDerivada('grande.webp');
    }

    public function urlMiniatura(): ?string
    {
        return $this->urlDerivada('miniatura.webp');
    }

    private function urlDerivada(string $nome): ?string
    {
        if (! $this->ficheiro) {
            return null;
        }

        $caminho = dirname($this->ficheiro).'/'.$nome;

        // Ainda sem versões geradas (ex.: ficheiro acabado de enviar): mostra o original.
        return Storage::disk('public')->exists($caminho)
            ? Storage::disk('public')->url($caminho).'?v='.substr((string) $this->ficheiro_hash, 0, 8)
            : $this->urlOriginal();
    }

    /** Proporção altura/largura, para reservar espaço e calcular recortes. */
    public function proporcao(): float
    {
        return ($this->largura && $this->altura) ? $this->altura / $this->largura : 0.75;
    }

    /** URL de incorporação do YouTube ou Vimeo a partir do URL guardado. */
    public function urlIncorporacao(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $this->video_url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $this->video_url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?dnt=1';
        }

        return null;
    }
}
