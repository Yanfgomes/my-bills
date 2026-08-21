<?php

use App\Livewire\Layout\TemaToggleTopbar;
use App\Models\ConfiguracaoUsuario;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;

/**
 * RF-PADRAO-CONFIGURACOES — alternador rapido de tema no topbar (App\Livewire\Layout\
 * TemaToggleTopbar), presente em toda tela autenticada. Cobre RN-009 (isolamento por
 * usuario) e a geracao de log de auditoria em alteracao, complementando o teste de
 * ConfiguracaoManagerTest (fluxo completo de "Salvar preferencias").
 */
uses(RefreshDatabase::class);

it('mount() le o tema ja compartilhado pelo middleware AplicarConfiguracaoUsuario, sem query propria', function () {
    $usuario = User::factory()->create();
    $configuracao = ConfiguracaoUsuario::paraUsuario($usuario->id);
    $configuracao->update(['tema' => 'escuro']);

    $this->actingAs($usuario);

    // Reproduz o efeito de AplicarConfiguracaoUsuario (View::share), que ja e coberto de
    // ponta a ponta em AplicarConfiguracaoUsuarioMiddlewareTest.php via requisicao HTTP real
    // -- aqui isola o comportamento proprio do componente (mount() le do compartilhamento,
    // sem query direta).
    View::share('configuracaoUsuario', $configuracao);

    Livewire::test(TemaToggleTopbar::class)->assertSet('tema', 'escuro');
});

it('alternar() inverte o tema do usuario autenticado, persiste e registra log de auditoria', function () {
    $usuario = User::factory()->create();
    $configuracao = ConfiguracaoUsuario::paraUsuario($usuario->id);
    expect($configuracao->tema)->toBe('claro');

    $this->actingAs($usuario);

    Livewire::test(TemaToggleTopbar::class)
        ->call('alternar')
        ->assertSet('tema', 'escuro');

    expect($configuracao->refresh()->tema)->toBe('escuro');

    $log = LogAuditoria::where('tabela_afetada', 'configuracoes_usuario')
        ->where('registro_id', $configuracao->id)
        ->where('acao', 'alteracao')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->valor_novo['tema'])->toBe('escuro');
});

it('RN-009: alternar() so afeta o registro do usuario autenticado, nunca o de outro usuario', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $configBob = ConfiguracaoUsuario::paraUsuario($bob->id);
    expect($configBob->tema)->toBe('claro');
    ConfiguracaoUsuario::paraUsuario($alice->id);

    $this->actingAs($alice);

    Livewire::test(TemaToggleTopbar::class)->call('alternar');

    expect($configBob->refresh()->tema)->toBe('claro');

    $configAlice = ConfiguracaoUsuario::where('usuario_id', $alice->id)->firstOrFail();
    expect($configAlice->tema)->toBe('escuro');
});
