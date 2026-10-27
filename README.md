# SGA — Sistema de Gestão Académica

Projecto 2 — Engenharia de Software — Escola Superior de Ciências Náuticas, 2026
Curso: Programação de Sistemas Informáticos e de Telecomunicações — Grupo 2

Plataforma web para gerir estudantes, docentes, cursos, disciplinas, inscrições, avaliações,
notas, pautas e relatórios de uma instituição de ensino superior. Tem quatro perfis:
Administrador, Coordenador do curso, Docente e Estudante.

## Funcionalidades

| Área | O que faz |
|---|---|
| Acesso | Login com bloqueio após 5 tentativas falhadas, recuperação de palavra-passe por e-mail, gestão de utilizadores, perfis e permissões |
| Cadastros | Estudantes, docentes, cursos com plano curricular, disciplinas com pré-requisitos, ano lectivo e períodos |
| Inscrições | O estudante escolhe turmas do semestre; o sistema valida pré-requisitos, vagas, duplicados e o período de inscrição |
| Avaliações e notas | O docente define as avaliações (com pesos), lança as notas e publica-as; a média de frequência e a classificação final são calculadas automaticamente |
| As minhas notas | O estudante vê o ano em que está, as disciplinas desse ano e as notas publicadas de cada uma, e pode pedir a rectificação de uma nota |
| Visibilidade por perfil | A direcção vê tudo; o docente só as suas turmas, os alunos inscritos nelas e as respectivas notas |
| Rectificações | O pedido é decidido pelo Coordenador do curso e fica registado na auditoria |
| Pautas | Pauta provisória por turma, homologação e exportação em PDF |
| Aproveitamento e relatórios | Média ponderada, créditos e percentagem de conclusão; taxa de aprovação por disciplina em JSON, CSV e PDF |
| Painel | Indicadores e gráficos diferentes para cada perfil |
| Notificações *(extensão opcional)* | Avisos na aplicação quando as notas são publicadas e quando um período de inscrição está perto de abrir ou fechar |

## Tecnologias

- **Backend:** PHP 8.3, Laravel 13, Laravel Sanctum (autenticação por token), PostgreSQL 16,
  Redis 7 (cache, filas e limitação de tentativas), DomPDF
- **Frontend:** React 18, TypeScript, Vite, Tailwind CSS, React Router
- **Qualidade:** Pest 4 (testes e cobertura), Pest Arch (regras de camadas), Larastan/PHPStan,
  Pint, ESLint, Vitest
- **DevOps:** Docker Compose, GitHub Actions, Nginx

## Instalação e execução

Requisitos: Docker com o plugin Compose e `make`. Não é preciso instalar PHP, Node nem
PostgreSQL na máquina.

**Primeira instalação** (copia os `.env`, constrói as imagens, gera a chave e carrega dados de
teste):

```bash
make instalar
```

**Arranques seguintes:**

```bash
make arrancar    # levanta os contentores, aplica migrações e seeders
make parar       # pára tudo
```

A aplicação fica em **http://localhost:8000**. Os e-mails enviados em desenvolvimento
(recuperação de palavra-passe) aparecem no Mailpit, em **http://localhost:8025**.

> Use só o Docker. Não corra `php artisan serve` fora dos contentores: o `backend/.env` aponta
> para os serviços `db` e `cache`, que só existem dentro da rede Docker, e o servidor local
> ocupa a porta 8000 de que o proxy precisa.

### Dados iniciais e contas

`make instalar` carrega a instituição de exemplo (`backend/database/seeders/InstituicaoSeeder.php`):

| Curso | Ano dos alunos | Exemplo de docente | Exemplo de aluno |
|---|---|---|---|
| LEI — Engenharia Informática | 1.º | `helena.zandamela@escn.ac.mz` (Programação I) | `ana.chauque@escn.ac.mz` |
| PSIT — Sistemas Informáticos e Telecomunicações | 2.º | `paulo.chissano@escn.ac.mz` (Programação II) | `katia.chauque@escn.ac.mz` |
| LRT — Redes e Telecomunicações | 2.º | `rosa.langa@escn.ac.mz` (Redes de Computadores I) | `vania.chauque@escn.ac.mz` |
| LEN — Engenharia Náutica | 3.º | `americo.nhaca@escn.ac.mz` (Navegação Costeira) | `fabio.mussa@escn.ac.mz` |
| LGP — Gestão Portuária | 4.º | `esperanca.mucavele@escn.ac.mz` (Logística Portuária) | `pedro.mussa@escn.ac.mz` |

