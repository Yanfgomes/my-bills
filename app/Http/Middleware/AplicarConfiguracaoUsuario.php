<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracaoUsuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * RF-PADRAO-CONFIGURACOES (arquitetura.camadas_servicos.mecanismo_locale_substituido):
 * substitui a persistencia so-por-sessao do locale (SetLocale, que roda logo antes deste
 * middleware no grupo 'web') por persistencia por usuario autenticado (RN-009). Sem sessao
 * autenticada (rotas guest: login/registro), este middleware nao faz nada — SetLocale
 * continua sendo a unica fonte de locale para essas rotas, sem ConfiguracaoUsuario.
 */
class AplicarConfiguracaoUsuario
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $configuracao = ConfiguracaoUsuario::paraUsuario(Auth::id());

            app()->setLocale($configuracao->idioma);

            // layouts/app.blade.php renderiza tema/fonte/alto-contraste/reducao-movimento
            // diretamente deste valor compartilhado, no primeiro paint (sem flash de tema
            // errado). TemaToggleTopbar::mount() tambem le daqui, sem query propria.
            View::share('configuracaoUsuario', $configuracao);
        }

        return $next($request);
    }
}
