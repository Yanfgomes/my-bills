<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica o idioma persistido em sessao (padroes_tecnologias.internacionalizacao) a cada
 * requisicao. idiomas_suportados: pt (padrao) / en / es.
 */
class SetLocale
{
    private const IDIOMAS_SUPORTADOS = ['pt', 'en', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));

        if (in_array($locale, self::IDIOMAS_SUPORTADOS, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