Cada curso tem 5 disciplinas do seu ano, cada uma com um docente diferente (25 docentes), e 10
alunos inscritos nessas 5 disciplinas (50 alunos). Os e-mails seguem o formato
`nome.apelido@escn.ac.mz`, sem acentos. O Administrador é `admin@escn.ac.mz`.

Todas as contas usam a palavra-passe `palavra-passe`. Altere-as antes de usar o sistema com
dados reais.

Para apagar tudo e voltar a este estado inicial:

```bash
docker compose exec api php artisan migrate:fresh --drop-types --seed
```

## Testes e qualidade

```bash
make testes            # suite completa do backend (195 testes)
make cobertura         # mesma suite com cobertura; falha abaixo de 70% (actual: 88,4%)
make lint              # Pint, PHPStan e ESLint
make testes-frontend   # Vitest (16 testes)
make dominio           # regras de negócio sem Docker nem base de dados (56 verificações)
```

O pipeline de CI (`.github/workflows/ci.yml`) corre tudo isto em cada `push` e *pull
request*, e ainda audita as dependências contra vulnerabilidades conhecidas (`composer audit`
e `npm audit`).

## Estrutura

```
backend/
  app/Dominio/          camada 4 — regras de negócio puras, sem Laravel (regulamento de
                        avaliação, validação de inscrição)
  app/Servicos/         camada 3 — casos de uso que orquestram domínio e persistência
  app/Infraestrutura/   camada 5 — implementações dos repositórios
  app/Http/             camada 2 — controladores da API REST, middleware de perfil, resources
  app/Models/           modelos Eloquent
  database/             migrações (a partir de docs/schema.sql) e seeders
  routes/api.php        rotas /api/v1, espelham docs/openapi.yaml
  tests/                Unit, Feature (API) e Arquitectura
frontend/src/
  paginas/              um ecrã por funcionalidade
  componentes/          layout, rotas protegidas, gráficos
  contextos/            sessão e tema
  servicos/api.ts       cliente HTTP
docker/                 Dockerfiles e configuração do Nginx (desenvolvimento e produção)
deploy/                 configuração do Apache da VPS
docs/                   documentação das fases do projecto
```

A arquitectura e a justificação de cada camada estão em `docs/fase3-arquitectura.md`.

## Documentação

| Fase | Documento |
|---|---|
| 1 — Engenharia de requisitos | [docs/fase1-requisitos.md](docs/fase1-requisitos.md) |
| 2 — Modelação (UML e base de dados) | [docs/fase2-modelacao.md](docs/fase2-modelacao.md), [docs/diagramas/](docs/diagramas/), [docs/schema.sql](docs/schema.sql) |
| 3 — Arquitectura e design | [docs/fase3-arquitectura.md](docs/fase3-arquitectura.md), [docs/openapi.yaml](docs/openapi.yaml) |
| 5 — Testes | [docs/fase5-testes.md](docs/fase5-testes.md) |
| 6 — DevOps e segurança | [docs/fase6-devops-seguranca.md](docs/fase6-devops-seguranca.md) |
| 7 — Manual de utilização | [docs/manual-utilizacao.md](docs/manual-utilizacao.md) |
| Implantação em produção | [docs/implantacao.md](docs/implantacao.md) |

## Implantação em produção

O backend corre numa VPS com `docker-compose.prod.yml` (atrás do Apache, que termina o HTTPS)
e o frontend é publicado no Vercel. O processo completo está em `docs/implantacao.md`. Na VPS:

```bash
make instalar-producao   # primeira vez
make publicar            # cada nova versão: pull, rebuild e migrações
```

## Nota sobre o regulamento de avaliação

`backend/app/Dominio/Regras/Regulamento/RegulamentoPadraoESCN.php` usa os limiares 14,0
(dispensa) e 10,0 (admissão a exame), e calcula a classificação final como média simples entre
frequência e exame. São premissas da Fase 1 (PR-03) ainda por confirmar com o Coordenador do
curso. Alterá-las não exige mudar mais nenhum ficheiro: é para isso que serve o padrão Strategy
(ADR-004).
