<x-filament-widgets::widget>
    <x-filament::section heading="Cópias de segurança" icon="heroicon-o-archive-box">
        <div style="display:grid;gap:.75rem">
            @if ($dataDaUltima)
                <p>
                    Última cópia: <strong>{{ $dataDaUltima->format('d/m/Y, H:i') }}</strong>.
                    Última descarga: <strong>{{ $ultimaDescarga?->format('d/m/Y, H:i') ?? 'nunca' }}</strong>.
                </p>

                @if ($emAtraso)
                    <x-filament::callout
                        color="warning"
                        icon="heroicon-o-exclamation-triangle"
                        heading="É tempo de guardar uma cópia fora do alojamento"
                        :description="($ultimaDescarga ? 'Ninguém descarrega uma cópia há mais de 30 dias.' : 'Ainda ninguém descarregou uma cópia.').' As cópias ficam no próprio alojamento: descarregue a última e guarde-a noutro sítio.'"
                    />
                @endif

                <div>
                    <x-filament::button tag="a" :href="route('copia-seguranca')" icon="heroicon-o-arrow-down-tray">
                        Descarregar a última cópia
                    </x-filament::button>
                </div>
            @else
                <p>Ainda não há cópias de segurança. São feitas todas as noites pelo cron do alojamento.</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
