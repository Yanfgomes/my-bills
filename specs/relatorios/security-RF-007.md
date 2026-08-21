# Pentest whitebox — RF-007 (Edicao e exclusao de despesa)

commit_ref: 63724ffd9fdad70abf28e6f5a3c0328f1fa1cd15
branch_ref: rf/RF-007
componente: App\Livewire\Despesas\DespesaManager

## 1. Criterios de seguranca declarados (piso, RN-005/RNF-002 via arquitetura.documentacao_tecnica)

Confirmado por leitura de codigo (`app/Livewire/Despesas/DespesaManager.php`):
- `editar(string $id)`: `Despesa::where('usuario_id', Auth::id())->findOrFail($id)` executado ANTES de `$this->authorize('update', $despesa)`. Um id de despesa de outro usuario gera 404 (ModelNotFoundException) sem alcancar a policy — nao ha diferenca observavel entre "nao existe" e "existe mas e de outro usuario".
- `atualizar()`: mesmo padrao (`findOrFail` escopado antes de `authorize('update', ...)`), usando `$this->editandoId` (propriedade publica do componente Livewire, portanto manipulavel pelo cliente via payload de update do Livewire).
- `excluir(string $id)`: mesmo padrao, `findOrFail` escopado antes de `authorize('delete', ...)`.
- `DespesaPolicy::update`/`delete` (reaproveitadas do RF-006, nao alteradas neste RF): `return $despesa->usuario_id === $usuario->id;` — corretas, simetricas as ja auditadas em `FonteRendaPolicy` (RF-004/RF-005).
- `Gate::policy(Despesa::class, DespesaPolicy::class)` registrado em `AppServiceProvider::boot()`.

## 2. Exploracao confirmada (ambiente local, Pest via Livewire::test)

Ambiente subiu localmente (PHP 8.4 via Herd + SQLite RefreshDatabase). Foi escrito um teste
temporario de exploracao (`tests/Feature/Despesas/SecurityPentestRF007Test.php`, removido apos a
execucao — nao faz parte da suite entregue, e artefato de trabalho do pentest) cobrindo:

1. `editar($id)` de despesa de outro usuario (atacante autenticado, id do dono) -> **bloqueado**:
   `ModelNotFoundException` (404), sem popular formulario nem vazar dado.
2. `atualizar()` apos setar `editandoId` diretamente (via `Livewire::test()->set('editandoId', ...)`,
   simulando payload de update manipulado no cliente) para o id de despesa de outro usuario ->
   **bloqueado**: `ModelNotFoundException`; registro do dono permanece inalterado (`descricao`
   continua `'Original'`).
3. `excluir($id)` de despesa de outro usuario -> **bloqueado**: `ModelNotFoundException`; registro
   nao removido.
4. RN-008 (imutabilidade de `mes_referencia`): setar `mesReferencia` para `'2099-12'` via
   `set()` (simulando manipulacao do payload, ja que o campo esta desabilitado na view) antes de
   `atualizar()` -> valor persistido permanece `'2026-01'` (inalterado). `mesReferencia` nao faz
   parte do array de validacao/update em `atualizar()`, e portanto e ignorado no servidor mesmo
   que o cliente o envie.
5. RN-002 (valor > 0): `atualizar()` com `valor = '0'` e `valor = '-10'` -> **rejeitado**
   (`assertHasErrors(['valor'])`), nada persistido (`valor` original `500.0` mantido).
6. `despesas.index` sem sessao autenticada -> redireciona para `login` (middleware `auth` na
   rota).

**Resultado: nenhuma das 6 tentativas de exploracao teve sucesso.** RN-005/RNF-002 (isolamento
por usuario / IDOR), RN-002 (valor positivo) e RN-008 (mes_referencia imutavel) estao
corretamente reforcados no servidor para editar/atualizar/excluir, resistentes a manipulacao
direta de propriedade do componente (equivalente a manipulacao de payload nesta stack
Blade+Livewire acoplada).

## 3. Analise estatica adicional (fora do escopo dos criterios declarados)

- Nenhuma rota/endpoint expõe `LogAuditoria` para update/delete (`routes/web.php` nao referencia
  o model; unico ponto de escrita e `AuditoriaObserver::registrar()` via `LogAuditoria::create()`).
  Trilha de auditoria permanece integra para as escritas deste RF (update/delete de Despesa).
- `mount()` reforca `authorize('viewAny', Despesa::class)` — defesa em profundidade sobre a
  query ja escopada por `usuario_id` em `render()`.
- Sem segredo/credencial em texto plano no diff do commit. Sem CORS/config nova neste RF
  (fe_be_desacoplado = false, sem rota de API separada).
- `cancelarEdicao()` nao persiste nada — sem superficie de ataque.
- Botao "Excluir" sem confirmacao (`wire:click="excluir(...)"` direto) é decisão de produto já
  aprovada no prototipo (TELA-005), não achado de seguranca — CSRF é mitigado pelo proprio
  mecanismo de token do Livewire (payload assinado/verificado pelo framework a cada request).

## 4. Ferramenta de seguranca automatizada

`arquitetura.padroes_tecnologias.stack.ferramenta_seguranca.status = pendente_infraestrutura`
(nenhuma ferramenta de SAST/scanner de dependencias configurada no projeto). Pentest seguiu
100% manual/whitebox, sem complemento automatizado. Sinalizado como lacuna de infraestrutura
(nao bloqueante) ao qa-coordenador-agent, mesmo padrao ja usado pelo code-review-agent para
`ferramenta_qualidade_codigo`.

## Conclusao

Nenhum achado de seguranca no RF-007. Criticidade maxima encontrada: nenhuma.
