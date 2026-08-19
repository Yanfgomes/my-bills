<div>
    <h1 class="text-[20px] font-semibold mb-5" style="color:var(--color-text)">
        {{ __('Minhas despesas') }}
    </h1>

    <div
        class="rounded-[10px] border p-5 mb-5"
        style="background-color:var(--color-card);border-color:var(--color-border)"
    >
        @if ($despesas->isEmpty())
            <p
                data-cy="despesa-vazio"
                class="rounded-lg border border-dashed px-3 py-3 text-[13px] text-center"
                style="color:var(--color-text-muted);border-color:var(--color-border)"
            >
                {{ __('Nenhuma despesa cadastrada ainda. Adicione sua primeira despesa abaixo.') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-[13px]" style="border-collapse:collapse">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left py-2 px-2" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Descricao') }}</th>
                            <th scope="col" class="text-left py-2 px-2" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Valor') }}</th>
                            <th scope="col" class="text-left py-2 px-2" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Categoria') }}</th>
                            <th scope="col" class="text-left py-2 px-2" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Mes/periodo') }}</th>
                        </tr>
                    </thead>
                    <tbody data-cy="despesa-lista">
                        @foreach ($despesas as $despesa)
                            <tr wire:key="despesa-{{ $despesa->id }}" data-cy="despesa-linha-{{ $despesa->id }}">
                                <td class="py-2 px-2" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $despesa->descricao }}</td>
                                <td class="py-2 px-2" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">R$ {{ number_format($despesa->valor, 2, ',', '.') }}</td>
                                <td class="py-2 px-2" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $despesa->categoria ?? '—' }}</td>
                                <td class="py-2 px-2" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $despesa->mes_referencia }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div
        class="rounded-[10px] border p-5 max-w-md"
        style="background-color:var(--color-card);border-color:var(--color-border)"
    >
        <h2 class="text-[16px] font-semibold mb-4" style="color:var(--color-text)">
            {{ __('Nova despesa') }}
        </h2>

        <form wire:submit="criar" novalidate>
            <div class="mb-3.5">
                <label for="despesa-descricao" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
                    {{ __('Descricao') }}
                </label>
                <input
                    id="despesa-descricao"
                    type="text"
                    wire:model="descricao"
                    data-cy="despesa-descricao"
                    placeholder="{{ __('Ex: Aluguel, Mercado') }}"
                    aria-describedby="despesa-descricao-error"
                    aria-invalid="{{ $errors->has('descricao') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="despesa-descricao-error" aria-live="polite">
                    @error('descricao')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="despesa-valor" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
                    {{ __('Valor (R$)') }}
                </label>
                <input
                    id="despesa-valor"
                    type="number"
                    min="0.01"
                    step="0.01"
                    wire:model="valor"
                    data-cy="despesa-valor"
                    placeholder="0,00"
                    aria-describedby="despesa-valor-error"
                    aria-invalid="{{ $errors->has('valor') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="despesa-valor-error" aria-live="polite">
                    @error('valor')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="despesa-categoria" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
                    {{ __('Categoria (opcional)') }}
                </label>
                <input
                    id="despesa-categoria"
                    type="text"
                    wire:model="categoria"
                    data-cy="despesa-categoria"
                    placeholder="{{ __('Ex: Moradia, Alimentacao') }}"
                    aria-describedby="despesa-categoria-error"
                    aria-invalid="{{ $errors->has('categoria') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="despesa-categoria-error" aria-live="polite">
                    @error('categoria')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="despesa-mes" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
                    {{ __('Mes/periodo de referencia') }}
                </label>
                <input
                    id="despesa-mes"
                    type="month"
                    wire:model="mesReferencia"
                    data-cy="despesa-mes"
                    aria-describedby="despesa-mes-error"
                    aria-invalid="{{ $errors->has('mesReferencia') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="despesa-mes-error" aria-live="polite">
                    @error('mesReferencia')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button
                type="submit"
                data-cy="despesa-submit"
                wire:loading.attr="aria-busy"
                wire:target="criar"
                class="focus-ring btn-primary-hover w-full mt-2 px-4 py-2.5 rounded-lg text-white text-[13px] font-semibold transition-colors"
                style="background-color:var(--color-primary)"
            >
                {{ __('Adicionar despesa') }}
            </button>
        </form>
    </div>
</div>
