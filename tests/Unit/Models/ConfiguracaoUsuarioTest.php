<?php

use App\Models\ConfiguracaoUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF-PADRAO-CONFIGURACOES — unidade de App\Models\ConfiguracaoUsuario. Cobre a correcao de
 * SEC-CONFIG-001 (usuario_id nunca mass-assignable, mesmo via fill()/create() com payload nao
 * filtrado) e o comportamento de paraUsuario() (unico ponto legitimo de criacao, RN-009).
 */
uses(Tests\TestCase::class, RefreshDatabase::class);

it('SEC-CONFIG-001: usuario_id nao e mass-assignable -- fill() com payload nao filtrado ignora usuario_id', function () {
    $usuario = User::factory()->create();
    $outroUsuario = User::factory()->create();

    $configuracao = ConfiguracaoUsuario::paraUsuario($usuario->id);

    $configuracao->fill([
        'usuario_id' => $outroUsuario->id,
        'tema' => 'escuro',
    ]);

    expect($configuracao->usuario_id)->toBe($usuario->id)
        ->and($configuracao->tema)->toBe('escuro');
});

it('SEC-CONFIG-001: create() com payload nao filtrado descarta usuario_id silenciosamente (nao fillable) -- nunca grava o id forjado do payload', function () {
    $outroUsuario = User::factory()->create();

    // usuario_id nao e fillable (correcao SEC-CONFIG-001): fill() o descarta em silencio, e a
    // coluna usuario_id (NOT NULL + FK) fica sem valor -- create() falha no banco em vez de
    // gravar o id forjado do payload, o que ja seria a falha de seguranca original (IDOR).
    expect(fn () => ConfiguracaoUsuario::create([
        'usuario_id' => $outroUsuario->id,
        'idioma' => 'pt',
        'tema' => 'claro',
        'tamanho_fonte' => 'medio',
        'alto_contraste' => false,
        'reducao_movimento' => false,
    ]))->toThrow(\Illuminate\Database\QueryException::class);

    expect(ConfiguracaoUsuario::where('usuario_id', $outroUsuario->id)->exists())->toBeFalse();
});

it('paraUsuario() cria o registro com defaults na primeira chamada e reutiliza o mesmo registro nas chamadas seguintes', function () {
    $usuario = User::factory()->create();

    $primeira = ConfiguracaoUsuario::paraUsuario($usuario->id);

    expect($primeira->wasRecentlyCreated)->toBeTrue()
        ->and($primeira->idioma)->toBe('pt')
        ->and($primeira->tema)->toBe('claro')
        ->and($primeira->tamanho_fonte)->toBe('medio')
        ->and($primeira->alto_contraste)->toBeFalse()
        ->and($primeira->reducao_movimento)->toBeFalse();

    $segunda = ConfiguracaoUsuario::paraUsuario($usuario->id);

    expect($segunda->wasRecentlyCreated)->toBeFalse()
        ->and($segunda->id)->toBe($primeira->id)
        ->and(ConfiguracaoUsuario::where('usuario_id', $usuario->id)->count())->toBe(1);
});

it('constraint UNIQUE em usuario_id impede um segundo registro para o mesmo usuario, mesmo contornando paraUsuario()', function () {
    $usuario = User::factory()->create();
    ConfiguracaoUsuario::paraUsuario($usuario->id);

    expect(function () use ($usuario) {
        $duplicado = new ConfiguracaoUsuario(['idioma' => 'en']);
        $duplicado->usuario_id = $usuario->id;
        $duplicado->save();
    })->toThrow(\Illuminate\Database\QueryException::class);
});

it('usuario() resolve o usuario dono do registro', function () {
    $usuario = User::factory()->create();
    $configuracao = ConfiguracaoUsuario::paraUsuario($usuario->id);

    expect($configuracao->usuario)->not->toBeNull()
        ->and($configuracao->usuario->id)->toBe($usuario->id);
});
