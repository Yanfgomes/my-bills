<div>
    <h1 class="text-[20px] font-semibold mb-5" style="color:var(--color-text)">
        {{ __('Overview financeiro') }}
    </h1>

    <div
        class="rounded-[10px] border p-5 mb-5 flex items-center justify-between flex-wrap gap-3"
        style="background-color:var(--color-card);border-color:var(--color-border)"
        wire:loading.attr="aria-busy"
        wire:target="mesAnterior,mesProximo"
    >
        <div class="flex items-center gap-3 flex-wrap">
            <button
                type="button"
                wire:click="mesAnterior"
                data-cy="overview-mes-anterior"
                aria-label="{{ __('Mes anterior') }}"
                class="focus-ring px-3 py-2 rounded-lg text-[13px] font-semibold border"
                style="border-color:var(--color-border);color:var(--color-text)"
            >
                &laquo; {{ __('Mes anterior') }}
            </button>

            <strong data-cy="overview-mes-atual" style="color:var(--color-text)">
                {{ $overview->mesReferencia }}
            </strong>

            <button
                type="button"
                wire:click="mesProximo"
                data-cy="overview-mes-proximo"
                aria-label="{{ __('Proximo mes') }}"
                class="focus-ring px-3 py-2 rounded-lg text-[13px] font-semibold border"
                style="border-color:var(--color-border);color:var(--color-text)"
            >
                {{ __('Proximo mes') }} &raquo;
            </button>
        </div>

        <span class="text-[12px]" style="color:var(--color-text-muted)">
            {{ __('Navegue entre meses para ver o historico.') }}
        </span>
    </div>

    <div
        class="rounded-[10px] border p-5"
        style="background-color:var(--color-card);border-color:var(--color-border)"
        aria-live="polite"
    >
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
                <div
                    data-cy="overview-alerta-percentual"
                    role="alert"
                    class="rounded-lg border px-3 py-3 text-[13px] mb-4"
                    style="background-color:var(--color-danger-bg);border-color:var(--color-danger-border);color:var(--color-danger)"
                >
                    {{ __('Atencao: as despesas superam a renda neste periodo.') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-lg border p-4" style="border-color:var(--color-border)">
                    <div class="text-[12px] mb-1" style="color:var(--color-text-muted)">{{ __('Renda total (mes)') }}</div>
                    <div data-cy="overview-renda-total" class="text-[18px] font-semibold" style="color:var(--color-text)">
                        R$ {{ number_format($overview->rendaTotal, 2, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-lg border p-4" style="border-color:var(--color-border)">
                    <div class="text-[12px] mb-1" style="color:var(--color-text-muted)">{{ __('Despesas totais (mes)') }}</div>
                    <div data-cy="overview-despesas-total" class="text-[18px] font-semibold" style="color:var(--color-text)">
                        R$ {{ number_format($overview->despesasTotal, 2, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-lg border p-4" style="border-color:var(--color-border)">
                    <div class="text-[12px] mb-1" style="color:var(--color-text-muted)">{{ __('Saldo disponivel (mes)') }}</div>
                    <div
                        data-cy="overview-saldo"
                        class="text-[18px] font-semibold"
                        style="color:{{ $overview->saldoDisponivel < 0 ? 'var(--color-danger)' : 'var(--color-success)' }}"
                    >
                        R$ {{ number_format($overview->saldoDisponivel, 2, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-lg border p-4" style="border-color:var(--color-border)">
                    <div class="text-[12px] mb-1" style="color:var(--color-text-muted)">{{ __('% da renda comprometido (mes)') }}</div>

                    @if ($overview->percentualComprometido === null)
                        <div data-cy="overview-sem-renda" class="text-[13px] font-semibold" style="color:var(--color-warn)">
                            {{ __('Sem renda cadastrada no periodo') }}
                        </div>
                    @else
                        <div
                            data-cy="overview-percentual"
                            class="text-[18px] font-semibold"
                            style="color:{{ $overview->alertaComprometimento ? 'var(--color-danger)' : 'var(--color-text)' }}"
                        >
                            {{ number_format($overview->percentualComprometido, 1, ',', '.') }}%
                        </div>
                        <div
                            class="mt-2 rounded-full overflow-hidden"
                            style="background-color:var(--color-border);height:8px"
                        >
                            <div
                                data-cy="overview-progress-fill"
                                class="h-full"
                                style="width:{{ min($overview->percentualComprometido, 100) }}%;background-color:{{ $overview->alertaComprometimento ? 'var(--color-danger)' : 'var(--color-success)' }}"
                            ></div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
