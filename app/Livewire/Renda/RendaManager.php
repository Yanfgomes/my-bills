<?php

namespace App\Livewire\Renda;

use App\Models\FonteRenda;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-004 — Cadastro de fonte de renda, TELA-004.
 * Contrato: arquitetura.documentacao_tecnica.componentes_livewire (App\Livewire\Renda\RendaManager).
 *
 * Escopo marco-1-mvp: apenas listar (leitura) e criar. Acoes de editar()/excluir() pertencem
 * a RF-005 (marco-2-gestao-completa) e nao existem aqui ainda (ver
 * componentes_livewire.RendaManager.escopo_marco).
 */
#[Layout('layouts.app')]
class RendaManager extends Component
{
    public string $descricao = '';

    public string $valorLiquido = '';

    public string $mesReferencia = '';

    /**
     * RN-005 (isolamento por usuario): reforca em nivel de acao a mesma restricao ja aplicada
     * na query de listagem abaixo (defesa em profundidade, mesmo padrao de convencoes.autorizacao).
     */
    public function mount(): void
    {
        $this->authorize('viewAny', FonteRenda::class);
    }

    public function criar(): void
    {
        $this->authorize('create', FonteRenda::class);

        $dados = $this->validate([
            'descricao' => ['required', 'string', 'max:120'],
            'valorLiquido' => ['required', 'numeric', 'gt:0'],
            'mesReferencia' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ], [
            'descricao.required' => __('Informe a descricao.'),
            'descricao.max' => __('A descricao deve ter no maximo 120 caracteres.'),
            'valorLiquido.required' => __('Informe um valor liquido maior que zero.'),
            'valorLiquido.numeric' => __('Informe um valor liquido maior que zero.'),
            'valorLiquido.gt' => __('o valor deve ser maior que zero'),
            'mesReferencia.required' => __('Informe o mes/periodo de referencia (formato AAAA-MM).'),
            'mesReferencia.regex' => __('Informe o mes/periodo de referencia (formato AAAA-MM).'),
        ]);

        FonteRenda::create([
            'usuario_id' => Auth::id(),
            'descricao' => $dados['descricao'],
            'valor_liquido' => $dados['valorLiquido'],
            'mes_referencia' => $dados['mesReferencia'],
        ]);

        $this->reset(['descricao', 'valorLiquido', 'mesReferencia']);
    }

    public function render()
    {
        $rendas = FonteRenda::where('usuario_id', Auth::id())
            ->orderByDesc('mes_referencia')
            ->orderByDesc('criado_em')
            ->get();

        return view('livewire.renda.renda-manager', [
            'rendas' => $rendas,
        ]);
    }
}
