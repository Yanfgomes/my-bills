{{--
    Adaptado dos templates "Listagem" (reference/bioform-layout/data-table.md §6.1) e
    "Formulario" (reference/bioform-layout/form-field.md §6.2) — mesmo padrao de
    livewire/renda/renda-manager.blade.php. Todos os data-cy originais preservados.

    RF-007: coluna "Acoes" (editar/excluir por linha, sem modal de confirmacao — TELA-005/
    prototipo) e formulario unico alternando entre modo criacao/edicao via $editandoId
    (mes/periodo desabilitado em edicao, RN-008).

    ACC-RF-008-01 (debito transversal registrado em specs/estado-rf-RF-008.json): classes
    text-[**px] convertidas para rem (unidade relativa, fonte_configuravel = true) neste
    arquivo — mesma correcao ja aplicada em renda-manager.blade.php/overview-financeiro.blade.php.
--}}
<div>
    <x-page-hero :title="__('Minhas despesas')" />

    <x-card-base class="mb-5">
        @if ($despesas->isEmpty())
            <p
                data-cy="despesa-vazio"
                class="rounded-lg border border-dashed px-3 py-3 text-[0.8125rem] text-center"
                style="color:var(--color-text-muted);border-color:var(--color-border)"
            >
                {{ __('Nenhuma despesa cadastrada ainda. Adicione sua primeira despesa abaixo.') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="app-table w-full text-[0.78125rem]" style="border-collapse:collapse">
                    <caption class="sr-only">{{ __('Minhas despesas') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Descricao') }}</th>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Valor') }}</th>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Categoria') }}</th>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Mes/periodo') }}</th>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Acoes') }}</th>
                        </tr>
                    </thead>
                    <tbody data-cy="despesa-lista">
                        @foreach ($despesas as $despesa)
                            <tr wire:key="despesa-{{ $despesa->id }}" data-cy="despesa-linha-{{ $despesa->id }}" class="transition-colors">
                                <td class="py-3 px-3.5" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $despesa->descricao }}</td>
                                <td class="py-3 px-3.5 tabular-nums" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">R$ {{ number_format($despesa->valor, 2, ',', '.') }}</td>
                                <td class="py-3 px-3.5" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $despesa->categoria ?? '—' }}</td>
                                <td class="py-3 px-3.5" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $despesa->mes_referencia }}</td>
                                <td class="py-2 px-3.5" style="border-bottom:1px solid var(--color-border)">
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            wire:click="editar('{{ $despesa->id }}')"
                                            data-cy="despesa-editar-{{ $despesa->id }}"
                                            aria-label="{{ __('Editar :descricao', ['descricao' => $despesa->descricao]) }}"
                                            class="focus-ring px-2.5 py-1.5 border-[1.5px] rounded-lg text-[0.6875rem] font-semibold transition-colors"
                                            style="border-color:var(--color-border);color:var(--color-text-muted)"
                                        >
                                            {{ __('Editar') }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="excluir('{{ $despesa->id }}')"
                                            data-cy="despesa-excluir-{{ $despesa->id }}"
                                            aria-label="{{ __('Excluir :descricao', ['descricao' => $despesa->descricao]) }}"
                                            class="focus-ring px-2.5 py-1.5 border-[1.5px] rounded-lg text-[0.6875rem] font-semibold transition-colors"
                                            style="border-color:var(--color-danger-border);color:var(--color-danger)"
                                        >
                                            {{ __('Excluir') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card-base>

    <div class="max-w-md">
        <x-card-base :title="$editandoId ? __('Editar despesa') : __('Nova despesa')">
            <form wire:submit="{{ $editandoId ? 'atualizar' : 'criar' }}" novalidate>
                <div class="mb-3.5">
                    <label for="despesa-descricao" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
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
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                    <div id="despesa-descricao-error" aria-live="polite">
                        @error('descricao')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-3.5">
                    <label for="despesa-valor" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
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
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                    <div id="despesa-valor-error" aria-live="polite">
                        @error('valor')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-3.5">
                    <label for="despesa-categoria" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
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
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                    <div id="despesa-categoria-error" aria-live="polite">
                        @error('categoria')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-3.5">
                    <label for="despesa-mes" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                        {{ __('Mes/periodo de referencia') }}
                    </label>
                    <input
                        id="despesa-mes"
                        type="month"
                        wire:model="mesReferencia"
                        data-cy="despesa-mes"
                        @disabled($editandoId)
                        aria-describedby="despesa-mes-error despesa-mes-nota"
                        aria-invalid="{{ $errors->has('mesReferencia') ? 'true' : 'false' }}"
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors disabled:opacity-60"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                    <div id="despesa-mes-error" aria-live="polite">
                        @error('mesReferencia')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                    @if ($editandoId)
                        <p id="despesa-mes-nota" data-cy="despesa-mes-nota" class="mt-1.5 text-[0.6875rem]" style="color:var(--color-text-muted)">
                            {{ __('Mes/periodo nao pode ser alterado apos a criacao (RN-008). Para mudar de mes, exclua e recrie o lancamento.') }}
                        </p>
                    @endif
                </div>

                <button
                    type="submit"
                    data-cy="despesa-submit"
                    wire:loading.attr="aria-busy"
                    wire:target="criar,atualizar"
                    class="focus-ring btn-primary-hover w-full mt-2 px-4 py-2.5 rounded-lg text-[0.8125rem] font-semibold transition-colors"
                    style="background-color:var(--color-primary);color:var(--color-on-primary)"
                >
                    {{ $editandoId ? __('Salvar alteracoes') : __('Adicionar despesa') }}
                </button>

                @if ($editandoId)
                    <button
                        type="button"
                        wire:click="cancelarEdicao"
                        data-cy="despesa-cancelar"
                        class="focus-ring w-full mt-2.5 px-4 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] font-semibold transition-colors"
                        style="border-color:var(--color-border);color:var(--color-text-muted)"
                    >
                        {{ __('Cancelar edicao') }}
                    </button>
                @endif
            </form>
        </x-card-base>
    </div>
</div>
