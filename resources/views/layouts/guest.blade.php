<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        {{--
            Evita FOUC do tema (mesmo mecanismo de layouts/app.blade.php): aplica o data-theme
            salvo antes do primeiro paint.
        --}}
        <script>
            (function () {
                var saved = localStorage.getItem('app_theme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-theme', saved);
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen font-sans antialiased flex flex-col" style="background-color:var(--color-bg);color:var(--color-text)">
        <a href="#conteudo-principal" class="skip-link focus-ring text-sm font-semibold">
            {{ __('Pular para o conteudo principal') }}
        </a>

        {{--
            Casca "convidado" (login/registro) — sem sidebar/topbar do AppShell autenticado, mas
            reusa o mesmo cabecalho de marca com gradiente (.app-hero,
            reference/bioform-layout/page-hero-and-cards.md) e o alternador de tema (adaptado de
            reference/bioform-layout/temas.md §9.6 para 2 estados).
        --}}
        <header class="app-hero relative overflow-hidden">
            <div aria-hidden="true" class="absolute -right-10 -top-10 w-56 h-56 rounded-full bg-white/[0.06] pointer-events-none"></div>
            <div class="relative z-10 max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-[30px] h-[30px] rounded-full border-2 border-white/40 bg-white/10 flex items-center justify-center flex-shrink-0">
                        <span class="text-[13px] font-bold text-white" aria-hidden="true">M</span>
                    </div>
                    <span class="text-[16px] font-extrabold tracking-tight text-white">my-bills</span>
                </div>

                <div x-data="{ dark: document.documentElement.getAttribute('data-theme') === 'dark' }">
                    <button
                        type="button"
                        data-cy="guest-topbar-theme"
                        :aria-pressed="dark"
                        aria-label="{{ __('Alternar tema claro ou escuro') }}"
                        class="focus-ring w-8 h-8 rounded-lg flex items-center justify-center text-white/80 hover:text-white transition-colors"
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
            </div>
        </header>

        <main id="conteudo-principal" class="flex-1 max-w-5xl w-full mx-auto px-4 py-10">
            {{ $slot }}
        </main>

        <footer role="contentinfo" class="max-w-5xl w-full mx-auto px-4 py-6 text-[11px]" style="color:var(--color-text-muted)">
            &copy; {{ date('Y') }} my-bills
        </footer>

        @livewireScripts
    </body>
</html>
