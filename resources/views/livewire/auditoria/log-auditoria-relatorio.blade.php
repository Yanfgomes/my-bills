{{--
    TELA-007 (RF-PADRAO-LOG-AUDITORIA). Adaptado do card unico ja aprovado em
    prototipo/index.html (#screen-auditoria) -- mesma estrutura (PageHero + CardBase,
    data-table.md/form-field.md) e tokens (tokens-and-setup.md) do resto do projeto (ver
    despesa-manager.blade.php). Somente leitura: nenhum wire:click deste arquivo altera ou
    exclui um registro de log (criterio_aceite).

    Foco preso no modal De/Para: implementado em JS puro (sem depender de plugin Alpine
    focus/trap nao confirmado no bundle do Livewire desta stack) -- ver <script> ao final,
    disparado pelos eventos 'auditoria-detalhe-aberto'/'auditoria-detalhe-fechado'
    (LogAuditoriaRelatorio::verDetalhe()/fecharDetalhe()).
--}}
<div>
    <x-page-hero
        :title="__('Relatorio de log de auditoria')"
        :subtitle="__('Consulta somente leitura ao seu proprio historico de acoes (RN-010).')"
    />

    <x-card-base class="mb-5">
        <p class="text-[0.78125rem] mb-4" style="color:var(--color-text-muted)">
            {{ __('Escopo self-audit: exibe apenas as acoes do proprio usuario autenticado -- nao ha filtro por usuario porque nao ha o que filtrar entre usuarios.') }}
        </p>

        <form wire:submit="filtrar" novalidate>
            <div class="grid gap-3.5" style="grid-template-columns:repeat(auto-fit, minmax(9.5rem, 1fr))">
                <div>
                    <label for="aud-filtro-tabela" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                        {{ __('Tabela/entidade') }}
                    </label>
                    <select
                        id="aud-filtro-tabela"
                        wire:model="tabelaAfetada"
                        data-cy="aud-filtro-tabela"
                        aria-describedby="aud-filtro-tabela-error"
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                        <option value="">{{ __('Todas') }}</option>
                        <option value="fontes_renda">fontes_renda</option>
                        <option value="despesas">despesas</option>
                        <option value="users">users</option>
                        <option value="configuracoes_usuario">configuracoes_usuario</option>
                    </select>
                    <div id="aud-filtro-tabela-error" aria-live="polite">
                        @error('tabelaAfetada')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="aud-filtro-acao" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                        {{ __('Acao') }}
                    </label>
                    <select
                        id="aud-filtro-acao"
                        wire:model="acao"
                        data-cy="aud-filtro-acao"
                        aria-describedby="aud-filtro-acao-error"
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                        <option value="">{{ __('Todas') }}</option>
                        <option value="criacao">{{ __('Criacao') }}</option>
                        <option value="alteracao">{{ __('Alteracao') }}</option>
                        <option value="exclusao">{{ __('Exclusao') }}</option>
                    </select>
                    <div id="aud-filtro-acao-error" aria-live="polite">
                        @error('acao')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="aud-filtro-inicio" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                        {{ __('Periodo (inicio)') }}
                    </label>
                    <input
                        id="aud-filtro-inicio"
                        type="date"
                        wire:model="periodoInicio"
                        data-cy="aud-filtro-inicio"
                        aria-describedby="aud-filtro-inicio-error"
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                    <div id="aud-filtro-inicio-error" aria-live="polite">
                        @error('periodoInicio')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="aud-filtro-fim" class="block text-[0.71875rem] font-semibold mb-1.5" style="color:var(--color-text-muted)">
                        {{ __('Periodo (fim)') }}
                    </label>
                    <input
                        id="aud-filtro-fim"
                        type="date"
                        wire:model="periodoFim"
                        data-cy="aud-filtro-fim"
                        aria-describedby="aud-filtro-fim-error"
                        class="focus-ring w-full px-3 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] outline-none transition-colors"
                        style="border-color:var(--color-border);background-color:var(--color-card);color:var(--color-text)"
                    >
                    <div id="aud-filtro-fim-error" aria-live="polite">
                        @error('periodoFim')
                            <p class="mt-1 text-[0.6875rem]" style="color:var(--color-danger)">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        data-cy="aud-filtrar"
                        wire:loading.attr="aria-busy"
                        wire:target="filtrar"
                        class="focus-ring btn-primary-hover w-full px-4 py-2.5 rounded-lg text-[0.8125rem] font-semibold transition-colors"
                        style="background-color:var(--color-primary);color:var(--color-on-primary)"
                    >
                        {{ __('Filtrar') }}
                    </button>
                </div>
            </div>
        </form>
    </x-card-base>

    {{--
        Resultado da consulta anunciado a leitor de tela sempre que o filtro e aplicado
        (criterio_aceite_acessibilidade) -- nao so atualizado visualmente.
    --}}
    <p aria-live="polite" class="sr-only">
        @if ($logs->isEmpty())
            {{ __('Nenhum registro de log encontrado para os filtros informados.') }}
        @else
            {{ __(':total registros de log encontrados.', ['total' => $logs->total()]) }}
        @endif
    </p>

    <x-card-base>
        @if ($logs->isEmpty())
            <p
                data-cy="aud-vazio"
                class="rounded-lg border border-dashed px-3 py-3 text-[0.8125rem] text-center"
                style="color:var(--color-text-muted);border-color:var(--color-border)"
            >
                {{ __('Nenhum registro de log encontrado para os filtros informados.') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="app-table w-full text-[0.78125rem]" style="border-collapse:collapse" data-cy="aud-tabela">
                    <caption class="sr-only">{{ __('Relatorio de log de auditoria') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Acao') }}</th>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Tabela/entidade') }}</th>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Data/hora') }}</th>
                            <th scope="col" class="text-left py-2.5 px-3.5 text-[0.65625rem] font-bold" style="color:var(--color-text-muted);border-bottom:1px solid var(--color-border)">{{ __('Detalhe') }}</th>
                        </tr>
                    </thead>
                    <tbody data-cy="aud-lista">
                        @foreach ($logs as $log)
                            <tr wire:key="aud-log-{{ $log->id }}" data-cy="aud-linha-{{ $log->id }}">
                                <td class="py-3 px-3.5" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">
                                    {{ match ($log->acao) {
                                        'criacao' => __('Criacao'),
                                        'alteracao' => __('Alteracao'),
                                        'exclusao' => __('Exclusao'),
                                        default => $log->acao,
                                    } }}
                                </td>
                                <td class="py-3 px-3.5" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $log->tabela_afetada }}</td>
                                <td class="py-3 px-3.5 tabular-nums" style="border-bottom:1px solid var(--color-border);color:var(--color-text)">{{ $log->criado_em->format('d/m/Y H:i') }}</td>
                                <td class="py-2 px-3.5" style="border-bottom:1px solid var(--color-border)">
                                    <button
                                        type="button"
                                        wire:click="verDetalhe({{ $log->id }})"
                                        onclick="window.__auditoriaFocoOrigem = this"
                                        data-cy="aud-ver-detalhe-{{ $log->id }}"
                                        aria-label="{{ __('Ver De/Para do registro de :acao em :tabela', ['acao' => $log->acao, 'tabela' => $log->tabela_afetada]) }}"
                                        class="focus-ring px-2.5 py-1.5 border-[1.5px] rounded-lg text-[0.6875rem] font-semibold transition-colors"
                                        style="border-color:var(--color-border);color:var(--color-text-muted)"
                                    >
                                        {{ __('Ver De/Para') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->onEachSide(1)->links() }}
            </div>
        @endif
    </x-card-base>

    @if ($detalhe)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" data-cy="aud-modal">
            <div class="absolute inset-0" style="background-color:rgb(0 0 0 / 0.4)" aria-hidden="true" wire:click="fecharDetalhe"></div>

            <div
                id="aud-modal-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="aud-modal-titulo"
                tabindex="-1"
                class="relative rounded-xl shadow-xl w-full max-w-lg p-5"
                style="background-color:var(--color-card);color:var(--color-text)"
            >
                <h2 id="aud-modal-titulo" class="text-[0.9375rem] font-bold">{{ __('Detalhe do registro (De/Para)') }}</h2>
                <p class="text-[0.78125rem] mt-1.5" style="color:var(--color-text-muted)" id="aud-modal-meta">
                    {{ match ($detalhe->acao) {
                        'criacao' => __('Criacao'),
                        'alteracao' => __('Alteracao'),
                        'exclusao' => __('Exclusao'),
                        default => $detalhe->acao,
                    } }}
                    &mdash; {{ $detalhe->tabela_afetada }}
                    &mdash; {{ $detalhe->criado_em->format('d/m/Y H:i') }}
                </p>

                <div class="grid gap-4 mt-4" style="grid-template-columns:repeat(auto-fit, minmax(11rem, 1fr))">
                    <div>
                        <h3 class="text-[0.75rem] font-bold mb-1.5" style="color:var(--color-text-muted)">{{ __('Valor anterior') }}</h3>
                        <pre data-cy="aud-modal-anterior" class="text-[0.71875rem] p-3 rounded-lg overflow-x-auto" style="background-color:var(--color-bg);border:1px solid var(--color-border)">{{ $detalhe->valor_anterior ? json_encode($detalhe->valor_anterior, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : __('(nao se aplica)') }}</pre>
                    </div>
                    <div>
                        <h3 class="text-[0.75rem] font-bold mb-1.5" style="color:var(--color-text-muted)">{{ __('Valor novo') }}</h3>
                        <pre data-cy="aud-modal-novo" class="text-[0.71875rem] p-3 rounded-lg overflow-x-auto" style="background-color:var(--color-bg);border:1px solid var(--color-border)">{{ $detalhe->valor_novo ? json_encode($detalhe->valor_novo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : __('(nao se aplica)') }}</pre>
                    </div>
                </div>

                <button
                    type="button"
                    id="aud-modal-fechar-btn"
                    wire:click="fecharDetalhe"
                    onclick="window.__auditoriaFocoOrigem && window.__auditoriaFocoOrigem.focus()"
                    data-cy="aud-modal-fechar"
                    class="focus-ring mt-5 px-4 py-2.5 border-[1.5px] rounded-lg text-[0.8125rem] font-semibold transition-colors"
                    style="border-color:var(--color-border);color:var(--color-text-muted)"
                >
                    {{ __('Fechar') }}
                </button>
            </div>
        </div>
    @endif

    {{--
        Foco preso no modal De/Para (criterio_aceite_acessibilidade): sem depender de plugin
        Alpine focus/trap -- implementacao vanilla, mesmo padrao de <script> isolado por
        evento Livewire ja usado em layouts/app.blade.php (listener 'configuracao-atualizada').
        Guard window.__auditoriaModalInit evita registrar o listener mais de uma vez caso este
        componente seja hidratado novamente (ex: navegacao SPA-like do Livewire).
    --}}
    <script>
        if (!window.__auditoriaModalInit) {
            window.__auditoriaModalInit = true;

            document.addEventListener('livewire:init', () => {
                let handlerTeclado = null;

                function focaveis(container) {
                    return Array.from(container.querySelectorAll(
                        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    ));
                }

                Livewire.on('auditoria-detalhe-aberto', () => {
                    // O modal acabou de entrar no DOM (Livewire ja processou o re-render antes
                    // de disparar o evento) -- proximo frame garante que o elemento existe.
                    requestAnimationFrame(() => {
                        const dialog = document.getElementById('aud-modal-dialog');
                        if (!dialog) return;

                        const alvo = focaveis(dialog)[0] || dialog;
                        alvo.focus();

                        handlerTeclado = function (ev) {
                            if (ev.key === 'Escape') {
                                ev.preventDefault();
                                // Reaproveita o botao "Fechar" ja ligado a fecharDetalhe() via
                                // wire:click -- evita duplicar a chamada ao componente Livewire.
                                const botaoFechar = document.getElementById('aud-modal-fechar-btn');
                                if (botaoFechar) botaoFechar.click();
                                return;
                            }
                            if (ev.key !== 'Tab') return;
                            const itens = focaveis(dialog);
                            if (itens.length === 0) return;
                            const primeiro = itens[0];
                            const ultimo = itens[itens.length - 1];
                            if (ev.shiftKey && document.activeElement === primeiro) {
                                ev.preventDefault();
                                ultimo.focus();
                            } else if (!ev.shiftKey && document.activeElement === ultimo) {
                                ev.preventDefault();
                                primeiro.focus();
                            }
                        };
                        document.addEventListener('keydown', handlerTeclado);
                    });
                });

                Livewire.on('auditoria-detalhe-fechado', () => {
                    if (handlerTeclado) {
                        document.removeEventListener('keydown', handlerTeclado);
                        handlerTeclado = null;
                    }
                    if (window.__auditoriaFocoOrigem) {
                        window.__auditoriaFocoOrigem.focus();
                        window.__auditoriaFocoOrigem = null;
                    }
                });
            });
        }
    </script>
</div>
