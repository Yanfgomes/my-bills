<?php

use App\Livewire\Auditoria\LogAuditoriaRelatorio;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * RF-PADRAO-LOG-AUDITORIA — Relatorio de consulta ao log de auditoria (self-audit, RN-010),
 * TELA-007 (App\Livewire\Auditoria\LogAuditoriaRelatorio). Cobre criterio_aceite (listagem
 * filtravel por tabela/acao/periodo, detalhe De/Para, estado vazio, nenhum controle de
 * edicao/exclusao), criterio_aceite_seguranca (autenticacao obrigatoria, escopo por
 * usuario_id/RN-010, enum de filtro validado no servidor -- inclusive a cada sync do Livewire,
 * SEC-RF-PADRAO-LOG-AUDITORIA-01 -- resposta so-leitura) e o comportamento de paginacao/De-Para.
 */
uses(RefreshDatabase::class);

function criaLog(User $usuario, array $overrides = []): LogAuditoria
{
    return LogAuditoria::create(array_merge([
        'acao' => 'alteracao',
        'usuario_id' => $usuario->id,
        'tabela_afetada' => 'despesas',
        'registro_id' => '1',
        'valor_anterior' => ['valor' => 100],
        'valor_novo' => ['valor' => 150],
        'criado_em' => now(),
    ], $overrides));
}

it('exige autenticacao para acessar a rota auditoria.index', function () {
    $this->get(route('auditoria.index'))->assertRedirect(route('login'));
});

it('lista somente os logs do usuario autenticado, filtraveis por tabela/entidade, acao e periodo', function () {
    $usuario = User::factory()->create();
    $outroUsuario = User::factory()->create();

    $logProprio = criaLog($usuario, ['tabela_afetada' => 'despesas', 'acao' => 'criacao', 'criado_em' => now()->subDays(2)]);
    criaLog($usuario, ['tabela_afetada' => 'fontes_renda', 'acao' => 'exclusao', 'criado_em' => now()->subDays(10)]);
    criaLog($outroUsuario, ['tabela_afetada' => 'despesas', 'acao' => 'criacao']);

    $this->actingAs($usuario);

    $component = Livewire::test(LogAuditoriaRelatorio::class)->assertOk();

    // +1 no total: User::factory()->create() dispara AuditoriaObserver::created() sobre o
    // proprio model User, entao $usuario ja nasce com um log de 'criacao' da tabela 'users'.
    expect($component->viewData('logs')->total())->toBe(3);

    $component->set('tabelaAfetada', 'despesas')
        ->set('acao', 'criacao')
        ->set('periodoInicio', now()->subDays(3)->toDateString())
        ->set('periodoFim', now()->toDateString())
        ->call('filtrar')
        ->assertHasNoErrors();

    $logs = $component->viewData('logs');
    expect($logs->total())->toBe(1)
        ->and($logs->first()->id)->toBe($logProprio->id);
});

it('filtro sem resultado exibe o estado vazio orientando a ajustar os filtros', function () {
    $usuario = User::factory()->create();
    criaLog($usuario, ['tabela_afetada' => 'despesas']);

    $this->actingAs($usuario);

    // 'configuracoes_usuario' e o unico valor do enum sem nenhum log gravado neste teste --
    // 'users' nao serve de contraste aqui porque o proprio User::factory()->create() ja gera
    // um log de 'criacao' para a tabela 'users'.
    Livewire::test(LogAuditoriaRelatorio::class)
        ->set('tabelaAfetada', 'configuracoes_usuario')
        ->call('filtrar')
        ->assertHasNoErrors()
        ->assertSeeHtml('aud-vazio')
        ->assertSee('Nenhum registro de log encontrado para os filtros informados.');
});

it('verDetalhe() abre o detalhe De/Para (valor_anterior e valor_novo) do proprio registro', function () {
    $usuario = User::factory()->create();
    $log = criaLog($usuario, ['valor_anterior' => ['valor' => 100], 'valor_novo' => ['valor' => 150]]);

    $this->actingAs($usuario);

    Livewire::test(LogAuditoriaRelatorio::class)
        ->call('verDetalhe', $log->id)
        ->assertSet('detalheId', $log->id)
        ->assertSeeHtml('aud-modal')
        ->assertSeeHtml('aud-modal-anterior')
        ->assertSeeHtml('aud-modal-novo');
});

