<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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

    /**
     * BUG-003-SEC: a rota GET '/registro' so serve o formulario; a submissao real (wire:submit)
     * bate no endpoint compartilhado POST /livewire/update do proprio pacote Livewire, que nao
     * tem throttle por padrao neste projeto (config/livewire.php nao publicado/customizado — ver
     * vendor/livewire/livewire/config/livewire.php). Aplicar 'throttle' na rota nomeada 'registro'
     * nao cobriria esse caminho. Em vez de publicar a config e aplicar throttle global a cada
     * requisicao Livewire da aplicacao (afetaria wire:model/outros componentes futuros), o limite e
     * aplicado aqui, por acao, chaveado por IP — mesmo padrao usado por Laravel Fortify para login.
     * Limita tentativas de registro (inclui tentativas com email ja cadastrado, RN-004) a 5 por
     * minuto por IP, mitigando criacao em massa de contas e enumeracao de e-mails em escala.
     */
    public function registrar(): void
    {
        $this->erroGeral = '';

        $chaveLimite = 'registro:'.request()->ip();

        if (RateLimiter::tooManyAttempts($chaveLimite, 5)) {
            $segundos = RateLimiter::availableIn($chaveLimite);
            $this->erroGeral = __('Muitas tentativas. Tente novamente em :segundos segundos.', ['segundos' => $segundos]);

            return;
        }

        RateLimiter::hit($chaveLimite, 60);

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
