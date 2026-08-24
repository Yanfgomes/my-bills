# Code Review — RF-PADRAO-LOG-AUDITORIA

commit_ref: cd5ac34 (branch rf/RF-PADRAO-LOG-AUDITORIA) — reauditoria pós-correção de
SEC-RF-PADRAO-LOG-AUDITORIA-01. Ciclo anterior: 7be70e5.

## Reauditoria pós-correção (cd5ac34)

Diff cd5ac34 (7be70e5 → cd5ac34) toca só `app/Livewire/Auditoria/LogAuditoriaRelatorio.php`,
adicionando `updatedTabelaAfetada()`/`updatedAcao()` (linhas 90-102). Confirmado que a correção
resolve o achado original: Livewire chama `updated{Prop}()` a cada sync de `wire:model` (a cada
requisição que altera a prop), antes de `render()`. Os dois hooks chamam `validateOnly()` com a
mesma regra `in:` já usada em `filtrar()`/`TABELAS_AUDITADAS`/`ACOES_AUDITADAS` — `validateOnly()`
lança `ValidationException` quando o valor sincronizado está fora do enum, o que aborta o ciclo de
vida da requisição Livewire antes de `render()` rodar, então `render()` nunca mais usa um valor de
`tabelaAfetada`/`acao` fora do enum previsto em `where()`. Achado original fechado.

Nenhum achado bloqueante novo introduzido pela correção: os dois hooks reusam a mesma fonte de
enum (`self::TABELAS_AUDITADAS`/`self::ACOES_AUDITADAS`) já usada em `filtrar()`, sem duplicar
regra nova, e não alteram nenhum outro comportamento (query de `render()`, autorização, i18n,
acessibilidade — inalterados, confirmado pelo diff restrito a este único arquivo).

A observação de duplicação da lista de tabelas auditadas em 3 pontos (achado 1 abaixo, do ciclo
anterior) **não foi tocada por esta correção** e continua válida no estado atual do código.

## Quality gate de análise estática

`arquitetura.padroes_tecnologias.stack.ferramenta_qualidade_codigo.status = pendente_infraestrutura`
(ferramenta/acionamento = null, confirmado pelo usuário durante o design). Nenhuma ferramenta
configurada até o momento — resultado registrado como `indisponivel`, não como falha do RF.
Nenhuma execução foi simulada/inventada. Reconfirmado no recorte lido para esta reauditoria
(cd5ac34): campo inalterado desde o ciclo anterior.

## Revisão qualitativa

Arquivos revisados: `app/Livewire/Auditoria/LogAuditoriaRelatorio.php`,
`app/Policies/LogAuditoriaPolicy.php`, `app/Providers/AppServiceProvider.php`,
`app/Models/LogAuditoria.php`, `database/migrations/2026_08_18_135525_create_logs_auditoria_table.php`,
`app/Observers/AuditoriaObserver.php`, `resources/views/livewire/auditoria/log-auditoria-relatorio.blade.php`,
`resources/views/layouts/partials/app-sidebar.blade.php`, `routes/web.php`, `lang/{pt,en,es}.json`.

### Achados

1. **Duplicação da lista de tabelas auditadas em 3 pontos, sem fonte única.**
   - `app/Providers/AppServiceProvider.php:36-42` registra os Observers (User, FonteRenda,
     Despesa, ConfiguracaoUsuario → tabelas `users`, `fontes_renda`, `despesas`,
     `configuracoes_usuario`).
   - `app/Livewire/Auditoria/LogAuditoriaRelatorio.php:18` redeclara a mesma lista em
     `TABELAS_AUDITADAS` (comentário afirma ser derivada do ServiceProvider, mas é um array
     literal independente, sem nenhuma referência de código a ele).
   - `resources/views/livewire/auditoria/log-auditoria-relatorio.blade.php` (bloco do `<select
     id="aud-filtro-tabela">`) redeclara os mesmos 4 valores como `<option>` estáticas.
   - Risco real (não hipotético): quando um novo model de domínio for observado em marco futuro
     (o próprio comentário do ServiceProvider já prevê isso), é preciso lembrar de atualizar os
     três lugares. Esquecer o segundo/terceiro não quebra o "adicionar auditoria" (o Observer
     continua gravando), mas o filtro do relatório passa a rejeitar/ocultar silenciosamente a
     nova tabela sem nenhum erro visível — falha por omissão, difícil de notar em revisão futura.
   - Sugestão de direção (não é decisão minha): expor a lista observada como constante única
     (ex.: em `AuditoriaObserver` ou em um enum/config) e ambos os outros pontos referenciarem
     essa única fonte.

2. **RN-010/log de auditoria: nenhuma gravação nova aplicável.** Confirmado que o RF é
   somente-leitura (nenhum método de `LogAuditoriaRelatorio` cria/altera/exclui um registro em
   `logs_auditoria`) — não há operação de escrita de dados de negócio neste RF, então a
   exigência de `RNF-PADRAO-LOG-AUDITORIA` de gravar no log não se aplica aqui. Registrado como
   verificação feita, não como achado.

### Demais pontos verificados, sem achado
- Nomenclatura/estrutura de pastas aderente a `convencoes` (`app/Livewire/Auditoria`,
  `app/Policies`, rota `auditoria.index` em dot notation, Blade em `resources/views/livewire/auditoria`).
- Autorização: `LogAuditoriaPolicy` sem `update()`/`delete()` (tabela imutável, coerente com a
  migration sem `updated_at` e `$timestamps = false` no model), escopo por `usuario_id ==
  Auth::id()` reforçado em `render()`/`verDetalhe()` e na Policy (defesa em profundidade, mesmo
  padrão de `FonteRendaPolicy`/`DespesaPolicy`).
- Filtros validados contra enum antes da query (`filtrar()`), sem `DB::table()`/query raw.
- i18n: chaves adicionadas em `lang/pt.json`, `lang/en.json`, `lang/es.json` — mesmas chaves nos
  3 arquivos, traduções coerentes.
- Foco preso/Esc/retorno de foco no modal implementado em JS vanilla com guard
  `window.__auditoriaModalInit` evitando registro duplicado do listener; `Livewire.on` singleton
  bem escopado.
