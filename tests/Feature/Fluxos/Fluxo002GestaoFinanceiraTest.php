<?php

use App\Livewire\Despesas\DespesaManager;
use App\Livewire\Overview\OverviewFinanceiro;
use App\Livewire\Renda\RendaManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * FLUXO-002 — Gestao financeira com isolamento por usuario (RF-003 + RF-004 + RF-006 + RF-008).
 * Teste de SISTEMA: exercita a composicao real de uso — cadastrar renda -> cadastrar despesa ->
 * conferir overview do mes — atravessando os 3 componentes Livewire reais na ordem em que o
 * usuario efetivamente navega, e nao cada RF isolado (que ja tem cobertura unitaria/integracao
 * propria em tests/Feature/Renda, tests/Feature/Despesas e tests/Feature/Overview).
 *
 * Nao repete aqui os cenarios ja cobertos nos testes por RF (validacao de campo, mensagens de
 * erro, ordenacao de listagem, Policy isolada) — cobre so o que so aparece quando os 3 dominios
 * sao usados juntos, na ordem real, e o isolamento cross-user atravessando os 3 ao mesmo tempo
 * (RN-005/RF-003, requisito transversal que une o fluxo).
 */
uses(RefreshDatabase::class);

it('FLUXO-002: usuario cadastra renda, cadastra despesa e ve o overview do mes refletir os dois lancamentos', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '6500')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Aluguel e contas')
        ->set('valor', '4150')
        ->set('categoria', 'Moradia')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee($mes)
        ->assertSeeHtml('data-cy="overview-renda-total"')
        ->assertSee('6.500,00')
        ->assertSee('4.150,00')
        ->assertSee('2.350,00')
        ->assertSee('63,8%')
        ->assertDontSeeHtml('data-cy="overview-alerta-percentual"')
        ->assertDontSeeHtml('data-cy="overview-vazio"')
        ->assertDontSeeHtml('data-cy="overview-sem-renda"');
});

it('FLUXO-002 / RN-007: cadastro de despesa que supera a renda do mes gera alerta no overview, exercitando os 3 componentes em sequencia', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Freelance')
        ->set('valorLiquido', '1000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Emergencia medica')
        ->set('valor', '2500')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSeeHtml('data-cy="overview-alerta-percentual"')
        ->assertSee('Atencao: as despesas superam a renda neste periodo.')
        ->assertSee('250,0%');
});

it('FLUXO-002 / RN-005: dois usuarios reais atravessando renda, despesa e overview permanecem isolados um do outro', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $mes = now()->format('Y-m');

    // Alice cadastra renda e despesa via componentes reais.
    $this->actingAs($alice);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario da Alice')
        ->set('valorLiquido', '5000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Despesa da Alice')
        ->set('valor', '1000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    // Bob cadastra renda e despesa proprias, em sessao separada, na mesma janela de tempo.
    $this->actingAs($bob);

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario do Bob')
        ->set('valorLiquido', '9000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Despesa do Bob')
        ->set('valor', '3000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    // Bob nao ve renda/despesa da Alice em nenhuma das 3 telas.
    Livewire::test(RendaManager::class)
        ->assertSee('Salario do Bob')
        ->assertDontSee('Salario da Alice');

    Livewire::test(DespesaManager::class)
        ->assertSee('Despesa do Bob')
        ->assertDontSee('Despesa da Alice');

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('9.000,00')
        ->assertSee('3.000,00')
        ->assertDontSee('5.000,00')
        ->assertDontSee('1.000,00');

    // Volta para Alice: mesma checagem no sentido inverso, confirmando isolamento nos dois lados.
    $this->actingAs($alice);

    Livewire::test(RendaManager::class)
        ->assertSee('Salario da Alice')
        ->assertDontSee('Salario do Bob');

    Livewire::test(DespesaManager::class)
        ->assertSee('Despesa da Alice')
        ->assertDontSee('Despesa do Bob');

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('5.000,00')
        ->assertSee('1.000,00')
        ->assertDontSee('9.000,00')
        ->assertDontSee('3.000,00');
});
