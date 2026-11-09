<?php

namespace App\Filament\Widgets;

use App\Services\CopiasSeguranca;
use Filament\Widgets\Widget;

/** Só para administradores: lembra que as cópias têm de ser guardadas fora do alojamento. */
class UltimaCopiaSeguranca extends Widget
{
    protected string $view = 'filament.widgets.ultima-copia-seguranca';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->ehAdmin() ?? false;
    }

    protected function getViewData(): array
    {
        $copias = app(CopiasSeguranca::class);

        return [
            'dataDaUltima' => $copias->dataDaUltima(),
            'ultimaDescarga' => $copias->ultimaDescarga(),
            'emAtraso' => $copias->descargaEmAtraso(),
        ];
    }
}
