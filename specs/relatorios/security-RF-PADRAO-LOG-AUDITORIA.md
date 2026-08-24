# Pentest whitebox — RF-PADRAO-LOG-AUDITORIA (redisparo pos-correcao)

commit_ref: cd5ac34 | branch_ref: rf/RF-PADRAO-LOG-AUDITORIA | data: 2026-08-24T11:47:18Z
ferramenta automatizada: indisponivel (ferramenta_seguranca = null, pendente_infraestrutura) — analise 100% manual + exploracao real via Pest/Livewire::test (removido apos verificacao, nao commitado).

## Reverificacao de SEC-RF-PADRAO-LOG-AUDITORIA-01 — NAO CONFIRMADA, achado reaberto

A correcao (cd5ac34) adicionou `updatedTabelaAfetada()`/`updatedAcao()` chamando
`$this->validateOnly()` a cada sync do Livewire, com a premissa (documentada no comentario do
proprio codigo, `LogAuditoriaRelatorio.php:81-89`) de que a `ValidationException` lancada ali
"aborta a requisicao antes do Livewire chamar render()". **Essa premissa esta incorreta** para
`livewire/livewire ^4.4` em uso neste projeto.

Rastreamento no vendor:
- `Wrapped::__call()` (`vendor/livewire/livewire/src/Wrapped.php:18-35`) envolve toda chamada de
  hook em `try/catch (\Throwable $e)`; ao capturar, dispara o hook global `exception` e so
  relanca se nenhum listener chamar `$stopPropagation()`.
- `SupportValidation::exception()` (`.../SupportValidation/SupportValidation.php:69-76`) escuta
  exatamente `ValidationException`: grava o error bag e chama `$stopPropagation()` — a excecao
  e engolida ali, nunca chega a interromper o ciclo de update que segue para `render()`.
- A propriedade ja foi setada em `updateProperty()` **antes** do hook `updated*` rodar; como a
  excecao nao propaga, o ciclo segue normalmente ate `render()` com o valor invalido intacto.

### Exploracao confirmada (nao so analise estatica)

Duas rodadas de teste descartavel (Pest + `Livewire::test`, removidos apos a verificacao):

1. Primeira rodada só checou `assertHasErrors(['tabelaAfetada'])` apos `set()` com valor fora
   do enum — passou, mas isso so prova que o error bag foi populado, nao que o valor foi
   bloqueado. **Insuficiente, gerou falso-negativo inicialmente.**
2. Segunda rodada, com `DB::enableQueryLog()` e inspecao do estado da prop apos `set()`:
   - `$component->get('tabelaAfetada')` apos `set('tabelaAfetada', 'valor_arbitrario_fora_do_enum')`
     retorna o valor **intacto**, nao revertido.
   - Query executada por `render()` (`count()` da paginacao): `select count(*) ... from
     "logs_auditoria" where "usuario_id" = ? and "tabela_afetada" = ?`, com bindings
     `["<uuid-usuario>", "valor_arbitrario_fora_do_enum"]` — o valor fora do enum chega
     literalmente ao `where()`.

Sem risco de SQL injection (Eloquent parametriza o `where()`), mas o `criterio_aceite_seguranca`
#3 ("filtros aceitam somente os valores previstos... nenhum parametro chega a consulta... [sem]
alterar o escopo") continua **nao atendido**: qualquer string arbitraria passa pelo filtro e e
aplicada a consulta antes de qualquer bloqueio efetivo. `validateOnly()` so produz uma mensagem
de erro visual — nao impede o uso do dado na mesma requisicao.

### Correcao sugerida (nao implementada por este agente — fora do escopo)

`updated*` nao e o lugar certo para *impedir* o dado de chegar a `render()`, porque a excecao
ali e sempre capturada localmente pelo Livewire. Fecha o achado: resetar a propriedade para
`null` dentro do proprio `updatedTabelaAfetada()`/`updatedAcao()` quando o valor nao esta no
enum (em vez de so validar), ou aplicar uma guarda adicional em `render()`
(`in_array($this->tabelaAfetada, self::TABELAS_AUDITADAS, true) ? $this->tabelaAfetada : null`)
antes do `where()`, independente do resultado de `validateOnly()`.

## Varredura geral do RF (commit cd5ac34) — sem achado novo alem do SEC-01 reaberto

- **Autenticacao**: rota `/auditoria` com middleware `auth` + `mount()` chama
  `authorize('viewAny', LogAuditoria::class)`. Exploracao real: `GET /auditoria` sem sessao ->
  redirect `/login`. Confirmado.
- **Autorizacao/IDOR**: `render()` sempre escopa por `usuario_id == Auth::id()` antes de
  qualquer filtro; `verDetalhe()` usa `findOrFail` escopado por `usuario_id` +
  `authorize('view', $log)` (`LogAuditoriaPolicy::view()`). Exploracao real: log de "vitima"
  criado, `verDetalhe()` chamado por "atacante" autenticado com o id do log da vitima ->
  `ModelNotFoundException` (nao revela existencia do registro). HTML de `render()` do atacante
  nao contem o id da vitima. Confirmado, sem bypass.
- **Exposicao de dado sensivel**: `AuditoriaObserver::ocultarCampos()` remove `getHidden()` do
  model (`senha`, `remember_token`) antes de gravar `valor_anterior`/`valor_novo`. Confirmado
  por leitura (`User::$hidden` via atributo `#[Hidden]`).
- **XSS**: `valor_anterior`/`valor_novo` renderizados via `{{ json_encode(...) }}` (Blade
  auto-escapa), dentro de `<pre>` — sem vetor de injecao de HTML/script.
- **Integridade da trilha de auditoria**: `LogAuditoriaPolicy` nao expoe `update`/`delete`;
  nenhuma rota/metodo do componente ou de qualquer outro ponto do app grava fora de
  `AuditoriaObserver::registrar()`; migration sem soft-delete/`updated_at`. Nenhum caminho de
  edicao/exclusao de log encontrado (analise estatica, sem rota a exercitar).
- **Configuracao**: nada especifico deste RF (CORS/headers sao transversais, fora do escopo
  pontual desta reverificacao).

## Criterios de aceite de seguranca declarados (5)

1. Requer autenticacao — atendido (confirmado por exploracao).
2. Escopo self-audit / RN-010, IDOR negado sem vazar existencia — atendido (confirmado por exploracao).
3. Filtros aceitam somente valores previstos, nada chega cru a query — **nao atendido** (achado SEC-01 reaberto, confirmado por exploracao).
4. Resposta so com registros do proprio usuario — atendido (confirmado por exploracao).
5. Somente leitura, sem edicao/exclusao — atendido (analise estatica).
