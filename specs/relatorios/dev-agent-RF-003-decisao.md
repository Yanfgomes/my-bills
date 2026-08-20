# RF-003 — decisao_pendente (gate ordem_execucao_dependencia)

Aberto por dev-agent em 2026-08-19T11:54:47Z, respondido em 2026-08-19T11:56:47Z.

## Pergunta
RF-003 ("Isolamento de dados por usuario autenticado") estava ordenado em `desenvolvimento.ordem_execucao`
antes de RF-004 (Renda), RF-006 (Despesas) e RF-008 (Overview). O proprio RF-003, em
`documentacao.requisitos_funcionais`, se descreve como "requisito transversal ... sem tela propria
(aplicado por RF-004 a RF-008)" e seu `criterio_aceite` fala exclusivamente de restringir
leitura/escrita de Renda, Despesas e Overview — nenhuma dessas entidades existia ainda no codigo
(so havia migrations/models de users e logs_auditoria, criados por RF-001/RF-002).
`arquitetura.documentacao_tecnica` tambem nao tinha nenhuma rota/componente/servico/contrato proprio
marcado para RF-003 — so a convencao geral (`padroes_tecnologias.convencoes.autorizacao`) de que
RendaPolicy/DespesaPolicy seriam criadas junto com os proprios RF-004/RF-006. Nao havia o que isolar
nem contra o que testar RN-005 naquele momento.

## Opcoes apresentadas
1. **Adiar RF-003 para o final do lote** (depois de RF-004, RF-006, RF-008) — reordenar
   `ordem_execucao` para RF-003 rodar por ultimo. Nesse momento, com FonteRenda/Despesa/Overview ja
   existentes, dev-agent implementa RF-003 consolidando RendaPolicy, DespesaPolicy e a restricao de
   leitura do Overview, com testabilidade real (dois usuarios distintos).
2. **Manter a ordem aprovada, implementar agora so infraestrutura generica de isolamento** — trait de
   global scope Eloquent e/ou base de Policy sem model concreto. Risco: codigo sem consumidor e sem
   cobertura de teste possivel agora, e decisao de arquitetura (global scope vs Policy por model) que
   nao cabe ao dev-agent.
3. **Tratar RF-003 como satisfeito por composicao, sem branch/commit proprio** — registrar que RF-003
   nao gera artefato de codigo isolado; seu criterio_aceite e verificado quando RF-004/RF-006/RF-008
   aplicarem as Policies. Diverge do padrao "todo RF tem branch e commit", exige decisao humana.

## Contexto
`planejamento.escopo.marcos[marco-1-mvp].rf_refs` ja aprovou a ordem
RF-001,RF-002,RF-003,RF-004,RF-006,RF-008 no gate `aprovacao_escopo` (analise-planejamento-agent).
dev-agent nao tem AskUserQuestion nem pode reordenar essa lista por conta propria — so sinalizar.
RF-001 e RF-002 ja estavam aprovado/mergeado. Nenhum model de Renda/Despesa/Overview existia no
codigo ainda (so users, logs_auditoria).

## Resposta
"Adiar RF-003 para o final do lote (depois de RF-004, RF-006, RF-008)."
