<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RF-004 — Cadastro de fonte de renda, DOM-002 (tabela fontes_renda).
 * Contrato: arquitetura.documentacao_tecnica.contratos_dados (FonteRendaInput/FonteRendaOutput).
 */
#[Fillable(['usuario_id', 'descricao', 'valor_liquido', 'mes_referencia'])]
class FonteRenda extends Model
{
    use HasUuids;

    protected $table = 'fontes_renda';

    /**
     * Colunas do contrato aprovado usam nomenclatura em portugues (CAMPO-REN-005/006:
     * criado_em/atualizado_em), nao os nomes padrao do Eloquent (created_at/updated_at) —
     * mesma convencao ja aplicada em logs_auditoria.
     */
    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return [
            'valor_liquido' => 'decimal:2',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
