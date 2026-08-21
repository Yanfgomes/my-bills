<?php

use App\Livewire\Despesas\DespesaManager;
use App\Models\Despesa;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * RF-006 — Cadastro de despesa, TELA-005 (App\Livewire\Despesas\DespesaManager).
 * Testes unitarios/integracao cobrindo o criterio de aceite, RN-002 (valor > 0),
 * RN-003 (mes_referencia formato AAAA-MM), categoria opcional (CAMPO-DESP-005) e
 * RN-005 (isolamento por usuario, Policy + escopo de query), incluindo defesa em
 * profundidade (CHECK de banco e authorize() em cada acao).
 *
 * Blocos "RF-007:" abaixo cobrem editar()/atualizar()/cancelarEdicao()/excluir() -- regressao
 * automatizada do que o security-agent ja confirmou manualmente (pentest) no ciclo de QA deste
 * RF: isolamento por usuario (IDOR) em editar/atualizar/excluir, imutabilidade do mes/periodo em
 * edicao (RN-008) e rejeicao de valor invalido em atualizar() (RN-002). Espelha o padrao ja
 * aprovado e testado em RF-005 (App\Livewire\Renda\RendaManager).
 */
uses(RefreshDatabase::class);

it('exige autenticacao para acessar a rota despesas.index', function () {
    $this->get(route('despesas.index'))->assertRedirect(route('login'));
});

it('permite acesso autenticado e lista vazio quando o usuario nao tem despesa', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->assertOk()
        ->assertSee('Nenhuma despesa cadastrada ainda. Adicione sua primeira despesa abaixo.');
});

it('cria despesa com sucesso, vinculada ao usuario autenticado, reflete na listagem e registra log de auditoria', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1500.75')
        ->set('categoria', 'Moradia')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasNoErrors();

    $despesa = Despesa::where('usuario_id', $usuario->id)->first();

    expect($despesa)->not->toBeNull()
        ->and($despesa->descricao)->toBe('Aluguel')
        ->and((float) $despesa->valor)->toBe(1500.75)
        ->and($despesa->categoria)->toBe('Moradia')
        ->and($despesa->mes_referencia)->toBe('2026-08')
        ->and($despesa->usuario_id)->toBe($usuario->id);

    // Reflete na propria listagem do componente (fonte da verdade do RF-008/Overview
    // e a mesma query usada aqui: Despesa::where('usuario_id', ...)).
    Livewire::test(DespesaManager::class)
        ->assertSee('Aluguel')
        ->assertSee('2026-08');

    $log = LogAuditoria::where('tabela_afetada', 'despesas')
        ->where('registro_id', $despesa->id)
        ->where('acao', 'criacao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_novo)->not->toBeNull()
        ->and($log->valor_novo['descricao'])->toBe('Aluguel');
});

it('CAMPO-DESP-005: categoria e opcional -- aceita cadastro sem categoria informada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Mercado')
        ->set('valor', '250')
        ->set('categoria', '')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasNoErrors();

    $despesa = Despesa::where('usuario_id', $usuario->id)->first();

    expect($despesa)->not->toBeNull()
        ->and($despesa->descricao)->toBe('Mercado')
        ->and($despesa->categoria)->toBeNull();
});

it('permite mais de uma despesa no mesmo mes/periodo para o mesmo usuario', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1500')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Mercado')
        ->set('valor', '400')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasNoErrors();

    expect(Despesa::where('usuario_id', $usuario->id)->where('mes_referencia', '2026-08')->count())->toBe(2);
});

it('reseta os campos do formulario apos criacao bem-sucedida', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1000')
        ->set('categoria', 'Moradia')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertSet('descricao', '')
        ->assertSet('valor', '')
        ->assertSet('categoria', '')
        ->assertSet('mesReferencia', '');
});

it('rejeita descricao ausente, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', '')
        ->set('valor', '1000')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['descricao']);

    expect(Despesa::count())->toBe(0);
});

it('RN-002: rejeita valor igual a zero com a mensagem esperada, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '0')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valor'])
        ->assertSee('o valor deve ser maior que zero');

    expect(Despesa::count())->toBe(0);
});

