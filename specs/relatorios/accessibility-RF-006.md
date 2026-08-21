# accessibility-agent — RF-006 — auditoria completa (TELA-005, Despesas)

## Contraste (WCAG AA)
RF-006 herda 100% da paleta/tokens já aprovados e auditados em RF-004 — nenhuma cor nova introduzida.
- Tabela: headers `#54606e`/card `#fff` ~6.35:1; valores `#1f2937`/card ~14.67:1; valores/bg página ~13.55:1.
- Formulário: labels ~14.67:1; placeholder ~6.35:1; botão primário tema claro `#2563eb`/`#fff` ~5.17:1; tema escuro `#3b82f6`/`#0f172a` ~4.85:1 (correção ACC-004-002 herdada); mensagens de erro `#dc2626`/card ~5.02:1.
- Tema escuro: `--color-on-primary: #0f172a`, contraste 4.85:1 em botões/skip-link, confirmado.
- Achado: nenhum.

## Tags para leitor de tela (ARIA)
- Estrutura semântica do layout (skip-link, header, `<nav aria-label>`, `<main id=conteudo-principal>`, footer, lang/title dinâmicos) presente e correta; link "Despesas" com `aria-current` dinâmico via `routeIs()`.
- H1 "Minhas despesas", H2 "Nova despesa"; tabela com `<thead><th scope=col>` nas 4 colunas, `<tbody data-cy=despesa-lista>`, linhas com `wire:key`/`data-cy`. Sem coluna de ações (RF-007, fora de escopo) — OK.
- Formulário: os 4 campos (descrição, valor, categoria, mês) com `label for`, `aria-describedby`, `aria-invalid` dinâmico, `data-cy`, `focus-ring`; divs de erro com `aria-live=polite`; botão submit com `wire:loading.attr=aria-busy`.
- i18n: todas as labels/placeholders via `__()`, presentes nos 3 idiomas.
- Foco visível: classe `focus-ring` em todos os elementos interativos, contraste já aprovado em RF-004.
- Achado: nenhum.

## Aderência estrutural (layout-guidelines)
- Tokens de cor 100% via `var(--color-*)` na view e no layout; paleta idêntica a RF-004.
- Suporte a tema claro/escuro via `:root`/`[data-theme=dark]`/`prefers-color-scheme`, sem hex hardcoded.
- Fonte configurável: tamanhos via Tailwind arbitrary values (convertidos a rem/em pelo framework); escala global de fonte fica para RF futuro de configurações (RNF-PADRAO-ACESSIBILIDADE-CONFIG) — OK para marco-1-mvp.
- Foco visível, redução de movimento (`prefers-reduced-motion`) e nota de alto contraste: mesmos mecanismos já aprovados em RF-004, herdados sem alteração.
- Landmarks HTML e `data-cy` consistentes com o padrão de RF-004.
- Achado: nenhum. RF-006 segue identicamente o padrão de layout/tokens/CSS já aprovado em RF-004.
