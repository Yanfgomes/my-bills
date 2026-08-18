<?php

use App\Livewire\Auth\RegistroForm;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

/**
 * RF-001 — Cadastro de usuario (registro aberto), TELA-002.
 * Testes unitarios/integracao do componente App\Livewire\Auth\RegistroForm, cobrindo o
 * criterio de aceite e as regras de negocio do RF (RN-004), mais os bugs corrigidos no
 * historico deste RF (BUG-003-SEC rate limiting, BUG-004-ACC/BUG-005-SEC confirmacao de
 * senha) como testes de regressao automatizados.
 */
uses(RefreshDatabase::class);

it('cria usuario com sucesso, faz hash da senha, registra log de auditoria e nao expoe a senha', function () {
    Livewire::test(RegistroForm::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'fulano@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'senha1234')
        ->call('registrar')
        ->assertHasNoErrors()
        ->assertRedirect(route('overview'));

    $usuario = User::where('email', 'fulano@example.com')->first();

    expect($usuario)->not->toBeNull()
        ->and($usuario->senha)->not->toBe('senha1234')
        ->and(Hash::check('senha1234', $usuario->senha))->toBeTrue();

    // Usuario autenticado apos o registro (Auth::login()).
    expect(auth()->id())->toBe($usuario->id);

    // Log de auditoria de criacao gravado, com usuario_id == id do proprio usuario recem-criado
    // (convencao documentada em AuditoriaObserver::registrar() para o auto-registro do RF-001).
    $log = LogAuditoria::where('tabela_afetada', 'users')
        ->where('registro_id', $usuario->id)
        ->where('acao', 'criacao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_novo)->not->toBeNull()
        ->and($log->valor_novo)->not->toHaveKey('senha')
        ->and($log->valor_novo)->not->toHaveKey('remember_token');
});

it('RN-004: rejeita cadastro com e-mail ja cadastrado, sem criar registro novo', function () {
    User::factory()->create(['email' => 'existente@example.com']);

    $totalAntes = User::count();

    Livewire::test(RegistroForm::class)
        ->set('nome', 'Outro Usuario')
        ->set('email', 'existente@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'senha1234')
        ->call('registrar')
        ->assertHasErrors(['email']);

    expect(User::count())->toBe($totalAntes);
});

it('rejeita senha com menos de 8 caracteres', function () {
    Livewire::test(RegistroForm::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'senhacurta@example.com')
        ->set('senha', 'abc123')
        ->set('senha_confirmation', 'abc123')
        ->call('registrar')
        ->assertHasErrors(['senha']);

    expect(User::where('email', 'senhacurta@example.com')->exists())->toBeFalse();
});

it('rejeita confirmacao de senha divergente', function () {
    Livewire::test(RegistroForm::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'divergente@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'outrasenha')
        ->call('registrar')
        ->assertHasErrors(['senha_confirmation']);

    expect(User::where('email', 'divergente@example.com')->exists())->toBeFalse();
});

it('rejeita confirmacao de senha vazia (regressao de BUG-005-SEC)', function () {
    // BUG-005-SEC: com a regra antiga ['same:senha'] (sem 'required') em 'senha_confirmation',
    // submeter esse campo vazio fazia o Validator pular a regra inteiramente (nao apenas
    // "passar"), permitindo cadastro sem a confirmacao de senha jamais ter sido checada. A
    // regra correta e ['required', 'same:senha'] -- este teste trava esse comportamento.
    Livewire::test(RegistroForm::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'confirmacaovazia@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', '')
        ->call('registrar')
        ->assertHasErrors(['senha_confirmation']);

    expect(User::where('email', 'confirmacaovazia@example.com')->exists())->toBeFalse();
});

it('nao duplica a mensagem de erro de confirmacao de senha no campo "senha" (regressao de BUG-004-ACC)', function () {
    // BUG-004-ACC: a falha de confirmacao de senha deve ficar isolada na chave de erro
    // 'senha_confirmation', nunca anexada a chave 'senha' (que so deve carregar os proprios
    // erros de required/min do campo Senha).
    Livewire::test(RegistroForm::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'naoduplica@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'diferente1234')
        ->call('registrar')
        ->assertHasErrors(['senha_confirmation'])
        ->assertHasNoErrors(['senha']);
});

it('BUG-003-SEC: bloqueia apos exceder o limite de tentativas de registro, sem persistir nada', function () {
    // Chave de rate limit e por IP (RateLimiter::hit('registro:'.request()->ip(), 60)), limite
    // de 5 tentativas/60s. Testes HTTP do Laravel usam IP padrao '127.0.0.1'; simulamos o
    // limite ja excedido para nao depender de estourar 6 requisicoes reais em sequencia.
    RateLimiter::clear('registro:127.0.0.1');
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('registro:127.0.0.1', 60);
    }

    $totalAntes = User::count();

    $component = Livewire::test(RegistroForm::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'ratelimit@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'senha1234')
        ->call('registrar');

    expect($component->get('erroGeral'))->not->toBe('');
    expect(User::count())->toBe($totalAntes);
    expect(User::where('email', 'ratelimit@example.com')->exists())->toBeFalse();

    RateLimiter::clear('registro:127.0.0.1');
});

it('permite registro normalmente quando o limite de tentativas nao foi excedido', function () {
    RateLimiter::clear('registro:127.0.0.1');

    Livewire::test(RegistroForm::class)
        ->set('nome', 'Fulano de Tal')
        ->set('email', 'semlimite@example.com')
        ->set('senha', 'senha1234')
        ->set('senha_confirmation', 'senha1234')
        ->call('registrar')
        ->assertHasNoErrors();

    expect(User::where('email', 'semlimite@example.com')->exists())->toBeTrue();

    RateLimiter::clear('registro:127.0.0.1');
});
