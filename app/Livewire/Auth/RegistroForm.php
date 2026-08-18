<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-001 — Cadastro de usuario (registro aberto), TELA-002.
 * Contrato: arquitetura.documentacao_tecnica.componentes_livewire (App\Livewire\Auth\RegistroForm).
 */
#[Layout('layouts.guest')]
class RegistroForm extends Component
{
    public string $nome = '';

    public string $email = '';

    public string $senha = '';

    public string $senha_confirmation = '';

    /**
     * Mensagem de erro geral (ex: RN-004 — email ja cadastrado), separada dos erros de campo
     * porque nao esta ligada a um input especifico.
     */
    public string $erroGeral = '';

    public function registrar(): void
    {
        $this->erroGeral = '';

        $dados = $this->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'senha' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nome.required' => __('Campo obrigatorio.'),
            'email.required' => __('Informe um email valido.'),
            'email.email' => __('Informe um email valido.'),
            'email.unique' => __('e-mail ja cadastrado'),
            'senha.required' => __('A senha deve ter no minimo 8 caracteres.'),
            'senha.min' => __('A senha deve ter no minimo 8 caracteres.'),
            'senha.confirmed' => __('As senhas nao coincidem.'),
        ]);

        $usuario = User::create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha' => Hash::make($dados['senha']),
        ]);

        Auth::login($usuario);

        $this->redirectRoute('overview', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.registro-form');
    }
}
