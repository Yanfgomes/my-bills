<?php

use App\Models\ConfiguracaoUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF-PADRAO-CONFIGURACOES — App\Http\Middleware\AplicarConfiguracaoUsuario. Cobre a
 * "aplicacao efetiva" do criterio_aceite (locale, tema, tamanho de fonte, alto contraste e
 * reducao de movimento realmente refletidos na resposta HTTP renderizada), registrado logo
 * apos SetLocale (bootstrap/app.php) para requisicao autenticada. Sem sessao autenticada,
 * este middleware nao atua (nao ha ConfiguracaoUsuario a resolver).
 */
uses(RefreshDatabase::class);

it('requisicao autenticada aplica locale, tema, tamanho de fonte, alto contraste e reducao de movimento persistidos na resposta', function () {
    $usuario = User::factory()->create();
    ConfiguracaoUsuario::paraUsuario($usuario->id)->update([
        'idioma' => 'en',
        'tema' => 'escuro',
        'tamanho_fonte' => 'grande',
        'alto_contraste' => true,
        'reducao_movimento' => true,
    ]);

    $this->actingAs($usuario);

    $response = $this->get(route('overview'));

    $response->assertOk();
    $response->assertSee('data-theme="dark"', false);
    $response->assertSee('data-font-size="grande"', false);

    $tagHtml = tagHtml($response);
    expect($tagHtml)->toContain('alto-contraste')
        ->and($tagHtml)->toContain('reduzir-movimento');
});

it('requisicao autenticada com defaults reflete tema claro/fonte media sem classes de contraste/reducao', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $response = $this->get(route('overview'));

    $response->assertOk();
    $response->assertSee('data-theme="light"', false);
    $response->assertSee('data-font-size="medio"', false);

    $tagHtml = tagHtml($response);
    expect($tagHtml)->not->toContain('alto-contraste')
        ->and($tagHtml)->not->toContain('reduzir-movimento');
});

it('aplica o locale do usuario (nao so o da sessao/SetLocale) em requisicao autenticada', function () {
    $usuario = User::factory()->create();
    ConfiguracaoUsuario::paraUsuario($usuario->id)->update(['idioma' => 'es']);

    // Sessao com locale diferente do registro persistido -- o registro do usuario
    // (AplicarConfiguracaoUsuario) tem a ultima palavra sobre SetLocale, conforme
    // arquitetura.camadas_servicos.mecanismo_locale_substituido.
    session(['locale' => 'en']);

    $this->actingAs($usuario);
    $this->get(route('overview'));

    expect(app()->getLocale())->toBe('es');
});

it('rota guest (login) nao e afetada -- SetLocale continua a unica fonte de locale sem sessao autenticada', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    // Sem ConfiguracaoUsuario compartilhada, a rota guest nao usa este middleware -- nao ha
    // erro renderizando a pagina de login sem $configuracaoUsuario compartilhado.
});

/**
 * Extrai so a tag <html ...> de abertura da resposta -- evita falso positivo de
 * assertSee/assertDontSee('alto-contraste')/('reduzir-movimento'), que tambem aparecem como
 * texto visivel na tela (rotulos "Alto contraste"/"Reducao de movimento", TELA-006) sem
 * relacao com a classe aplicada em <html> por este middleware.
 */
function tagHtml($response): string
{
    preg_match('/<html\b[^>]*>/', $response->getContent(), $matches);

    return $matches[0] ?? '';
}
