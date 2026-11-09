<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * As cópias ficam no próprio alojamento: só protegem o arquivo se alguém as guardar
 * noutro sítio. Isto diz qual é a última e quando foi descarregada uma cópia pela última vez.
 */
class CopiasSeguranca
{
    private const DIAS_ATE_AVISAR = 30;

    /** Caminho da cópia mais recente, ou null se ainda não houver nenhuma. */
    public function ultima(): ?string
    {
        $copias = glob($this->pasta().'/crevelhos-*.zip') ?: [];
        rsort($copias);

        return $copias[0] ?? null;
    }

    public function dataDaUltima(): ?Carbon
    {
        $ultima = $this->ultima();

        return $ultima ? Carbon::createFromTimestamp(filemtime($ultima), config('app.timezone')) : null;
    }

    public function ultimaDescarga(): ?Carbon
    {
        $registo = $this->registoDescarga();

        return is_file($registo) ? Carbon::parse(trim(file_get_contents($registo))) : null;
    }

    public function registarDescarga(): void
    {
        file_put_contents($this->registoDescarga(), now()->toIso8601String());
    }

    public function descargaEmAtraso(): bool
    {
        $descarga = $this->ultimaDescarga();

        return $descarga === null || $descarga->lt(now()->subDays(self::DIAS_ATE_AVISAR));
    }

    private function pasta(): string
    {
        return storage_path('app/copias');
    }

    private function registoDescarga(): string
    {
        return $this->pasta().'/ultima-descarga.txt';
    }
}
