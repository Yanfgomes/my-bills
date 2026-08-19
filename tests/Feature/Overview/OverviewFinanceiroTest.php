<?php

use App\Livewire\Overview\OverviewFinanceiro;
use App\Models\Despesa;
use App\Models\FonteRenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * RF-008 — Overview financeiro, TELA-003 (App\Livewire\Overview\OverviewFinanceiro).
 * Testes de integracao (componente Livewire + rota + OverviewService real, sem mock) cobrindo
 * autenticacao/rota, os mesmos 6 cenarios de negocio verificados no OverviewServiceTest (agora
 * atraves da tela renderizada), navegacao de mes, e a defesa de SEC-RF-008-01 (#[Locked] em
 * $mesSelecionado).
 */
uses(RefreshDatabase::class);

it('exige autenticacao para acessar a rota overview', function () {
    $this->get(route('overview'))->assertRedirect(route('login'));
});

it('permite acesso autenticado e exibe o estado vazio quando nao ha renda nem despesa no mes', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee(now()->format('Y-m'))
        ->assertSeeHtml('data-cy="overview-vazio"')
        ->assertDontSeeHtml('data-cy="overview-alerta-percentual"');
});

it('RN-001: exibe o indicador alternativo em vez de percentual quando renda_total e zero', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1200,
        'mes_referencia' => now()->format('Y-m'),
    ]);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSeeHtml('data-cy="overview-sem-renda"')
        ->assertSee('Sem renda cadastrada no periodo')
        ->assertDontSeeHtml('data-cy="overview-percentual"');
});

it('calcula e exibe renda, despesas, saldo e percentual corretamente em um cenario normal (sem alerta)', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario',
        'valor_liquido' => 6500,
        'mes_referencia' => $mes,
    ]);
    Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Contas',
        'valor' => 4150,
        'mes_referencia' => $mes,
    ]);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSeeHtml('data-cy="overview-renda-total"')
        ->assertSee('6.500,00')
        ->assertSee('4.150,00')
        ->assertSee('2.350,00')
        ->assertSee('63,8%')
        ->assertDontSeeHtml('data-cy="overview-alerta-percentual"');
});

it('RN-007: exibe alerta e percentual sem teto em 100% quando despesas superam a renda', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario',
        'valor_liquido' => 1000,
        'mes_referencia' => $mes,
    ]);
    Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Emergencia medica',
        'valor' => 2500,
        'mes_referencia' => $mes,
    ]);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSeeHtml('data-cy="overview-alerta-percentual"')
        ->assertSee('Atencao: as despesas superam a renda neste periodo.')
        ->assertSee('250,0%');
});

it('RN-005: overview exibido nao inclui renda/despesa de outro usuario', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $mes = now()->format('Y-m');

    FonteRenda::create([
        'usuario_id' => $alice->id,
        'descricao' => 'Salario da Alice',
        'valor_liquido' => 5000,
        'mes_referencia' => $mes,
    ]);
    Despesa::create([
        'usuario_id' => $alice->id,
        'descricao' => 'Despesa da Alice',
        'valor' => 1000,
        'mes_referencia' => $mes,
    ]);

    FonteRenda::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Salario do Bob',
        'valor_liquido' => 9000,
        'mes_referencia' => $mes,
    ]);
    Despesa::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Despesa do Bob',
        'valor' => 3000,
        'mes_referencia' => $mes,
    ]);

    $this->actingAs($alice);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('5.000,00')
        ->assertSee('1.000,00')
        ->assertDontSee('9.000,00')
        ->assertDontSee('3.000,00');
});

it('navegacao de mes: mesAnterior() e mesProximo() alteram e revertem mesSelecionado corretamente', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mesAtual = now()->format('Y-m');
    $mesAnterior = now()->subMonthNoOverflow()->format('Y-m');

    $componente = Livewire::test(OverviewFinanceiro::class)
        ->assertSet('mesSelecionado', $mesAtual);

    $componente->call('mesAnterior')
        ->assertSet('mesSelecionado', $mesAnterior)
        ->assertSee($mesAnterior);

    $componente->call('mesProximo')
        ->assertSet('mesSelecionado', $mesAtual)
        ->assertSee($mesAtual);
});

it('filtra por mes_referencia — dados de outro mes nao entram no calculo do mes exibido', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mesAtual = now()->format('Y-m');
    $mesAnterior = now()->subMonthNoOverflow()->format('Y-m');

    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario mes anterior',
        'valor_liquido' => 5000,
        'mes_referencia' => $mesAnterior,
    ]);
    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario mes atual',
        'valor_liquido' => 6000,
        'mes_referencia' => $mesAtual,
    ]);

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('6.000,00')
        ->assertDontSee('5.000,00');
});

it('SEC-RF-008-01: mesSelecionado e marcado com #[Locked] no componente', function () {
    $reflexao = new ReflectionProperty(OverviewFinanceiro::class, 'mesSelecionado');

    $atributosLocked = $reflexao->getAttributes(Locked::class);

    expect($atributosLocked)->not->toBeEmpty();
});

it('SEC-RF-008-01: rejeita tentativa de sobrescrever mesSelecionado via atualizacao de propriedade do Livewire (payload do cliente)', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(OverviewFinanceiro::class)
        ->assertSet('mesSelecionado', now()->format('Y-m'))
        ->set('mesSelecionado', '9999-99')
        ->assertSet('mesSelecionado', now()->format('Y-m'));
})->throws(CannotUpdateLockedPropertyException::class);
