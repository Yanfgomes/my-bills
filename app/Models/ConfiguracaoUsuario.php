<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RF-PADRAO-CONFIGURACOES — Preferencias do usuario autenticado (RN-009), DOM-005
 * (tabela configuracoes_usuario). Relacao 1:1 com User, reforcada por UNIQUE(usuario_id)
 * na migration. Contrato: arquitetura.documentacao_tecnica.contratos (ConfiguracaoManager).
 */
#[Fillable(['usuario_id', 'idioma', 'tema', 'tamanho_fonte', 'alto_contraste', 'reducao_movimento'])]
class ConfiguracaoUsuario extends Model
{
    use HasUuids;

    protected $table = 'configuracoes_usuario';

    /**
     * CAMPO-CFG-001..008 aprovados nao incluem criado_em — mesma convencao ja usada em
     * FonteRenda/Despesa/LogAuditoria (colunas em portugues, nao created_at/updated_at
     * padrao do Eloquent).
     */
    const CREATED_AT = null;

    const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return [
            'alto_contraste' => 'boolean',
            'reducao_movimento' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
