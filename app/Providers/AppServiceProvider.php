<?php

namespace App\Providers;

use App\Models\Despesa;
use App\Models\FonteRenda;
use App\Models\User;
use App\Observers\AuditoriaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auditoria (arquitetura.padroes_tecnologias.log_auditoria): todo model de dado de
        // negocio e observado aqui. Novos models de dominio (FonteRenda, Despesa, marcos
        // futuros) entram nesta mesma lista quando forem implementados.
        User::observe(AuditoriaObserver::class);
        FonteRenda::observe(AuditoriaObserver::class);
        Despesa::observe(AuditoriaObserver::class);
    }
}
