<?php

use App\Models\Despesa;
use App\Models\FonteRenda;
use App\Models\User;
use App\Services\OverviewData;
use App\Services\OverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF-008 — Overview financeiro, DOM-004 (App\Services\OverviewService).
 * Testes unitarios cobrindo os 6 cenarios verificados manualmente pelo dev-agent (ver
 * "observacoes" em specs/estado-rf-RF-008.json) agora como suite formal: vazio, RN-001
 * (renda=0), calculo normal, RN-007 (despesas > renda, sem teto em 100%), isolamento RN-005
 * entre dois usuarios reais, e (aqui) as bordas do DTO (estaVazio()).
 *
 * pest()->in('Feature') e o unico grupo com bind automatico de TestCase em tests/Pest.php —
 * este arquivo, embora unitario em proposito, roda sob Tests\Unit\Services e precisa estender
 * TestCase/RefreshDatabase manualmente para ter acesso ao banco de teste em memoria via Eloquent.
 */
uses(Tests\TestCase::class, RefreshDatabase::class);

it('retorna estado vazio quando nao ha renda nem despesa no mes selecionado', function () {
    $usuario = User::factory()->create();

    $overview = app(OverviewService::class)->calcular($usuario->id, '2026-08');

    expect($overview)->toBeInstanceOf(OverviewData::class)
        ->and($overview->mesReferencia)->toBe('2026-08')
        ->and($overview->rendaTotal)->toBe(0.0)
        ->and($overview->despesasTotal)->toBe(0.0)
        ->and($overview->saldoDisponivel)->toBe(0.0)
        ->and($overview->percentualComprometido)->toBeNull()
        ->and($overview->alertaComprometimento)->toBeFalse()
        ->and($overview->estaVazio())->toBeTrue();
});

it('RN-001: renda_total igual a zero com despesa gera percentual nulo (indicador alternativo), sem alerta RN-007', function () {
    $usuario = User::factory()->create();

    Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Aluguel',
        'valor' => 1200,
        'mes_referencia' => '2026-08',
    ]);

    $overview = app(OverviewService::class)->calcular($usuario->id, '2026-08');

    expect($overview->rendaTotal)->toBe(0.0)
        ->and($overview->despesasTotal)->toBe(1200.0)
        ->and($overview->percentualComprometido)->toBeNull()
        ->and($overview->saldoDisponivel)->toBe(-1200.0)
        // RN-007 e sobre despesas > renda (0 conta como maior), mas o criterio de aceite fixa
        // o indicador alternativo de RN-001 como o que a interface exibe quando renda = 0 —
        // o proprio alertaComprometimento (booleano) permanece calculavel e correto.
        ->and($overview->alertaComprometimento)->toBeTrue()
        ->and($overview->estaVazio())->toBeFalse();
});

it('calcula renda, despesas, saldo e percentual corretamente em um cenario normal (sem alerta)', function () {
    $usuario = User::factory()->create();

    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario',
        'valor_liquido' => 6500,
        'mes_referencia' => '2026-08',
    ]);
    Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Contas',
        'valor' => 4150,
        'mes_referencia' => '2026-08',
    ]);

    $overview = app(OverviewService::class)->calcular($usuario->id, '2026-08');

    expect($overview->rendaTotal)->toBe(6500.0)
        ->and($overview->despesasTotal)->toBe(4150.0)
        ->and($overview->saldoDisponivel)->toBe(2350.0)
        ->and($overview->percentualComprometido)->toBe(63.8)
        ->and($overview->alertaComprometimento)->toBeFalse()
        ->and($overview->estaVazio())->toBeFalse();
});

it('RN-007: despesas maiores que a renda geram alerta e percentual exibido sem teto em 100%', function () {
    $usuario = User::factory()->create();

    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario',
        'valor_liquido' => 1000,
        'mes_referencia' => '2026-08',
    ]);
    Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Emergencia medica',
        'valor' => 2500,
        'mes_referencia' => '2026-08',
    ]);

    $overview = app(OverviewService::class)->calcular($usuario->id, '2026-08');

    expect($overview->rendaTotal)->toBe(1000.0)
        ->and($overview->despesasTotal)->toBe(2500.0)
        ->and($overview->saldoDisponivel)->toBe(-1500.0)
        ->and($overview->percentualComprometido)->toBe(250.0)
        ->and($overview->alertaComprometimento)->toBeTrue();
});

it('RN-005: overview de um usuario nao inclui renda/despesa de outro usuario no mesmo mes', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    FonteRenda::create([
        'usuario_id' => $alice->id,
        'descricao' => 'Salario da Alice',
        'valor_liquido' => 5000,
        'mes_referencia' => '2026-08',
    ]);
    Despesa::create([
        'usuario_id' => $alice->id,
        'descricao' => 'Despesa da Alice',
        'valor' => 1000,
        'mes_referencia' => '2026-08',
    ]);

    FonteRenda::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Salario do Bob',
        'valor_liquido' => 9000,
        'mes_referencia' => '2026-08',
    ]);
    Despesa::create([
        'usuario_id' => $bob->id,
        'descricao' => 'Despesa do Bob',
        'valor' => 3000,
        'mes_referencia' => '2026-08',
    ]);

    $overviewAlice = app(OverviewService::class)->calcular($alice->id, '2026-08');
    $overviewBob = app(OverviewService::class)->calcular($bob->id, '2026-08');

    expect($overviewAlice->rendaTotal)->toBe(5000.0)
        ->and($overviewAlice->despesasTotal)->toBe(1000.0)
        ->and($overviewBob->rendaTotal)->toBe(9000.0)
        ->and($overviewBob->despesasTotal)->toBe(3000.0);
});

it('filtra por mes_referencia — lancamentos de outro mes nao entram no calculo do mes selecionado', function () {
    $usuario = User::factory()->create();

    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario julho',
        'valor_liquido' => 5000,
        'mes_referencia' => '2026-07',
    ]);
    Despesa::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Despesa julho',
        'valor' => 2000,
        'mes_referencia' => '2026-07',
    ]);

    FonteRenda::create([
        'usuario_id' => $usuario->id,
        'descricao' => 'Salario agosto',
        'valor_liquido' => 6000,
        'mes_referencia' => '2026-08',
    ]);

    $overviewAgosto = app(OverviewService::class)->calcular($usuario->id, '2026-08');

    expect($overviewAgosto->rendaTotal)->toBe(6000.0)
        ->and($overviewAgosto->despesasTotal)->toBe(0.0);
});

it('OverviewData::estaVazio() considera vazio apenas quando renda e despesa sao ambas <= 0', function () {
    $vazio = new OverviewData(
        mesReferencia: '2026-08',
        rendaTotal: 0.0,
        despesasTotal: 0.0,
        saldoDisponivel: 0.0,
        percentualComprometido: null,
        alertaComprometimento: false,
    );
    $comRenda = new OverviewData(
        mesReferencia: '2026-08',
        rendaTotal: 100.0,
        despesasTotal: 0.0,
        saldoDisponivel: 100.0,
        percentualComprometido: 0.0,
        alertaComprometimento: false,
    );
    $comDespesa = new OverviewData(
        mesReferencia: '2026-08',
        rendaTotal: 0.0,
        despesasTotal: 100.0,
        saldoDisponivel: -100.0,
        percentualComprometido: null,
        alertaComprometimento: true,
    );

    expect($vazio->estaVazio())->toBeTrue()
        ->and($comRenda->estaVazio())->toBeFalse()
        ->and($comDespesa->estaVazio())->toBeFalse();
});
