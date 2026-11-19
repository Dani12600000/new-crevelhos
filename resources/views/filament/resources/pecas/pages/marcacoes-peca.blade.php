<x-filament-panels::page>
    @php
        $peca = $this->record;
        $marcacoes = $this->marcacoes;
        // Com muitas pessoas, números mais pequenos para não taparem as caras.
        $compacto = $marcacoes->count() > 20;
    @endphp

    <style>
        .crv-editor { display: grid; gap: 1.5rem; }
        @media (min-width: 1100px) {
            .crv-editor { grid-template-columns: minmax(0, 1.5fr) minmax(340px, 1fr); align-items: start; }
            /* A imagem fica à vista enquanto se percorre a lista de números. */
            .crv-coluna-imagem { position: sticky; top: 5rem; }
        }
        .crv-barra { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: .6rem; }
        .crv-barra button, .crv-barra label {
            display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .7rem; border-radius: .5rem;
            border: 1px solid rgb(0 0 0 / .15); font-size: .875rem; font-weight: 600; background: rgb(255 255 255 / .04); cursor: pointer;
        }
        .crv-barra button:disabled { opacity: .4; cursor: default; }
        .crv-barra .zoom { min-width: 3.5rem; text-align: center; font-variant-numeric: tabular-nums; }
        .crv-janela { overflow: auto; max-height: max(320px, calc(100vh - 16rem)); border-radius: .5rem; overscroll-behavior: contain; }
        .crv-imagem { position: relative; cursor: crosshair; user-select: none; touch-action: none; }
        .crv-imagem img { width: 100%; display: block; pointer-events: none; }
        .crv-num {
            position: absolute; transform: translate(-50%, -50%); width: 30px; height: 30px; border-radius: 50%;
            background: #1d4f9e; color: #fff; border: 2px solid #fff; font-weight: 700; font-size: 14px;
            display: grid; place-items: center; cursor: grab; box-shadow: 0 1px 4px rgb(0 0 0 / .6); z-index: 2;
        }
        .crv-compacto .crv-num { width: 22px; height: 22px; font-size: 11px; border-width: 1.5px; }
        .crv-num.vazio { background: #fff; color: #1d4f9e; border-color: #1d4f9e; }
        .crv-num.oculta { opacity: .45; border-style: dashed; }
        .crv-num.ativa, .crv-num.realce { outline: 3px solid #f59e0b; outline-offset: 2px; z-index: 3; }
        .crv-num.realce { transform: translate(-50%, -50%) scale(1.35); }
        .crv-raio { position: absolute; transform: translate(-50%, -50%); border: 2px dashed #f59e0b; border-radius: 50%; pointer-events: none; }
        .crv-raio.todos { border-width: 1.5px; border-color: rgb(245 158 11 / .85); }
        /* Com os recortes à vista, o número fica só em texto para não tapar a cara. */
        .crv-ver-caras .crv-num:not(.ativa):not(.realce):not(:hover) {
            background: transparent; color: #fff; border-color: transparent; box-shadow: none;
            text-shadow: 0 0 3px #000, 0 0 2px #000, 0 0 1px #000;
        }
        .crv-recortes { display: grid; grid-template-columns: repeat(auto-fill, minmax(58px, 1fr)); gap: .4rem; }
        .crv-mini { position: relative; padding: 0; border: 2px solid transparent; border-radius: .4rem; overflow: hidden; aspect-ratio: 1; background: #ddd; cursor: pointer; }
        .crv-mini img { position: absolute; max-width: none; pointer-events: none; }
        .crv-mini span {
            position: absolute; left: 2px; top: 2px; min-width: 18px; padding: 0 3px; border-radius: 9px;
            background: #1d4f9e; color: #fff; font-size: 11px; font-weight: 700; line-height: 18px; text-align: center;
        }
        .crv-mini.vazio span { background: #fff; color: #1d4f9e; }
        .crv-mini.ativa, .crv-mini:hover { border-color: #f59e0b; }
        /* minmax(0, 1fr) e min-width: 0 deixam a coluna encolher; senão a grelha de recortes alarga a página. */
        .crv-editor > *, .crv-lista > * { min-width: 0; }
        .crv-lista { display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; }
        .crv-cartao { border: 1px solid rgb(0 0 0 / .1); border-radius: .75rem; padding: .9rem; background: rgb(255 255 255 / .02); }
        .crv-cartao.ativa { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
        .crv-topo { display: flex; gap: .75rem; align-items: center; }
        .crv-recorte { position: relative; width: 64px; height: 64px; flex: none; border-radius: 50%; overflow: hidden; background: #ddd; }
        .crv-recorte img { position: absolute; max-width: none; }
        .crv-acoes { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
        .crv-sug { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; font-size: .875rem; padding: .35rem 0; border-top: 1px dashed rgb(0 0 0 / .1); }
        .crv-sug .nome { font-weight: 600; }
        .crv-ajuda { font-size: .875rem; opacity: .8; }
        .crv-aviso { color: #b45309; font-size: .8rem; font-weight: 600; }
    </style>

    <div class="crv-editor"
         x-data="{
            zoom: 1,
            recortes: false,
            realce: null,
            init() {
                try { this.recortes = localStorage.getItem('crv-recortes') === '1' } catch (e) {}
                this.$watch('recortes', v => { try { localStorage.setItem('crv-recortes', v ? '1' : '0') } catch (e) {} });
            },
            mudarZoom(fator) {
                const j = this.$refs.janela;
                // Manter o mesmo ponto da imagem no centro da janela.
                const cx = (j.scrollLeft + j.clientWidth / 2) / j.scrollWidth;
                const cy = (j.scrollTop + j.clientHeight / 2) / j.scrollHeight;
                this.zoom = Math.min(5, Math.max(1, Math.round(this.zoom * fator * 4) / 4));
                this.$nextTick(() => {
                    j.scrollLeft = cx * j.scrollWidth - j.clientWidth / 2;
                    j.scrollTop = cy * j.scrollHeight - j.clientHeight / 2;
                });
            },
            irParaNumero(id) {
                const j = this.$refs.janela;
                const n = j.querySelector(`[data-marcacao='${id}']`);
                if (n) j.scrollTo({ left: n.offsetLeft - j.clientWidth / 2, top: n.offsetTop - j.clientHeight / 2, behavior: 'smooth' });
            },
            irParaCartao(id) {
                document.getElementById('cartao-' + id)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            },
         }">
        <div class="crv-coluna-imagem">
            <p class="crv-ajuda" style="margin-bottom:.75rem">
                <strong>Clique na cara</strong> para pôr o próximo número ({{ \App\Models\Marcacao::proximoNumero($peca) }}).
                Arraste um número para o mudar de sítio. O círculo tracejado é o recorte da cara usado no site.
                @if ($compacto)
                    Com muitas pessoas, use o <strong>zoom</strong> e veja os <strong>recortes</strong> para confirmar cada cara.
                @endif
            </p>

            <div class="crv-barra">
                <button type="button" x-on:click="mudarZoom(1 / 1.5)" x-bind:disabled="zoom <= 1" title="Afastar">−</button>
                <span class="zoom" x-text="Math.round(zoom * 100) + '%'">100%</span>
                <button type="button" x-on:click="mudarZoom(1.5)" x-bind:disabled="zoom >= 5" title="Aproximar">+</button>
                <button type="button" x-on:click="zoom = 1" x-show="zoom > 1" x-cloak>Ver tudo</button>
                <label>
                    <input type="checkbox" x-model="recortes">
                    Mostrar todos os recortes
                </label>
            </div>

            <div class="crv-janela {{ $compacto ? 'crv-compacto' : '' }}" x-ref="janela"
                 x-bind:class="{ 'crv-ver-caras': recortes }"
                 x-bind:style="{ overflowX: zoom > 1 ? 'auto' : 'hidden' }"
                 x-on:wheel="if ($event.ctrlKey) { $event.preventDefault(); mudarZoom($event.deltaY < 0 ? 1.25 : 0.8) }">
            <div class="crv-imagem"
                 x-bind:style="{ width: (zoom * 100) + '%' }"
                 x-data="{
                    posicao(e) {
                        const r = this.$el.getBoundingClientRect();
                        return [(e.clientX - r.left) / r.width * 100, (e.clientY - r.top) / r.height * 100];
                    },
                 }"
                 x-on:click="const [x, y] = posicao($event); $wire.adicionar(x, y)">
                <img src="{{ $peca->urlImagem() }}" alt="{{ $peca->titulo_exibicao }}">

                @foreach ($marcacoes as $m)
                    @if ($this->selecionada === $m->id)
                        <span class="crv-raio" style="left: {{ $m->x }}%; top: {{ $m->y }}%; width: {{ $m->raio * 2 }}%; aspect-ratio: 1;"></span>
                    @else
                        <span class="crv-raio todos" x-show="recortes" x-cloak style="left: {{ $m->x }}%; top: {{ $m->y }}%; width: {{ $m->raio * 2 }}%; aspect-ratio: 1;"></span>
                    @endif
                    <button type="button" wire:key="num-{{ $m->id }}-{{ $m->x }}-{{ $m->y }}"
                            data-marcacao="{{ $m->id }}"
                            class="crv-num {{ $m->pessoa ? '' : 'vazio' }} {{ $m->oculta ? 'oculta' : '' }} {{ $this->selecionada === $m->id ? 'ativa' : '' }}"
                            x-bind:class="{ realce: realce === {{ $m->id }} }"
                            style="left: {{ $m->x }}%; top: {{ $m->y }}%"
                            title="{{ $m->pessoa?->nome ?? 'Por identificar' }}"
                            x-data="{ inicio: null, mexeu: false }"
                            x-on:click.stop
                            x-on:pointerdown.stop="inicio = [$event.clientX, $event.clientY]; mexeu = false; $el.setPointerCapture($event.pointerId)"
                            x-on:pointermove="if (inicio) {
                                if (Math.abs($event.clientX - inicio[0]) + Math.abs($event.clientY - inicio[1]) > 4) mexeu = true;
                                if (mexeu) { const r = $el.parentElement.getBoundingClientRect();
                                    $el.style.left = (($event.clientX - r.left) / r.width * 100) + '%';
                                    $el.style.top = (($event.clientY - r.top) / r.height * 100) + '%'; }
                            }"
                            x-on:pointerup="if (inicio) {
                                const r = $el.parentElement.getBoundingClientRect();
                                if (mexeu) { $wire.mover({{ $m->id }}, ($event.clientX - r.left) / r.width * 100, ($event.clientY - r.top) / r.height * 100) }
                                else { $wire.selecionar({{ $m->id }}).then(() => irParaCartao({{ $m->id }})) }
                                inicio = null;
                            }">
                        {{ $m->numero }}
                    </button>
                @endforeach
            </div>
            </div>
        </div>

        <div class="crv-lista">
            @if ($marcacoes->count() > 1)
                <x-filament::section collapsible :collapsed="false" compact>
                    <x-slot name="heading">Todos os recortes ({{ $marcacoes->count() }})</x-slot>
                    <x-slot name="description">Confirme que cada número está numa cara. Clique num recorte para o encontrar na fotografia.</x-slot>
                    <div class="crv-recortes">
                        @foreach ($marcacoes as $m)
                            <button type="button" wire:key="mini-{{ $m->id }}-{{ $m->x }}-{{ $m->y }}-{{ $m->raio }}"
                                    class="crv-mini {{ $m->pessoa ? '' : 'vazio' }} {{ $this->selecionada === $m->id ? 'ativa' : '' }}"
                                    title="N.º {{ $m->numero }}: {{ $m->pessoa?->nome ?? 'por identificar' }}"
                                    x-on:mouseenter="realce = {{ $m->id }}" x-on:mouseleave="realce = null"
                                    x-on:click="irParaNumero({{ $m->id }}); $wire.selecionar({{ $m->id }}).then(() => irParaCartao({{ $m->id }}))">
                                <img src="{{ $peca->urlImagem() }}" alt="" style="{{ $m->estiloRecorte() }}">
                                <span>{{ $m->numero }}</span>
                            </button>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            @forelse ($marcacoes as $m)
                @php
                    $pendentes = $m->sugestoes->where('estado', $this->estadoPendente());
                    $conflito = $pendentes->map(fn ($s) => mb_strtolower(trim($s->nome_sugerido)))->unique()->count() > 1;
                @endphp
                <div class="crv-cartao {{ $this->selecionada === $m->id ? 'ativa' : '' }}" wire:key="cartao-{{ $m->id }}"
                     id="cartao-{{ $m->id }}"
                     x-on:mouseenter="realce = {{ $m->id }}" x-on:mouseleave="realce = null">
                    <div class="crv-topo">
                        <div class="crv-recorte">
                            <img src="{{ $peca->urlImagem() }}" alt="" style="{{ $m->estiloRecorte() }}">
                        </div>
                        <div style="flex:1">
                            <div style="font-weight:700;font-size:1.05rem">
                                N.º {{ $m->numero }}:
                                @if ($m->pessoa)
                                    {{ $m->pessoa->nome }}
                                @else
                                    <span style="opacity:.7">por identificar</span>
                                @endif
                            </div>
                            @if ($m->oculta)
                                <div class="crv-aviso">Escondida no site</div>
                            @endif
                            @if ($m->pessoa?->oculta)
                                <div class="crv-aviso">Pessoa escondida no site</div>
                            @endif
                            @if ($conflito)
                                <div class="crv-aviso">⚠ Há sugestões diferentes para este número</div>
                            @endif
                            @if ($this->selecionada === $m->id)
                                <label class="crv-ajuda" style="display:flex;gap:.5rem;align-items:center;margin-top:.3rem">
                                    Tamanho do recorte
                                    <input type="range" min="2" max="30" step="0.5" value="{{ $m->raio }}"
                                           wire:change="alterarRaio({{ $m->id }}, $event.target.value)">
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="crv-acoes">
                        {{ ($this->ligarPessoaAction)(['marcacao' => $m->id]) }}
                        @if ($m->pessoa)
                            {{ ($this->desligarPessoaAction)(['marcacao' => $m->id]) }}
                        @endif
                        {{ ($this->sugestaoAction)(['marcacao' => $m->id]) }}
                        {{ ($this->alternarOcultaAction)(['marcacao' => $m->id]) }}
                        {{ ($this->removerAction)(['marcacao' => $m->id]) }}
                    </div>

                    @foreach ($m->sugestoes as $s)
                        <div class="crv-sug" wire:key="sug-{{ $s->id }}">
                            <x-filament::badge :color="$s->estado->getColor()" size="sm">{{ $s->estado->getLabel() }}</x-filament::badge>
                            <span class="nome">{{ $s->nome_sugerido }}</span>
                            <span style="opacity:.7">{{ $s->fonte->getLabel() }}@if ($s->data), {{ $s->data->format('d/m/Y') }}@endif</span>
                            @if ($s->estado === $this->estadoPendente())
                                <span style="margin-left:auto;display:flex;gap:.3rem">
                                    {{ ($this->aceitarSugestaoAction)(['sugestao' => $s->id]) }}
                                    {{ ($this->rejeitarSugestaoAction)(['sugestao' => $s->id]) }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @empty
                <x-filament::section>
                    Ainda não há números nesta fotografia. Clique numa cara para começar.
                </x-filament::section>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
