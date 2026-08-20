<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        {{--
            Evita FOUC do tema: aplica o data-theme salvo (ou nada, deixando a media query
            prefers-color-scheme de resources/css/app.css decidir) antes do primeiro paint —
            precisa rodar antes do Alpine, que so inicializa depois do body.
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
    <body
        class="min-h-screen font-sans antialiased"
        style="background-color:var(--color-bg);color:var(--color-text)"
        x-data
        x-init="
            Alpine.store('sidebar', {
                collapsed: localStorage.getItem('app_sidebar_collapsed') === 'true',
                toggle() {
                    this.collapsed = !this.collapsed;
                    localStorage.setItem('app_sidebar_collapsed', String(this.collapsed));
                },
            });
        "
    >
        <a href="#conteudo-principal" class="skip-link focus-ring text-sm font-semibold">
            {{ __('Pular para o conteudo principal') }}
        </a>

        {{--
            Casca da aplicacao adaptada de reference/bioform-layout/app-shell.md (4.1
            AppLayout.vue): sidebar colapsavel + topbar + main + footer. Store Pinia do
            sidebar.js -> Alpine.store('sidebar') acima; AppSidebar/AppTopbar/AppFooter ->
            partials/app-sidebar.blade.php, app-topbar.blade.php, app-footer.blade.php.
        --}}
        <div class="flex min-h-screen">
            @include('layouts.partials.app-sidebar')

            <div class="flex flex-col flex-1 min-w-0">
                @include('layouts.partials.app-topbar')

                <main id="conteudo-principal" role="main" class="flex-1 px-4 py-6 md:px-7 md:py-7 max-w-5xl w-full mx-auto">
                    {{ $slot }}
                </main>

                @include('layouts.partials.app-footer')
            </div>
        </div>

        @livewireScripts
    </body>
</html>
