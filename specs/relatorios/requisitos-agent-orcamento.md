# requisitos-agent — histórico completo (corte de orçamento de texto)

Este arquivo preserva o texto integral de campos de `specs/spec.json` que
estouraram o limite de caracteres do orçamento de texto (regra 5 de
`reference/protocolo-especialista.md`). O JSON mantém apenas um resumo curto
e `detalhes_ref` apontando para a seção correspondente aqui.

## codebase_existente.observacoes

Diretorio do projeto contem apenas README.md, .gitignore e specs/spec.json (commit inicial). Nenhum arquivo de dependencia/config (package.json, composer.json, go.mod etc.), migration, model ou rota encontrado. Tratado como projeto novo (greenfield) — Fases 1-4 partem de perguntas em branco, sem draft de engenharia reversa.

## dominios.historico_revisoes[0]

- gate: aprovacao_dominios
- resposta: Confirmar os 4 dominios como propostos
- respondido_em: 2026-08-17T16:45:00Z
- resultado: Usuario confirmou os 4 dominios exatamente como propostos (DOM-001 Usuarios, DOM-002 Renda, DOM-003 Despesas, DOM-004 Overview Financeiro), sem ajustes. dominios.status marcado aprovado.

## campos.historico_revisoes[0] (rodada 1)

- gate: aprovacao_campos
- rodada: 1
- resposta: Decidir sobre historico/recorrencia mensal: usuario optou por 'Por mes/periodo (com historico)' — cada renda/despesa deve ter uma data ou mes de referencia; o usuario deve poder ver o overview de meses diferentes e o historico fica registrado mes a mes (navegacao por mes, comparacao entre periodos). Este requisito substitui o draft anterior de DOM-002/DOM-003 sem recorte temporal. As demais decisoes do draft original permanecem confirmadas: multiplas fontes de renda e multiplas despesas por usuario, e categoria opcional de texto livre em despesas.
- respondido_em: 2026-08-17T16:52:00Z
- resultado: Draft revisado: adicionados CAMPO-REN-007 (mes_referencia) e CAMPO-DESP-008 (mes_referencia), ambos obrigatorios, formato YYYY-MM. DOM-004 (Overview) passou a expor PARAM-OVW-001 (mes_selecionado, default mes corrente, navegavel) e todos os indicadores calculados (IND-OVW-001..004) agora filtram por mes_referencia == mes_selecionado. Prototipo (prototipo/index.html) atualizado com seletor de mes/navegacao no Overview e campo de mes/periodo nos formularios de Renda e Despesas. Multiplicidade de renda/despesas e categoria opcional de despesa mantidas sem alteracao. Novo gate aprovacao_campos reaberto para confirmacao do draft revisado — 1a rodada de ajuste, dentro do limite de 2.

## campos.historico_revisoes[1] (rodada 2)

- gate: aprovacao_campos
- rodada: 2
- resposta: Confirmar campos e prototipo revisados
- respondido_em: 2026-08-17T16:59:00Z
- resultado: Usuario confirmou o draft revisado (modelo por mes/periodo com historico) sem nenhum ajuste adicional. campos.status marcado aprovado; todos os itens de campos.dominios.* passaram de 'proposto' para 'aprovado'. documentacao.prototipo.status marcado aprovado (pendente apenas da aplicacao das regras de negocio da Fase 3 sobre o mesmo prototipo, conforme previsto na opcao escolhida). Nenhuma pergunta aberta na resposta do usuario (ex: comportamento de edicao retroativa de mes_referencia) foi tratada como decidida implicitamente — permanece em aberto e sera levantada como regra de negocio na Fase 3.

## regras_negocio.historico_revisoes[0]

