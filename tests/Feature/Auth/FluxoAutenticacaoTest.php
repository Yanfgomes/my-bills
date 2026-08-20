<?php

use App\Livewire\Auth\LoginForm;
use App\Livewire\Auth\RegistroForm;
use App\Livewire\Overview\OverviewFinanceiro;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * FLUXO-001 — Autenticacao (RF-001 registro + RF-002 login), teste de SISTEMA.
 *
 * Nao repete os testes unitarios/integracao ja existentes em RegistroFormTest.php e
 * LoginFormTest.php (validacao de campo a campo, rate limiting, mensagens, RN-004, RN-006 etc).
 * Cobre a COMPOSICAO real de uso do fluxo -- registro seguido de login com as mesmas credenciais,
 * ponta a ponta ate uma pagina autenticada -- e a regressao de BUG-007-SEC (session fixation) nos
 * dois pontos de entrada juntos, condicao que so a composicao do fluxo evidencia por completo.
 */
uses(RefreshDatabase::class);

it('fluxo feliz completo: registro -> redireciona para overview -> logout -> login com as mesmas credenciais -> acessa overview autenticado', function () {
    // Etapa 1: registro de um novo usuario.
    Livewire::test(RegistroForm::class)
        ->set('nome', 'Ciclo Completo')
        ->set('email', 'ciclo.completo@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'senha1234')
        ->call('registrar')
        ->assertHasNoErrors()
        ->assertRedirect(route('overview'));

    $usuario = User::where('email', 'ciclo.completo@example.com')->first();
    expect($usuario)->not->toBeNull();

    // Registro ja autentica o usuario (Auth::login() em RegistroForm) -- confirma antes de
    // encerrar essa sessao para exercitar o login como etapa separada e independente.
    expect(auth()->id())->toBe($usuario->id);

    $this->post(route('logout'))->assertRedirect(route('login'));
    expect(auth()->check())->toBeFalse();

    // Etapa 2: login com as credenciais recem-criadas no registro.
    Livewire::test(LoginForm::class)
        ->set('email', 'ciclo.completo@example.com')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasNoErrors()
        ->assertRedirect(route('overview'));

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($usuario->id);

    // Etapa 3: sessao autenticada e de fato utilizavel -- acessa uma pagina protegida por
    // middleware 'auth' (nao um componente Livewire isolado, mas a rota real do fluxo).
    $this->get(route('overview'))
        ->assertOk()
        ->assertSeeLivewire(OverviewFinanceiro::class);
});

it('BUG-007-SEC (regressao de composicao): registro seguido de login cada um regenera o id de sessao, sem herdar sessao fixada de uma etapa anterior', function () {
    // Simula um atacante fixando um ID de sessao ANTES do cadastro da vitima (session fixation).
    // Se RegistroForm::registrar() nao regenerasse a sessao (BUG-007-SEC original), o ID fixado
    // aqui permaneceria autenticado apos o cadastro -- exatamente o cenario que a composicao do
    // fluxo de registro->login precisa travar como regressao, nos dois pontos de entrada.
    session()->start();
    $idSessaoFixadoAntesDoRegistro = session()->getId();

    Livewire::test(RegistroForm::class)
        ->set('nome', 'Alvo Fixation')
        ->set('email', 'alvo.fixation@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'senha1234')
        ->call('registrar')
        ->assertHasNoErrors();

    $usuario = User::where('email', 'alvo.fixation@example.com')->first();

    expect(auth()->id())->toBe($usuario->id)
        ->and(session()->has('login_web_'.sha1(SessionGuard::class)))->toBeTrue()
        ->and(session()->getId())->not->toBe($idSessaoFixadoAntesDoRegistro);

    $this->post(route('logout'))->assertRedirect(route('login'));
    expect(auth()->check())->toBeFalse();

    // Repete o mesmo ataque no ponto de entrada do login, sobre o MESMO usuario ja cadastrado --
    // regressao do comportamento equivalente ja corrigido em LoginForm::autenticar().
    session()->start();
    $idSessaoFixadoAntesDoLogin = session()->getId();

    Livewire::test(LoginForm::class)
        ->set('email', 'alvo.fixation@example.com')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasNoErrors();

    expect(auth()->id())->toBe($usuario->id)
        ->and(session()->has('login_web_'.sha1(SessionGuard::class)))->toBeTrue()
        ->and(session()->getId())->not->toBe($idSessaoFixadoAntesDoLogin);
});

it('login rejeitado nao autentica nem concede acesso a pagina protegida do fluxo (RN-006 na composicao)', function () {
    User::factory()->create([
        'email' => 'credenciais.invalidas@example.com',
        'senha' => Hash::make('senhacorreta'),
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', 'credenciais.invalidas@example.com')
        ->set('senha', 'senhaerrada')
        ->call('autenticar');

    expect(auth()->check())->toBeFalse();

    $this->get(route('overview'))->assertRedirect(route('login'));
});
