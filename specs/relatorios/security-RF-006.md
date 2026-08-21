# security-agent — RF-006 — pentest whitebox completo

## RNF-002 (IDOR) — evidência
Exploração real via worktree isolado (scratchpad, sqlite isolado, nunca `database/database.sqlite` do projeto), Laravel real bootado: criados 2 usuários reais (Alice/Bob), 1 Despesa cada.
1. Autenticado como Alice: `Gate::allows('view'|'update'|'delete', $despesaDeBob)` = false nos 3 casos (DespesaPolicy nega corretamente, contra usuário real).
2. `Gate::allows('view'|'viewAny'|'create', ...)` para recurso próprio/geral de Alice = true, sem falso negativo.
3. Query de listagem (`Despesa::where('usuario_id', Auth::id())`) retornou apenas o registro de Alice (count=1).
4. Após `Auth::logout()`, `Gate::allows('view', ...)` = false sem exceção.
5. Rotas: única rota tocando despesa é `GET /despesas -> DespesaManager`, middleware `[web, auth]`, sem parâmetro `{id}` — não existe hoje ação que aceite identificador de Despesa vindo do cliente (editar/excluir são RF-007).

Status: atendido, 0 achados confirmados.

## Verificações adicionais (sem achado, registradas como observação)
- RN-002 (valor > 0): validado no backend + CHECK de banco (defesa em profundidade); INSERT direto com valor negativo rejeitado pelo SQLite (QueryException).
- RN-003 (formato AAAA-MM): validado só na camada de app (regex), sem CHECK de banco — INSERT direto com mês inválido foi aceito pelo SQLite. Não é vulnerabilidade hoje (nenhuma rota aceita SQL cru ou contorna o `validate()`), mas é debito técnico de defesa em profundidade — mesmo padrão provável em `fontes_renda`/RF-004.
- Mass assignment: `Despesa` inclui `usuario_id` no `Fillable`; não é explorável hoje porque o único ponto de escrita (`DespesaManager::criar()`) sempre força `usuario_id => Auth::id()`. Ponto de atenção estrutural: se um RF futuro reusar `Despesa::create()` sem repetir essa linha, o vetor se torna real.
- XSS: saída sempre via `{{ }}` (auto-escape), sem achado.
- SQL injection: Eloquent parametrizado em toda leitura/escrita, sem achado.
- CSRF: protegido pelo mecanismo padrão do Livewire + middleware `auth`.
- Exposição de dado sensível: nenhuma.

Veredito: 0 vulnerabilidades confirmadas, 0 por análise estática. Pentest manual (sem SAST automatizado, ferramenta indisponível/pendente_infraestrutura).