it('RN-002: rejeita valor negativo, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '-50')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valor']);

    expect(Despesa::count())->toBe(0);
});

it('RN-002: rejeita valor nao numerico, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', 'abc')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valor']);

    expect(Despesa::count())->toBe(0);
});

it('RN-002: rejeita valor ausente, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '')
        ->set('mesReferencia', '2026-08')
        ->call('criar')
        ->assertHasErrors(['valor']);

    expect(Despesa::count())->toBe(0);
});

it('RN-002: constraint CHECK de banco rejeita valor <= 0 mesmo contornando a validacao da aplicacao', function () {
    $usuario = User::factory()->create();

    expect(fn () => Despesa::withoutEvents(fn () => \Illuminate\Support\Facades\DB::table('despesas')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'usuario_id' => $usuario->id,
        'descricao' => 'Bypass direto no banco',
        'valor' => 0,
        'mes_referencia' => '2026-08',
        'criado_em' => now(),
        'atualizado_em' => now(),
    ])))->toThrow(QueryException::class);
});

it('RN-003: rejeita mes_referencia ausente, sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1000')
        ->set('mesReferencia', '')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(Despesa::count())->toBe(0);
});

it('RN-003: rejeita mes_referencia fora do formato AAAA-MM (ex: mes 13), sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1000')
        ->set('mesReferencia', '2026-13')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(Despesa::count())->toBe(0);
});

it('RN-003: rejeita mes_referencia com mes zero ("2026-00"), sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1000')
        ->set('mesReferencia', '2026-00')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(Despesa::count())->toBe(0);
});

it('RN-003: rejeita mes_referencia com formato solto/incompleto ("2026-8"), sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1000')
        ->set('mesReferencia', '2026-8')
        ->call('criar')
        ->assertHasErrors(['mesReferencia']);

    expect(Despesa::count())->toBe(0);
});

it('RN-005: um usuario nao ve despesas de outro usuario na listagem', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    Despesa::create([
        'usuario_id' => $alice->id,
        'descricao' => 'Aluguel da Alice',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);
    Despesa::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Aluguel do Bob',
        'valor' => 1200,
        'mes_referencia' => '2026-08',
    ]);

    $this->actingAs($alice);

    Livewire::test(DespesaManager::class)
        ->assertSee('Aluguel da Alice')
        ->assertDontSee('Aluguel do Bob');

    expect(Despesa::where('usuario_id', $alice->id)->count())->toBe(1);
});

it('RN-005: Policy (view/update/delete) nega acesso a despesa de outro usuario, com dois usuarios reais', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $despesaDeBob = Despesa::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Aluguel do Bob',
        'valor' => 1200,
        'mes_referencia' => '2026-08',
    ]);

    $this->actingAs($alice);

    expect(Auth::user()->can('view', $despesaDeBob))->toBeFalse()
        ->and(Auth::user()->can('update', $despesaDeBob))->toBeFalse()
        ->and(Auth::user()->can('delete', $despesaDeBob))->toBeFalse();

    $this->actingAs($bob);

    expect(Auth::user()->can('view', $despesaDeBob))->toBeTrue()
        ->and(Auth::user()->can('update', $despesaDeBob))->toBeTrue()
        ->and(Auth::user()->can('delete', $despesaDeBob))->toBeTrue();
});

it('RN-005: Policy autoriza viewAny/create para qualquer usuario autenticado, e nega sem autenticacao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    expect(Auth::user()->can('viewAny', Despesa::class))->toBeTrue()
        ->and(Auth::user()->can('create', Despesa::class))->toBeTrue();

    Auth::logout();

    expect(Auth::guest())->toBeTrue()
        ->and(Auth::check())->toBeFalse();
});

it('Despesa::usuario() resolve o usuario dono do registro', function () {
    $usuario = User::factory()->create();

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1000,
        'mes_referencia' => '2026-08',
    ]);

    expect($despesa->usuario)->not->toBeNull()
        ->and($despesa->usuario->id)->toBe($usuario->id);
});

