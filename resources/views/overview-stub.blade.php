<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans antialiased" style="background-color:var(--color-bg);color:var(--color-text)">
        <main class="max-w-2xl mx-auto px-4 py-10">
            <p style="color:var(--color-text-muted)">
                {{-- Stub temporario: App\Livewire\Overview\OverviewFinanceiro (RF-003/RF-008) substitui esta tela. --}}
                Ola, {{ auth()->user()->nome }}. Overview financeiro em construcao.
            </p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button
                    type="submit"
                    data-cy="logout-submit"
                    class="focus-ring px-4 py-2.5 rounded-lg text-white text-[13px] font-semibold"
                    style="background-color:var(--color-primary)"
                >
                    {{ __('Sair') }}
                </button>
            </form>
        </main>
    </body>
</html>
