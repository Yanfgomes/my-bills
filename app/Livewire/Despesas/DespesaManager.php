<?php

namespace App\Livewire\Despesas;

use App\Models\Despesa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-006 — Cadastro de despesa, TELA-005.
 * Contrato: arquitetura.documentacao_tecnica.componentes_livewire (App\Livewire\Despesas\DespesaManager).
 *
 * Escopo marco-1-mvp: apenas listar (leitura) e criar. Acoes de editar()/excluir() pertencem
 * a RF-007 (marco-2-gestao-completa) e nao existem aqui ainda (ver
 * componentes_livewire.DespesaManager.escopo_marco).
 */
#[Layout('layouts.app')]
class DespesaManager extends Component
{
    public string $descricao = '';

    public string $valor = '';

    public string $categoria = '';

    public string $mesReferencia = '';

    /**
     * RN-005 (isolamento por usuario): reforca em nivel de acao a mesma restricao ja aplicada
     * na query de listagem abaixo (defesa em profundidade, mesmo padrao de convencoes.autorizacao).
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Despesa::class);
    }

    public function criar(): void
    {
        $this->authorize('create', Despesa::class);

        $dados = $this->validate([
            'descricao' => ['required', 'string', 'max:120'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'categoria' => ['nullable', 'string', 'max:60'],
            'mesReferencia' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ], [
            'descricao.required' => __('Informe a descricao.'),
            'descricao.max' => __('A descricao deve ter no maximo 120 caracteres.'),
            'valor.required' => __('Informe um valor maior que zero.'),
            'valor.numeric' => __('Informe um valor maior que zero.'),
            'valor.gt' => __('o valor deve ser maior que zero'),
            'categoria.max' => __('A categoria deve ter no maximo 60 caracteres.'),
            'mesReferencia.required' => __('Informe o mes/periodo de referencia (formato AAAA-MM).'),
            'mesReferencia.regex' => __('Informe o mes/periodo de referencia (formato AAAA-MM).'),
        ]);

        Despesa::create([
            'usuario_id' => Auth::id(),
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'categoria' => $dados['categoria'] !== '' ? $dados['categoria'] : null,
            'mes_referencia' => $dados['mesReferencia'],
        ]);

        $this->reset(['descricao', 'valor', 'categoria', 'mesReferencia']);
    }

    public function render()
    {
        $despesas = Despesa::where('usuario_id', Auth::id())
            ->orderByDesc('mes_referencia')
            ->orderByDesc('criado_em')
            ->get();

        return view('livewire.despesas.despesa-manager', [
            'despesas' => $despesas,
        ]);
    }
}
