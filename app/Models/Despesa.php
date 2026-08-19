<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RF-006 — Cadastro de despesa, DOM-003 (tabela despesas).
 * Contrato: arquitetura.documentacao_tecnica.contratos_dados (DespesaInput/DespesaOutput).
 */
#[Fillable(['usuario_id', 'descricao', 'valor', 'categoria', 'mes_referencia'])]
class Despesa extends Model
{
    use HasUuids;

    protected $table = 'despesas';

    /**
     * Colunas do contrato aprovado usam nomenclatura em portugues (CAMPO-DESP-006/007:
     * criado_em/atualizado_em), nao os nomes padrao do Eloquent (created_at/updated_at) —
     * mesma convencao ja aplicada em fontes_renda/logs_auditoria.
     */
    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
