# code-review-agent — RF-006 — revisão qualitativa completa

- RN-PADRAO-LOG-AUDITORIA confirmado: `Despesa::observe(AuditoriaObserver::class)` registrado em `app/Providers/AppServiceProvider.php`, reutilizando o Observer genérico já existente. Mesmo padrão já validado em RF-004.
- Nomenclatura/estrutura/rota aderentes a `convencoes`: `Despesa`/`despesas`, `DespesaManager` em `app/Livewire/Despesas`, view em `resources/views/livewire/despesas/`, rota `despesas.index`. Índice composto `(usuario_id, mes_referencia)` presente (RNF-003).
- Autorização (RN-005): `DespesaPolicy` replica exatamente o shape de `FonteRendaPolicy` (viewAny/create usados neste RF; view/update/delete já presentes para RF-007). `$this->authorize('viewAny'/'create', Despesa::class)` presentes, além de escopo de query por `usuario_id` — defesa em profundidade.
- RNF-PADRAO-IDIOMA confirmado: 13 strings novas via `__()`, presentes em `lang/{pt,en,es}.json`, sem chave órfã.
- Tokens de cor: nenhum hex hardcoded, tudo via `var(--color-*)`, mesmo padrão de `renda-manager.blade.php`. Apontamento (não achado deste agente) para o accessibility-agent conferir contraste do botão de submit nos dois temas.
- Migration de despesas usa `CREATE TABLE` bruto (não Schema Builder) — mesma justificativa técnica já aceita em RF-004 (SQLite não suporta `ALTER TABLE ADD CONSTRAINT` para o `CHECK (valor > 0)` exigido). Reprodução consistente, sem achado novo.
- [Não bloqueante, herdado de RF-004, não corrigido] Mensagem da regra `valor.gt` ("o valor deve ser maior que zero") sem maiúscula inicial/ponto final, destoando das demais mensagens do componente, e redundante com `valor.required`/`valor.numeric`. Sugestão para o dev-agent: unificar texto/formatação de `valor.gt` com `valor.required`/`valor.numeric` em `RendaManager` e `DespesaManager` (RN-002 é uma única regra de negócio do ponto de vista do usuário).
- `app/Models/Despesa.php`: `CREATED_AT`/`UPDATED_AT` remapeados para `criado_em`/`atualizado_em`, cast `valor => decimal:2`, relação `usuario()` — shape idêntico a `FonteRenda.php`.
- Escopo marco-1-mvp (listar+criar) respeitado: nenhum `editar()`/`excluir()` em `DespesaManager` nem rota correspondente, conforme `escopo_marco` já registrado pelo design-agent para RF-007. `DespesaPolicy` antecipa `view()`/`update()`/`delete()` sem consumidor ainda, mesma antecipação já aceita em `FonteRendaPolicy` (RF-004).

Veredito: `revisao_qualitativa.status = concluida`, sem achado bloqueante.
