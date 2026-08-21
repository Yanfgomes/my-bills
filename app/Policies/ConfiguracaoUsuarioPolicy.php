<?php

namespace App\Policies;

use App\Models\ConfiguracaoUsuario;
use App\Models\User;

/**
 * RF-PADRAO-CONFIGURACOES/RN-009 (preferencias 1:1 por usuario) — aplicada em todo
 * componente Livewire que le/escreve ConfiguracaoUsuario (ConfiguracaoManager,
 * TemaToggleTopbar), mesmo padrao de defesa em profundidade de FonteRendaPolicy/
 * DespesaPolicy: a query ja escopa por usuario_id == Auth::id() antes de qualquer
 * authorize(), a Policy reforca.
 */
class ConfiguracaoUsuarioPolicy
{
    public function view(User $usuario, ConfiguracaoUsuario $configuracao): bool
    {
        return $configuracao->usuario_id === $usuario->id;
    }

    public function update(User $usuario, ConfiguracaoUsuario $configuracao): bool
    {
        return $configuracao->usuario_id === $usuario->id;
    }
}
