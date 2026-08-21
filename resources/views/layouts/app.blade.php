{{--
    RF-PADRAO-CONFIGURACOES: data-theme/data-font-size/alto-contraste/reducao-movimento
    renderizados diretamente de $configuracaoUsuario (compartilhado por
    App\Http\Middleware\AplicarConfiguracaoUsuario, sempre presente nas rotas autenticadas que
    usam este layout) -- sem script de FOUC baseado em localStorage: o primeiro paint ja sai
    correto, server-side, por usuario (arquitetura.camadas_servicos.mecanismo_tema_substituido).
--}}
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $configuracaoUsuario->tema === 'escuro' ? 'dark' : 'light' }}"
    data-font-size="{{ $configuracaoUsuario->tamanho_fonte }}"
    @class(['alto-contraste' => $configuracaoUsuario->alto_contraste, 'reduzir-movimento' => $configuracaoUsuario->reducao_movimento])
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

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

        {{--
            RF-PADRAO-CONFIGURACOES: TemaToggleTopbar::alternar() e ConfiguracaoManager::
            salvar() disparam o mesmo evento Livewire 'configuracao-atualizada' (payload
            parcial ou completo) -- este listener aplica os atributos/classes no <html> sem
            reload, para toda tela autenticada (o toggle do topbar existe em todas elas).
        --}}
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('configuracao-atualizada', (evento) => {
                    var dados = Array.isArray(evento) ? evento[0] : evento;
                    var raiz = document.documentElement;

                    if (dados.tema) {
                        raiz.setAttribute('data-theme', dados.tema === 'escuro' ? 'dark' : 'light');
                    }
                    if (dados.tamanhoFonte) {
                        raiz.setAttribute('data-font-size', dados.tamanhoFonte);
                    }
                    if (typeof dados.altoContraste === 'boolean') {
                        raiz.classList.toggle('alto-contraste', dados.altoContraste);
                    }
                    if (typeof dados.reducaoMovimento === 'boolean') {
                        raiz.classList.toggle('reduzir-movimento', dados.reducaoMovimento);
                    }
                });
            });
        </script>

        @livewireScripts
    </body>
</html>
