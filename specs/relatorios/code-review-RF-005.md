# Code Review — RF-005 (edição/exclusão de fonte de renda)

commit_ref: 2d8e8c198a4f4d5eb1dec380c1e95b2034644c94
branch_ref: rf/RF-005

## Quality gate de análise estática

`arquitetura.padroes_tecnologias.stack.ferramenta_qualidade_codigo` está com `status = pendente_infraestrutura`
(ferramenta/acionamento nulos, confirmado pelo usuário — não há SonarQube/similar configurado).
Gate registrado como `indisponivel`, conforme protocolo. Nenhum resultado inventado.

Executado apenas Laravel Pint (formatador oficial da stack, PSR-12) sobre os dois arquivos
tocados no commit, como checagem informal (não substitui o gate ausente):

```
php vendor/bin/pint --test app/Livewire/Renda/RendaManager.php resources/views/livewire/renda/renda-manager.blade.php
{"tool":"pint","result":"passed"}
```

Sem violação de formatação.

## Revisão qualitativa

### Arquivos revisados
- `app/Livewire/Renda/RendaManager.php` (diff completo lido)
- `resources/views/livewire/renda/renda-manager.blade.php` (diff completo lido)
- Dependências conferidas: `app/Policies/FonteRendaPolicy.php` (métodos `update`/`delete` já
  existiam, reaproveitados corretamente), `app/Observers/AuditoriaObserver.php` e
  `app/Providers/AppServiceProvider.php` (confirma `FonteRenda::observe(AuditoriaObserver::class)`
  já registrado — `updated`/`deleted` disparam log automaticamente, sem chamada explícita
  necessária nos novos métodos).

### Aderência aos 4 contratos aprovados pelo design-agent
- `editar(string $id)`: `findOrFail` escopado por `usuario_id == Auth::id()` antes de `authorize`
  (evita vazar existência de registro de terceiro) — conforme contrato e RN-005/RNF-002. OK.
- `atualizar()`: `mesReferencia` corretamente fora do array de `validate()` e do payload de
  `update()` — RN-008 reforçado no servidor, não só na view (`@disabled($editandoId)`). OK.
- `cancelarEdicao()`: reseta sem persistir. OK.
- `excluir(string $id)`: sem modal de confirmação, fiel a TELA-004; reseta o formulário se o item
  excluído era o que estava em edição. OK. Sem `authorize` faltando.

### Achado: duplicação de regras/mensagens de validação (DRY)
`RendaManager::criar()` (linhas 48-60) e `RendaManager::atualizar()` (linhas 106-115) repetem
literalmente as mesmas 2 regras (`descricao`, `valorLiquido`) e as mesmas 5 mensagens de erro.
Nenhuma convenção nova sendo violada (Form Objects também são aceitos por
`convencoes.validacao`), mas é duplicação real que vai divergir silenciosamente se uma mensagem
mudar em só um dos dois métodos no futuro. Sugestão: extrair para um método privado
`regrasComuns()`/`mensagensComuns()` reaproveitado pelos dois métodos.

### ACC-RF-008-01 (débito de acessibilidade corrigido neste commit)
Todas as ocorrências de `text-[**px]` no arquivo blade foram convertidas para `rem` usando a
fórmula já aplicada em `overview-financeiro.blade.php` (valor_px / 16), conferidas uma a uma:
`13px→0.8125rem`, `12.5px→0.78125rem`, `10.5px→0.65625rem`, `11.5px→0.71875rem`,
`11px→0.6875rem` — todas matematicamente corretas. Os novos botões Editar/Excluir também usam
`text-[0.6875rem]`, consistente com a escala já usada nas mensagens de erro do mesmo formulário
(sem tamanho novo inventado). `grep` confirma zero ocorrências remanescentes de `text-[**px]`
neste arquivo. Sem indício de regressão visual (mesmos valores em px, só unidade trocada).

### Auditoria (RNF-PADRAO-LOG-AUDITORIA)
`FonteRenda::observe(AuditoriaObserver::class)` já registrado desde RF-004 no
`AppServiceProvider`; `AuditoriaObserver::updated()`/`deleted()` gravam automaticamente em
`logs_auditoria` com os 5 campos obrigatórios (ação, usuário, De/Para via `getChanges()`,
tabela/entidade, `criado_em`). Nenhuma chamada explícita necessária em `atualizar()`/`excluir()` —
confirmado, comportamento correto.

### Demais pontos
- Nomenclatura, estrutura de pastas e Policy seguem `convencoes` sem desvio.
- `wire:target="criar,atualizar"` e alternância de label/botões conforme `$editandoId` corretos.
- Sem observação sobre a ausência de modal de confirmação — decisão de design já aprovada
  (TELA-004), fora de escopo desta revisão.

## Conclusão
Nenhum achado bloqueante. 1 observação de qualidade (duplicação de validação) registrada como
não bloqueante em `qa.code_review.revisao_qualitativa.observacoes`.
