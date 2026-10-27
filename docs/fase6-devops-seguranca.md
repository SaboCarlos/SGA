# Fase 6 — DevOps e Segurança

**Projecto 2 — Sistema de Gestão Académica (SGA)**
Escola Superior de Ciências Náuticas — Engenharia de Software
Curso: Programação de Sistemas Informáticos e de Telecomunicações
Grupo 2 — 3 estudantes | Maputo, 2026

Entrega correspondente ao período **20/10 – 29/10** do cronograma da disciplina.

---

## Índice

1. [Artefactos exigidos e onde estão](#1-artefactos-exigidos-e-onde-estão)
2. [Dockerização e pipeline CI/CD](#2-dockerização-e-pipeline-cicd)
3. [Autenticação e autorização](#3-autenticação-e-autorização)
4. [Análise de segurança](#4-análise-de-segurança)
5. [Protecção contra vulnerabilidades comuns (OWASP Top 10)](#5-protecção-contra-vulnerabilidades-comuns-owasp-top-10)
6. [Riscos conhecidos e aceites](#6-riscos-conhecidos-e-aceites)
7. [Como verificar](#7-como-verificar)

---

## 1. Artefactos exigidos e onde estão

| # | Artefacto (enunciado, secção 5.6) | Onde está | Secção |
|---|---|---|---|
| 1 | Dockerização da aplicação | `docker-compose.yml`, `docker-compose.prod.yml`, `docker/` | 2.1 |
| 2 | Pipeline básico de CI/CD | `.github/workflows/ci.yml`, `Makefile` (alvo `publicar`) | 2.2 |
| 3 | Análise básica de segurança | Este documento | 4 |
| 4 | Autenticação | `AuthController`, Laravel Sanctum | 3.1 |
| 5 | Autorização | `ExigePerfil`, `Utilizador::gereTurma`, controladores | 3.2 |
| 6 | Protecção contra vulnerabilidades comuns | Transversal ao código | 5 |

---

## 2. Dockerização e pipeline CI/CD

### 2.1. Dockerização

Há dois ambientes, cada um com o seu ficheiro Compose, para que nada do desenvolvimento chegue
à produção por engano.

| Serviço | Desenvolvimento (`docker-compose.yml`) | Produção (`docker-compose.prod.yml`) |
|---|---|---|
| `proxy` | Nginx: serve o frontend e encaminha `/api` | Nginx só para a API, com cabeçalhos de segurança e `server_tokens off` |
| `api` | PHP-FPM com o código montado, `pcov` para cobertura | Imagem própria (`docker/api.prod.Dockerfile`): build em duas etapas, `composer install --no-dev`, autoload optimizado, OPcache |
| `worker` | Fila de tarefas em segundo plano (envio de e-mails, notificações) | Igual |
| `scheduler` | Agendador (avisos de prazos) | Igual |
| `db` | PostgreSQL 16 | PostgreSQL 16, volume persistente |
| `cache` | Redis 7 (cache, fila, limitação de tentativas) | Redis 7, volume persistente |
| `frontend` | Imagem Nginx com o build do React | Não existe: o frontend é publicado no Vercel |
| `mailpit` | Caixa de correio falsa para testar e-mails | Não existe: usa o SMTP real |

O ambiente de desenvolvimento arranca com um único comando (`make arrancar` ou
`make instalar` numa máquina limpa). A implantação em produção está descrita passo a passo em
[implantacao.md](implantacao.md).

### 2.2. Pipeline CI/CD

**Integração contínua.** O ficheiro `.github/workflows/ci.yml` corre em cada `push` para
`main` e em cada *pull request*, com dois trabalhos em paralelo:

| Etapa | Backend | Frontend |
|---|---|---|
| 1 | Verificação do domínio sem dependências (`verificar-dominio.php`) | `npm ci` |
| 2 | `composer install` | **Auditoria de segurança das dependências de produção** (`npm audit --omit=dev`) |
| 3 | **Auditoria de segurança das dependências** (`composer audit`) | ESLint |
| 4 | Migrações contra um PostgreSQL real (serviço do GitHub Actions) | Vitest |
| 5 | Estilo de código (Pint) | Build de produção |
| 6 | Análise estática (Larastan/PHPStan) | |
| 7 | Testes com cobertura mínima de 70% (`pest --coverage --min=70`) | |

Se qualquer etapa falhar, o pipeline fica vermelho e a alteração não deve ser integrada. As
duas etapas de auditoria foram acrescentadas nesta fase (secção 4.2).

**Entrega contínua.** A publicação é semi-automática, de propósito: na VPS, `make publicar`
faz `git pull --ff-only`, reconstrói as imagens e aplica as migrações. O frontend é publicado
automaticamente pelo Vercel a cada `push` para `main`. Não se automatizou o deploy do backend a
partir do GitHub Actions porque isso obrigaria a guardar uma chave SSH da VPS no GitHub. Para um
projecto académico com um único servidor, o ganho não compensa o risco.

---

## 3. Autenticação e autorização

### 3.1. Autenticação

| Mecanismo | Implementação | Requisito |
|---|---|---|
| Tokens de API | Laravel Sanctum; o token é devolvido no login e enviado no cabeçalho `Authorization: Bearer` | RNF-08 |
| Expiração do token | 60 minutos por omissão (`SANCTUM_EXPIRACAO`); o token expira mesmo que continue a ser usado | RNF-11 |
| Palavras-passe | Guardadas com *hash* bcrypt (`Hash::make`), nunca em claro | RNF-09 |
| Bloqueio de conta | 5 tentativas falhadas seguidas bloqueiam o e-mail durante 15 minutos (`RateLimiter`) | US-01 |
| Recuperação de palavra-passe | Link por e-mail com token de uso único; a resposta é sempre 202, exista ou não a conta, para não revelar que e-mails estão registados | US-04 |
| Desactivação | Desactivar um utilizador revoga todos os seus tokens | US-02 |
| Rotas anónimas | Só `login`, `recuperar` e `redefinir`; tudo o resto exige `auth:sanctum` | RNF-11 |

### 3.2. Autorização

A autorização é verificada **sempre no servidor**. O frontend esconde menus e botões, mas isso
é apenas usabilidade: qualquer pedido directo à API passa pelas mesmas regras. Há três níveis,
do mais geral para o mais específico:

1. **Por perfil, na rota.** O middleware `ExigePerfil` (`perfil:ADMINISTRADOR`) protege as
   operações de gestão: cadastros, oferta formativa e calendário. Recusa com 403 antes de chegar
   ao controlador.
2. **Por relação com a turma.** A direcção (Administrador e Coordenador, `ehDireccao()`) vê a
   instituição toda. O docente vê apenas as turmas que lecciona e, através delas, os estudantes
   inscritos, as inscrições, as notas e os pedidos de rectificação (`gereTurma()`,
   `leccionaA()`).
3. **Por propriedade do registo.** Um estudante só vê as suas inscrições, as suas notas e os
   seus pedidos de rectificação, e só pode pedir a rectificação de uma nota sua. O filtro é
   aplicado na consulta, por isso não depende de parâmetros enviados pelo cliente.

**Matriz de permissões (resumo):**

| Operação | Administrador | Coordenador | Docente | Estudante |
|---|---|---|---|---|
| Criar/editar cursos, disciplinas, turmas, docentes, ano lectivo | ✅ | ❌ | ❌ | ❌ |
| Criar/editar estudantes | ✅ | ✅ | ❌ | ❌ |
| Gerir utilizadores, perfis e permissões | ✅ | ❌ | ❌ | ❌ |
| Definir avaliações, lançar e publicar notas | ✅ | ❌ | Só nas suas turmas | ❌ |
| Ver turmas | Todas | Todas | Só as que lecciona | Só aquelas em que está inscrito |
| Ver pauta, inscritos e classificações da turma | ✅ | ✅ | Só nas suas turmas | ❌ |
| Ver estudantes e inscrições | Todos | Todos | Só os inscritos nas suas turmas | Só as próprias |
| Ver lista de docentes | ✅ | ✅ | ❌ | ❌ |
| Homologar pauta | ✅ | ✅ | Só nas suas turmas | ❌ |
| Ver o seu ano, as disciplinas desse ano e as notas (`GET /notas`) | — | — | — | ✅ |
| Inscrever-se em turmas | — | — | — | ✅ |
| Pedir rectificação | ✅ | ✅ | Só de notas das suas turmas | Só de notas suas |
| Ver pedidos de rectificação | Todos | Todos | Os das suas disciplinas e, se coordena um curso, todos os desse curso | Só os próprios |
| Decidir rectificação | ✅ | Coordenador do curso | ❌ | ❌ |
| Relatórios e auditoria | ✅ | ✅ | ❌ | ❌ |

---

## 4. Análise de segurança

### 4.1. Método

A análise foi feita sobre o sistema a correr, não só sobre o desenho da Fase 3:

1. **Teste de autorização com o token de cada perfil.** Para cada endpoint, fez-se o pedido com
   o token de uma estudante e de um docente, incluindo pedidos de escrita com corpo vazio. Uma
   resposta 422 (erro de validação) em vez de 403 revela que o pedido passou a autorização.
2. **Auditoria das dependências** com `composer audit` e `npm audit`, contra as bases de dados
   públicas de vulnerabilidades (GitHub Advisory Database, FriendsOfPHP).
3. **Revisão da configuração de produção:** `APP_DEBUG=false`, cabeçalhos HTTP e CORS.

### 4.2. Dependências vulneráveis

| Componente | Problema | Severidade | Acção |
|---|---|---|---|
| `laravel/framework` 13.26 | Bypass da autenticação por token com credenciais que não são texto | **Crítica** | Actualizado para 13.38 |
| `laravel/framework` 13.26 | Eliminação de dados não autorizada através de relações; XSS na página de depuração | Média, baixa | Idem |
| `league/commonmark` 2.10.0 | *Bypass* do filtro de HTML; negação de serviço em tabelas | Alta, média | Actualizado para 2.10.4 |
| `symfony/http-foundation` 7.4.16 | ReDoS no cabeçalho `Cache-Control` | — | Actualizado para 7.4.21 |
| `league/flysystem` 3.35.2 | Verificação de caracteres de controlo contornável | Baixa | Actualizado para 3.36.0 |
| `react-router-dom` 6.x | *Open redirect* com barra invertida em `<Link>`/`useNavigate` | Moderada | Actualizado para 7.18 (sem alterações de código) |

Depois das actualizações, os 169 testes do backend e os 16 do frontend continuam a passar, e
`composer audit` e `npm audit --omit=dev` não reportam nada. Para que uma nova vulnerabilidade
não passe despercebida, as duas auditorias passaram a ser etapas do CI (secção 2.2).

**Risco aceite — ferramentas de desenvolvimento.** Ficam 6 avisos em `vite` 5, `vitest` 2,
`esbuild` e `tinypool`, incluindo dois classificados como críticos no `tinypool`. Estas
bibliotecas só correm na máquina do programador (servidor de desenvolvimento e execução de
testes) e **não entram no bundle servido aos utilizadores**. A correcção exige saltar para
`vite` 7 e `vitest` 4, e essa combinação falha a instalar por um defeito conhecido do npm na
resolução de dependências *peer*. Contorná-lo obrigaria a mudar a resolução de dependências do
projecto inteiro (`legacy-peer-deps`) a três semanas da defesa. A actualização fica planeada
para depois da entrega; até lá, o servidor de desenvolvimento não deve ser exposto fora da
máquina local.

### 4.3. Falhas de controlo de acesso encontradas e corrigidas

Esta foi a descoberta mais importante da análise. O menu do frontend escondia correctamente as
páginas de cada perfil, mas **vários endpoints não verificavam quem fazia o pedido**. Com o token
de uma estudante, obtinham-se estes resultados:

| Pedido | Antes | Depois | Gravidade |
|---|---|---|---|
| `GET /turmas/{id}/pauta` — notas de todos os colegas | 200 | 403 | Alta (dados pessoais) |
| `GET /avaliacoes/{id}/classificacoes` | 200 | 403 | Alta |
| `GET /turmas/{id}/inscritos` | 200 | 403 | Média |
| `POST /pautas/{id}/homologar` — tornar uma pauta definitiva | Permitido | 403 | **Crítica** |
| `POST /cursos`, `/disciplinas`, `/turmas`, `/docentes`, `/estudantes`, `/anos-lectivos` | Permitido | 403 | **Crítica** |
| `PUT /cursos/{id}`, `/disciplinas/{id}` | Permitido | 403 | **Crítica** |
| `GET /estudantes` — lista de todos os estudantes | 200 | 403 | Alta |
| `GET /relatorios/aproveitamento-disciplina` | 200 | 403 | Média |
| `GET /inscricoes?estudante_id=<outro>` | Inscrições e notas do outro | Só as próprias | Alta |
| `GET /rectificacoes` | Pedidos de todos | Só os próprios | Média |
| `POST /rectificacoes` sobre a nota de um colega | Permitido | 403 | Alta |

**Causa.** A autorização tinha sido implementada caso a caso, dentro de alguns controladores
(por exemplo, `store` de avaliações e gestão de utilizadores), mas não havia uma regra
transversal. Endpoints acrescentados mais tarde ficaram sem verificação. Os testes cobriam o
caminho feliz de quem tinha permissão, mas não o caso de quem não a tinha.

**Correcção.** Foram introduzidos os três níveis da secção 3.2. Os testes em
`tests/Feature/AcessoEstudanteApiTest.php` fixam cada linha da tabela acima, para que uma
regressão faça o CI falhar. Para o estudante continuar a ver o que é seu, criou-se o endpoint
`GET /notas` e o ecrã "As minhas notas", que só mostram as notas já publicadas.

**Lição para o processo.** A Definição de Pronto da Fase 1 (secção 8.5, item 5) já exigia
testar "a tentativa de acesso indevido", mas o critério não foi verificado nas histórias
implementadas por último. A regra não estava errada; faltou aplicá-la. Daqui em diante, nenhum
endpoint novo é integrado sem o teste "um utilizador sem permissão recebe 403" ao lado do teste
do caminho feliz.

---

## 5. Protecção contra vulnerabilidades comuns (OWASP Top 10)

| Categoria OWASP 2021 | Protecção no SGA |
|---|---|
| A01 — Controlo de acesso quebrado | Três níveis de autorização no servidor (secção 3.2), com testes negativos (secção 4.3) |
| A02 — Falhas criptográficas | Palavras-passe com bcrypt; HTTPS terminado no Apache da VPS; tokens Sanctum guardados como *hash* na base de dados |
| A03 — Injecção | Todas as consultas usam o Eloquent/Query Builder com parâmetros ligados; não há SQL concatenado com dados do utilizador. O React escapa o texto por omissão, e não se usa `dangerouslySetInnerHTML` |
| A04 — Desenho inseguro | Regras de negócio no domínio, isoladas e testadas; notas publicadas são imutáveis e só mudam através de rectificação aprovada e auditada |
| A05 — Configuração insegura | `APP_DEBUG=false` em produção; `server_tokens off`; cabeçalhos `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`; CORS restrito às origens configuradas, sem credenciais |
| A06 — Componentes vulneráveis | `composer audit` e `npm audit` no CI (secção 4.2) |
| A07 — Falhas de identificação e autenticação | Bloqueio após 5 tentativas; expiração do token; mensagens de recuperação que não revelam contas existentes |
| A08 — Falhas de integridade | Dependências fixadas por `composer.lock` e `package-lock.json`; instalação reprodutível com `npm ci` |
| A09 — Falhas de registo e monitorização | Tabela de auditoria só de escrita (rectificações, alterações sensíveis), consultável pelo Administrador e Coordenador; tentativas de alteração devolvem 405 |
| A10 — SSRF | O sistema não faz pedidos HTTP a URLs fornecidos pelo utilizador |

Validação de entrada: todos os endpoints de escrita validam o corpo com `$request->validate`
(tipos, intervalos — por exemplo, notas entre 0 e 20 — e existência das chaves estrangeiras).

---

## 6. Riscos conhecidos e aceites

| Risco | Porque se aceita nesta entrega | Mitigação planeada |
|---|---|---|
| Ferramentas de desenvolvimento vulneráveis (secção 4.2) | Não chegam à produção | Actualizar `vite`/`vitest` depois da defesa |
| Token guardado em `localStorage` | Um ataque XSS poderia lê-lo. O React já escapa o conteúdo e o token expira em 60 minutos | Mudar para cookie `HttpOnly` com o modo SPA do Sanctum |
| Sem cabeçalho `Content-Security-Policy` | Exige afinação com o Vercel e as fontes externas | Definir CSP no `vercel.json` |

---

## 7. Como verificar

```bash
# Auditorias de dependências (as mesmas do CI)
docker compose exec api composer audit
cd frontend && npm audit --omit=dev

# Testes de autorização (secção 4.3)
docker compose exec api ./vendor/bin/pest tests/Feature/AcessoEstudanteApiTest.php

# Suite completa, com cobertura
docker compose exec api ./vendor/bin/pest --coverage --min=70
```

Verificação manual: entrar como `ana@escn.ac.mz` (estudante), confirmar que o menu tem
"As minhas notas" e que a lista de turmas não mostra os links "Avaliações" nem "Pauta". Abrir
directamente `/turmas/1/pauta` no browser deve levar de volta ao painel.

---

## Controlo do documento

| Versão | Data | Autores | Alterações |
|---|---|---|---|
| 1.1 | 2026-10-27 | Grupo 2 | Visibilidade por perfil: o docente passou a ver só as suas turmas, estudantes, inscrições e rectificações; o estudante só as turmas em que está inscrito; a lista de docentes ficou reservada à direcção (`AcessoDocenteApiTest`) |
| 1.0 | 2026-10-27 | Grupo 2 | Versão inicial: Docker, CI/CD, autenticação e autorização, análise de segurança com as falhas de controlo de acesso e as dependências vulneráveis encontradas e corrigidas |

**Documento anterior:** Fase 5 — Testes
**Documento seguinte:** Fase 7 — Documentação e Apresentação
