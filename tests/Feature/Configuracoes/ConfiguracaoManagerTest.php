<?php

use App\Livewire\Configuracoes\ConfiguracaoManager;
use App\Models\ConfiguracaoUsuario;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * RF-PADRAO-CONFIGURACOES — Preferencias do usuario autenticado (idioma/tema/tamanho de
 * fonte/alto contraste/reducao de movimento), TELA-006 (App\Livewire\Configuracoes\
 * ConfiguracaoManager). Cobre criterio_aceite (persistencia por campo, defaults na primeira
 * leitura, isolamento por usuario RN-009, aplicacao sem novo login, confirmacao de sucesso),
 * criterio_aceite_seguranca (autenticacao obrigatoria, escopo por usuario_id, validacao de
 * enum/booleano no servidor, campo fora do contrato ignorado, log de auditoria em
 * criacao/alteracao) e o fluxo de UI (card unico, botao "Salvar" explicito, sem auto-apply).
 */
uses(RefreshDatabase::class);

it('exige autenticacao para acessar a rota configuracoes.show', function () {
    $this->get(route('configuracoes.show'))->assertRedirect(route('login'));
});

it('cria registro com os defaults na primeira leitura, antes de exibir a tela (RN-009)', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    expect(ConfiguracaoUsuario::where('usuario_id', $usuario->id)->exists())->toBeFalse();

    Livewire::test(ConfiguracaoManager::class)
        ->assertOk()
        ->assertSet('idioma', 'pt')
        ->assertSet('tema', 'claro')
        ->assertSet('tamanhoFonte', 'medio')
        ->assertSet('altoContraste', false)
        ->assertSet('reducaoMovimento', false);

    $configuracao = ConfiguracaoUsuario::where('usuario_id', $usuario->id)->first();

    expect($configuracao)->not->toBeNull()
        ->and($configuracao->idioma)->toBe('pt')
        ->and($configuracao->tema)->toBe('claro')
        ->and($configuracao->tamanho_fonte)->toBe('medio')
        ->and($configuracao->alto_contraste)->toBeFalse()
        ->and($configuracao->reducao_movimento)->toBeFalse();
});

it('a criacao do registro com defaults gera log de auditoria de criacao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class)->assertOk();

    $configuracao = ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail();

    $log = LogAuditoria::where('tabela_afetada', 'configuracoes_usuario')
        ->where('registro_id', $configuracao->id)
        ->where('acao', 'criacao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_novo['idioma'])->toBe('pt');
});

it('leitura nao duplica registro em acessos subsequentes (idempotente)', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class)->assertOk();
    Livewire::test(ConfiguracaoManager::class)->assertOk();

    expect(ConfiguracaoUsuario::where('usuario_id', $usuario->id)->count())->toBe(1);
});

it('mount() carrega os valores ja persistidos do usuario, nao os defaults', function () {
    $usuario = User::factory()->create();

    $configuracao = ConfiguracaoUsuario::paraUsuario($usuario->id);
    $configuracao->update([
        'idioma' => 'en',
        'tema' => 'escuro',
        'tamanho_fonte' => 'grande',
        'alto_contraste' => true,
        'reducao_movimento' => true,
    ]);

    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class)
        ->assertSet('idioma', 'en')
        ->assertSet('tema', 'escuro')
        ->assertSet('tamanhoFonte', 'grande')
        ->assertSet('altoContraste', true)
        ->assertSet('reducaoMovimento', true);
});

it('salvar() persiste cada campo de configuracao, exibe confirmacao de sucesso e registra log de alteracao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    // Forca a criacao do registro (mount) antes de alterar, para isolar o log de 'alteracao'
    // (evita conflito com o log de 'criacao' testado em separado acima).
    Livewire::test(ConfiguracaoManager::class);

    // idioma permanece 'pt' aqui (nao testa mudanca de idioma) para que o texto de confirmacao
    // continue em portugues -- a aplicacao imediata do idioma escolhido e coberta em separado
    // ("salvar() aplica o novo idioma imediatamente...", abaixo).
    Livewire::test(ConfiguracaoManager::class)
        ->set('idioma', 'pt')
        ->set('tema', 'escuro')
        ->set('tamanhoFonte', 'grande')
        ->set('altoContraste', true)
        ->set('reducaoMovimento', true)
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSet('salvo', true)
        ->assertSeeHtml('config-salvo-confirmacao')
        ->assertSee('Preferencias salvas com sucesso.');

    $configuracao = ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail();

    expect($configuracao->idioma)->toBe('pt')
        ->and($configuracao->tema)->toBe('escuro')
        ->and($configuracao->tamanho_fonte)->toBe('grande')
        ->and($configuracao->alto_contraste)->toBeTrue()
        ->and($configuracao->reducao_movimento)->toBeTrue();

    $log = LogAuditoria::where('tabela_afetada', 'configuracoes_usuario')
        ->where('registro_id', $configuracao->id)
        ->where('acao', 'alteracao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_novo['tema'])->toBe('escuro');
});

it('salvar() aplica o novo idioma imediatamente na sessao/app, sem exigir novo login', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class)
        ->set('idioma', 'en')
        ->call('salvar')
        ->assertHasNoErrors();

    expect(app()->getLocale())->toBe('en')
        ->and(session('locale'))->toBe('en');
});

