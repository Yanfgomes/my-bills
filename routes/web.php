<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\LoginForm;
use App\Livewire\Auth\RegistroForm;
use App\Livewire\Despesas\DespesaManager;
use App\Livewire\Overview\OverviewFinanceiro;
use App\Livewire\Renda\RendaManager;
use Illuminate\Support\Facades\Route;

Route::get('/registro', RegistroForm::class)
    ->middleware('guest')
    ->name('registro');

Route::get('/login', LoginForm::class)
    ->middleware('guest')
    ->name('login');

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

// RF-008 (Overview financeiro). Substitui o stub temporario que existia entre RF-001/RF-002 e
// este RF (view overview-stub, removida). Rota flat 'overview', mesma convencao ja usada desde
// RF-001/RF-002 para o destino pos-login/registro (nao dot notation por dominio, diferente de
// renda.index/despesas.index).
Route::get('/overview', OverviewFinanceiro::class)
    ->middleware('auth')
    ->name('overview');

// RF-004 (Renda). Rota em dot notation por dominio (renda.index), conforme
// arquitetura.padroes_tecnologias.convencoes.nomenclatura — diferente das rotas de auth
// (registro/login/logout), que sao flat por decisao ja registrada em marcos anteriores.
Route::get('/renda', RendaManager::class)
    ->middleware('auth')
    ->name('renda.index');

// RF-006 (Despesas). Mesma convencao de nomenclatura de renda.index (dot notation por dominio).
Route::get('/despesas', DespesaManager::class)
    ->middleware('auth')
    ->name('despesas.index');

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'overview' : 'registro');
});
