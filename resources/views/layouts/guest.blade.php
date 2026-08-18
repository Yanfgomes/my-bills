<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen font-sans antialiased" style="background-color:var(--color-bg);color:var(--color-text)">
        <a href="#conteudo-principal" class="skip-link focus-ring text-sm font-semibold">
            {{ __('Pular para o conteudo principal') }}
        </a>

        <header class="border-b" style="background-color:var(--color-card);border-color:var(--color-border)">
            <div class="max-w-5xl mx-auto px-4 py-3">
                <span class="text-[18px] font-semibold" style="color:var(--color-text)">my-bills</span>
            </div>
        </header>

        <main id="conteudo-principal" class="max-w-5xl mx-auto px-4 py-8">
            {{ $slot }}
        </main>

        <footer class="max-w-5xl mx-auto px-4 py-6 text-[12px]" style="color:var(--color-text-muted)">
            &copy; {{ date('Y') }} my-bills
        </footer>

        @livewireScripts
    </body>
</html>
