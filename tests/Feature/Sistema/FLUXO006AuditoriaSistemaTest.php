<?php

use App\Livewire\Auditoria\LogAuditoriaRelatorio;
use App\Livewire\Despesas\DespesaManager;
use App\Models\Despesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * FLUXO-006 (teste de sistema, marco-3-padroes-pipeline) — "gerar auditoria -> consultar -> ver
 * detalhe", ponta-a-ponta sobre RF-PADRAO-LOG-AUDITORIA (TELA-007), exercitando a acao auditavel
 * real (DespesaManager, RF-006/RF-007, ja auditada desde o marco-1 via AuditoriaObserver) antes da
 * consulta -- nao o componente de relatorio isolado (ja coberto em
 * tests/Feature/Auditoria/LogAuditoriaRelatorioTest.php).
 */
uses(RefreshDatabase::class);

it('fluxo completo: usuario cria e edita uma despesa, consulta o log filtrando por tabela/acao/periodo, abre e fecha o detalhe De/Para, e confirma o estado vazio para um filtro sem correspondencia', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    // 1) Gera acoes auditaveis reais: criar despesa (acao=criacao) e depois edita-la
    // (acao=alteracao), ambas via DespesaManager (RF-006/RF-007), que dispara
    // AuditoriaObserver::created()/updated() sobre a tabela 'despesas'.
    $criacao = Livewire::test(DespesaManager::class)
        ->set('descricao', 'Conta de luz')
        ->set('valor', '250.00')
        ->set('categoria', 'moradia')
        ->set('mesReferencia', now()->format('Y-m'))
        ->call('criar')
        ->assertHasNoErrors();

    $despesa = Despesa::where('usuario_id', $usuario->id)->where('descricao', 'Conta de luz')->firstOrFail();

    $criacao->call('editar', $despesa->id)
        ->set('valor', '275.50')
        ->set('categoria', 'utilidades')
        ->call('atualizar')
        ->assertHasNoErrors();

    // 2) Abre o relatorio de log/auditoria (TELA-007) e filtra pela tabela/acao/periodo da
    // acao que acabou de gerar.
    $relatorio = Livewire::test(LogAuditoriaRelatorio::class)
        ->set('tabelaAfetada', 'despesas')
        ->set('acao', 'alteracao')
        ->set('periodoInicio', now()->toDateString())
        ->set('periodoFim', now()->toDateString())
        ->call('filtrar')
        ->assertHasNoErrors();

    $logs = $relatorio->viewData('logs');
    expect($logs->total())->toBe(1);

    $logAlteracao = $logs->first();
    expect($logAlteracao->tabela_afetada)->toBe('despesas')
        ->and($logAlteracao->acao)->toBe('alteracao')
        ->and($logAlteracao->registro_id)->toBe((string) $despesa->id);

    // 3) Abre o detalhe De/Para do registro recem-gerado: foco preso, Esc fecha, foco retorna
    // ao botao de origem sao comportamento client-side (JS na view, ja cobertos pela auditoria
    // de acessibilidade da Onda 1); aqui a asserção de sistema confirma o contrato do
    // componente (estado do modal + payload De/Para correto) que sustenta esse comportamento.
    $relatorio->call('verDetalhe', $logAlteracao->id)
        ->assertSet('detalheId', $logAlteracao->id)
        ->assertSeeHtml('aud-modal')
        ->assertSee('"valor": "250.00"')
        ->assertSee('"categoria": "moradia"')
        ->assertSee('"valor": "275.50"')
        ->assertSee('"categoria": "utilidades"');

    $relatorio->call('fecharDetalhe')
        ->assertSet('detalheId', null)
        ->assertDontSeeHtml('data-cy="aud-modal"');

    // 4) Filtro sem correspondencia (tabela auditada sem nenhum log gravado neste teste) exibe
    // o estado vazio.
    Livewire::test(LogAuditoriaRelatorio::class)
        ->set('tabelaAfetada', 'fontes_renda')
        ->call('filtrar')
        ->assertHasNoErrors()
        ->assertSeeHtml('aud-vazio')
        ->assertSee('Nenhum registro de log encontrado para os filtros informados.');

    // Nenhum controle de edicao/exclusao disponivel na tela de auditoria, mesmo apos o fluxo
    // completo de navegacao (criterio_aceite).
    Livewire::test(LogAuditoriaRelatorio::class)
        ->assertDontSeeHtml('wire:click="editar')
        ->assertDontSeeHtml('wire:click="excluir');
});

it('fluxo completo: acoes de um usuario nunca aparecem no relatorio de log de outro usuario (RN-010)', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $this->actingAs($alice);
    Livewire::test(DespesaManager::class)
        ->set('descricao', 'Supermercado')
        ->set('valor', '80.00')
        ->set('categoria', 'alimentacao')
        ->set('mesReferencia', now()->format('Y-m'))
        ->call('criar')
        ->assertHasNoErrors();

    $this->actingAs($bob);
    $relatorioBob = Livewire::test(LogAuditoriaRelatorio::class)
        ->set('tabelaAfetada', 'despesas')
        ->call('filtrar');

    expect($relatorioBob->viewData('logs')->total())->toBe(0);
});
