<?php

namespace App\Policies;

use App\Models\FonteRenda;
use App\Models\User;

/**
 * RF-004/RN-005 (isolamento de dados por usuario autenticado) — aplicada em todo componente
 * Livewire que le/escreve FonteRenda (arquitetura.padroes_tecnologias.convencoes.autorizacao).
 *
 * Nota (RF-003, decisao_pendente resolvida em specs/estado-rf-RF-003.json): RF-003 foi adiado
 * para o final do lote por nao ter entidade propria para isolar/testar no momento em que foi
 * ordenado. Esta Policy e a materializacao de RN-005 para o dominio Renda, criada agora junto
 * com o proprio RF-004 (unico model FonteRenda existe a partir desta branch) — cobre viewAny
 * (listagem, reforcando o escopo ja aplicado na query de RendaManager) e create (unica escrita
 * do escopo marco-1-mvp, listar+criar). view/update/delete ja existem aqui, seguindo o formato
 * padrao de Policy do Laravel, para RF-005 (marco-2-gestao-completa, edicao/exclusao) reutilizar
 * sem precisar recriar a Policy — nao ha decisao de arquitetura nova nisso, apenas o metodo de
 * instancia mais obvio de RN-005 (usuario_id do registro == usuario autenticado). Quando RF-003
 * rodar (ultimo do lote), sua responsabilidade e consolidar/validar este e o equivalente de
 * Despesa com testabilidade cross-user real, nao criar a Policy do zero.
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
