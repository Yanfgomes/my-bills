<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Tabela de auditoria unica e imutavel (RNF-PADRAO-LOG-AUDITORIA). Gravada exclusivamente
 * pelos Model Observers (app/Observers) — nenhum outro ponto do app deve criar, alterar ou
 * excluir um registro aqui.
 */
#[Fillable(['acao', 'usuario_id', 'tabela_afetada', 'registro_id', 'valor_anterior', 'valor_novo', 'criado_em'])]
class LogAuditoria extends Model
{
    protected $table = 'logs_auditoria';

    /**
     * Tabela imutavel: sem updated_at, so criado_em (sem timestamps automaticos do Eloquent).
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'valor_anterior' => 'array',
            'valor_novo' => 'array',
            'criado_em' => 'datetime',
        ];
    }
}
