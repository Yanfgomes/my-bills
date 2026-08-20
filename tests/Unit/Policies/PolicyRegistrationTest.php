<?php

use App\Models\Despesa;
use App\Models\FonteRenda;
use App\Models\User;
use App\Policies\DespesaPolicy;
use App\Policies\FonteRendaPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * RF-003/RN-005 (consolidacao) — unidade pura das Policies de FonteRenda/Despesa (dono vs
 * nao-dono, sem tocar banco/HTTP) mais a confirmacao de que o registro explicito em
 * App\Providers\AppServiceProvider::boot() (Gate::policy) resolve para as classes corretas.
 * A cobertura cross-user via Livewire/HTTP ja existe em tests/Feature/Renda/RendaManagerTest.php
 * e tests/Feature/Despesas/DespesaManagerTest.php (RN-005) — este arquivo cobre o que aquelas
 * nao cobrem: o mapeamento explicito Model->Policy em si (o unico comportamento novo do commit
 * de RF-003), e as Policies isoladas, sem depender de Livewire/rota/banco.
 */
uses(Tests\TestCase::class);

it('registra explicitamente FonteRenda->FonteRendaPolicy e Despesa->DespesaPolicy via Gate::policy em AppServiceProvider::boot()', function () {
    expect(Gate::getPolicyFor(FonteRenda::class))->toBeInstanceOf(FonteRendaPolicy::class)
        ->and(Gate::getPolicyFor(Despesa::class))->toBeInstanceOf(DespesaPolicy::class);
});

it('FonteRendaPolicy: view/update/delete autorizam o dono e negam usuario diferente, em unidade pura', function () {
    $policy = new FonteRendaPolicy();

    $dono = new User();
    $dono->id = 'usuario-dono';
    $outro = new User();
    $outro->id = 'usuario-outro';

    $fonte = new FonteRenda(['usuario_id' => 'usuario-dono']);

    expect($policy->view($dono, $fonte))->toBeTrue()
        ->and($policy->update($dono, $fonte))->toBeTrue()
        ->and($policy->delete($dono, $fonte))->toBeTrue()
        ->and($policy->view($outro, $fonte))->toBeFalse()
        ->and($policy->update($outro, $fonte))->toBeFalse()
        ->and($policy->delete($outro, $fonte))->toBeFalse();
});

it('FonteRendaPolicy: viewAny/create autorizam qualquer usuario autenticado, em unidade pura', function () {
    $policy = new FonteRendaPolicy();

    $usuario = new User();
    $usuario->id = 'qualquer-usuario';

    expect($policy->viewAny($usuario))->toBeTrue()
        ->and($policy->create($usuario))->toBeTrue();
});

it('DespesaPolicy: view/update/delete autorizam o dono e negam usuario diferente, em unidade pura', function () {
    $policy = new DespesaPolicy();

    $dono = new User();
    $dono->id = 'usuario-dono';
    $outro = new User();
    $outro->id = 'usuario-outro';

    $despesa = new Despesa(['usuario_id' => 'usuario-dono']);

    expect($policy->view($dono, $despesa))->toBeTrue()
        ->and($policy->update($dono, $despesa))->toBeTrue()
        ->and($policy->delete($dono, $despesa))->toBeTrue()
        ->and($policy->view($outro, $despesa))->toBeFalse()
        ->and($policy->update($outro, $despesa))->toBeFalse()
        ->and($policy->delete($outro, $despesa))->toBeFalse();
});

it('DespesaPolicy: viewAny/create autorizam qualquer usuario autenticado, em unidade pura', function () {
    $policy = new DespesaPolicy();

    $usuario = new User();
    $usuario->id = 'qualquer-usuario';

    expect($policy->viewAny($usuario))->toBeTrue()
        ->and($policy->create($usuario))->toBeTrue();
});
