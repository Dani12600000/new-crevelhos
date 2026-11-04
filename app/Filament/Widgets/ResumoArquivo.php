<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoSugestao;
use App\Filament\Resources\Comentarios\ComentarioResource;
use App\Filament\Resources\Pecas\PecaResource;
use App\Filament\Resources\Sugestoes\SugestaoResource;
use App\Models\Comentario;
use App\Models\Marcacao;
use App\Models\Peca;
use App\Models\Sugestao;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** O que há para fazer, logo à entrada do backoffice. */
class ResumoArquivo extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Peças publicadas', Peca::publicadas()->count())
                ->description(Peca::where('publicado', false)->count().' por publicar')
                ->url(PecaResource::getUrl()),
            Stat::make('Sem título', Peca::whereNull('titulo')->count())
                ->description('Peças à espera de um título')
                ->url(PecaResource::getUrl()),
            Stat::make('Sugestões pendentes', Sugestao::where('estado', EstadoSugestao::Pendente)->count())
                ->description(Marcacao::whereNull('pessoa_id')->count().' números por identificar')
                ->color('warning')
                ->url(SugestaoResource::getUrl()),
            Stat::make('Memórias por publicar', Comentario::where('publicado', false)->count())
                ->url(ComentarioResource::getUrl()),
        ];
    }
}
