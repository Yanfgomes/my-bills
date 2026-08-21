<?php

use App\Livewire\Despesas\DespesaManager;
use App\Livewire\Overview\OverviewFinanceiro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * FLUXO-004 — Edicao e exclusao de despesa (RF-007), dominio_ref unico DOM-003.
 * Teste de SISTEMA: exercita o fluxo de uso real completo — listar despesas -> editar
 * (descricao/valor/categoria, RN-002) -> confirmar mes/periodo somente leitura na edicao
 * (RN-008) -> excluir -> confirmar reflexo no Overview (RF-008) — atravessando os 2
 * componentes Livewire reais (DespesaManager, OverviewFinanceiro) na ordem em que o usuario
 * efetivamente navega.
 *
 * Nao repete aqui os cenarios ja cobertos em tests/Feature/Despesas/DespesaManagerTest.php
 * (validacao de campo isolada, mensagens de erro, IDOR por acao, ordenacao de listagem) --
 * cobre so o que aparece quando edicao/exclusao de despesa e o Overview sao usados juntos, na
 * ordem real de navegacao, incluindo o isolamento cross-user atravessando os 2 componentes
 * (RN-005, requisito transversal que une o fluxo). Pentest de composicao do fluxo (IDOR via
 * propriedade Livewire manipulada) ja confirmado pelo security-agent (testes.seguranca.itens,
 * FLUXO-004) -- nao repetido aqui.
 */
uses(RefreshDatabase::class);

it('FLUXO-004: usuario lista, edita descricao/valor/categoria de uma despesa e ve o Overview refletir o novo valor', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel')
        ->set('valor', '1500')
        ->set('categoria', 'Moradia')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('1.500,00');

    // Usuario abre a listagem, entra em modo edicao e altera descricao/valor/categoria.
    $despesaId = \App\Models\Despesa::where('usuario_id', $usuario->id)->first()->id;

    Livewire::test(DespesaManager::class)
        ->assertSee('Aluguel')
        ->call('editar', $despesaId)
        ->assertSet('mesReferencia', $mes)
        ->assertSeeHtml('data-cy="despesa-mes-nota"')
        ->set('descricao', 'Aluguel reajustado')
        ->set('valor', '1800')
        ->set('categoria', 'Casa')
        ->call('atualizar')
        ->assertHasNoErrors()
        ->assertSee('Aluguel reajustado')
        ->assertSee('Casa')
        ->assertSeeHtml('data-cy="despesa-linha-'.$despesaId.'"');

    // Overview reflete o novo valor editado, sem precisar de nenhuma acao adicional do usuario.
    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('1.800,00')
        ->assertDontSee('1.500,00');
});

it('FLUXO-004/RN-008: mes/periodo permanece somente leitura durante a edicao e imutavel no banco, mesmo apos atualizar()', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mesOriginal = now()->subMonthsNoOverflow(2)->format('Y-m');

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Internet')
        ->set('valor', '120')
        ->set('mesReferencia', $mesOriginal)
        ->call('criar')
        ->assertHasNoErrors();

    $despesa = \App\Models\Despesa::where('usuario_id', $usuario->id)->first();

    // Usuario entra em edicao: campo mes/periodo vem desabilitado na view (RN-008) e a nota
    // explicativa (excluir e recriar para mudar de mes) esta visivel e associada via aria.
    $componente = Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->assertSeeHtml('data-cy="despesa-mes-nota"')
        ->assertSeeHtml('disabled');

    $componente
        ->set('descricao', 'Internet fibra')
        ->set('valor', '150')
        ->call('atualizar')
        ->assertHasNoErrors();

    expect($despesa->refresh()->mes_referencia)->toBe($mesOriginal)
        ->and($despesa->descricao)->toBe('Internet fibra');

    // Overview do mes original segue refletindo a despesa (nao foi movida de periodo).
    Livewire::test(OverviewFinanceiro::class)
        ->call('mesAnterior')
        ->call('mesAnterior') // mes atual -> mes atual - 2, equivalente a navegacao real do usuario
        ->assertSee($mesOriginal)
        ->assertSee('150,00');
});

it('FLUXO-004/RN-002: edicao com valor invalido e rejeitada, despesa e Overview permanecem inalterados', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Streaming')
        ->set('valor', '50')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    $despesa = \App\Models\Despesa::where('usuario_id', $usuario->id)->first();

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesa->id)
        ->set('valor', '0')
        ->call('atualizar')
        ->assertHasErrors(['valor']);

    expect((float) $despesa->refresh()->valor)->toBe(50.0);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('50,00');
});

it('FLUXO-004: usuario exclui uma despesa e o Overview do mes deixa de contabiliza-la, inclusive removendo o alerta de despesa acima da renda', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    Livewire::test(\App\Livewire\Renda\RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Conserto emergencial')
        ->set('valor', '1500')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    // Antes da exclusao: despesa supera renda, Overview mostra alerta (RN-007).
    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSeeHtml('data-cy="overview-alerta-percentual"');

    $despesa = \App\Models\Despesa::where('usuario_id', $usuario->id)->first();

    Livewire::test(DespesaManager::class)
        ->call('excluir', $despesa->id)
        ->assertDontSeeHtml('data-cy="despesa-linha-'.$despesa->id.'"')
        ->assertSeeHtml('data-cy="despesa-vazio"');

    expect(\App\Models\Despesa::find($despesa->id))->toBeNull();

    // Depois da exclusao: Overview nao contabiliza mais a despesa removida, alerta some.
    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertDontSee('1.500,00')
        ->assertDontSeeHtml('data-cy="overview-alerta-percentual"');
});

it('FLUXO-004/RN-005: dois usuarios reais editam e excluem despesas proprias sem afetar o Overview um do outro', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $mes = now()->format('Y-m');

    $this->actingAs($alice);
    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Despesa da Alice')
        ->set('valor', '300')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    $this->actingAs($bob);
    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Despesa do Bob')
        ->set('valor', '900')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    // Bob edita e exclui sua propria despesa, na sua sessao.
    $despesaDoBob = \App\Models\Despesa::where('usuario_id', $bob->id)->first();

    Livewire::test(DespesaManager::class)
        ->call('editar', $despesaDoBob->id)
        ->set('valor', '950')
        ->call('atualizar')
        ->assertHasNoErrors();

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('950,00')
        ->assertDontSee('300,00');

    Livewire::test(DespesaManager::class)->call('excluir', $despesaDoBob->id);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSeeHtml('data-cy="overview-vazio"');

    // Alice, em sua propria sessao, nunca teve a despesa alterada nem o Overview afetado.
    $this->actingAs($alice);

    Livewire::test(DespesaManager::class)
        ->assertSee('Despesa da Alice')
        ->assertDontSee('Despesa do Bob');

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('300,00')
        ->assertDontSeeHtml('data-cy="overview-vazio"');

    expect(\App\Models\Despesa::where('usuario_id', $alice->id)->count())->toBe(1);
});
