<?php

namespace App\Policies;

use App\Models\Despesa;
use App\Models\User;

/**
 * RF-006/RN-005 (isolamento de dados por usuario autenticado) — aplicada em todo componente
 * Livewire que le/escreve Despesa (arquitetura.padroes_tecnologias.convencoes.autorizacao).
 *
 * Mesma logica ja aplicada em App\Policies\FonteRendaPolicy (RF-004): viewAny/create cobrem o
 * escopo marco-1-mvp (listar+criar); view/update/delete seguem o shape padrao de Policy do
 * Laravel (usuario_id do registro == usuario autenticado), sem decisao de arquitetura nova,
 * para RF-007 (marco-2-gestao-completa, edicao/exclusao) reutilizar sem recriar a classe.
 * Quando RF-003 rodar (ultimo do lote), sua responsabilidade e consolidar/validar esta Policy
 * (e a equivalente de Renda) com testabilidade cross-user real.
 */
class DespesaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, Despesa $despesa): bool
    {
        return $despesa->usuario_id === $usuario->id;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, Despesa $despesa): bool
    {
        return $despesa->usuario_id === $usuario->id;
    }

    public function delete(User $usuario, Despesa $despesa): bool
    {
        return $despesa->usuario_id === $usuario->id;
    }
}
