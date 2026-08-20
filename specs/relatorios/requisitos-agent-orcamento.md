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

## documentacao.historico_revisoes[0]

- gate: aprovacao_documentacao
- resposta: Confirmar draft — acessibilidade WCAG AA + leitor de tela + tema claro e escuro
- respondido_em: 2026-08-17T17:14:00Z
- resultado: Usuario confirmou os 8 RFs (RF-001 a RF-008) e os 6 RNFs propostos (RNF-001 a RNF-003, RNF-PADRAO-IDIOMA, RNF-PADRAO-ACESSIBILIDADE-CONFIG, RNF-PADRAO-LOG-AUDITORIA) sem ajuste, e decidiu o nivel de acessibilidade alvo do sistema: WCAG 2.2 AA em todas as telas, suporte funcional a leitor de tela (navegacao por teclado, landmarks/ARIA, testado com NVDA e VoiceOver), contraste minimo 4.5:1 (texto normal) e 3:1 (texto grande/componentes de UI), e tema claro e escuro selecionavel pelo usuario. Registrado RNF-004 (categoria acessibilidade, origem usuario, status aprovado, transversal — rf_refs vazio) com essa decisao. Todos os RFs e RNFs em documentacao.requisitos_funcionais/requisitos_nao_funcionais tiveram status atualizado de 'proposto' para 'aprovado'. documentacao.status marcado 'aprovado'. Nenhuma referencia orfa identificada: todo RF referencia dominio/campos/regras existentes e aprovados, toda tela de documentacao.prototipo.telas aponta rf_refs validos e nenhuma tela ficou com rf_refs vazio sem ser removida a chave (todas as 5 telas atendem pelo menos um RF). A implementacao concreta de RNF-004 (alternador de tema, ARIA/landmarks, foco de teclado) na interface real fica para o design-agent/dev-agent traduzirem o prototipo aprovado ao stack, conforme o principio de que nao ha fase de redesenho de tela adiante — nao bloqueia a conclusao desta fase, pois a decisao de negocio ja esta totalmente especificada no proprio RNF-004 (metrica_alvo e criterio_verificacao).
