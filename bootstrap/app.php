<?php

use App\Http\Middleware\AplicarConfiguracaoUsuario;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // RF-PADRAO-CONFIGURACOES: AplicarConfiguracaoUsuario roda logo apos SetLocale
        // (arquitetura.camadas_servicos.mecanismo_locale_substituido) — SetLocale continua
        // resolvendo o locale por sessao para rotas guest; para requisicao autenticada, o
        // locale por usuario (ConfiguracaoUsuario) tem a ultima palavra.
        $middleware->web(append: [
            SetLocale::class,
            AplicarConfiguracaoUsuario::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
