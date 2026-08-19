<div>
    <h1 class="text-[20px] font-semibold mb-5" style="color:var(--color-text)">
        {{ __('Minhas fontes de renda') }}
    </h1>

    <div
        class="rounded-[10px] border p-5 mb-5"
        style="background-color:var(--color-card);border-color:var(--color-border)"
    >
        @if ($rendas->isEmpty())
            <p
                data-cy="renda-vazio"
                class="rounded-lg border border-dashed px-3 py-3 text-[13px] text-center"
                style="color:var(--color-text-muted);border-color:var(--color-border)"
            >
                {{ __('Nenhuma fonte de renda cadastrada ainda. Adicione sua primeira fonte de renda abaixo.') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-[13px]" style="border-collapse:collapse">
                    <thead>
                        <tr>
                            <th scope="col" class="text-left py-2 px-2" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Descricao') }}</th>
                            <th scope="col" class="text-left py-2 px-2" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Valor liquido') }}</th>
                            <th scope="col" class="text-left py-2 px-2" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Mes/periodo') }}</th>
                        </tr>
                    </thead>
                    <tbody data-cy="renda-lista">
                        @foreach ($rendas as $fonte)
                            <tr wire:key="renda-{{ $fonte->id }}" data-cy="renda-linha-{{ $fonte->id }}">
                                <td class="py-2 px-2" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $fonte->descricao }}</td>
                                <td class="py-2 px-2" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">R$ {{ number_format($fonte->valor_liquido, 2, ',', '.') }}</td>
                                <td class="py-2 px-2" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $fonte->mes_referencia }}</td>
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
            {{ __('Nova fonte de renda') }}
        </h2>

        <form wire:submit="criar" novalidate>
            <div class="mb-3.5">
                <label for="renda-descricao" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
                    {{ __('Descricao') }}
                </label>
                <input
                    id="renda-descricao"
                    type="text"
                    wire:model="descricao"
                    data-cy="renda-descricao"
                    placeholder="{{ __('Ex: Salario, Freelance') }}"
                    aria-describedby="renda-descricao-error"
                    aria-invalid="{{ $errors->has('descricao') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="renda-descricao-error" aria-live="polite">
                    @error('descricao')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="renda-valor" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
                    {{ __('Valor liquido recebido (R$)') }}
                </label>
                <input
                    id="renda-valor"
                    type="number"
                    min="0.01"
                    step="0.01"
                    wire:model="valorLiquido"
                    data-cy="renda-valor"
                    placeholder="0,00"
                    aria-describedby="renda-valor-error"
                    aria-invalid="{{ $errors->has('valorLiquido') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="renda-valor-error" aria-live="polite">
                    @error('valorLiquido')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3.5">
                <label for="renda-mes" class="block text-[12px] font-semibold mb-1.5" style="color:var(--color-text)">
                    {{ __('Mes/periodo de referencia') }}
                </label>
                <input
                    id="renda-mes"
                    type="month"
                    wire:model="mesReferencia"
                    data-cy="renda-mes"
                    aria-describedby="renda-mes-error"
                    aria-invalid="{{ $errors->has('mesReferencia') ? 'true' : 'false' }}"
                    class="focus-ring w-full px-3 py-2.5 border rounded-lg text-[13px] outline-none"
                    style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                >
                <div id="renda-mes-error" aria-live="polite">
                    @error('mesReferencia')
                        <p class="mt-1 text-[12px]" style="color:var(--color-danger)">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button
                type="submit"
                data-cy="renda-submit"
                wire:loading.attr="aria-busy"
                wire:target="criar"
                class="focus-ring btn-primary-hover w-full mt-2 px-4 py-2.5 rounded-lg text-white text-[13px] font-semibold transition-colors"
                style="background-color:var(--color-primary)"
            >
                {{ __('Adicionar renda') }}
            </button>
        </form>
    </div>
</div>
