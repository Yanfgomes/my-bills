<?php

namespace App\Livewire\Configuracoes;

use App\Models\ConfiguracaoUsuario;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-PADRAO-CONFIGURACOES — Preferencias do usuario autenticado (idioma/tema/fonte/alto
 * contraste/reducao de movimento), TELA-006.
 * Contrato: arquitetura.documentacao_tecnica.contratos (ConfiguracaoManager.mount()/salvar()).
 */
#[Layout('layouts.app')]
class ConfiguracaoManager extends Component
{
    public string $idioma = 'pt';

    public string $tema = 'claro';

    public string $tamanhoFonte = 'medio';

    public bool $altoContraste = false;

    public bool $reducaoMovimento = false;

    /**
     * true so na requisicao em que o registro acabou de ser criado (wasRecentlyCreated) —
     * controla se a view dispara a deteccao client-side de prefers-reduced-motion
     * (criterio_aceite: "Reducao de movimento tem como valor inicial o prefers-reduced-motion
     * do sistema operacional").
     */
    public bool $recemCriado = false;

    public bool $salvo = false;

    /**
     * firstOrCreate com defaults (RN-009) -- se o usuario ainda nao tinha registro, ele nasce
     * aqui antes de qualquer exibicao (criterio_aceite). authorize('view', ...) reforca em
     * nivel de acao a mesma restricao usuario_id == Auth::id() ja aplicada na query.
     */
    public function mount(): void
    {
        $configuracao = ConfiguracaoUsuario::firstOrCreate(
            ['usuario_id' => Auth::id()],
            [
                'idioma' => 'pt',
                'tema' => 'claro',
                'tamanho_fonte' => 'medio',
                'alto_contraste' => false,
                'reducao_movimento' => false,
            ]
        );

        $this->authorize('view', $configuracao);

        $this->idioma = $configuracao->idioma;
        $this->tema = $configuracao->tema;
        $this->tamanhoFonte = $configuracao->tamanho_fonte;
        $this->altoContraste = $configuracao->alto_contraste;
        $this->reducaoMovimento = $configuracao->reducao_movimento;
        $this->recemCriado = $configuracao->wasRecentlyCreated;
    }

    /**
     * Disparo unico, chamado pelo x-init da view so quando o registro acabou de ser criado
     * nesta requisicao (mount() acima) -- persiste reducaoMovimento com o valor detectado no
     * navegador (prefers-reduced-motion) antes de qualquer interacao do usuario
     * (RNF-PADRAO-ACESSIBILIDADE-CONFIG).
     */
    public function aplicarPreferenciaSistema(bool $reduzir): void
    {
        if (! $this->recemCriado) {
            return;
        }

        $configuracao = ConfiguracaoUsuario::where('usuario_id', Auth::id())->firstOrFail();

        $this->authorize('update', $configuracao);

        $configuracao->update(['reducao_movimento' => $reduzir]);

        $this->reducaoMovimento = $reduzir;
        $this->recemCriado = false;

        $this->dispatch('configuracao-atualizada', reducaoMovimento: $reduzir);
    }

    /**
     * Sempre resolve o registro pelo usuario autenticado (nunca por um id vindo do payload) --
     * criterio_aceite_seguranca: campo fora do contrato (ex: usuario_id) enviado na requisicao
     * e ignorado, nunca sobrescrito pelo valor recebido; nao ha forma de ler/alterar preferencia
     * de outro usuario.
     */
    public function salvar(): void
    {
        $configuracao = ConfiguracaoUsuario::where('usuario_id', Auth::id())->firstOrFail();

        $this->authorize('update', $configuracao);

        $dados = $this->validate([
            'idioma' => ['required', 'string', 'in:pt,en,es'],
            'tema' => ['required', 'string', 'in:claro,escuro'],
            'tamanhoFonte' => ['required', 'string', 'in:pequeno,medio,grande'],
            'altoContraste' => ['boolean'],
            'reducaoMovimento' => ['boolean'],
        ]);

        $configuracao->update([
            'idioma' => $dados['idioma'],
            'tema' => $dados['tema'],
            'tamanho_fonte' => $dados['tamanhoFonte'],
            'alto_contraste' => $dados['altoContraste'],
            'reducao_movimento' => $dados['reducaoMovimento'],
        ]);

        // Efeito imediato sem novo login (criterio_aceite) -- fonte do locale passa a ser o
        // registro do usuario (AplicarConfiguracaoUsuario), sessao acompanha para a proxima
        // requisicao ate o middleware reler o registro atualizado.
        app()->setLocale($dados['idioma']);
        session(['locale' => $dados['idioma']]);

        $this->recemCriado = false;
        $this->salvo = true;

        // layouts/app.blade.php escuta este evento para atualizar data-theme/data-font-size/
        // alto-contraste/reducao-movimento no <html> sem reload (mesmo evento usado por
        // TemaToggleTopbar::alternar()).
        $this->dispatch(
            'configuracao-atualizada',
            tema: $dados['tema'],
            tamanhoFonte: $dados['tamanhoFonte'],
            altoContraste: (bool) $dados['altoContraste'],
            reducaoMovimento: (bool) $dados['reducaoMovimento'],
        );
    }

    public function render()
    {
        return view('livewire.configuracoes.configuracao-manager');
    }
}