- gate: aprovacao_regras
- resposta: Confirmar RN-001 a RN-007 como propostas. RN-008: Imutavel — mes_referencia nao pode ser alterado apos a criacao do lancamento; para mudar de mes, o usuario exclui e recria o lancamento no mes correto (campo mes_referencia somente leitura fora da criacao).
- respondido_em: 2026-08-17T17:04:00Z
- resultado: Usuario confirmou as 7 regras propostas sem ajuste e decidiu RN-008 (mes_referencia imutavel apos a criacao). regras_negocio.status marcado aprovado; RN-001 a RN-007 passaram de 'proposto' para 'aprovado'; RN-008 registrado ja como 'aprovado'. Prototipo (prototipo/index.html) atualizado para refletir as 8 regras na interacao: validacao de valor>0 (RN-002) e de formato/obrigatoriedade de mes_referencia (RN-003) nos formularios de Renda/Despesas, com mensagens de erro dedicadas por campo; estado alternativo do Overview quando renda_total do mes selecionado = 0 (RN-001 — indicador 'Sem renda cadastrada no periodo' no lugar do percentual, mes de demonstracao 2025-12 adicionado ao mock); alerta visual dedicado quando despesas_total > renda_total (RN-007 — banner de atencao e barra em vermelho, mes de demonstracao 2026-09 adicionado); campo mes/periodo tornado somente leitura ao editar um lancamento existente de Renda/Despesa, com nota explicativa e botoes Editar/Excluir funcionais (RN-008); unicidade de email (RN-004) e credenciais invalidas (RN-006) wireados nas telas de Registro/Login via valores reservados de demonstracao documentados na propria tela. Isolamento por usuario (RN-005) e uma regra de backend, sem representacao visual direta possivel num mock estatico — sera verificada pelo security-agent (RNF-002) e pelo dev-agent na implementacao real. Nenhum conflito identificado entre as 8 regras nem dependencia de campo inexistente. Seguindo para a Fase 4 (documentacao/RFs/RNFs).

## dominios.historico_revisoes[1] (rodada 2)

- gate: aprovacao_dominios
- rodada: 2
- resposta: Confirmar os 2 dominios propostos
- respondido_em: 2026-08-21T15:20:00Z
- resultado: DOM-005 (Configuracoes do Sistema) e DOM-006 (Auditoria) confirmados sem ajustes, para dar tela aos RNFs padrao orfaos (RNF-PADRAO-IDIOMA, RNF-PADRAO-ACESSIBILIDADE-CONFIG, RNF-PADRAO-LOG-AUDITORIA). Segue para levantamento de campos (Fase 2).

## campos.historico_revisoes[2] (rodada 3 — fechamento do gate DOM-005/DOM-006)

- gate: aprovacao_campos
- rodada: 3
- pergunta original: Confirma os campos propostos para DOM-005 (Configuracoes do Sistema) e DOM-006 (Auditoria), e as 2 novas telas do prototipo (TELA-006 Configuracoes, TELA-007 Relatorio de Auditoria) ja geradas em prototipo/index.html?
- opcao escolhida: "Confirmar campos e telas propostos" — marca CAMPO-CFG-001..008 e CAMPO-AUD-001..008 como aprovados, campos.status volta a aprovado, documentacao.prototipo.status volta a aprovado, e segue para Fase 3 (regras de negocio: proposta de isolamento por usuario tambem para DOM-005/DOM-006).
- contexto: DOM-005/DOM-006 confirmados no gate anterior (aprovacao_dominios, rodada 2). Campos de DOM-006 (CAMPO-AUD-001..008) sao reverso_codebase: ja existem em app/Models/LogAuditoria.php, app/Observers/AuditoriaObserver.php e na migration database/migrations/2026_08_18_135525_create_logs_auditoria_table.php — nenhum dado novo, so o RF de consulta (Fase 4). Campos de DOM-005 sao novos (nenhuma tabela/tela de preferencia existe hoje); tema/idioma ja tem mecanismo parcial no codebase (toggle no topbar via localStorage, idioma via sessao) que o draft propoe estender para persistencia por usuario. Prototipo (prototipo/index.html) ja tinha as 2 telas novas geradas e abertas no navegador para teste (TELA-006 Configuracoes, TELA-007 Auditoria com filtros/listagem/modal De-Para).
- resposta: Confirmar campos e telas propostos
- respondido_em: 2026-08-21T15:40:00Z
- resultado: Usuario confirmou o draft sem ajustes. Todos os itens CAMPO-CFG-001..008 e CAMPO-AUD-001..008 passaram de 'aguardando' para 'aprovado'; campos.status voltou a 'aprovado'; documentacao.prototipo.status voltou a 'aprovado' (TELA-006 e TELA-007 mantidas como geradas). A questao especifica levantada na 3a opcao do gate (persistir idioma/tema por usuario, em vez de sessao/localStorage atual) foi resolvida implicitamente pela escolha da 1a opcao: CAMPO-CFG-003 (idioma) e CAMPO-CFG-004 (tema) permanecem no draft como persistidos por usuario, conforme proposto — mecanismo de storage exato permanece decisao do design-agent. Segue para Fase 3 (regras de negocio de DOM-005/DOM-006).