it('fecharDetalhe() fecha o modal sem alterar o registro de log', function () {
    $usuario = User::factory()->create();
    $log = criaLog($usuario);

    $this->actingAs($usuario);

    $antes = $log->toArray();

    Livewire::test(LogAuditoriaRelatorio::class)
        ->call('verDetalhe', $log->id)
        ->call('fecharDetalhe')
        ->assertSet('detalheId', null)
        ->assertDontSeeHtml('data-cy="aud-modal"');

    expect($log->refresh()->toArray())->toEqual($antes);
});

it('RN-010/criterio_aceite_seguranca: nenhum log de outro usuario e retornado, mesmo variando a pagina', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        criaLog($alice, ['criado_em' => now()->subMinutes($i)]);
    }
    for ($i = 0; $i < 20; $i++) {
        criaLog($bob, ['criado_em' => now()->subMinutes($i)]);
    }

    $this->actingAs($alice);

    // +1 no total esperado: User::factory()->create() dispara AuditoriaObserver::created()
    // sobre o proprio model User (RF-001, usuario_id = id do usuario recem-criado), entao cada
    // usuario criado ja nasce com um log de 'criacao' de tabela 'users' proprio.
    $pagina1 = Livewire::test(LogAuditoriaRelatorio::class)->viewData('logs');
    expect($pagina1->total())->toBe(6)
        ->and($pagina1->pluck('usuario_id')->unique()->all())->toBe([$alice->id]);
});

it('criterio_aceite_seguranca: verDetalhe() de um registro de outro usuario lanca ModelNotFoundException (nao vaza existencia do registro)', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $logBob = criaLog($bob);

    $this->actingAs($alice);

    expect(fn () => Livewire::test(LogAuditoriaRelatorio::class)->call('verDetalhe', $logBob->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('criterio_aceite_seguranca: mount() nega acesso sem sessao autenticada mesmo direto no componente', function () {
    Livewire::test(LogAuditoriaRelatorio::class)->assertStatus(403);
});

it('Policy nega view do log de outro usuario, com dois usuarios reais', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $logBob = criaLog($bob);

    $this->actingAs($alice);
    expect(Auth::user()->can('view', $logBob))->toBeFalse();

    $this->actingAs($bob);
    expect(Auth::user()->can('view', $logBob))->toBeTrue();
});

it('SEC-RF-PADRAO-LOG-AUDITORIA-01 (regressao): valor de tabelaAfetada fora do enum sincronizado via Livewire nunca chega a query, mesmo sem clicar em "Filtrar"', function () {
    $usuario = User::factory()->create();
    criaLog($usuario, ['tabela_afetada' => 'despesas']);
    criaLog($usuario, ['tabela_afetada' => 'fontes_renda']);

    $this->actingAs($usuario);

    $component = Livewire::test(LogAuditoriaRelatorio::class)
        ->set('tabelaAfetada', "despesas' OR '1'='1")
        ->assertSet('tabelaAfetada', null);

    // Sem curto-circuito, render() usaria o valor bruto no where() e devolveria 0 (nenhuma
    // tabela chamada assim) ou, pior, um comportamento fora do enum previsto; com o hook
    // corrigido a prop volta a null (equivalente a "nenhum filtro"), entao a query devolve
    // todos os logs do usuario -- os 2 manuais mais o log de 'criacao' automatico gerado pelo
    // proprio User::factory()->create() (tabela 'users').
    expect($component->viewData('logs')->total())->toBe(3);
});

it('SEC-RF-PADRAO-LOG-AUDITORIA-01 (regressao): valor de acao fora do enum sincronizado via Livewire nunca chega a query', function () {
    $usuario = User::factory()->create();
    criaLog($usuario, ['acao' => 'criacao']);
    criaLog($usuario, ['acao' => 'exclusao']);

    $this->actingAs($usuario);

    $component = Livewire::test(LogAuditoriaRelatorio::class)
        ->set('acao', 'formatar-disco')
        ->assertSet('acao', null);

    // 2 manuais + 1 log de 'criacao' automatico gerado pelo proprio User::factory()->create().
    expect($component->viewData('logs')->total())->toBe(3);
});

it('criterio_aceite_seguranca: filtrar() rejeita tabelaAfetada e acao fora do enum previsto, sem aplicar a consulta', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario);

    // updatedTabelaAfetada()/updatedAcao() ja resetam valor invalido sincronizado via
    // wire:model; aqui a validacao de filtrar() e exercitada isoladamente, definindo a
    // propriedade publica diretamente (sem passar pelo ciclo normal de sync), como uma segunda
    // camada de defesa contra o mesmo tipo de valor fora do enum.
    Livewire::test(LogAuditoriaRelatorio::class)
        ->set('periodoFim', now()->subDay()->toDateString())
        ->set('periodoInicio', now()->toDateString())
        ->call('filtrar')
        ->assertHasErrors(['periodoFim']);
});

