# tester-agent — FLUXO-003 (Edicao e exclusao de fonte de renda) — teste de sistema

- RFs: RF-005 (dominio_ref unico, DOM-002)
- Branch/commit testado: `main` (RF-005 mergeado, `b2e7150`/PR #11), teste commitado em `75063da` na branch `test/fluxo-003-sistema`
- Framework: Pest (`vendor/bin/pest`), PHP 8.4.22 (Herd)
- Arquivo criado: `tests/Feature/Fluxos/Fluxo003RendaEdicaoExclusaoTest.php` (3 testes, nao repete os unitarios/integracao ja existentes em `tests/Feature/Renda/RendaManagerTest.php`, 100% cobertura conforme `qa.tester` de RF-005)

## Casos cobertos
1. Fluxo feliz completo: cadastrar renda -> listar -> overview reflete -> editar() entra em modo edicao -> atualizar() com valor invalido (RN-002) rejeitado, nada persiste -> atualizar() valido persiste descricao/valor mantendo mes_referencia original (RN-008) -> listagem/overview refletem a edicao -> excluir() remove o registro -> overview do mes deixa de contabiliza-lo (RF-008).
2. RN-008 isolado: usuario forca `mesReferencia` via propriedade publica manipulada durante `atualizar()` — servidor ignora, mes_referencia original permanece.
3. RN-005/IDOR: dois usuarios reais; Bob tenta `editar()`/`excluir()` pelo id real da fonte de renda da Alice — `ModelNotFoundException` em ambos, sem vazar existencia do registro alheio; overview de Bob nunca contabiliza a renda da Alice.

## Execucao
```
php vendor/bin/pest tests/Feature/Fluxos/Fluxo003RendaEdicaoExclusaoTest.php
Tests: 3 passed (39 assertions)
```
Suite geral do projeto (`php vendor/bin/pest`, sem escopo): 136 passed, 2 failed — `Tests\Feature\ExampleTest` (scaffold padrao pre-existente, ja documentado em ciclos anteriores) e `Tests\Feature\Fluxos\Fluxo004DespesaEdicaoExclusaoTest` (escopo de FLUXO-004/RF-007, fora deste ciclo, concorrente na mesma execucao). Nenhuma das duas falhas relacionada a FLUXO-003/RF-005.
