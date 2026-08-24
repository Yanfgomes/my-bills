{{--
    Adaptado de reference/bioform-layout/app-shell.md (4.3 AppSidebar.vue). Vue Pinia store
    (stores/sidebar.js) -> Alpine `collapsed` no x-data raiz de layouts/app.blade.php (ver
    $store.sidebar). RouterLink/route() -> route() do Laravel; navGroups fixo (dominio pequeno:
    Overview/Renda/Despesas), sem grupos rotulados como no fragmento original.
--}}
<nav
    aria-label="{{ __('Navegacao principal') }}"
    class="app-shell-sidebar app-sidebar-transition flex-shrink-0 flex flex-col min-h-screen sticky top-0 h-screen z-30 border-r"
    :class="$store.sidebar.collapsed ? 'w-[60px]' : 'w-[220px]'"
>
    <div class="border-b px-5 pt-6 pb-5" style="border-color:var(--color-border)" :class="$store.sidebar.collapsed ? 'px-[15px]' : 'px-5'">
        <div class="flex items-center gap-2">
            <div
                class="w-[30px] h-[30px] rounded-full border-2 flex items-center justify-center flex-shrink-0"
                style="border-color:var(--color-primary);background-color:var(--color-primary-soft)"
            >
                <span class="text-[13px] font-bold" style="color:var(--color-primary)" aria-hidden="true">M</span>
            </div>
            <span x-show="!$store.sidebar.collapsed" class="text-[16px] font-extrabold tracking-tight" style="color:var(--color-text)">my-bills</span>
        </div>
    </div>

    <div class="pt-4 pb-1 flex-1">
        @php
            $navItems = [
                ['route' => 'overview', 'label' => __('Overview'), 'cy' => 'nav-overview'],
                ['route' => 'renda.index', 'label' => __('Renda'), 'cy' => 'nav-renda'],
                ['route' => 'despesas.index', 'label' => __('Despesas'), 'cy' => 'nav-despesas'],
                ['route' => 'configuracoes.show', 'label' => __('Configuracoes'), 'cy' => 'nav-config'],
                ['route' => 'auditoria.index', 'label' => __('Log de auditoria'), 'cy' => 'nav-auditoria'],
            ];
        @endphp

        @foreach ($navItems as $item)
            <a
                href="{{ route($item['route']) }}"
                data-cy="{{ $item['cy'] }}"
                aria-current="{{ request()->routeIs($item['route']) ? 'page' : 'false' }}"
                class="app-nav-item focus-ring flex items-center text-[12.5px] font-medium transition-colors duration-150"
                :class="$store.sidebar.collapsed ? 'justify-center py-2.5' : 'gap-2.5 px-5 py-2.5'"
            >
                <span class="w-[8px] h-[8px] rounded-full flex-shrink-0" style="background-color:currentColor;opacity:.6" aria-hidden="true"></span>
                <span x-show="!$store.sidebar.collapsed">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="mt-auto border-t px-5 py-4" style="border-color:var(--color-border)">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                data-cy="logout-submit"
                class="focus-ring w-full flex items-center gap-2 text-[12.5px] font-semibold rounded-lg px-2 py-2 transition-colors"
                style="color:var(--color-text-muted)"
                :class="$store.sidebar.collapsed ? 'justify-center' : ''"
                :title="$store.sidebar.collapsed ? @js(__('Sair')) : null"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span x-show="!$store.sidebar.collapsed">{{ __('Sair') }}</span>
            </button>
        </form>
    </div>
</nav>
