# Pentest de fluxo — FLUXO-002 (Gestão financeira: renda -> despesa -> overview)

Fase 3 do `qa-coordenador-agent`. Branch `main` (fluxo já mergeado). RFs: RF-004 (RendaManager,
DOM-002), RF-006 (DespesaManager, DOM-003), RF-008 (OverviewFinanceiro, DOM-004). Sinal de risco:
travessia de 3 domínios (código estático 730680c no momento da auditoria).

## Escopo revisado
- `app/Livewire/Renda/RendaManager.php`
- `app/Livewire/Despesas/DespesaManager.php`
- `app/Livewire/Overview/OverviewFinanceiro.php`
- `app/Policies/FonteRendaPolicy.php`, `app/Policies/DespesaPolicy.php`
- `app/Services/OverviewService.php`, `app/Services/OverviewData.php`
- `app/Models/FonteRenda.php`, `app/Models/Despesa.php`, `app/Models/LogAuditoria.php`
- `app/Observers/AuditoriaObserver.php`
- `app/Providers/AppServiceProvider.php` (registro de Gate::policy)
- `routes/web.php` (middleware `auth` nas 3 rotas)
- `database/migrations/2026_08_19_140000_create_fontes_renda_table.php` e
  `..._150000_create_despesas_table.php` (CHECK de valor > 0 em nível de banco)

## Achados por análise estática (composição)
1. Os 3 componentes derivam o usuário exclusivamente de `Auth::id()`/`auth()->id()` no servidor
   (`render()`/`criar()`/`mount()`), nunca de propriedade pública `wire:model` ou parâmetro de
   rota — não há vetor de "usuario_id" trocável via payload Livewire em nenhum dos 3 domínios, o
   que elimina o principal vetor de IDOR de composição (um domínio confiar em identificador que
   outro domínio deixou "aberto").
2. `mount()` dos 3 componentes chama `$this->authorize('viewAny', ...)`, reforçando em nível de
   ação a mesma restrição já aplicada nas queries (`where('usuario_id', Auth::id())`), consistente
   nos 3 domínios — nenhum ponto de composição contorna a checagem de um domínio usando estado
   deixado por outro.
3. `$mesSelecionado` em `OverviewFinanceiro` é `#[Locked]` (correção prévia CR-RF-008-01/SEC-RF-008-01,
   já registrada); `mesReferencia` em Renda/Despesa é validado por regex antes de persistir — não
   encontrado caminho de composição (renda em um mês, despesa em outro, navegação de overview)
   capaz de contornar RN-001/RN-007 ou vazar dado de mês não selecionado.
4. `FonteRenda`/`Despesa` usam `#[Fillable(...)]` explícito sem incluir nada derivado de input não
   confiável para `usuario_id` (sempre setado no array de `create()` a partir de `Auth::id()`, fora
   do `$dados` validado) — sem vetor de mass assignment cross-user.
5. Tabela única de auditoria (`logs_auditoria`) ainda sem tela de consulta no escopo aprovado
   (RNF-PADRAO-LOG-AUDITORIA.relatorio_consulta) — nenhuma rota do fluxo expõe log de outro
   domínio/usuário; não é achado deste fluxo (falta de RF, já sinalizado ao orquestrador
   anteriormente).

## Achados confirmados por exploração (testes de pentest escritos e executados nesta auditoria)
Testes escritos em `tests/Feature/Fluxos/_SecPentestFluxo002Test.php` (temporário, removido após
confirmação — evidência preservada aqui), executados via
`vendor/bin/pest tests/Feature/Fluxos/_SecPentestFluxo002Test.php` (Pest, SQLite in-memory):

1. **Acesso não autenticado às 3 rotas do fluxo** (`GET /renda`, `/despesas`, `/overview`) —
   confirmado bloqueio (`redirect('/login')`) nos 3 casos. PASSOU (sem achado).
2. **Tentativa de injetar `usuario_id` de outro usuário via payload Livewire** — `Livewire::test(
   RendaManager::class)->set('usuarioId', $bob->id)` lança exceção (propriedade inexistente/não
   pública); registro criado em seguida ficou com `usuario_id === $alice->id`, nunca o de Bob.
   PASSOU (sem achado).
3. **Sequência renda(mês A) -> despesa(mês B) -> overview do mês corrente** — confirmado que o
   overview não mistura dado de mês não selecionado dentro da mesma sessão/usuário. PASSOU (sem
   achado).

Um 4º teste (exclusão em cascata de usuário terceiro, tentando forçar `usuario_id` órfão em
`fontes_renda`/`despesas`) falhou por `FOREIGN KEY constraint failed` ao excluir o usuário — mas a
causa é a ausência de `onDelete('cascade')` na FK `logs_auditoria.usuario_id`
(`database/migrations/2026_08_18_135525_create_logs_auditoria_table.php:28`), não uma falha de
isolamento. **Não é achado deste fluxo**: não há RF no escopo aprovado que exponha exclusão de
conta de usuário (nenhuma rota/ação alcançável por atacante ou usuário legítimo hoje) — o teste
forçou `User::delete()` diretamente via Eloquent, fora de qualquer superfície de ataque real.
Registrado aqui só como observação para o `qa-coordenador-agent` avaliar se vale abrir um RF de
exclusão de conta no futuro (ponto de atenção de integridade referencial, não de segurança).

## RNF-002 (IDOR sobre Renda/Despesas/Overview, verificação na composição)
Critério: "0 rotas vulneráveis a IDOR ... verificado por pentest whitebox direcionado a cada RF
de escrita/leitura desses domínios" — já verificado por RF isoladamente (RF-004/RF-006 nesta
auditoria, RF-008 em ciclo anterior, `testes.seguranca.itens` existente). Nesta Fase 3, verificado
adicionalmente **na composição**: a ausência de qualquer identificador de registro trocável no
escopo atual (marco-1-mvp só implementa listar+criar, sem rota com `{id}` para editar/excluir —
isso só chega em RF-005/RF-007, marco-2) reduz a superfície de IDOR clássico (troca de ID na URL)
a zero nesta janela; o vetor investigado nesta Fase 3 foi injeção de identificador via estado de
componente/sessão entre os 3 domínios, sem sucesso (achado 2 acima). Atendido.

## Conclusão
Nenhum achado confirmado por exploração. Nenhum achado por análise estática além de observações
já cobertas por RF isolado. Criticidade máxima: nenhuma.