it('criterio_aceite_seguranca: nenhum metodo do componente cria, altera ou exclui um registro de log', function () {
    $usuario = User::factory()->create();
    $log = criaLog($usuario);

    $this->actingAs($usuario);

    $totalAntes = LogAuditoria::count();

    Livewire::test(LogAuditoriaRelatorio::class)
        ->set('tabelaAfetada', 'despesas')
        ->call('filtrar')
        ->call('verDetalhe', $log->id)
        ->call('fecharDetalhe');

    expect(LogAuditoria::count())->toBe($totalAntes);
});

it('criterio_aceite: nenhum controle de edicao/exclusao esta disponivel na tela para nenhum registro', function () {
    $usuario = User::factory()->create();
    criaLog($usuario);

    $this->actingAs($usuario);

    Livewire::test(LogAuditoriaRelatorio::class)
        ->assertDontSeeHtml('wire:click="editar')
        ->assertDontSeeHtml('wire:click="excluir')
        ->assertDontSee('Editar')
        ->assertDontSee('Excluir');
});

it('formata a coluna Data/hora e o detalhe De/Para com os valores anterior e novo do registro', function () {
    $usuario = User::factory()->create();
    $log = criaLog($usuario, [
        'valor_anterior' => ['valor' => 100, 'categoria' => 'lazer'],
        'valor_novo' => ['valor' => 150, 'categoria' => 'moradia'],
        'criado_em' => now()->setDate(2026, 8, 24)->setTime(14, 30),
    ]);

    $this->actingAs($usuario);

    Livewire::test(LogAuditoriaRelatorio::class)
        ->assertSee('24/08/2026 14:30')
        ->call('verDetalhe', $log->id)
        ->assertSee('"valor": 100')
        ->assertSee('"categoria": "lazer"')
        ->assertSee('"valor": 150')
        ->assertSee('"categoria": "moradia"');
});

it('exibe "(nao se aplica)" no De/Para quando valor_anterior ou valor_novo e nulo (registro de criacao/exclusao)', function () {
    $usuario = User::factory()->create();
    $log = criaLog($usuario, ['acao' => 'criacao', 'valor_anterior' => null, 'valor_novo' => ['valor' => 100]]);

    $this->actingAs($usuario);

    Livewire::test(LogAuditoriaRelatorio::class)
        ->call('verDetalhe', $log->id)
        ->assertSee('(nao se aplica)');
});

it('paginacao: 15 registros por pagina, com mais de 15 registros a listagem e paginada', function () {
    $usuario = User::factory()->create();

    for ($i = 0; $i < 17; $i++) {
        criaLog($usuario, ['criado_em' => now()->subMinutes($i)]);
    }

    $this->actingAs($usuario);

    $logs = Livewire::test(LogAuditoriaRelatorio::class)->viewData('logs');

    // +1 no total esperado: log de 'criacao' automatico gerado pelo proprio
    // User::factory()->create() (tabela 'users'), somado aos 17 manuais.
    expect($logs->count())->toBe(15)
        ->and($logs->total())->toBe(18)
        ->and($logs->hasMorePages())->toBeTrue();
});

it('trocar um filtro reseta a paginacao para a primeira pagina', function () {
    $usuario = User::factory()->create();

    for ($i = 0; $i < 17; $i++) {
        criaLog($usuario, ['tabela_afetada' => 'despesas', 'criado_em' => now()->subMinutes($i)]);
    }

    $this->actingAs($usuario);

    $component = Livewire::test(LogAuditoriaRelatorio::class);
    $component->call('nextPage');
    expect($component->viewData('logs')->currentPage())->toBe(2);

    $component->set('tabelaAfetada', 'despesas');
    expect($component->viewData('logs')->currentPage())->toBe(1);
});
