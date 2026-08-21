<?php

use App\Livewire\Overview\OverviewFinanceiro;
use App\Livewire\Renda\RendaManager;
use App\Models\FonteRenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * FLUXO-003 — Edicao e exclusao de fonte de renda (RF-005), dominio_ref unico (DOM-002).
 * Teste de SISTEMA: exercita o fluxo de uso completo real — listar fontes de renda -> editar
 * (descricao/valor, RN-002) -> confirmar mes/periodo somente leitura na edicao (RN-008) ->
 * excluir -> confirmar reflexo no Overview do mes correspondente (RF-008) -- tudo na ordem em
 * que o usuario efetivamente navega, atravessando RendaManager e OverviewFinanceiro reais.
 *
 * Nao repete aqui os cenarios ja cobertos em tests/Feature/Renda/RendaManagerTest.php
 * (unitario/integracao de RF-005, 100% de cobertura) nem o pentest de composicao ja fechado em
 * testes.seguranca.itens (FLUXO-003, criticidade "nenhum") -- cobre so a composicao real de uso
 * do fluxo completo, incluindo o reflexo cross-componente no Overview (RF-008).
 */
uses(RefreshDatabase::class);

it('FLUXO-003: usuario lista, edita (RN-002) e exclui uma fonte de renda, com reflexo no Overview', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mes = now()->format('Y-m');

    // Etapa 1: cadastro previo (pre-condicao do fluxo — RF-004 ja aprovado) e listagem.
    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario')
        ->set('valorLiquido', '5000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors()
        ->assertSee('Salario')
        ->assertSee('5.000,00');

    $fonteRenda = FonteRenda::where('usuario_id', $usuario->id)->firstOrFail();

    // Etapa 2: overview reflete a renda recem-cadastrada, antes de qualquer edicao.
    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('5.000,00');

    // Etapa 3: usuario entra em modo edicao — mes/periodo populado mas nao pode ser alterado
    // (RN-008): o componente nem expoe o campo a validacao de atualizar().
    $componenteEdicao = Livewire::test(RendaManager::class)
        ->call('editar', $fonteRenda->id)
        ->assertSet('descricao', 'Salario')
        ->assertSet('valorLiquido', '5000.00')
        ->assertSet('mesReferencia', $mes)
        ->assertSet('editandoId', (string) $fonteRenda->id);

    // Etapa 4: edicao com valor invalido (RN-002) e rejeitada — nada persiste, segue em edicao.
    $componenteEdicao
        ->set('valorLiquido', '0')
        ->call('atualizar')
        ->assertHasErrors('valorLiquido');

    expect(FonteRenda::find($fonteRenda->id)->valor_liquido)->toEqual('5000.00');

    // Etapa 5: edicao valida de descricao/valor persiste; mes/periodo permanece o original mesmo
    // sem ter sido reenviado (RN-008 -- servidor nunca aceita mesReferencia no payload de update).
    $componenteEdicao
        ->set('descricao', 'Salario reajustado')
        ->set('valorLiquido', '5800')
        ->call('atualizar')
        ->assertHasNoErrors();

    $fonteRenda->refresh();
    expect($fonteRenda->descricao)->toBe('Salario reajustado');
    expect($fonteRenda->valor_liquido)->toEqual('5800.00');
    expect($fonteRenda->mes_referencia)->toBe($mes);

    // Etapa 6: listagem e overview refletem a edicao.
    Livewire::test(RendaManager::class)
        ->assertSee('Salario reajustado')
        ->assertSee('5.800,00');

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('5.800,00')
        ->assertDontSee('5.000,00');

    // Etapa 7: exclusao remove o registro; overview do mes deixa de contabiliza-lo (RF-008).
    Livewire::test(RendaManager::class)
        ->call('excluir', $fonteRenda->id)
        ->assertDontSee('Salario reajustado');

    expect(FonteRenda::find($fonteRenda->id))->toBeNull();

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSeeHtml('data-cy="overview-vazio"')
        ->assertDontSee('5.800,00');
});

it('FLUXO-003 / RN-008: mes/periodo de referencia nao muda mesmo se enviado manipulado no payload de atualizar()', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $mesOriginal = now()->format('Y-m');
    $mesForjado = now()->addMonth()->format('Y-m');

    Livewire::test(RendaManager::class)
        ->set('descricao', 'Renda extra')
        ->set('valorLiquido', '1200')
        ->set('mesReferencia', $mesOriginal)
        ->call('criar')
        ->assertHasNoErrors();

    $fonteRenda = FonteRenda::where('usuario_id', $usuario->id)->firstOrFail();

    // Usuario entra em edicao e forca mesReferencia via propriedade publica manipulada -- RN-008
    // e reforcado no servidor: atualizar() nem inclui o campo na validacao/payload de update.
    Livewire::test(RendaManager::class)
        ->call('editar', $fonteRenda->id)
        ->set('mesReferencia', $mesForjado)
        ->set('valorLiquido', '1300')
        ->call('atualizar')
        ->assertHasNoErrors();

    $fonteRenda->refresh();
    expect($fonteRenda->mes_referencia)->toBe($mesOriginal);
    expect($fonteRenda->valor_liquido)->toEqual('1300.00');
});

it('FLUXO-003 / RN-005: edicao e exclusao de fonte de renda permanecem isoladas entre usuarios reais', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $mes = now()->format('Y-m');

    $this->actingAs($alice);
    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario da Alice')
        ->set('valorLiquido', '4000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    $rendaAlice = FonteRenda::where('usuario_id', $alice->id)->firstOrFail();

    $this->actingAs($bob);
    Livewire::test(RendaManager::class)
        ->set('descricao', 'Salario do Bob')
        ->set('valorLiquido', '7000')
        ->set('mesReferencia', $mes)
        ->call('criar')
        ->assertHasNoErrors();

    // Bob tenta editar/excluir a renda da Alice pelo id real dela (IDOR) -- ModelNotFoundException
    // identica a de id inexistente, sem vazar existencia do registro alheio.
    expect(fn () => Livewire::test(RendaManager::class)->call('editar', $rendaAlice->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(fn () => Livewire::test(RendaManager::class)->call('excluir', $rendaAlice->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    // Renda da Alice permanece intacta; overview de Bob nunca a contabilizou.
    $rendaAlice->refresh();
    expect($rendaAlice->descricao)->toBe('Salario da Alice');

    Livewire::test(OverviewFinanceiro::class)
        ->assertOk()
        ->assertSee('7.000,00')
        ->assertDontSee('4.000,00');
});
