<?php

namespace App\Policies;

use App\Models\LogAuditoria;
use App\Models\User;

/**
 * RF-PADRAO-LOG-AUDITORIA/RN-010 (escopo self-audit: cada usuario consulta apenas as
 * proprias acoes) — mesmo padrao de defesa em profundidade de FonteRendaPolicy/DespesaPolicy/
 * ConfiguracaoUsuarioPolicy: a query em LogAuditoriaRelatorio::render() ja escopa por
 * usuario_id == Auth::id() antes de qualquer authorize(), a Policy reforca.
 *
 * Sem update()/delete(): a tabela de log e imutavel (nenhum endpoint da aplicacao edita ou
 * exclui um registro), entao a Policy nao expoe essas acoes.
 */
class LogAuditoriaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, LogAuditoria $log): bool
    {
        return $log->usuario_id === $usuario->id;
    }
}
