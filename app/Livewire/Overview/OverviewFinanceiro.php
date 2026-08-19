<?php

namespace App\Livewire\Overview;

use App\Services\OverviewService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-008 — Overview financeiro com navegacao por mes, TELA-003.
 * Contrato: arquitetura.documentacao_tecnica.servicos (App\Services\OverviewService).
 *
 * Overview nao tem model/Policy propria (nao ha entidade "Overview" a autorizar) — RN-005 e
 * aplicada aqui passando sempre Auth::id() ao servico, nunca um valor vindo de input do
 * cliente/wire:model (nao existe propriedade publica de usuario neste componente).
 */
#[Layout('layouts.app')]
class OverviewFinanceiro extends Component
{
    public string $mesSelecionado = '';

    public function mount(): void
    {
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
