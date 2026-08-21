<?php

namespace App\Livewire\Auditoria;

use App\Models\LogAuditoria;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * RF-PADRAO-LOG-AUDITORIA — Relatorio de consulta ao log de auditoria (self-audit, RN-010),
 * TELA-007. Contrato: arquitetura.documentacao_tecnica.contratos
 * (App\Livewire\Auditoria\LogAuditoriaRelatorio.render()/verDetalhe()/fecharDetalhe()).
 *
 * Somente leitura: nenhum metodo deste componente cria, altera ou exclui um registro de
 * logs_auditoria (criterio_aceite_seguranca) -- a tabela e gravada exclusivamente pelo
 * App\Observers\AuditoriaObserver.
 */
#[Layout('layouts.app')]
class LogAuditoriaRelatorio extends Component
{
    use WithPagination;

    /**
     * Enum das tabelas efetivamente observadas por AuditoriaObserver
     * (App\Providers\AppServiceProvider::boot()) -- unica fonte de valores aceitos pelo filtro
     * tabelaAfetada (criterio_aceite_seguranca: filtro so aceita os valores previstos).
     */
    private const TABELAS_AUDITADAS = ['users', 'fontes_renda', 'despesas', 'configuracoes_usuario'];

    private const ACOES_AUDITADAS = ['criacao', 'alteracao', 'exclusao'];

    public ?string $tabelaAfetada = null;

    public ?string $acao = null;

    public ?string $periodoInicio = null;

    public ?string $periodoFim = null;

    /**
     * null = nenhum modal aberto; id do registro de log = modal De/Para aberto para esse
     * registro (contrato verDetalhe()/fecharDetalhe()).
     */
    public ?int $detalheId = null;

    /**
     * RN-010 (defesa em profundidade, mesmo padrao de convencoes.autorizacao): reforca em nivel
     * de acao a mesma restricao ja aplicada na query de listagem em render().
     */
    public function mount(): void
    {
        $this->authorize('viewAny', LogAuditoria::class);
    }

    /**
     * Livewire reseta a paginacao sempre que um filtro muda, para nao ficar preso numa pagina
     * alem do novo total de resultados.
     */
    public function updatingTabelaAfetada(): void
    {
        $this->resetPage();
    }

    public function updatingAcao(): void
    {
        $this->resetPage();
    }

    public function updatingPeriodoInicio(): void
    {
        $this->resetPage();
    }

    public function updatingPeriodoFim(): void
    {
        $this->resetPage();
    }

    /**
     * Contrato/botao "Filtrar" do prototipo (TELA-007): valida os filtros contra os enums
     * previstos antes de aplicar (criterio_aceite_seguranca) -- render() so usa propriedades
     * ja validadas aqui.
     */
    public function filtrar(): void
    {
        $this->validate([
            'tabelaAfetada' => ['nullable', 'string', 'in:'.implode(',', self::TABELAS_AUDITADAS)],
            'acao' => ['nullable', 'string', 'in:'.implode(',', self::ACOES_AUDITADAS)],
            'periodoInicio' => ['nullable', 'date'],
            'periodoFim' => ['nullable', 'date', 'after_or_equal:periodoInicio'],
        ]);

        $this->resetPage();
    }

    /**
     * Contrato verDetalhe(int $id): findOrFail escopado por usuario_id evita vazar existencia
     * de registro de terceiro antes mesmo do authorize() rodar (RN-010), mesmo padrao ja usado
     * em RendaManager/DespesaManager para FonteRenda/Despesa.
     */
    public function verDetalhe(int $id): void
    {
        $log = LogAuditoria::where('usuario_id', Auth::id())->findOrFail($id);

        $this->authorize('view', $log);

        $this->detalheId = $log->id;

        // Foco preso enquanto o modal esta aberto (criterio_aceite_acessibilidade) -- o
        // listener em resources/views/livewire/auditoria/log-auditoria-relatorio.blade.php
        // move o foco para dentro do dialog e prende Tab/Shift+Tab nele.
        $this->dispatch('auditoria-detalhe-aberto');
    }

    /**
     * Contrato fecharDetalhe(): fecha o modal sem alterar/excluir nada -- devolve o foco ao
     * botao "Ver De/Para" de origem (criterio_aceite_acessibilidade), tratado no JS da view.
     */
    public function fecharDetalhe(): void
    {
        $this->detalheId = null;

        $this->dispatch('auditoria-detalhe-fechado');
    }

    /**
     * RN-010: query sempre escopada por usuario_id == Auth::id() antes de qualquer filtro do
     * usuario (criterio_aceite_seguranca) -- nenhuma pagina/paginacao devolve registro de
     * outro usuario.
     */
    public function render()
    {
        $logs = LogAuditoria::where('usuario_id', Auth::id())
            ->when($this->tabelaAfetada, fn ($query, $tabela) => $query->where('tabela_afetada', $tabela))
            ->when($this->acao, fn ($query, $acao) => $query->where('acao', $acao))
            ->when($this->periodoInicio, fn ($query, $inicio) => $query->whereDate('criado_em', '>=', $inicio))
            ->when($this->periodoFim, fn ($query, $fim) => $query->whereDate('criado_em', '<=', $fim))
            ->orderByDesc('criado_em')
            ->paginate(15);

        $detalhe = $this->detalheId !== null
            ? LogAuditoria::where('usuario_id', Auth::id())->find($this->detalheId)
            : null;

        return view('livewire.auditoria.log-auditoria-relatorio', [
            'logs' => $logs,
            'detalhe' => $detalhe,
        ]);
    }
}
