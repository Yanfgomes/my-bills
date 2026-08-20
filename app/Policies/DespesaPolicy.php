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
 *
 * RF-003 (consolidacao, ultimo RF do lote marco-1-mvp): validado com testabilidade cross-user
 * real (dois usuarios, dados cruzados) em tests/Feature/Despesas/DespesaManagerTest.php —
 * view/update/delete negados para o dono errado e concedidos para o dono certo, viewAny/create
 * concedidos a qualquer usuario autenticado e negados sem sessao. Registro explicito em
 * App\Providers\AppServiceProvider::boot() (Gate::policy) formaliza o mapeamento Model->Policy
 * antes coberto so por convencao de nome.
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
