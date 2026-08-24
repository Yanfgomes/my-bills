<?php

use App\Livewire\Configuracoes\ConfiguracaoManager;
use App\Livewire\Layout\TemaToggleTopbar;
use App\Models\ConfiguracaoUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * FLUXO-005 — Configuracoes do sistema (RF-PADRAO-CONFIGURACOES), dominio_ref unico DOM-005.
 * Teste de SISTEMA: exercita o fluxo de uso real completo — usuario autenticado abre TELA-006
 * (ConfiguracaoManager), confirma os defaults na primeira leitura (RN-009) -> altera
 * idioma/tema/tamanho de fonte/alto contraste/reducao de movimento -> Salva -> recebe
 * confirmacao anunciada a leitor de tela -> navega para outra tela autenticada (Overview) e
 * confirma o efeito aplicado sem novo login, incluindo o alternador rapido de tema no topbar
 * (TemaToggleTopbar), presente em toda tela autenticada.
 *
 * Nao repete aqui os cenarios ja cobertos em tests/Feature/Configuracoes/ConfiguracaoManagerTest.php,
 * TemaToggleTopbarTest.php e AplicarConfiguracaoUsuarioMiddlewareTest.php (validacao de campo
 * isolada, mensagens de erro, IDOR por acao, log de auditoria por acao) -- cobre so o que
 * aparece quando a tela de preferencias e as demais telas autenticadas sao usadas juntas, na
 * ordem real de navegacao do usuario.
 */
uses(RefreshDatabase::class);

it('FLUXO-005: usuario abre Configuracoes, confirma defaults, altera preferencias, salva com confirmacao e ve o efeito refletido em outra tela sem novo login', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    // 1) Usuario abre TELA-006 pela primeira vez -- defaults confirmados antes de qualquer
    // alteracao (RN-009: pt, claro, medio, alto_contraste=false, reducao_movimento=false).
    Livewire::test(ConfiguracaoManager::class)
        ->assertOk()
        ->assertSet('idioma', 'pt')
        ->assertSet('tema', 'claro')
        ->assertSet('tamanhoFonte', 'medio')
        ->assertSet('altoContraste', false)
        ->assertSet('reducaoMovimento', false);

    // 2) Usuario altera idioma, tema, tamanho de fonte, alto contraste e reducao de movimento,
    // e salva -- confirmacao de sucesso e anunciada a leitor de tela (criterio_aceite_acessibilidade).
    Livewire::test(ConfiguracaoManager::class)
        ->set('idioma', 'en')
        ->set('tema', 'escuro')
        ->set('tamanhoFonte', 'grande')
        ->set('altoContraste', true)
        ->set('reducaoMovimento', true)
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSet('salvo', true)
        ->assertSeeHtml('config-salvo-confirmacao')
        ->assertSeeHtml('aria-live="polite"');

    $configuracao = ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail();
    expect($configuracao->idioma)->toBe('en')
        ->and($configuracao->tema)->toBe('escuro')
        ->and($configuracao->tamanho_fonte)->toBe('grande')
        ->and($configuracao->alto_contraste)->toBeTrue()
        ->and($configuracao->reducao_movimento)->toBeTrue();

    // 3) Usuario navega para outra tela autenticada (Overview), sem novo login: locale, tema e
    // tamanho de fonte ja alterados sao aplicados na resposta real renderizada pelo servidor,
    // via App\Http\Middleware\AplicarConfiguracaoUsuario.
    $overview = $this->get(route('overview'));

    $overview->assertOk();
    $overview->assertSee('data-theme="dark"', false);
    $overview->assertSee('data-font-size="grande"', false);

    preg_match('/<html\b[^>]*>/', $overview->getContent(), $matches);
    $tagHtml = $matches[0] ?? '';
    expect($tagHtml)->toContain('alto-contraste')
        ->and($tagHtml)->toContain('reduzir-movimento');

    expect(app()->getLocale())->toBe('en');

    // 4) O alternador rapido de tema no topbar, presente em toda tela autenticada, ja reflete o
    // tema recem-salvo -- sem query propria, lendo do mesmo compartilhamento do middleware.
    Livewire::test(TemaToggleTopbar::class)->assertSet('tema', 'escuro');
});

it('FLUXO-005: alternador rapido de tema no topbar altera a preferencia e o efeito aparece de volta na tela de Configuracoes, sem novo login', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    // Usuario abre Configuracoes primeiro, criando o registro com defaults (RN-009).
    Livewire::test(ConfiguracaoManager::class)
        ->assertOk()
        ->assertSet('tema', 'claro');

    // Usuario usa o alternador rapido do topbar em outra tela, sem passar pela tela de
    // Configuracoes -- inverte o tema do proprio usuario autenticado.
    Livewire::test(TemaToggleTopbar::class)
        ->call('alternar')
        ->assertSet('tema', 'escuro');

    // A tela de Configuracoes, reaberta na mesma sessao (sem novo login), reflete a alteracao
    // feita pelo alternador rapido do topbar.
    Livewire::test(ConfiguracaoManager::class)
        ->assertSet('tema', 'escuro');

    // E a resposta HTTP real de outra tela autenticada tambem ja reflete o novo tema.
    $overview = $this->get(route('overview'));
    $overview->assertOk();
    $overview->assertSee('data-theme="dark"', false);
});

it('FLUXO-005/RN-009: dois usuarios reais alteram suas proprias preferencias na mesma sessao de testes sem vazamento cruzado', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $this->actingAs($alice);
    Livewire::test(ConfiguracaoManager::class)
        ->set('idioma', 'es')
        ->set('tema', 'escuro')
        ->set('tamanhoFonte', 'pequeno')
        ->call('salvar')
        ->assertHasNoErrors();

    $this->actingAs($bob);
    Livewire::test(ConfiguracaoManager::class)
        ->assertOk()
        ->assertSet('idioma', 'pt')
        ->assertSet('tema', 'claro')
        ->assertSet('tamanhoFonte', 'medio');

    // Bob navega para o Overview: continua vendo os proprios defaults, nunca as preferencias
    // de Alice.
    $overviewBob = $this->get(route('overview'));
    $overviewBob->assertOk();
    $overviewBob->assertSee('data-theme="light"', false);
    expect(app()->getLocale())->toBe('pt');

    // Alice, de volta a sua propria sessao, continua vendo as proprias preferencias.
    $this->actingAs($alice);
    $overviewAlice = $this->get(route('overview'));
    $overviewAlice->assertOk();
    $overviewAlice->assertSee('data-theme="dark"', false);
    expect(app()->getLocale())->toBe('es');

    expect(ConfiguracaoUsuario::where('usuario_id', $bob->id)->firstOrFail()->tema)->toBe('claro')
        ->and(ConfiguracaoUsuario::where('usuario_id', $alice->id)->firstOrFail()->tema)->toBe('escuro');
});
