<?php

namespace App\Livewire\Layout;

use App\Models\ConfiguracaoUsuario;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * RF-PADRAO-CONFIGURACOES — alternador rapido de tema no topbar, presente em toda tela
 * autenticada (montado em layouts/partials/app-topbar.blade.php, nao so TELA-006).
 * Substitui o toggle Alpine-only + localStorage que existia antes (sem vinculo a usuario).
 * Contrato: arquitetura.documentacao_tecnica.contratos (TemaToggleTopbar.alternar()).
 */
class TemaToggleTopbar extends Component
{
    public string $tema = 'claro';

    /**
     * Le a ConfiguracaoUsuario ja compartilhada por AplicarConfiguracaoUsuario (middleware),
     * sem query propria -- o componente confia que o middleware ja rodou para toda rota
     * autenticada onde esta view e montada.
     */
    public function mount(): void
    {
        $configuracao = view()->shared('configuracaoUsuario');

        $this->tema = $configuracao?->tema ?? 'claro';
    }

    public function alternar(): void
    {
        $configuracao = ConfiguracaoUsuario::where('usuario_id', Auth::id())->firstOrFail();

        $this->authorize('update', $configuracao);

        $novoTema = $configuracao->tema === 'claro' ? 'escuro' : 'claro';

        $configuracao->update(['tema' => $novoTema]);

        $this->tema = $novoTema;

        // layouts/app.blade.php escuta este evento para atualizar data-theme no <html> sem
        // reload (mesmo evento usado por ConfiguracaoManager::salvar()).
        $this->dispatch('configuracao-atualizada', tema: $novoTema);
    }

    public function render()
    {
        return view('livewire.layout.tema-toggle-topbar');
    }
}
