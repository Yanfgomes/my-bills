<?php

namespace App\Livewire\Despesas;

use App\Models\Despesa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-006/RF-007 — Cadastro, edicao e exclusao de despesa, TELA-005.
 * Contrato: arquitetura.documentacao_tecnica.componentes_livewire (App\Livewire\Despesas\DespesaManager).
 *
 * RF-007 (marco-2-gestao-completa) adiciona editar()/atualizar()/cancelarEdicao()/excluir() ao
 * componente de listar+criar ja aprovado em RF-006 (marco-1-mvp) — mesmo formulario alterna entre
 * modo criacao e modo edicao via $editandoId, mesmo padrao ja aplicado em
 * App\Livewire\Renda\RendaManager (RF-005), conforme TELA-005/prototipo (editarDespesa()/
 * cancelarEdicaoDespesa()/excluirDespesa()).
 */
#[Layout('layouts.app')]
class DespesaManager extends Component
{
    public string $descricao = '';

    public string $valor = '';

    public string $categoria = '';

    public string $mesReferencia = '';

    /**
     * RF-007: null = modo criacao (comportamento original de RF-006); id da Despesa = modo
     * edicao. Controla tanto a view (mes/periodo somente leitura, RN-008) quanto qual acao
     * wire:submit executa (criar() ou atualizar()).
     */
    public ?string $editandoId = null;

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

    /**
     * RF-007/contrato editar(): popula o formulario com o registro e entra em modo edicao.
     * findOrFail escopado por usuario_id evita vazar existencia de registro de terceiro antes
     * mesmo do authorize() rodar (RN-005/RNF-002) -- 404 identico ao de um id inexistente.
     */
    public function editar(string $id): void
    {
        $despesa = Despesa::where('usuario_id', Auth::id())->findOrFail($id);

        $this->authorize('update', $despesa);

        $this->descricao = $despesa->descricao;
        $this->valor = (string) $despesa->valor;
        $this->categoria = $despesa->categoria ?? '';
        $this->mesReferencia = $despesa->mes_referencia;
        $this->editandoId = $despesa->id;
        $this->resetErrorBag();
    }

    /**
     * RF-007/contrato atualizar(): mesReferencia deliberadamente fora da validacao/payload de
     * update -- RN-008 (mes/periodo imutavel apos a criacao) e reforcado no servidor, nao so
     * desabilitando o campo na view, para rejeitar qualquer tentativa de altera-lo via payload
     * manipulado.
     */
    public function atualizar(): void
    {
        if ($this->editandoId === null) {
            return;
        }

        $despesa = Despesa::where('usuario_id', Auth::id())->findOrFail($this->editandoId);

        $this->authorize('update', $despesa);

        $dados = $this->validate([
            'descricao' => ['required', 'string', 'max:120'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'categoria' => ['nullable', 'string', 'max:60'],
        ], [
            'descricao.required' => __('Informe a descricao.'),
            'descricao.max' => __('A descricao deve ter no maximo 120 caracteres.'),
            'valor.required' => __('Informe um valor maior que zero.'),
            'valor.numeric' => __('Informe um valor maior que zero.'),
            'valor.gt' => __('o valor deve ser maior que zero'),
            'categoria.max' => __('A categoria deve ter no maximo 60 caracteres.'),
        ]);

        $despesa->update([
            'descricao' => $dados['descricao'],
            'valor' => $dados['valor'],
            'categoria' => $dados['categoria'] !== '' ? $dados['categoria'] : null,
        ]);

        $this->reset(['descricao', 'valor', 'categoria', 'mesReferencia', 'editandoId']);
    }

    /**
     * RF-007/contrato cancelarEdicao(): reseta para o estado de criacao sem persistir nada.
     */
    public function cancelarEdicao(): void
    {
        $this->reset(['descricao', 'valor', 'categoria', 'mesReferencia', 'editandoId']);
        $this->resetErrorBag();
    }

    /**
     * RF-007/contrato excluir(): sem modal de confirmacao, conforme prototipo aprovado
     * (TELA-005/excluirDespesa() nao usa confirm()). Se o item excluido era o que estava em
     * edicao, o formulario tambem reseta (mesmo comportamento do mock).
     */
    public function excluir(string $id): void
    {
        $despesa = Despesa::where('usuario_id', Auth::id())->findOrFail($id);

        $this->authorize('delete', $despesa);

        $despesa->delete();

        if ($this->editandoId === $id) {
            $this->reset(['descricao', 'valor', 'categoria', 'mesReferencia', 'editandoId']);
            $this->resetErrorBag();
        }
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
