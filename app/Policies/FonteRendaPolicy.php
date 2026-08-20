<?php

namespace App\Policies;

use App\Models\FonteRenda;
use App\Models\User;

/**
 * RF-004/RN-005 (isolamento de dados por usuario autenticado) — aplicada em todo componente
 * Livewire que le/escreve FonteRenda (arquitetura.padroes_tecnologias.convencoes.autorizacao).
 *
 * Esta Policy e a materializacao de RN-005 para o dominio Renda, criada junto com o proprio
 * RF-004 — cobre viewAny (listagem, reforcando o escopo ja aplicado na query de RendaManager) e
 * create (unica escrita do escopo marco-1-mvp, listar+criar). view/update/delete ja existem
 * aqui, seguindo o formato padrao de Policy do Laravel, para RF-005 (marco-2-gestao-completa,
 * edicao/exclusao) reutilizar sem precisar recriar a Policy.
 *
 * RF-003 (consolidacao, ultimo RF do lote marco-1-mvp): validado com testabilidade cross-user
 * real (dois usuarios, dados cruzados) em tests/Feature/Renda/RendaManagerTest.php — view/
 * update/delete negados para o dono errado e concedidos para o dono certo, viewAny/create
 * concedidos a qualquer usuario autenticado e negados sem sessao. Registro explicito em
 * App\Providers\AppServiceProvider::boot() (Gate::policy) formaliza o mapeamento Model->Policy
 * antes coberto so por convencao de nome.
 */
class FonteRendaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, FonteRenda $fonteRenda): bool
    {
        return $fonteRenda->usuario_id === $usuario->id;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, FonteRenda $fonteRenda): bool
    {
        return $fonteRenda->usuario_id === $usuario->id;
    }

    public function delete(User $usuario, FonteRenda $fonteRenda): bool
    {
        return $fonteRenda->usuario_id === $usuario->id;
    }
}
