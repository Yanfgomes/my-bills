<?php

namespace App\Livewire\Renda;

use App\Models\FonteRenda;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-004/RF-005 — Cadastro, edicao e exclusao de fonte de renda, TELA-004.
 * Contrato: arquitetura.documentacao_tecnica.componentes_livewire (App\Livewire\Renda\RendaManager).
 *
 * RF-005 (marco-2-gestao-completa) adiciona editar()/atualizar()/cancelarEdicao()/excluir() ao
 * componente de listar+criar ja aprovado em RF-004 (marco-1-mvp) — mesmo formulario alterna entre
 * modo criacao e modo edicao via $editandoId, conforme TELA-004/prototipo (editarRenda()/
 * cancelarEdicaoRenda()/excluirRenda()).
 */
#[Layout('layouts.app')]
class RendaManager extends Component
{
    public string $descricao = '';

    public string $valorLiquido = '';

    public string $mesReferencia = '';

    /**
     * RF-005: null = modo criacao (comportamento original de RF-004); id da FonteRenda = modo
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

    /**
     * RF-005/contrato editar(): popula o formulario com o registro e entra em modo edicao.
     * findOrFail escopado por usuario_id evita vazar existencia de registro de terceiro antes
     * mesmo do authorize() rodar (RN-005/RNF-002) -- 404 identico ao de um id inexistente.
     */
    public function editar(string $id): void
    {
        $fonteRenda = FonteRenda::where('usuario_id', Auth::id())->findOrFail($id);

        $this->authorize('update', $fonteRenda);

        $this->descricao = $fonteRenda->descricao;
        $this->valorLiquido = (string) $fonteRenda->valor_liquido;
        $this->mesReferencia = $fonteRenda->mes_referencia;
        $this->editandoId = $fonteRenda->id;
        $this->resetErrorBag();
    }

    /**
     * RF-005/contrato atualizar(): mesReferencia deliberadamente fora da validacao/payload de
     * update -- RN-008 (mes/periodo imutavel apos a criacao) e reforcado no servidor, nao so
     * desabilitando o campo na view, para rejeitar qualquer tentativa de altera-lo via payload
     * manipulado.
     */
    public function atualizar(): void
    {
        if ($this->editandoId === null) {
            return;
        }

        $fonteRenda = FonteRenda::where('usuario_id', Auth::id())->findOrFail($this->editandoId);

        $this->authorize('update', $fonteRenda);

        $dados = $this->validate([
            'descricao' => ['required', 'string', 'max:120'],
            'valorLiquido' => ['required', 'numeric', 'gt:0'],
        ], [
            'descricao.required' => __('Informe a descricao.'),
            'descricao.max' => __('A descricao deve ter no maximo 120 caracteres.'),
            'valorLiquido.required' => __('Informe um valor liquido maior que zero.'),
            'valorLiquido.numeric' => __('Informe um valor liquido maior que zero.'),
            'valorLiquido.gt' => __('o valor deve ser maior que zero'),
        ]);

        $fonteRenda->update([
            'descricao' => $dados['descricao'],
            'valor_liquido' => $dados['valorLiquido'],
        ]);

        $this->reset(['descricao', 'valorLiquido', 'mesReferencia', 'editandoId']);
    }

    /**
     * RF-005/contrato cancelarEdicao(): reseta para o estado de criacao sem persistir nada.
     */
    public function cancelarEdicao(): void
    {
        $this->reset(['descricao', 'valorLiquido', 'mesReferencia', 'editandoId']);
        $this->resetErrorBag();
    }

    /**
     * RF-005/contrato excluir(): sem modal de confirmacao, conforme prototipo aprovado
     * (TELA-004/excluirRenda() nao usa confirm()). Se o item excluido era o que estava em
     * edicao, o formulario tambem reseta (mesmo comportamento do mock).
     */
    public function excluir(string $id): void
    {
        $fonteRenda = FonteRenda::where('usuario_id', Auth::id())->findOrFail($id);

        $this->authorize('delete', $fonteRenda);

        $fonteRenda->delete();

        if ($this->editandoId === $id) {
            $this->reset(['descricao', 'valorLiquido', 'mesReferencia', 'editandoId']);
            $this->resetErrorBag();
        }
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
