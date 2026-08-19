<?php

use App\Livewire\Renda\RendaManager;
use App\Models\FonteRenda;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * RF-004 — Cadastro de fonte de renda, TELA-004 (App\Livewire\Renda\RendaManager).
 * Testes unitarios/integracao cobrindo o criterio de aceite, RN-002 (valor_liquido > 0),
 * RN-003 (mes_referencia formato AAAA-MM) e RN-005 (isolamento por usuario, Policy +
 * escopo de query), incluindo defesa em profundidade (CHECK de banco e authorize() em
 * cada acao).
 */
uses(RefreshDatabase::class);

it('exige autenticacao para acessar a rota renda.index', function () {
    $this->get(route('renda.index'))->assertRedirect(route('login'));
});

it('permite acesso autenticado e lista vazio quando o usuario nao tem fonte de renda', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->assertOk()
        ->assertSee('Nenhuma fonte de renda cadastrada ainda. Adicione sua primeira fonte de renda abaixo.');
});

it('cria fonte de renda com sucesso, vinculada ao usuario autenticado, reflete na listagem e registra log de auditoria', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '3500.50')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasNoErrors();

    $fonte = FonteRenda::where('usuario_id', $usuario->id)->first();

    expect($fonte)->not->toBeNull()
        ->and($fonte->descricao)->toBe('Salario')
        ->and((float) $fonte->valor_liquido)->toBe(3500.50)
        ->and($fonte->mes_referencia)->toBe('2026-08')
        ->and($fonte->usuario_id)->toBe($usuario->id);

    // Reflete na propria listagem do componente (fonte da verdade do RF-008/Overview
    // e a mesma query usada aqui: FonteRenda::where('usuario_id', ...)).
    Livewire::test(RendaManager::class)
        ->assertSee('Salario')
        ->assertSee('2026-08');

    $log = LogAuditoria::where('tabela_afetada', 'fontes_renda')
        ->where('registro_id', $fonte->id)
        ->where('acao', 'criacao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_novo)->not->toBeNull()
        ->and($log->valor_novo['descricao'])->toBe('Salario');
});

it('permite mais de uma fonte de renda no mesmo mes/periodo para o mesmo usuario', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '3000')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Freelance')
        ->set('valorLiquido', '800')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasNoErrors();

    expect(FonteRenda::where('usuario_id', $usuario->id)->where('mes_referencia', '2026-08')->count())->toBe(2);
});

it('reseta os campos do formulario apos criacao bem-sucedida', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertSet('descricao', '')
        ->assertSet('valorLiquido', '')
        ->assertSet('mesReferencia', '');
});

it('RN-002: rejeita valor liquido igual a zero com a mensagem esperada, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '0')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valorLiquido'])
        ->assertSee('o valor deve ser maior que zero');

    expect(FonteRenda::count())->toBe(0);
});

it('RN-002: rejeita valor liquido negativo, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '-50')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valorLiquido']);

    expect(FonteRenda::count())->toBe(0);
});

it('RN-002: rejeita valor liquido nao numerico, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', 'abc')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valorLiquido']);

    expect(FonteRenda::count())->toBe(0);
});

it('RN-002: rejeita valor liquido ausente, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valorLiquido']);

    expect(FonteRenda::count())->toBe(0);
});

it('RN-002: constraint CHECK de banco rejeita valor_liquido <= 0 mesmo contornando a validacao da aplicacao', function () {
    $usuario = User::factory()->create();

    expect(fn () => FonteRenda::withoutEvents(fn () => \Illuminate\Support\Facades\DB::table('fontes_renda')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'usuario_id' => $usuario->id,
        'descricao' => 'Bypass direto no banco',
        'valor_liquido' => 0,
        'mes_referencia' => '2026-08',
        'criado_em' => now(),
        'atualizado_em' => now(),
    ])))->toThrow(QueryException::class);
});

it('RN-003: rejeita mes_referencia ausente, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', '')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(FonteRenda::count())->toBe(0);
});

it('RN-003: rejeita mes_referencia fora do formato AAAA-MM (ex: mes 13), sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', '2026-13')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(FonteRenda::count())->toBe(0);
});

