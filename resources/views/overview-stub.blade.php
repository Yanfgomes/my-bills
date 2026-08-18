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
                {{-- Stub temporario: App\Livewire\Overview\OverviewFinanceiro (RF-003/RF-008) substitui esta tela.
                     Logout (rota 'logout') e escopo do RF-002 — nao antecipado aqui. --}}
                Ola, {{ auth()->user()->nome }}. Overview financeiro em construcao.
            </p>
        </main>
    </body>
</html>
