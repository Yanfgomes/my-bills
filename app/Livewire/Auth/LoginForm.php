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
            'senha' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => __('Informe um email valido.'),
            'email.email' => __('Informe um email valido.'),
            'senha.required' => __('Informe sua senha.'),
        ]);

        $usuario = User::where('email', $dados['email'])->first();

        // BUG-006-SEC: quando $usuario e null, Hash::check() nunca rodaria por causa do
        // curto-circuito do '||' — isso cria um timing side-channel (bcrypt e computacionalmente
        // caro; o caminho "email nao existe" retornaria quase instantaneo, o caminho "email existe,
        // senha errada" seria mensuravelmente mais lento), permitindo enumerar emails cadastrados
        // por medicao de tempo de resposta mesmo com a mensagem de erro sendo generica. Para
        // equalizar o tempo dos dois caminhos, sempre executamos Hash::check() — contra o hash real
        // do usuario quando ele existe, ou contra um hash dummy fixo (mesmo custo bcrypt) quando
        // nao existe — antes de decidir a mensagem generica unica (RN-006).
        // Nota: Hash::check() precisa ser a PRIMEIRA operacao do '&&'/avaliada incondicionalmente —
        // se a checagem de $usuario viesse antes por curto-circuito, o bug se repetiria de outra
        // forma. $senhaConfere e sempre calculado antes de qualquer decisao baseada em $usuario.
        $senhaConfere = Hash::check($dados['senha'], $usuario?->getAuthPassword() ?? $this->hashDummyParaEqualizarTempo());
        $credenciaisValidas = $usuario !== null && $senhaConfere;

        if (! $credenciaisValidas) {
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

    /**
     * BUG-006-SEC: hash bcrypt fixo (nao corresponde a nenhuma senha real) usado como alvo de
     * Hash::check() quando o email informado nao esta cadastrado, apenas para consumir o mesmo
     * custo computacional do caminho "email existe" e equalizar o tempo de resposta dos dois
     * casos, fechando o timing side-channel de enumeracao de usuarios.
     */
    private function hashDummyParaEqualizarTempo(): string
    {
        return '$2y$12$eImiTXuWVxfM37uY4JANjQZ4nO3aWQrO7Fb4kL9d1oPjKm4o8f/2G';
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
