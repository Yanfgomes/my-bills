{{--
    Adaptado de reference/bioform-layout/dashboard-donut.md (5.8 DashboardDonutCard.vue, versao
    simplificada para um unico indicador de 2 segmentos — comprometido/disponivel — em vez do
    componente generico N-segmentos do fragmento) e reference/bioform-layout/page-hero-and-cards.md
    (PageHero/CardBase/AlertBanner). Navegador de mes e cards de resumo em CardBase; a antiga
    barra de progresso linear vira o anel donut, mantendo o data-cy original
    (overview-progress-fill) no arco preenchido. Todos os data-cy/aria-live/aria-busy originais
    preservados.
--}}
@php
    $percentual = $overview->percentualComprometido;
    $pctParaAnel = $percentual === null ? 0 : min($percentual, 100);
    $raio = 60;
    $circunferencia = 2 * M_PI * $raio;
    $dashPreenchido = ($pctParaAnel / 100) * $circunferencia;
    $corAnel = $overview->alertaComprometimento ? 'var(--color-danger)' : 'var(--color-success)';
@endphp
<div>
    <x-page-hero :title="__('Overview financeiro')" />

    <x-card-base class="mb-5">
        <div
            class="flex items-center justify-between flex-wrap gap-3"
            wire:loading.attr="aria-busy"
            wire:target="mesAnterior,mesProximo"
        >
            <div class="flex items-center gap-3 flex-wrap">
                <button
                    type="button"
                    wire:click="mesAnterior"
                    data-cy="overview-mes-anterior"
                    aria-label="{{ __('Mes anterior') }}"
                    class="focus-ring px-3 py-2 rounded-lg text-[12px] font-semibold border transition-colors"
                    style="border-color:var(--color-border);color:var(--color-text)"
                >
                    &laquo; {{ __('Mes anterior') }}
                </button>

                <strong data-cy="overview-mes-atual" class="text-[13px]" style="color:var(--color-text)">
                    {{ $overview->mesReferencia }}
                </strong>

                <button
                    type="button"
                    wire:click="mesProximo"
                    data-cy="overview-mes-proximo"
                    aria-label="{{ __('Proximo mes') }}"
                    class="focus-ring px-3 py-2 rounded-lg text-[12px] font-semibold border transition-colors"
                    style="border-color:var(--color-border);color:var(--color-text)"
                >
                    {{ __('Proximo mes') }} &raquo;
                </button>
            </div>

            <span class="text-[11.5px]" style="color:var(--color-text-muted)">
                {{ __('Navegue entre meses para ver o historico.') }}
            </span>
        </div>
    </x-card-base>

    <x-card-base aria-live="polite">
        @if ($overview->estaVazio())
            <p
                data-cy="overview-vazio"
                class="rounded-lg border border-dashed px-3 py-3 text-[13px] text-center"
                style="color:var(--color-text-muted);border-color:var(--color-border)"
            >
                {{ __('Nenhuma renda ou despesa cadastrada para este mes. Cadastre sua renda e suas despesas para ver o overview deste periodo.') }}
            </p>
        @else
            @if ($overview->alertaComprometimento)
                <x-alert-banner type="error" data-cy="overview-alerta-percentual">
                    {{ __('Atencao: as despesas superam a renda neste periodo.') }}
                </x-alert-banner>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[repeat(3,1fr)_280px] gap-4">
                <div class="rounded-lg border p-4" style="border-color:var(--color-border)">
                    <div class="text-[11px] mb-1" style="color:var(--color-text-muted)">{{ __('Renda total (mes)') }}</div>
                    <div data-cy="overview-renda-total" class="text-[16px] font-bold tabular-nums" style="color:var(--color-text)">
                        R$ {{ number_format($overview->rendaTotal, 2, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-lg border p-4" style="border-color:var(--color-border)">
                    <div class="text-[11px] mb-1" style="color:var(--color-text-muted)">{{ __('Despesas totais (mes)') }}</div>
                    <div data-cy="overview-despesas-total" class="text-[16px] font-bold tabular-nums" style="color:var(--color-text)">
                        R$ {{ number_format($overview->despesasTotal, 2, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-lg border p-4" style="border-color:var(--color-border)">
                    <div class="text-[11px] mb-1" style="color:var(--color-text-muted)">{{ __('Saldo disponivel (mes)') }}</div>
                    <div
                        data-cy="overview-saldo"
                        class="text-[16px] font-bold tabular-nums"
                        style="color:{{ $overview->saldoDisponivel < 0 ? 'var(--color-danger)' : 'var(--color-success)' }}"
                    >
                        R$ {{ number_format($overview->saldoDisponivel, 2, ',', '.') }}
                    </div>
                </div>

                {{-- Indicador donut (adaptado de DashboardDonutCard, ver comentario do topo do arquivo) --}}
                <div class="rounded-lg border p-4 flex flex-col items-center" style="border-color:var(--color-border)">
                    <div class="text-[11px] mb-2 self-start" style="color:var(--color-text-muted)">{{ __('% da renda comprometido (mes)') }}</div>

                    @if ($percentual === null)
                        <div data-cy="overview-sem-renda" class="text-[12.5px] font-semibold text-center py-6" style="color:var(--color-warn)">
                            {{ __('Sem renda cadastrada no periodo') }}
                        </div>
                    @else
                        <svg width="120" height="120" viewBox="0 0 140 140" role="img" aria-label="{{ number_format($percentual, 1, ',', '.') }}%">
                            <circle cx="70" cy="70" r="{{ $raio }}" fill="none" stroke="var(--color-border)" stroke-width="14" />
                            <circle
                                data-cy="overview-progress-fill"
                                cx="70" cy="70" r="{{ $raio }}" fill="none"
                                stroke="{{ $corAnel }}"
                                stroke-width="14"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $dashPreenchido }} {{ $circunferencia }}"
                                transform="rotate(-90 70 70)"
                            />
                            <text x="70" y="65" text-anchor="middle" font-size="20" font-weight="700" fill="{{ $overview->alertaComprometimento ? 'var(--color-danger)' : 'var(--color-text)' }}">
                                {{ number_format($percentual, 1, ',', '.') }}%
                            </text>
                            <text x="70" y="83" text-anchor="middle" font-size="10" fill="var(--color-text-muted)">{{ __('comprometido') }}</text>
                        </svg>
                        <span data-cy="overview-percentual" class="sr-only">{{ number_format($percentual, 1, ',', '.') }}%</span>

                        <ul class="mt-2 w-full space-y-1">
                            <li class="flex items-center gap-2 text-[11px]" style="color:var(--color-text-muted)">
                                <span class="w-2 h-2 rounded-sm flex-shrink-0" style="background-color:{{ $corAnel }}"></span>
                                {{ __('Comprometido') }}
                            </li>
                            <li class="flex items-center gap-2 text-[11px]" style="color:var(--color-text-muted)">
                                <span class="w-2 h-2 rounded-sm flex-shrink-0" style="background-color:var(--color-border)"></span>
                                {{ __('Disponivel') }}
                            </li>
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </x-card-base>
</div>