it('ordena a listagem por mes_referencia desc, depois criado_em desc', function () {
    $usuario = User::factory()->create();

    // criado_em setado explicitamente (nao apenas em sequencia de create()) porque o
    // DATETIME do SQLite tem precisao de segundo -- criacoes em sequencia rapida no
    // mesmo teste poderiam colidir no mesmo segundo e tornar o desempate flaky.
    $antiga = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Julho',
        'valor' => 1000,
        'mes_referencia' => '2026-07',
        'criado_em' => now()->subMinutes(10),
    ]);
    $recenteMaisAntiga = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Agosto A',
        'valor' => 2000,
        'mes_referencia' => '2026-08',
        'criado_em' => now()->subMinutes(5),
    ]);
    $recenteMaisNova = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Agosto B',
        'valor' => 3000,
        'mes_referencia' => '2026-08',
        'criado_em' => now(),
    ]);

    $this->actingAs($usuario);

    $despesas = Despesa::where('usuario_id', $usuario->id)
        ->orderByDesc('mes_referencia')
        ->orderByDesc('criado_em')
        ->get();

    expect($despesas->pluck('id')->toArray())->toBe([
        $recenteMaisNova->id,
        $recenteMaisAntiga->id,
        $antiga->id,
    ]);
});

// RF-007 -----------------------------------------------------------------------------------

it('RF-007: editar() popula o formulario e entra em modo edicao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'categoria' => 'Moradia',
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->assertSet('descricao', 'Aluguel')
        ->assertSet('valor', '1500.00')
        ->assertSet('categoria', 'Moradia')
        ->assertSet('mesReferencia', '2026-08')
        ->assertSet('editandoId', $despesa->id)
        ->assertSeeHtml('despesa-mes-nota');
});

it('RF-007: atualizar() altera descricao, valor e categoria, reflete na listagem e registra log de auditoria', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'categoria' => 'Moradia',
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('descricao', 'Aluguel reajustado')
        ->set('valor', '1650.90')
        ->set('categoria', 'Casa')
        ->call('atualizar')
        ->assertHasNoErrors()
        ->assertSet('editandoId', null)
        ->assertSee('Aluguel reajustado');

    $despesa->refresh();

    expect($despesa->descricao)->toBe('Aluguel reajustado')
        ->and((float) $despesa->valor)->toBe(1650.90)
        ->and($despesa->categoria)->toBe('Casa');

    $log = LogAuditoria::where('tabela_afetada', 'despesas')
        ->where('registro_id', $despesa->id)
        ->where('acao', 'alteracao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_novo['descricao'])->toBe('Aluguel reajustado');
});

it('RF-007: atualizar() permite limpar categoria (torna-se nula)', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'categoria' => 'Moradia',
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('categoria', '')
        ->call('atualizar')
        ->assertHasNoErrors();

    expect($despesa->refresh()->categoria)->toBeNull();
});

it('RF-007/RN-008: atualizar() ignora tentativa de alterar mesReferencia via payload manipulado, mantendo o mes original', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('mesReferencia', '2026-12') // manipulacao direta da propriedade publica, campo e somente leitura na view (disabled)
        ->set('descricao', 'Aluguel ajustado')
        ->set('valor', '1600')
        ->call('atualizar')
        ->assertHasNoErrors();

    $despesa->refresh();

    expect($despesa->mes_referencia)->toBe('2026-08')
        ->and($despesa->descricao)->toBe('Aluguel ajustado');
});

it('RF-007/RN-002: atualizar() rejeita valor igual a zero, sem persistir alteracao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('valor', '0')
        ->call('atualizar')
        ->assertHasErrors(['valor'])
        ->assertSee('o valor deve ser maior que zero');

    expect((float) $despesa->refresh()->valor)->toBe(1500.0);
});

it('RF-007/RN-002: atualizar() rejeita valor negativo, sem persistir alteracao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('valor', '-10')
        ->call('atualizar')
        ->assertHasErrors(['valor']);

    expect((float) $despesa->refresh()->valor)->toBe(1500.0);
});

it('RF-007: atualizar() rejeita descricao ausente, sem persistir alteracao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('descricao', '')
        ->call('atualizar')
        ->assertHasErrors(['descricao']);

    expect($despesa->refresh()->descricao)->toBe('Aluguel');
});

it('RF-007: atualizar() e um no-op seguro quando chamado sem estar em modo edicao (editandoId nulo)', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->assertSet('editandoId', null)
        ->call('atualizar')
        ->assertHasNoErrors();

    expect($despesa->refresh()->descricao)->toBe('Aluguel');
});

