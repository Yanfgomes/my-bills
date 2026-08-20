<?php

namespace App\Livewire\Overview;

use App\Models\Despesa;
use App\Models\FonteRenda;
use App\Services\OverviewService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * RF-008 — Overview financeiro com navegacao por mes, TELA-003.
 * Contrato: arquitetura.documentacao_tecnica.servicos (App\Services\OverviewService).
 *
 * Overview nao tem model/Policy propria (nao ha entidade "Overview" a autorizar) — RN-005 e
 * aplicada aqui passando sempre Auth::id() ao servico, nunca um valor vindo de input do
 * cliente/wire:model (nao existe propriedade publica de usuario neste componente).
 *
 * RF-003 (consolidacao, ultimo RF do lote marco-1-mvp): validado com testabilidade cross-user
 * real (dois usuarios, dados cruzados) em tests/Feature/Overview/OverviewFinanceiroTest.php e
 * tests/Unit/Services/OverviewServiceTest.php — overview de um usuario nunca inclui renda/
 * despesa de outro. mesSelecionado (#[Locked], PARAM-OVW-001) so e alterado por
 * mesAnterior()/mesProximo() no servidor, nunca por payload do cliente, e sempre chega ao
 * OverviewService::calcular() ja formatado (Y-m) — nenhum parametro de filtro chega a consulta
 * como trecho de codigo (Eloquent where() com bind).
 */
#[Layout('layouts.app')]
class OverviewFinanceiro extends Component
{
    /**
     * CR-RF-008-01-fix (correcao de SEC-RF-008-01): #[Locked] impede que o payload de
     * sincronizacao do Livewire sobrescreva esta propriedade a partir do cliente — o unico jeito
     * de alterar mesSelecionado passa a ser mesAnterior()/mesProximo() (codigo do servidor, que
     * sempre produz um valor 'Y-m' valido a partir do proprio valor anterior). Elimina o vetor que
     * permitia enviar um valor fora do formato esperado e derrubar Carbon::createFromFormat() com
     * excecao nao tratada (500).
     */
    #[Locked]
    public string $mesSelecionado = '';

    public function mount(): void
    {
        // CR-RF-008-01: defesa em profundidade (mesmo padrao de convencoes.autorizacao ja aplicado
        // em RendaManager::mount()/DespesaManager::mount() para as mesmas entidades) — reforca em
        // nivel de acao a restricao que o OverviewService ja aplica via Auth::id() nas queries.
        $this->authorize('viewAny', FonteRenda::class);
        $this->authorize('viewAny', Despesa::class);

        $this->mesSelecionado = now()->format('Y-m');
    }

    public function mesAnterior(): void
    {
        $this->mesSelecionado = Carbon::createFromFormat('Y-m-d', $this->mesSelecionado.'-01')
            ->subMonthNoOverflow()
            ->format('Y-m');
    }

    public function mesProximo(): void
    {
        $this->mesSelecionado = Carbon::createFromFormat('Y-m-d', $this->mesSelecionado.'-01')
            ->addMonthNoOverflow()
            ->format('Y-m');
    }

    public function render()
    {
        $overview = app(OverviewService::class)->calcular(Auth::id(), $this->mesSelecionado);

        return view('livewire.overview.overview-financeiro', [
            'overview' => $overview,
        ]);
    }
}
