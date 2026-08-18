<?php

use App\Livewire\Auth\RegistroForm;
use Illuminate\Support\Facades\Route;

Route::get('/registro', RegistroForm::class)
    ->middleware('guest')
    ->name('registro');

// Stub minimo de destino pos-registro/login (RF-001/RF-002 redirecionam para 'overview').
// Substituido pelo App\Livewire\Overview\OverviewFinanceiro real quando RF-003/RF-008 forem
// implementados (arquitetura.documentacao_tecnica.rotas) — nao antecipa aquele escopo, so
// mantem o redirect ja aprovado para RF-001 funcional entre um RF e outro.
Route::get('/overview', function () {
    return view('overview-stub');
})->middleware('auth')->name('overview');

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'overview' : 'registro');
});
