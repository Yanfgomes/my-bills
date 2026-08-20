{{--
    Adaptado de reference/bioform-layout/app-shell.md (4.5 AppTopbar.vue). useBreadcrumb()
    (composable Vue) -> mapa PHP simples por rota atual, ja que este projeto tem so 3 telas
    autenticadas (sem hierarquia de breadcrumb real). Seletor de tema: adaptacao do card
    "Aparencia" (reference/bioform-layout/temas.md §9.6) para 2 estados (claro/escuro), em vez
    dos 3 temas do BioForm — este projeto so declara suporte_tema = claro_escuro.
--}}
@php
    $breadcrumbLabels = [
        'overview' => __('Overview'),
        'renda.index' => __('Renda'),
        'despesas.index' => __('Despesas'),
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

    <div class="flex items-center gap-2.5" x-data="{ dark: document.documentElement.getAttribute('data-theme') === 'dark' }">
        <button
            type="button"
            data-cy="btn-topbar-theme"
            :aria-pressed="dark"
            aria-label="{{ __('Alternar tema claro ou escuro') }}"
            class="focus-ring w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
            style="color:var(--color-text-muted)"
            @click="
                dark = !dark;
                document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
                localStorage.setItem('app_theme', dark ? 'dark' : 'light');
            "
        >
            <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg x-show="dark" xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
    </div>
</header>
