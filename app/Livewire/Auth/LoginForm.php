<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * RF-002 — Login (autenticacao), TELA-001.
 * Contrato: arquitetura.documentacao_tecnica.componentes_livewire (App\Livewire\Auth\LoginForm).
 */
#[Layout('layouts.guest')]
class LoginForm extends Component
{
    public string $email = '';

    public string $senha = '';

    /**
     * Mensagem de erro geral (RN-006 — credenciais invalidas; limite de tentativas), separada dos
     * erros de campo porque nao esta ligada a um input especifico — segue o mesmo padrao ja
     * estabelecido em RegistroForm::$erroGeral (RF-001).
     */
    public string $erroGeral = '';

    /**
     * Segue o mesmo padrao ja estabelecido em RegistroForm::registrar() (RF-001, BUG-003-SEC):
     * a submissao real (wire:submit) bate no endpoint compartilhado POST /livewire/update do
     * pacote Livewire, sem throttle proprio configurado (config/livewire.php nao publicado/
     * customizado). O login e um alvo classico de forca bruta/credential stuffing, entao aplica-se
     * o mesmo padrao ja auditado e aprovado no RF-001, adaptado a chave email+IP (mesma logica do
     * trait Illuminate\Foundation\Auth\ThrottlesLogins do proprio Laravel): limita a combinacao
     * email+IP a 5 tentativas por 60s, sem travar outros emails testados do mesmo IP nem o mesmo
     * email tentado de IPs diferentes isoladamente alem do que essa chave composta ja cobre.
     */
    public function autenticar(): void
    {
        $this->erroGeral = '';

        $chaveLimite = $this->chaveLimiteTentativas();

        if (RateLimiter::tooManyAttempts($chaveLimite, 5)) {
            $segundos = RateLimiter::availableIn($chaveLimite);
            $this->erroGeral = __('Muitas tentativas. Tente novamente em :segundos segundos.', ['segundos' => $segundos]);

            return;
        }

        $dados = $this->validate([
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ], [
            'email.required' => __('Informe um email valido.'),
            'email.email' => __('Informe um email valido.'),
            'senha.required' => __('Informe sua senha.'),
        ]);

        $usuario = User::where('email', $dados['email'])->first();

        // RN-006: mensagem generica unica (erroGeral, nao ligada a um campo especifico), sem
        // indicar qual dos dois esta incorreto — nem se o email existe (evita oraculo de
        // enumeracao). Conta como tentativa mesmo quando so o formato de email/senha vazia falhou
        // a validacao acima? Nao: RateLimiter::hit so roda aqui, apos validacao de formato passar,
        // porque uma tentativa de credenciais so existe quando ha de fato email+senha a comparar.
        if (! $usuario || ! Hash::check($dados['senha'], $usuario->getAuthPassword())) {
            RateLimiter::hit($chaveLimite, 60);

            $this->erroGeral = __('Email ou senha invalidos.');

            return;
        }

        RateLimiter::clear($chaveLimite);

        Auth::login($usuario);
        session()->regenerate();

        $this->redirectRoute('overview', navigate: true);
    }

    private function chaveLimiteTentativas(): string
    {
        return 'login:'.Str::lower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