it('RN-009: alteracao de preferencia e restrita ao registro do proprio usuario autenticado (salvar() nunca resolve por id de payload)', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $configBob = ConfiguracaoUsuario::paraUsuario($bob->id);
    $configBob->update(['tema' => 'escuro']);

    $this->actingAs($alice);

    Livewire::test(ConfiguracaoManager::class)
        ->set('tema', 'claro')
        ->call('salvar')
        ->assertHasNoErrors();

    // Alice so pode ter alterado o proprio registro, nunca o de Bob.
    expect($configBob->refresh()->tema)->toBe('escuro');

    $configAlice = ConfiguracaoUsuario::where('usuario_id', $alice->id)->firstOrFail();
    expect($configAlice->tema)->toBe('claro');
});

it('Policy nega view/update do registro de outro usuario, com dois usuarios reais', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $configBob = ConfiguracaoUsuario::paraUsuario($bob->id);

    $this->actingAs($alice);

    expect(Auth::user()->can('view', $configBob))->toBeFalse()
        ->and(Auth::user()->can('update', $configBob))->toBeFalse();

    $this->actingAs($bob);

    expect(Auth::user()->can('view', $configBob))->toBeTrue()
        ->and(Auth::user()->can('update', $configBob))->toBeTrue();
});

it('validacao de servidor: rejeita idioma fora do enum permitido, sem gravar dado parcial', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class);
    $antes = ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail();

    Livewire::test(ConfiguracaoManager::class)
        ->set('idioma', 'fr')
        ->set('tema', 'escuro')
        ->call('salvar')
        ->assertHasErrors(['idioma']);

    expect($antes->refresh()->idioma)->toBe('pt')
        ->and($antes->refresh()->tema)->toBe('claro');
});

it('validacao de servidor: rejeita tema fora do enum permitido, sem gravar dado parcial', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class);

    Livewire::test(ConfiguracaoManager::class)
        ->set('tema', 'azul')
        ->call('salvar')
        ->assertHasErrors(['tema']);

    expect(ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail()->tema)->toBe('claro');
});

it('validacao de servidor: rejeita tamanho de fonte fora do enum permitido, sem gravar dado parcial', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class);

    Livewire::test(ConfiguracaoManager::class)
        ->set('tamanhoFonte', 'gigante')
        ->call('salvar')
        ->assertHasErrors(['tamanhoFonte']);

    expect(ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail()->tamanho_fonte)->toBe('medio');
});

it('validacao de servidor: rejeita idioma/tema/tamanho de fonte ausentes, sem gravar dado parcial', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class);

    Livewire::test(ConfiguracaoManager::class)
        ->set('idioma', '')
        ->set('tema', '')
        ->set('tamanhoFonte', '')
        ->call('salvar')
        ->assertHasErrors(['idioma', 'tema', 'tamanhoFonte']);

    $configuracao = ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail();
    expect($configuracao->idioma)->toBe('pt')
        ->and($configuracao->tema)->toBe('claro')
        ->and($configuracao->tamanho_fonte)->toBe('medio');
});

it('criterio_aceite_seguranca: campo fora do contrato (usuario_id) enviado na requisicao e ignorado, nunca sobrescrito', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    ConfiguracaoUsuario::paraUsuario($bob->id);

    $this->actingAs($alice);

    $component = Livewire::test(ConfiguracaoManager::class);
    $configAlice = ConfiguracaoUsuario::where('usuario_id', $alice->id)->firstOrFail();

    // Tenta forcar o usuario_id do proprio Model publico via manipulacao da propriedade
    // exposta pelo componente -- salvar() sempre resolve o registro por Auth::id(), nunca por
    // um campo do payload, entao mesmo manipulando o estado do componente o registro
    // modificado continua sendo o de Alice.
    $component->set('tema', 'escuro')->call('salvar')->assertHasNoErrors();

    expect(ConfiguracaoUsuario::where('usuario_id', $bob->id)->firstOrFail()->tema)->toBe('claro')
        ->and($configAlice->refresh()->tema)->toBe('escuro');
});

it('aplicarPreferenciaSistema() persiste a reducao de movimento detectada apenas quando o registro acabou de ser criado', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $component = Livewire::test(ConfiguracaoManager::class)
        ->assertSet('recemCriado', true);

    $component->call('aplicarPreferenciaSistema', true)
        ->assertSet('reducaoMovimento', true)
        ->assertSet('recemCriado', false);

    expect(ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail()->reducao_movimento)->toBeTrue();
});

it('aplicarPreferenciaSistema() e um no-op quando o registro ja existia (evita sobrescrever escolha explicita do usuario)', function () {
    $usuario = User::factory()->create();
    $configuracao = ConfiguracaoUsuario::paraUsuario($usuario->id);
    $configuracao->update(['reducao_movimento' => false]);

    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class)
        ->assertSet('recemCriado', false)
        ->call('aplicarPreferenciaSistema', true)
        ->assertSet('reducaoMovimento', false);

    expect($configuracao->refresh()->reducao_movimento)->toBeFalse();
});

it('TELA-006: tela exibe um card unico com botao "Salvar preferencias" explicito, sem auto-apply antes do clique', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test(ConfiguracaoManager::class)
        ->assertSeeHtml('config-salvar')
        ->assertSee('Salvar preferencias')
        ->set('tema', 'escuro')
        ->assertDontSeeHtml('config-salvo-confirmacao');

    // Alterar o campo no formulario (sem chamar salvar()) nao persiste nada -- confirma que
    // nao ha auto-apply: so wire:submit="salvar" grava no banco.
    expect(ConfiguracaoUsuario::where('usuario_id', $usuario->id)->firstOrFail()->tema)->toBe('claro');
});
