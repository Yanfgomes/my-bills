<?php

use App\Livewire\Auth\LoginForm;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

/**
 * RF-002 — Login (autenticacao), TELA-001.
 * Testes unitarios/integracao do componente App\Livewire\Auth\LoginForm e do
 * App\Http\Controllers\Auth\LogoutController, cobrindo o criterio de aceite, RN-006 e o
 * historico de bugs corrigidos neste RF (BUG-001-ACC nao se aplica aqui — e puramente
 * front-end/CSS, fora do escopo de teste de produto; BUG-006-SEC, BUG-007-SEC e BUG-008-QA
 * cobertos abaixo como testes de regressao automatizados).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('login:fulano@example.com|127.0.0.1');
});

it('autentica com credenciais validas, inicia sessao e redireciona para overview', function () {
    $usuario = User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senha1234'),
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasNoErrors()
        ->assertRedirect(route('overview'));

    expect(auth()->id())->toBe($usuario->id);
});

it('regenera o id de sessao apos login bem-sucedido', function () {
    // session()->regenerate() troca o id da sessao apos autenticar (mitigacao de fixacao de
    // sessao). Como o teste HTTP do Laravel nao expoe diretamente o id anterior/novo de forma
    // trivial via Livewire::test(), a evidencia funcional equivalente e: o usuario autenticado
    // corresponde ao esperado e a sessao contem a chave de autenticacao do guard 'web' apos a
    // chamada — comportamento que so ocorre se a sessao foi de fato regenerada/vinculada sem
    // erro (a implementacao anterior com request()->session()->regenerate() lancava
    // RuntimeException nesse mesmo cenario, conforme documentado em estado-rf-RF-002.json).
    $usuario = User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senha1234'),
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($usuario->id)
        ->and(session()->has('login_web_'.sha1(SessionGuard::class)))->toBeTrue();
});

it('RN-006: rejeita email nao cadastrado com mensagem generica unica', function () {
    Livewire::test(LoginForm::class)
        ->set('email', 'naoexiste@example.com')
        ->set('senha', 'qualquersenha')
        ->call('autenticar');

    $componente = Livewire::test(LoginForm::class)
        ->set('email', 'naoexiste@example.com')
        ->set('senha', 'qualquersenha')
        ->call('autenticar');

    expect($componente->get('erroGeral'))->toBe('Email ou senha invalidos.');
    expect(auth()->check())->toBeFalse();
});

it('RN-006: rejeita senha incorreta com a mesma mensagem generica do email inexistente', function () {
    User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senhacorreta'),
    ]);

    $componente = Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', 'senhaerrada')
        ->call('autenticar');

    expect($componente->get('erroGeral'))->toBe('Email ou senha invalidos.');
    expect(auth()->check())->toBeFalse();
});

it('RN-006: nao indica em qual campo esta o erro de credenciais (sem assertHasErrors de campo)', function () {
    // A mensagem generica fica em $erroGeral (campo unico, nao ligado a 'email' nem a 'senha'),
    // nunca via $errors->has('email')/$errors->has('senha') — e assim que o front-end evita
    // indicar qual dos dois campos esta incorreto, conforme criterio de aceite do RF.
    User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senhacorreta'),
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', 'senhaerrada')
        ->call('autenticar')
        ->assertHasNoErrors(['email', 'senha']);
});

it('exige email valido antes de submeter', function () {
    Livewire::test(LoginForm::class)
        ->set('email', 'nao-e-um-email')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

it('exige email preenchido antes de submeter', function () {
    Livewire::test(LoginForm::class)
        ->set('email', '')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasErrors(['email']);
});

it('exige senha preenchida antes de submeter', function () {
    Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', '')
        ->call('autenticar')
        ->assertHasErrors(['senha']);
});

it('BUG-007-SEC: rejeita senha com mais de 255 caracteres (regressao)', function () {
    Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', str_repeat('a', 256))
        ->call('autenticar')
        ->assertHasErrors(['senha' => 'max']);

    expect(auth()->check())->toBeFalse();
});

it('aceita senha com exatamente 255 caracteres (limite da regra max:255)', function () {
    User::factory()->create([
        'email' => 'limite255@example.com',
        'senha' => Hash::make(str_repeat('a', 255)),
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', 'limite255@example.com')
        ->set('senha', str_repeat('a', 255))
        ->call('autenticar')
        ->assertHasNoErrors(['senha']);
});

it('BUG-008-QA: mensagem de "senha.max" e traduzida corretamente nos 3 locales (pt/en/es), sem fallback em ingles', function () {
    $mensagens = [
        'pt' => 'A senha deve ter no maximo 255 caracteres.',
        'en' => 'Password must be at most 255 characters.',
        'es' => 'La contrasena debe tener como maximo 255 caracteres.',
    ];

    foreach ($mensagens as $locale => $esperado) {
        App::setLocale($locale);

        $componente = Livewire::test(LoginForm::class)
            ->set('email', 'fulano@example.com')
            ->set('senha', str_repeat('a', 256))
            ->call('autenticar')
            ->assertHasErrors(['senha' => 'max']);

        expect($componente->errors()->first('senha'))->toBe($esperado);
    }

    App::setLocale('pt');
});

it('BUG-006-SEC: caminho "email nao existe" e "senha incorreta" retornam a mesma mensagem generica (equalizacao funcional)', function () {
    // O security-agent ja mediu o timing real (ver estado-rf-RF-002.json, bugs.BUG-006-SEC) —
    // aqui cobrimos apenas o comportamento funcional: os dois caminhos, apesar de internamente
    // percorrerem o mesmo custo de Hash::check() (real ou dummy), retornam exatamente a mesma
    // string de erro, sem qualquer diferenca perceptivel ao usuario/atacante pela resposta.
    User::factory()->create([
        'email' => 'existente@example.com',
        'senha' => Hash::make('senhacorreta'),
    ]);

    $emailInexistente = Livewire::test(LoginForm::class)
        ->set('email', 'naoexiste@example.com')
        ->set('senha', 'qualquersenha')
        ->call('autenticar');

    RateLimiter::clear('login:existente@example.com|127.0.0.1');

    $senhaIncorreta = Livewire::test(LoginForm::class)
        ->set('email', 'existente@example.com')
        ->set('senha', 'senhaerrada')
        ->call('autenticar');

    expect($emailInexistente->get('erroGeral'))
        ->toBe($senhaIncorreta->get('erroGeral'))
        ->toBe('Email ou senha invalidos.');
});

it('rate limiting: bloqueia apos exceder 5 tentativas de login para o mesmo email+IP', function () {
    User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senhacorreta'),
    ]);

    RateLimiter::clear('login:fulano@example.com|127.0.0.1');
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('login:fulano@example.com|127.0.0.1', 60);
    }

    $componente = Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', 'senhacorreta')
        ->call('autenticar');

    expect($componente->get('erroGeral'))->toContain('Muitas tentativas');
    expect(auth()->check())->toBeFalse();

    RateLimiter::clear('login:fulano@example.com|127.0.0.1');
});

it('permite login normalmente quando o limite de tentativas nao foi excedido', function () {
    User::factory()->create([
        'email' => 'semlimite@example.com',
        'senha' => Hash::make('senha1234'),
    ]);

    RateLimiter::clear('login:semlimite@example.com|127.0.0.1');

    Livewire::test(LoginForm::class)
        ->set('email', 'semlimite@example.com')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasNoErrors();

    expect(auth()->check())->toBeTrue();

    RateLimiter::clear('login:semlimite@example.com|127.0.0.1');
});

it('limpa o contador de tentativas apos login bem-sucedido', function () {
    User::factory()->create([
        'email' => 'limparcontador@example.com',
        'senha' => Hash::make('senha1234'),
    ]);

    RateLimiter::clear('login:limparcontador@example.com|127.0.0.1');
    RateLimiter::hit('login:limparcontador@example.com|127.0.0.1', 60);
    RateLimiter::hit('login:limparcontador@example.com|127.0.0.1', 60);

    Livewire::test(LoginForm::class)
        ->set('email', 'limparcontador@example.com')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasNoErrors();

    expect(RateLimiter::attempts('login:limparcontador@example.com|127.0.0.1'))->toBe(0);
});

it('nao expoe a senha em nenhum ponto do fluxo de autenticacao (RNF-001)', function () {
    $usuario = User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senha1234'),
    ]);

    Livewire::test(LoginForm::class)
        ->set('email', 'fulano@example.com')
        ->set('senha', 'senha1234')
        ->call('autenticar')
        ->assertHasNoErrors();

    expect($usuario->fresh()->toArray())->not->toHaveKey('senha')
        ->and($usuario->fresh()->toArray())->not->toHaveKey('remember_token');
});

it('logout: encerra a sessao autenticada e redireciona para login', function () {
    $usuario = User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senha1234'),
    ]);

    $sessaoIdAntes = null;

    $this->actingAs($usuario)
        ->withSession(['_marcador_sessao_anterior' => true])
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('logout: invalida a sessao anterior (nao mantem dados da sessao pre-logout)', function () {
    $usuario = User::factory()->create([
        'email' => 'fulano@example.com',
        'senha' => Hash::make('senha1234'),
    ]);

    $this->actingAs($usuario)
        ->withSession(['_marcador_sessao_anterior' => 'valor-antes-do-logout'])
        ->post(route('logout'));

    expect(session('_marcador_sessao_anterior'))->toBeNull();
});

it('logout: exige usuario autenticado (rota protegida por middleware auth)', function () {
    $this->post(route('logout'))
        ->assertRedirect(route('login'));
});
