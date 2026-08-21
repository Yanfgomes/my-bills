{{--
    TELA-006 (RF-PADRAO-CONFIGURACOES). Adaptado do card unico ja aprovado em
    prototipo/index.html (#screen-config) -- nao o layout de 4 cards com auto-apply de
    reference/bioform-layout/configuracoes.md, que conflitaria com o botao "Salvar
    preferencias" ja validado com o usuario (layout-guidelines.md: o prototipo aprovado e a
    especificacao de tela, nao redesenhada). Estrutura (PageHero + CardBase, form-field.md) e
    tokens (tokens-and-setup.md) seguem a mesma base do resto do projeto (ver
    renda-manager.blade.php).
--}}
<div>
    <x-page-hero
        :title="__('Configuracoes do sistema')"
        :subtitle="__('Preferencias do usuario autenticado.')"
    />

    <div
        x-data
        x-init="
            if (@js($recemCriado)) {
                $wire.aplicarPreferenciaSistema(window.matchMedia('(prefers-reduced-motion: reduce)').matches);
            }
        "
    >
        <div class="max-w-xl">
            <x-card-base>
                <form wire:submit="salvar" novalidate>
                    <div class="mb-4">
                        <label for="config-idioma" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                            {{ __('Idioma') }}
                        </label>
                        <select
                            id="config-idioma"
                            wire:model="idioma"
                            data-cy="config-idioma"
                            aria-describedby="config-idioma-error"
                            class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                            style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                        >
                            <option value="pt">{{ __('Portugues') }}</option>
                            <option value="en">{{ __('English') }}</option>
                            <option value="es">{{ __('Espanol') }}</option>
                        </select>
                        <div id="config-idioma-error" aria-live="polite">
                            @error('idioma')
                                <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <span id="config-tema-label" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                            {{ __('Tema') }}
                        </span>
                        <div role="group" aria-labelledby="config-tema-label" class="flex gap-2.5">
                            <button
                                type="button"
                                wire:click="$set('tema', 'claro')"
                                data-cy="config-tema-claro"
                                aria-pressed="{{ $tema === 'claro' ? 'true' : 'false' }}"
                                class="focus-ring flex-1 px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] font-semibold transition-colors"
                                style="{{ $tema === 'claro' ? 'border-color:var(--color-primary);color:var(--color-primary);background-color:var(--color-primary-soft)' : 'border-color:var(--color-border);color:var(--color-text-muted)' }}"
                            >
                                {{ __('Claro') }}
                            </button>
                            <button
                                type="button"
                                wire:click="$set('tema', 'escuro')"
                                data-cy="config-tema-escuro"
                                aria-pressed="{{ $tema === 'escuro' ? 'true' : 'false' }}"
                                class="focus-ring flex-1 px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] font-semibold transition-colors"
                                style="{{ $tema === 'escuro' ? 'border-color:var(--color-primary);color:var(--color-primary);background-color:var(--color-primary-soft)' : 'border-color:var(--color-border);color:var(--color-text-muted)' }}"
                            >
                                {{ __('Escuro') }}
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="config-fonte" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                            {{ __('Tamanho de fonte') }}
                        </label>
                        <select
                            id="config-fonte"
                            wire:model="tamanhoFonte"
                            data-cy="config-fonte"
                            class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                            style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                        >
                            <option value="pequeno">{{ __('Pequena') }}</option>
                            <option value="medio">{{ __('Media') }}</option>
                            <option value="grande">{{ __('Grande') }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between gap-3 py-3 border-t" style="border-color:var(--color-border)">
                        <div>
                            <p class="text-[0.8125rem] font-semibold" style="color:var(--color-text)">{{ __('Alto contraste') }}</p>
                            <p class="text-[0.71875rem] mt-0.5" style="color:var(--color-text-muted)">{{ __('Aumenta o contraste de cores da interface.') }}</p>
                        </div>
                        <label class="relative inline-flex items-center flex-shrink-0" for="config-alto-contraste">
                            <span class="sr-only">{{ __('Alto contraste') }}: {{ $altoContraste ? __('Ativado') : __('Desativado') }}</span>
                            <input
                                type="checkbox"
                                id="config-alto-contraste"
                                role="switch"
                                wire:model="altoContraste"
                                data-cy="config-alto-contraste"
                                class="focus-ring w-5 h-5"
                            >
                        </label>
                    </div>

                    <div class="flex items-center justify-between gap-3 py-3 border-t" style="border-color:var(--color-border)">
                        <div>
                            <p class="text-[0.8125rem] font-semibold" style="color:var(--color-text)">{{ __('Reducao de movimento') }}</p>
                            <p class="text-[0.71875rem] mt-0.5" style="color:var(--color-text-muted)">{{ __('Desativa ou reduz animacoes e transicoes nao essenciais. O valor inicial reflete a preferencia do seu sistema operacional.') }}</p>
                        </div>
                        <label class="relative inline-flex items-center flex-shrink-0" for="config-reducao-movimento">
                            <span class="sr-only">{{ __('Reducao de movimento') }}: {{ $reducaoMovimento ? __('Ativado') : __('Desativado') }}</span>
                            <input
                                type="checkbox"
                                id="config-reducao-movimento"
                                role="switch"
                                wire:model="reducaoMovimento"
                                data-cy="config-reducao-movimento"
                                class="focus-ring w-5 h-5"
                            >
                        </label>
                    </div>

                    <p class="text-[0.71875rem] mt-3" style="color:var(--color-text-muted)">
                        {{ __('Foco visivel em todo elemento interativo e garantido em toda a interface por padrao — nao e um controle separado nesta tela.') }}
                    </p>

                    <button
                        type="submit"
                        data-cy="config-salvar"
                        wire:loading.attr="aria-busy"
                        wire:target="salvar"
                        class="focus-ring btn-primary-hover w-full mt-4 px-4 py-2.5 rounded-lg text-[0.8125rem] font-semibold transition-colors"
                        style="background-color:var(--color-primary);color:var(--color-on-primary)"
                    >
                        {{ __('Salvar preferencias') }}
                    </button>

                    <div aria-live="polite" class="mt-3">
                        @if ($salvo)
                            <p
                                data-cy="config-salvo-confirmacao"
                                class="text-[0.75rem] font-semibold text-center"
                                style="color:var(--color-success)"
                            >
                                {{ __('Preferencias salvas com sucesso.') }}
                            </p>
                        @endif
                    </div>
                </form>
            </x-card-base>
        </div>
    </div>
</div>
