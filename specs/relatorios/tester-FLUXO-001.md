# tester-agent — FLUXO-001 (Autenticacao) — teste de sistema

- RFs: RF-001 (registro), RF-002 (login)
- Branch/commit testado: `fix/bug-007-sec-session-fixation` @ `5165a79` (fix BUG-007-SEC), teste commitado em `440bc51`
- Framework: Pest (`vendor/bin/pest`), PHP 8.4.22 (Herd `php84`) — o `vendor/composer/platform_check.php` do projeto exige PHP >= 8.4.1; PHP 8.3 (tambem disponivel no ambiente) falha no bootstrap do Composer autoloader.
- Arquivo criado: `tests/Feature/Auth/FluxoAutenticacaoTest.php` (3 testes, nao repete os unitarios/integracao ja existentes em `RegistroFormTest.php`/`LoginFormTest.php`)

## Casos cobertos
1. Fluxo feliz completo: registro -> `assertRedirect(overview)` -> logout -> login com as mesmas credenciais -> `assertRedirect(overview)` -> GET `/overview` autenticado retorna 200 e renderiza `OverviewFinanceiro` (sessao de fato utilizavel, nao so autenticada em memoria).
2. Regressao de composicao de BUG-007-SEC: fixa um ID de sessao antes do registro, confirma que `session()->getId()` muda apos `RegistroForm::registrar()`; repete o mesmo ataque no login subsequente sobre o mesmo usuario ja cadastrado, confirma novamente a regeneracao. Cobre os dois pontos de entrada do fluxo juntos, nao isoladamente.
3. Composicao negativa (RN-006): login com senha incorreta nao autentica e GET `/overview` redireciona para `/login` (middleware `auth` real, nao assercao isolada de componente).

## Execucao
```
php vendor/bin/pest tests/Feature/Auth/FluxoAutenticacaoTest.php --colors=never
Tests: 3 passed (29 assertions)
```
Suite completa de `tests/Feature/Auth/` (30 testes, incluindo `FluxoAutenticacaoTest`, `LoginFormTest`, `RegistroFormTest`): 30 passed (124 assertions).

Suite geral do projeto (`php vendor/bin/pest`, sem escopo): 97 passed, 1 failed — `Tests\Feature\ExampleTest::test_the_application_returns_a_successful_response` (scaffold padrao do Laravel, espera GET `/` = 200; a rota real redireciona para `registro`/`overview` conforme `routes/web.php`). Pre-existente, fora do escopo de FLUXO-001/RF-001/RF-002 e nao tocado por este ciclo — nao incluido no veredito deste teste de sistema.
