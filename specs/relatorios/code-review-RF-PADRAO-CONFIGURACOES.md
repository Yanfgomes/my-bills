# Code Review — RF-PADRAO-CONFIGURACOES (re-disparo pos SEC-CONFIG-001)

commit_ref: 178cedc (anterior: 43a8563)

## Quality gate (analise estatica)
Ferramenta `arquitetura.padroes_tecnologias.stack.ferramenta_qualidade_codigo` nao configurada
(status pendente_infraestrutura, confirmado pelo usuario). resultado = indisponivel.
Laravel Pint (formatacao) rodado pelo dev-agent nos 3 arquivos tocados: limpo, sem achados.

## Revisao qualitativa
Escopo: diff 43a8563 -> 178cedc (app/Http/Middleware/AplicarConfiguracaoUsuario.php,
app/Livewire/Configuracoes/ConfiguracaoManager.php, app/Models/ConfiguracaoUsuario.php).

- `usuario_id` removido de `#[Fillable(...)]` em ConfiguracaoUsuario — confirmado, corrige
  SEC-CONFIG-001 (mass-assignment/IDOR).
- Novo `ConfiguracaoUsuario::paraUsuario(string $usuarioId)`: busca por where('usuario_id', ...)->first(),
  senao cria via `new self([...campos nao-sensiveis...])` + atribuicao direta de
  `usuario_id` (fora do array fillable) + save(). Atribuicao direta de propriedade sempre
  e permitida independente de $fillable — abordagem correta, usuario_id nunca passa por
  array mass-assignavel.
- Os dois chamadores anteriores (ConfiguracaoManager::mount() e AplicarConfiguracaoUsuario)
  foram migrados para `ConfiguracaoUsuario::paraUsuario(Auth::id())`, eliminando a duplicacao
  de logica de criacao/defaults que existia antes (cada um tinha seu proprio firstOrCreate
  com o mesmo array de defaults) — resolve tambem a observacao de baixa prioridade do
  code-review anterior (redundancia entre os dois pontos de criacao).
- paraUsuario() e de fato o unico ponto de criacao no codebase; nenhum outro fill()/create()/
  firstOrCreate() de ConfiguracaoUsuario encontrado fora dele.
- Nomenclatura (metodo em camelCase, nome descritivo) aderente a arquitetura.padroes_tecnologias.convencoes.
- Observacao nao-bloqueante (nao registrada como achado): where(...)->first() seguido de
  new+save() sem transacao/lock tem uma janela teorica de corrida sob duas requests
  concorrentes do mesmo usuario recem-logado, mitigada pelo UNIQUE(usuario_id) da migration
  (a segunda tentativa falharia com exception, nao criaria duplicata silenciosa). Mesma
  exposicao teorica que o firstOrCreate anterior tinha; nao e regressao introduzida por esta
  correcao, por isso nao virou achado formal.
- N/A: RF nao envolve entidade de log de auditoria (RNF-PADRAO-LOG-AUDITORIA) diretamente
  como alvo de escrita nesta correcao — ConfiguracaoUsuario ja e coberto pelo mecanismo de
  captura via Observer registrado globalmente (fora do escopo deste diff).

## Resultado
Nenhum achado bloqueante. Correcao de SEC-CONFIG-001 esta correta e completa; observacao
qualitativa anterior (redundancia de firstOrCreate) foi resolvida como efeito colateral da
propria correcao.