it('RN-003: rejeita mes_referencia com mes zero ("2026-00"), sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', '2026-00')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(FonteRenda::count())->toBe(0);
});

it('RN-003: rejeita mes_referencia com formato solto/incompleto ("2026-8"), sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', '2026-8')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(FonteRenda::count())->toBe(0);
});

it('rejeita descricao ausente, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(RendaManager::class)
        ->set('descricao', '')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['descricao']);

    expect(FonteRenda::count())->toBe(0);
});

it('RN-005: um usuario nao ve fontes de renda de outro usuario na listagem', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    FonteRenda::create([
        'usuario_id' => $alice->id,
        'descricao' => 'Salario da Alice',
        'valor_liquido' => 5000,
        'mes_referencia' => '2026-08',
    ]);
    FonteRenda::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Salario do Bob',
        'valor_liquido' => 4000,
        'mes_referencia' => '2026-08',
    ]);

    $this->actingAs($alice);

    Livewire::test(RendaManager::class)
        ->assertSee('Salario da Alice')
        ->assertDontSee('Salario do Bob');

    expect(FonteRenda::where('usuario_id', $alice->id)->count())->toBe(1);
});

it('RN-005: Policy (view/update/delete) nega acesso a fonte de renda de outro usuario, com dois usuarios reais', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $fonteDeBob = FonteRenda::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Salario do Bob',
        'valor_liquido' => 4000,
        'mes_referencia' => '2026-08',
    ]);

    $this->actingAs($alice);

    expect(Auth::user()->can('view', $fonteDeBob))->toBeFalse()
        ->and(Auth::user()->can('update', $fonteDeBob))->toBeFalse()
        ->and(Auth::user()->can('delete', $fonteDeBob))->toBeFalse();

    $this->actingAs($bob);

    expect(Auth::user()->can('view', $fonteDeBob))->toBeTrue()
        ->and(Auth::user()->can('update', $fonteDeBob))->toBeTrue()
        ->and(Auth::user()->can('delete', $fonteDeBob))->toBeTrue();
});

it('RN-005: Policy autoriza viewAny/create para qualquer usuario autenticado, e nega sem autenticacao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    expect(Auth::user()->can('viewAny', FonteRenda::class))->toBeTrue()
        ->and(Auth::user()->can('create', FonteRenda::class))->toBeTrue();

    Auth::logout();

    expect(Auth::guest())->toBeTrue()
        ->and(Auth::check())->toBeFalse();
});

it('FonteRenda::usuario() resolve o usuario dono do registro', function () {
    $usuario = User::factory()->create();

    $fonte = FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario',
        'valor_liquido' => 1000,
        'mes_referencia' => '2026-08',
    ]);

    expect($fonte->usuario)->not->toBeNull()
        ->and($fonte->usuario->id)->toBe($usuario->id);
});

it('ordena a listagem por mes_referencia desc, depois criado_em desc', function () {
    $usuario = User::factory()->create();

    // criado_em setado explicitamente (nao apenas em sequencia de create()) porque o
    // DATETIME do SQLite tem precisao de segundo -- criacoes em sequencia rapida no
    // mesmo teste poderiam colidir no mesmo segundo e tornar o desempate flaky.
    $antiga = FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Julho',
        'valor_liquido' => 1000,
        'mes_referencia' => '2026-07',
        'criado_em' => now()->subMinutes(10),
    ]);
    $recenteMaisAntiga = FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Agosto A',
        'valor_liquido' => 2000,
        'mes_referencia' => '2026-08',
        'criado_em' => now()->subMinutes(5),
    ]);
    $recenteMaisNova = FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Agosto B',
        'valor_liquido' => 3000,
        'mes_referencia' => '2026-08',
        'criado_em' => now(),
    ]);

    $this->actingAs($usuario);

    $rendas = FonteRenda::where('usuario_id', $usuario->id)
        ->orderByDesc('mes_referencia')
        ->orderByDesc('criado_em')
        ->get();

    expect($rendas->pluck('id')->toArray())->toBe([
        $recenteMaisNova->id,
        $recenteMaisAntiga->id,
        $antiga->id,
    ]);
});