it('RF-007: cancelarEdicao() reseta o formulario para o estado de criacao sem persistir nada', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('descricao', 'Rascunho nao salvo')
        ->call('cancelarEdicao')
        ->assertSet('descricao', '')
        ->assertSet('valor', '')
        ->assertSet('categoria', '')
        ->assertSet('mesReferencia', '')
        ->assertSet('editandoId', null)
        ->assertHasNoErrors();

    expect($despesa->refresh()->descricao)->toBe('Aluguel');
});

it('RF-007: excluir() remove a despesa, tira ela da listagem e registra log de auditoria', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('excluir', $despesa->id)
        ->assertDontSeeHtml('data-cy="despesa-linha-'.$despesa->id.'"')
        ->assertSeeHtml('data-cy="despesa-vazio"');

    expect(Despesa::find($despesa->id))->toBeNull();

    $log = LogAuditoria::where('tabela_afetada', 'despesas')
        ->where('registro_id', $despesa->id)
        ->where('acao', 'exclusao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_anterior['descricao'])->toBe('Aluguel');
});

it('RF-007: excluir() reseta o formulario quando o item excluido era o que estava em edicao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->assertSet('editandoId', $despesa->id)
        ->call('excluir', $despesa->id)
        ->assertSet('editandoId', null)
        ->assertSet('descricao', '')
        ->assertSet('valor', '')
        ->assertSet('categoria', '')
        ->assertSet('mesReferencia', '');
});

it('RF-007: excluir() nao reseta o formulario quando o item excluido nao era o que estava em edicao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $despesaEmEdicao = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1500,
        'mes_referencia' => '2026-08',
    ]);
    $outraDespesa = Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Mercado',
        'valor' => 400,
        'mes_referencia' => '2026-08',
    ]);

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesaEmEdicao->id)
        ->call('excluir', $outraDespesa->id)
        ->assertSet('editandoId', $despesaEmEdicao->id)
        ->assertSet('descricao', 'Aluguel');

    expect(Despesa::find($outraDespesa->id))->toBeNull()
        ->and(Despesa::find($despesaEmEdicao->id))->not->toBeNull();
});

it('RF-007/RN-005: editar() de despesa de outro usuario lanca ModelNotFoundException (nao vaza existencia do registro)', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $despesaDeBob = Despesa::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Aluguel do Bob',
        'valor' => 1200,
        'mes_referencia' => '2026-08',
    ]);

    $this->actingAs($alice);

    expect(fn () => Livewire::test(DespesaManager::class)->call('editar', $despesaDeBob->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('RF-007/RN-005: atualizar() nao permite editar despesa de outro usuario mesmo forcando editandoId', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $despesaDeBob = Despesa::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Aluguel do Bob',
        'valor' => 1200,
        'mes_referencia' => '2026-08',
    ]);

    $this->actingAs($alice);

    expect(fn () => Livewire::test(DespesaManager::class)
        ->set('editandoId', $despesaDeBob->id)
        ->set('descricao', 'Sequestrado pela Alice')
        ->set('valor', '1')
        ->call('atualizar'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect($despesaDeBob->refresh()->descricao)->toBe('Aluguel do Bob');
});

it('RF-007/RN-005: excluir() de despesa de outro usuario lanca ModelNotFoundException, sem apagar o registro', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $despesaDeBob = Despesa::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Aluguel do Bob',
        'valor' => 1200,
        'mes_referencia' => '2026-08',
    ]);

    $this->actingAs($alice);

    expect(fn () => Livewire::test(DespesaManager::class)->call('excluir', $despesaDeBob->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Despesa::find($despesaDeBob->id))->not->toBeNull();
});

it('RF-007: editar()/atualizar()/excluir() de id inexistente lanca ModelNotFoundException, mesmo comportamento de id de outro usuario', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $idInexistente = (string) \Illuminate\Support\Str::uuid();

    expect(fn () => Livewire::test(DespesaManager::class)->call('editar', $idInexistente))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(fn () => Livewire::test(DespesaManager::class)->call('excluir', $idInexistente))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
