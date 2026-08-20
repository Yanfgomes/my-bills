# tester-agent — RF-003 (isolamento de dados por usuario autenticado, RN-005)

## Escopo testado
Commit `e596c4b` (branch `rf/RF-003`): docblocks + `Gate::policy(FonteRenda::class,
FonteRendaPolicy::class)` e `Gate::policy(Despesa::class, DespesaPolicy::class)` em
`App\Providers\AppServiceProvider::boot()`.

## Testes novos (commit `601aeda`, mesma branch)
`tests/Unit/Policies/PolicyRegistrationTest.php` — 5 testes unitarios puros (sem DB/HTTP):

1. `Gate::getPolicyFor(FonteRenda::class)` resolve `FonteRendaPolicy`; `Gate::getPolicyFor(Despesa::class)`
   resolve `DespesaPolicy` — confirma o registro explicito adicionado por este RF (o unico
   comportamento novo do diff).
2. `FonteRendaPolicy`: `view`/`update`/`delete` autorizam o dono e negam usuario diferente.
3. `FonteRendaPolicy`: `viewAny`/`create` autorizam qualquer usuario autenticado.
4. `DespesaPolicy`: `view`/`update`/`delete` autorizam o dono e negam usuario diferente.
5. `DespesaPolicy`: `viewAny`/`create` autorizam qualquer usuario autenticado.

Todos passaram. Testes puros (sem `RefreshDatabase`), instanciando `User`/`FonteRenda`/`Despesa`
diretamente, sem tocar banco/HTTP/Livewire — complementam (nao duplicam) a cobertura cross-user
ja existente em `tests/Feature/Renda/RendaManagerTest.php` e
`tests/Feature/Despesas/DespesaManagerTest.php` (RN-005, 2 usuarios reais via HTTP/Livewire).

## Cobertura (pest --coverage)
- `Policies\FonteRendaPolicy`: 100%
- `Policies\DespesaPolicy`: 100%
- `Providers\AppServiceProvider`: 100%
- Gate do projeto (`testes.cobertura_minima`): 90% — atendido com folga nos arquivos do diff.

## Suite completa do projeto
91 testes relacionados a RN-005/RF-003/004/006/008 (Unit + Feature de Renda, Despesas, Overview,
Policies) — todos verdes, 344+ asserções.

**Achado fora de escopo, nao corrigido:** `tests/Feature/ExampleTest.php` (teste padrao do
scaffold Laravel, `GET /` esperando 200) falha com 302, porque a rota raiz redireciona para
login. Confirmado via `git log` que este teste existe desde o commit `dbdae02` (RF-001) e nunca
foi atualizado — nao e uma regressao introduzida por RF-003, nem toca RN-005/Policies. Reportado
para o qa-coordenador-agent triar (nao e responsabilidade do tester-agent decidir severidade).