## decisao_pendente (gate aprovacao_regras, aberta em 2026-08-21T15:45:00Z) — contexto completo

Nao ha dominio/papel de admin no sistema (DOM-001 so define usuario com cadastro/login/isolamento de dados proprios). O padrao de isolamento por usuario ja aprovado (RN-005) cobre Renda/Despesas/Overview (DOM-002/003/004). RN-009 propoe estender exatamente o mesmo padrao a DOM-005 (Configuracoes do Sistema): cada usuario le/edita so seu proprio registro de preferencias, criado com defaults na primeira leitura se ainda nao existir. Baixa ambiguidade — mesma logica ja aprovada, incluida no gate apenas porque toda regra exige aprovacao humana explicita (Fase 3).

RN-010 (log de auditoria, DOM-006) e genuinamente ambigua: o RNF-PADRAO-LOG-AUDITORIA (aprovado no marco anterior) ja descreve um relatorio com filtro por "usuario" (PARAM-AUD-001) — o que so faz sentido pratico se o relatorio permite consultar acoes de outros usuarios (escopo global), ja que filtrar pelo proprio usuario autenticado seria redundante (o resultado seria sempre o mesmo usuario). Por outro lado, o sistema nao tem conceito de administrador/papel privilegiado em nenhum dominio aprovado — abrir consulta global de auditoria a qualquer usuario autenticado expõe o historico de acoes de terceiros (quem criou/editou o que e quando), o que pode ser incompativel com a expectativa de privacidade do produto (app de financas pessoais). As duas opcoes:

1. Escopo restrito (self-audit): cada usuario consulta somente seu proprio log de acoes (WHERE usuario_id == autenticado, sempre, sem excecao). O filtro "usuario" da tela deixa de fazer sentido como filtro do proprio usuario (ele so pode ver a si mesmo) — remover da UI ou mante-lo desabilitado/oculto.
2. Escopo global: qualquer usuario autenticado pode consultar o log de auditoria de todos os usuarios do sistema, sem controle de papel/permissao adicional (unico papel existente e "usuario autenticado"). O filtro "usuario" (PARAM-AUD-001) mantem sentido pratico como esta descrito no RNF-PADRAO-LOG-AUDITORIA.

Esta decisao nao e do requisitos-agent decidir sozinho (RN-010 fica com acao "DECISAO PENDENTE" ate aqui) — depende de expectativa de privacidade/produto que so o usuario dono do projeto pode definir.

## documentacao.historico_revisoes[0]

- gate: aprovacao_documentacao
- resposta: Confirmar draft — acessibilidade WCAG AA + leitor de tela + tema claro e escuro
- respondido_em: 2026-08-17T17:14:00Z
- resultado: Usuario confirmou os 8 RFs (RF-001 a RF-008) e os 6 RNFs propostos (RNF-001 a RNF-003, RNF-PADRAO-IDIOMA, RNF-PADRAO-ACESSIBILIDADE-CONFIG, RNF-PADRAO-LOG-AUDITORIA) sem ajuste, e decidiu o nivel de acessibilidade alvo do sistema: WCAG 2.2 AA em todas as telas, suporte funcional a leitor de tela (navegacao por teclado, landmarks/ARIA, testado com NVDA e VoiceOver), contraste minimo 4.5:1 (texto normal) e 3:1 (texto grande/componentes de UI), e tema claro e escuro selecionavel pelo usuario. Registrado RNF-004 (categoria acessibilidade, origem usuario, status aprovado, transversal — rf_refs vazio) com essa decisao. Todos os RFs e RNFs em documentacao.requisitos_funcionais/requisitos_nao_funcionais tiveram status atualizado de 'proposto' para 'aprovado'. documentacao.status marcado 'aprovado'. Nenhuma referencia orfa identificada: todo RF referencia dominio/campos/regras existentes e aprovados, toda tela de documentacao.prototipo.telas aponta rf_refs validos e nenhuma tela ficou com rf_refs vazio sem ser removida a chave (todas as 5 telas atendem pelo menos um RF). A implementacao concreta de RNF-004 (alternador de tema, ARIA/landmarks, foco de teclado) na interface real fica para o design-agent/dev-agent traduzirem o prototipo aprovado ao stack, conforme o principio de que nao ha fase de redesenho de tela adiante — nao bloqueia a conclusao desta fase, pois a decisao de negocio ja esta totalmente especificada no proprio RNF-004 (metrica_alvo e criterio_verificacao).
