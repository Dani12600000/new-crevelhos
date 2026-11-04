<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['nome', 'slug'])]
class Tema extends Model
{
    protected $table = 'temas';

    protected static function booted(): void
    {
        static::saving(function (Tema $tema) {
            if (blank($tema->slug)) {
                $tema->slug = Str::slug($tema->nome);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function pecas(): BelongsToMany
    {
        return $this->belongsToMany(Peca::class, 'peca_tema');
    }
}
