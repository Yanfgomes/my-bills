{{--
    Adaptado de reference/bioform-layout/app-shell.md (4.5 AppTopbar.vue). useBreadcrumb()
    (composable Vue) -> mapa PHP simples por rota atual, ja que este projeto tem so 4 telas
    autenticadas (sem hierarquia de breadcrumb real). Seletor de tema: adaptacao do card
    "Aparencia" (reference/bioform-layout/temas.md §9.6) para 2 estados (claro/escuro), em vez
    dos 3 temas do BioForm — este projeto so declara suporte_tema = claro_escuro. RF-PADRAO-
    CONFIGURACOES: o botao de tema agora e o componente Livewire TemaToggleTopbar (persistencia
    por usuario via ConfiguracaoUsuario), nao mais Alpine-only + localStorage.
--}}
@php
    $breadcrumbLabels = [
        'overview' => __('Overview'),
        'renda.index' => __('Renda'),
        'despesas.index' => __('Despesas'),
        'configuracoes.show' => __('Configuracoes'),
    ];
    $currentLabel = collect($breadcrumbLabels)->first(fn ($label, $route) => request()->routeIs($route)) ?? __('Overview');
@endphp
<header
    class="h-[52px] border-b px-4 flex items-center justify-between sticky top-0 z-40"
    style="background-color:var(--color-card);border-color:var(--color-border)"
>
    <div class="flex items-center gap-3">
        <button
            type="button"
            data-cy="btn-topbar-menu"
            aria-label="{{ __('Alternar menu') }}"
            class="focus-ring w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
            style="color:var(--color-text-muted)"
            @click="$store.sidebar.toggle()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <nav aria-label="{{ __('Trilha de navegacao') }}">
            <span class="text-[12.5px] font-bold" style="color:var(--color-text)">{{ $currentLabel }}</span>
        </nav>
    </div>

    <div class="flex items-center gap-2.5">
        <livewire:layout.tema-toggle-topbar />
    </div>
</header>
