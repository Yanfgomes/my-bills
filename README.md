# my-bills

Sistema de gerenciamento de contas pessoais com registro aberto, login e senha. Cada usuário visualiza apenas suas próprias informações financeiras: cadastra o valor líquido recebido (renda) e suas despesas, mês a mês, e o sistema apresenta um overview consolidado da saúde financeira, com navegação entre períodos.

## Funcionalidades

- **Cadastro e login** — registro aberto (sem convite/aprovação), autenticação por e-mail e senha, com isolamento total de dados por usuário.
- **Renda** — cadastro, edição e exclusão de fontes de renda (mais de uma por mês), vinculadas a um mês/período de referência.
- **Despesas** — cadastro, edição e exclusão de despesas, com categoria opcional, vinculadas a um mês/período de referência.
- **Overview financeiro** — painel consolidado por mês (renda total, despesas totais, saldo disponível, percentual da renda comprometido), com navegação entre meses para comparar períodos.
- **Configurações do sistema** — preferências por usuário: idioma (PT/EN/ES), tema (claro/escuro), tamanho de fonte, alto contraste e redução de movimento.
- **Log de auditoria** — relatório somente-leitura (self-audit) de todas as operações de criação/alteração/exclusão feitas pelo próprio usuário, com filtros por tabela, ação e período, e detalhe "De/Para" por registro.

## Stack

- **Backend**: PHP 8.3+ / Laravel 13, Eloquent ORM
- **Frontend**: Blade + Livewire 4 + Alpine.js, TailwindCSS 4 (sem SPA nem API separada — renderização no servidor com hidratação parcial via Livewire)
- **Banco de dados**: SQLite
- **Testes**: Pest
- **Qualidade/acessibilidade**: WCAG 2.2 AA, PSR-12 (Laravel Pint)

## Requisitos

- PHP >= 8.3 com extensão PDO SQLite
- Composer
- Node.js + npm

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Ou, em um único passo (script `setup` do `composer.json`):

```bash
composer run setup
```

## Rodando em desenvolvimento

```bash
composer run dev
```

Sobe em paralelo o servidor (`php artisan serve`), a fila (`queue:listen`), o log em tempo real (`php artisan pail`) e o Vite (`npm run dev`).

> **Nota (Windows)**: `composer run dev` pode falhar por falta da extensão `pcntl` (usada pelo `pail`), derrubando o grupo inteiro via `--kill-others`. Nesse caso, suba só o essencial:
> ```bash
> npm run build   # ou npm run dev em outro terminal, para hot-reload
> php artisan serve
> ```

Acesse `http://localhost:8000`.

## Testes

```bash
composer run test
```

## Estado do projeto

Projeto concluído — todo o escopo planejado (3 marcos, 10 requisitos funcionais, 7 requisitos não funcionais) foi implementado, testado e mergeado em `main`. O deploy automático para produção está retido por falta de infraestrutura de CI/CD configurada; até lá, o código mergeado não reflete automaticamente o ambiente de produção.

Detalhes de requisitos, arquitetura e histórico de decisões ficam em [`specs/spec.json`](specs/spec.json).
