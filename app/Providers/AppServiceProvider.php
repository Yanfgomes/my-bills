<?php

namespace App\Providers;

use App\Models\Despesa;
use App\Models\FonteRenda;
use App\Models\User;
use App\Observers\AuditoriaObserver;
use App\Policies\DespesaPolicy;
use App\Policies\FonteRendaPolicy;
use Illuminate\Support\Facades\Gate;
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

        // RF-003/RN-005 (isolamento de dados por usuario autenticado), consolidacao: registro
        // explicito das Policies ja criadas por RF-004/RF-006 (App\Policies\FonteRendaPolicy/
        // DespesaPolicy). Laravel 13 resolveria as duas por convencao de nome (Model em
        // App\Models -> {Model}Policy em App\Policies) mesmo sem este bloco -- os testes de
        // RN-005 de RF-004/006/008 ja passam hoje com a resolucao implicita, confirmando que a
        // convencao funciona (nao havia gap funcional). O registro explicito abaixo nao muda
        // comportamento nenhum: torna a autorizacao por usuario auditavel neste unico ponto,
        // em vez de depender silenciosamente da convencao de nomenclatura continuar valendo
        // caso um model ou uma Policy mude de nome/namespace no futuro.
        Gate::policy(FonteRenda::class, FonteRendaPolicy::class);
        Gate::policy(Despesa::class, DespesaPolicy::class);
    }
}
