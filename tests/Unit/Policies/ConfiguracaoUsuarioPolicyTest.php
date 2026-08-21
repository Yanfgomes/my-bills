<?php

use App\Models\ConfiguracaoUsuario;
use App\Models\User;
use App\Policies\ConfiguracaoUsuarioPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * RF-PADRAO-CONFIGURACOES/RN-009 — unidade pura de ConfiguracaoUsuarioPolicy (dono vs
 * nao-dono, sem tocar banco/HTTP), mais a confirmacao do registro explicito em
 * App\Providers\AppServiceProvider::boot() (Gate::policy). Mesmo padrao de
 * tests/Unit/Policies/PolicyRegistrationTest.php (FonteRenda/Despesa) -- a cobertura
 * cross-user via Livewire ja existe em ConfiguracaoManagerTest/TemaToggleTopbarTest (RN-009);
 * este arquivo cobre a Policy isolada.
 */
uses(Tests\TestCase::class);

it('registra explicitamente ConfiguracaoUsuario->ConfiguracaoUsuarioPolicy via Gate::policy em AppServiceProvider::boot()', function () {
    expect(Gate::getPolicyFor(ConfiguracaoUsuario::class))->toBeInstanceOf(ConfiguracaoUsuarioPolicy::class);
});

it('view/update autorizam o dono e negam usuario diferente, em unidade pura', function () {
    $policy = new ConfiguracaoUsuarioPolicy();

    $dono = new User();
    $dono->id = 'usuario-dono';
    $outro = new User();
    $outro->id = 'usuario-outro';

    $configuracao = new ConfiguracaoUsuario(['idioma' => 'pt']);
    $configuracao->usuario_id = 'usuario-dono';

    expect($policy->view($dono, $configuracao))->toBeTrue()
        ->and($policy->update($dono, $configuracao))->toBeTrue()
        ->and($policy->view($outro, $configuracao))->toBeFalse()
        ->and($policy->update($outro, $configuracao))->toBeFalse();
});
