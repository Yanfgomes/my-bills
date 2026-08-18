<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nome', 'email', 'senha'])]
#[Hidden(['senha', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
        ];
    }

    /**
     * Nome da coluna de senha usada pelo guard de autenticacao (RNF-001: senha
     * nunca em texto puro, sempre hash). Colunas do contrato aprovado usam
     * nomenclatura em portugues (CAMPO-USU-002/003/004: nome/email/senha), nao
     * os nomes padrao do scaffold Laravel (name/password).
     */
    public function getAuthPassword(): string
    {
        return $this->senha;
    }
}
