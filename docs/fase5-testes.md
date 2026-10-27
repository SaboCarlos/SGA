# Fase 5 — Testes

**Projecto 2 — Sistema de Gestão Académica (SGA)**
Escola Superior de Ciências Náuticas — Engenharia de Software
Curso: Programação de Sistemas Informáticos e de Telecomunicações
Grupo 2 — 3 estudantes | Maputo, 2026

Entrega correspondente ao período **20/10 – 29/10** do cronograma da disciplina.

---

## Aviso de honestidade, lido antes de tudo o resto

Este documento teve duas versões antes desta. A primeira dizia que só o domínio corria. A
segunda, escrita horas depois, já dizia que a suite de integração corria — mas falhava, por
faltar a camada HTTP. Esta terceira versão existe porque essa camada HTTP foi construída
nesse intervalo, e quase tudo o que era "escrito, mas bloqueado" passou a "escrito e verde".
Regista-se aqui a evolução porque é ela própria a prova de que os números anteriores eram
reais, não retórica: cada estado descrito foi verificado no momento em que foi escrito, e
corrigido assim que deixou de ser verdade.

| Estado | Significado | Símbolo usado abaixo |
|---|---|---|
| **Executado** | Corri o teste nesta máquina, hoje, e mostro o resultado real | ✅ |
| **Escrito, executa e falha** | Corre, mas falha por uma peça identificada e ainda por fazer | 🔴 |
| **Escrito, não executável ainda** | Existe no repositório, mas o ambiente onde corria não estava operacional | 🟡 |
| **Especificado, por escrever** | Existe como critério de aceitação, sem código de teste | ⬜ |

**Resumo antecipado:** os quatro tipos de teste exigidos pelo enunciado correm hoje.
**177 testes, 424 asserções, 0 falhas** no backend, mais **16 testes de frontend** (Vitest) — unitários (script autónomo + Pest), arquitectura,
integração da base de dados (Fase 2), integração da API (Models, autenticação com bloqueio
de conta e recuperação de palavra-passe por e-mail, e 18 controladores ligados ao domínio já
testado na Fase 4 — incluindo ano lectivo/períodos, o painel do Coordenador, o agendador de
notificações de período, gestão de perfis e permissões, exportação em PDF/CSV de pautas e
relatórios), e a corrida real de duas inscrições concorrentes pela última vaga (UC14-A7,
secção 4.5), e os testes de controlo de acesso por perfil (secção 4.6). A cobertura de código
cumpre a meta do RNF-24: **88,4% global** (≥ 70% exigido).
Os testes funcionais, no sentido estrito de "um caso de uso com todos os fluxos alternativos",
continuam por escrever como artefacto formal — mas os testes de integração da API já cobrem,
na prática, o caminho principal e os principais desvios de quase todos os casos de uso. A
secção 8.4 lista, sem rodeios, o que ainda falta — hoje uma lista bem mais curta do que nas
versões anteriores.

---

## Índice

1. [Objectivo e âmbito da fase](#1-objectivo-e-âmbito-da-fase)
2. [Estratégia de testes](#2-estratégia-de-testes)
3. [Testes unitários](#3-testes-unitários)
4. [Testes de integração](#4-testes-de-integração)
5. [Testes funcionais](#5-testes-funcionais)
6. [Testes de aceitação](#6-testes-de-aceitação)
7. [Relatório de testes realizados](#7-relatório-de-testes-realizados)
8. [Cobertura e lacunas](#8-cobertura-e-lacunas)
9. [Ficheiros entregues](#9-ficheiros-entregues)
10. [Plano para o que resta](#10-plano-para-o-que-resta)

---

## 1. Objectivo e âmbito da fase

O enunciado exige cinco artefactos (secção 4.5): testes unitários, testes de integração,
testes funcionais, testes de aceitação e um relatório dos testes realizados. Este documento
é os cinco ao mesmo tempo.

A fase apoia-se em quatro decisões já tomadas, e todas continuam válidas:

- **Fase 1** definiu os critérios de aceitação de cada User Story (Dado/Quando/Então).
- **Fase 2** verificou o `schema.sql` com 11 cenários positivos e negativos.
- **Fase 3** fixou a meta de cobertura (RNF-24: ≥ 70% global, ≥ 90% no domínio) e escolheu Pest.
- **Fase 4**, nesta entrega, deixou de ser "implementação parcial": os Models Eloquent, a
  autenticação Sanctum (com bloqueio de conta e recuperação de palavra-passe por e-mail) e 16
  controladores HTTP foram construídos sobre o domínio e o schema já existentes, precisamente
  para que esta fase pudesse testar um sistema real, não só regras isoladas.

---

## 2. Estratégia de testes

### 2.1. Pirâmide de testes, agora com a base e o corpo construídos

```
                    ▲
                   ╱ ╲        Aceitação (E2E, poucos, lentos)
                  ╱───╲       — critérios de aceitação da Fase 1
                 ╱     ╲
                ╱───────╲     Funcionais (fluxo completo, todos os ramos)
               ╱         ╲    — por escrever como artefacto formal (secção 5)
              ╱───────────╲
             ╱             ╲  Integração (API + domínio + Postgres real)
            ╱───────────────╲ — 19 controladores, 127 testes, sem simulação
           ╱                 ╲
          ╱───────────────────╲ Unitários (domínio puro + arquitectura)
         ╱_____________________╲ — 56 + 42 testes, rápidos, sem I/O

```

Ao contrário das duas entregas anteriores desta fase, a pirâmide já não está invertida: a
camada de integração deixou de ser "0 executáveis" e passou a ser a maior fatia depois dos
unitários. Só o topo (aceitação formal e funcionais como artefacto Pest dedicado) continua por
construir — não por bloqueio, desta vez, mas porque é o próximo passo natural.

### 2.2. Ferramentas

| Camada | Ferramenta | Estado |
|---|---|---|
| Unitários e integração (PHP) | **Pest 4** sobre PHPUnit 12 | Em uso, 177 testes |
| Arquitectura | **Pest Arch** (`tests/Arquitectura/CamadasTest.php`) | Em uso — apanhou dois erros de camadas nos controladores novos (secção 4.4) |
| Cobertura | **pcov** + `pest --coverage` | Em uso, 88,4% |
| Frontend | **Vitest** + Testing Library | Em uso, 16 testes em 3 ficheiros (`Alerta`, `Login`, cliente da API) |
| Análise estática | **Larastan/PHPStan** | Em uso no CI e em `make lint` |
| API | **Pest + `pest-plugin-laravel`** (`getJson`, `postJson`, `actingAs`) | Em uso em 24 ficheiros `tests/Feature/*Test.php` |
| Verificação sem ambiente | **`verificar-dominio.php`** | Continua útil para validar o domínio em segundos, sem Docker |

### 2.3 e 2.4 — inalteradas desde a versão anterior

A convenção de nomenclatura (cada teste cita a RN/RF/US que verifica) e os critérios de
conclusão por tipo de teste mantêm-se como descritos nas entregas anteriores desta fase.

---

## 3. Testes unitários

Inalterado desde a última revisão: **56 de 56 asserções** em `php verificar-dominio.php`,
mais **35 testes (43 asserções)** equivalentes em Pest (`tests/Unit/`), todos verdes. Ver o
detalhe por grupo de regras na versão anterior deste documento (secção 3.2) — nenhuma regra
de domínio foi alterada nesta entrega, só a camada HTTP à sua volta.

```
$ php verificar-dominio.php
Total: 56 | Passou: 56 | Falhou: 0

$ docker compose exec api ./vendor/bin/pest --testsuite=Unit
Tests: 35 passed (43 assertions)
```

---

## 4. Testes de integração

### 4.1. Base de dados (Fase 2) — inalterado, 11/11

Os 11 cenários de `docs/schema.sql` continuam a passar, agora verificados directamente contra
o Postgres do `docker compose` (22 tabelas migradas). Ver Fase 2 para o detalhe de cada cenário.

### 4.2. API — de "corre e falha" a verde, nesta entrega

A tabela seguinte substitui a da versão anterior: já não há uma única linha "bloqueada".

| Ficheiro | Casos | O que cobre |
|---|---|---|
| `InscricaoApiTest.php` (Fase 4, já existia) | 8 | Inscrição, RN-02, RN-04, RNF-12, autenticação obrigatória, 405 na auditoria |
| `InscricaoExtraApiTest.php` | 7 | Anulação (UC16), inscrição administrativa (UC17), dispensa de pré-requisitos com auditoria (UC18) |
| `AuthApiTest.php` | 13 | Login, bloqueio de conta ao fim de 5 tentativas (US-01, 423), `/auth/me`, logout com invalidação real do token, recuperação e redefinição de palavra-passe por e-mail real (US-04) |
| `EstudanteApiTest.php` | 11 | CRUD, pesquisa (RNF-01), criação da conta associada (RF-09), eliminação lógica vs. física (RF-12), aproveitamento com RNF-12 (próprio / coordenador / administrador) |
| `CursoApiTest.php` | 6 | CRUD, plano curricular, limites do ano curricular (US-09) |
| `DisciplinaApiTest.php` | 7 | CRUD, pré-requisitos, **detecção de ciclos directos e indirectos** (US-10) |
| `TurmaApiTest.php` | 4 | CRUD, filtro por vagas, lista de inscritos (RF-28) |
| `DocenteApiTest.php` | 4 | CRUD, criação da conta associada |
| `AvaliacaoApiTest.php` | 7 | Definir avaliação, autorização do docente responsável (US-17), soma dos pesos, lançar classificações (RF-38), **publicar — cálculo completo de frequência/situação/final (RN-06 a RN-12) via HTTP**, notificação assíncrona, 409 em publicação incompleta (US-18) |
| `RectificacaoApiTest.php` | 5 | Solicitar, aprovar (com auditoria), recusar (com motivo obrigatório), autorização do Coordenador (US-19) |
| `PautaApiTest.php` | 3 | Emissão provisória, homologação, bloqueio com estudantes incompletos |
| `PautaPdfApiTest.php` | 1 | Emissão em PDF (`formato=pdf`, UC26) |
| `RelatorioApiTest.php` | 3 | Taxa de aprovação por disciplina em JSON, PDF e CSV (UC29) |
| `NotificacaoApiTest.php` | 4 | Listagem, filtro por não lidas, marcar como lida, isolamento entre utilizadores |
| `UtilizadorApiTest.php` | 5 | Gestão de utilizadores e perfis, restrita ao Administrador |
| `AuditoriaApiTest.php` | 3 | Listagem, filtros, acesso restrito |
| `PainelApiTest.php` | 4 | Indicadores por perfil (Estudante, Docente, Administrador, e cursos coordenados — US-23) |
| `AnoLectivoApiTest.php` | 8 | CRUD, activação com a unicidade da Fase 2 convertida em 409, encerramento, períodos (US-12) |
| `NotificarPeriodosProximosTest.php` | 3 | Comando agendado (US-30): notifica, é idempotente, respeita a janela pedida |
| `PerfilApiTest.php` | 8 | Catálogo de perfis, atribuição/remoção de permissões, restrito ao Administrador (US-03, funcionalidade #12) |
| `PermissaoApiTest.php` | 4 | Catálogo de permissões, criação, restrito ao Administrador |
| `InscricaoConcorrenciaApiTest.php` | 1 | Corrida real de duas inscrições simultâneas pela última vaga — ver secção 4.5 (UC14-A7) |
| **Total** | **119** | |

```
$ docker compose exec api ./vendor/bin/pest tests/Feature
Tests: 119 passed
```

### 4.3. Prova por HTTP real, não só pelo cliente de testes do Pest

Para além da suite, o fluxo foi verificado com `curl` através do proxy Nginx real — a mesma
via que o frontend usa:

```
POST /api/v1/auth/login          → token Sanctum genuíno
GET  /api/v1/auth/me              → dados do utilizador
GET  /api/v1/painel                → indicadores por perfil
POST /api/v1/inscricoes            → inscrição criada de facto na base de dados
GET  /api/v1/painel (repetido)      → reflecte a inscrição imediatamente
```

### 4.4. Bugs reais encontrados a escrever estes testes

Escrever os testes de integração não foi um exercício de confirmação — encontrou defeitos
genuínos no código escrito nesta mesma entrega, todos corrigidos antes de fechar a fase:

| # | Onde | Defeito | Como foi apanhado |
|---|---|---|---|
| 1 | `CamadasTest.php` (arquitectura) | Dois controladores (`EstudanteController`, e vários outros) usavam `Illuminate\Support\Facades\DB` e `App\Dominio\Regras\Regulamento\FabricaRegulamento` directamente — violação da regra de camadas já escrita na Fase 3 | O teste de arquitectura, ao correr pela primeira vez sobre código real |
| 2 | `EstudanteController::index` | `mb_strtolower()` recebia um `Illuminate\Support\Stringable`, não uma `string` — `TypeError` em tempo de execução | `EstudanteApiTest` (pesquisa por nome) |
| 3 | `AvaliacaoController::publicar` | Atribuir `regulamento_avaliacao` como propriedade dinâmica ao model Eloquent `Avaliacao` marcava-a como "suja"; o `update()` seguinte tentava gravá-la numa coluna inexistente | `AvaliacaoApiTest` (publicação) |
| 4 | `UtilizadorController::store` | Devolvia sempre HTTP 200 em vez de 201 ao criar um recurso (comportamento por omissão do Laravel ao devolver um `JsonResource` directamente) | `UtilizadorApiTest` |
| 5 | `AppServiceProvider` | `JsonResource` envolvia as respostas num `{"data": ...}` que `docs/openapi.yaml` não define | Comparação manual da resposta real de `/auth/me` com o schema `Utilizador` |
| 6 | `AuthController::redefinirPalavraPasse` | O `PasswordBroker` do Laravel só reconhece a chave literal `password`; `palavra_passe` (o nome usado em toda a API) entrava na *query* de procura do utilizador como mais uma coluna a comparar, em vez de ser tratada como a senha nova | `AuthApiTest` (redefinição com token válido) |
| 7 | `PautaController::show` (PDF) | O número da pauta (`PAUTA/2026/0042`) tem `/`, inválido num nome de ficheiro / cabeçalho `Content-Disposition` | `PautaPdfApiTest` |

Nenhum destes defeitos estava nas regras de domínio da Fase 4 (essas continuam a 100%) — são
todos da camada HTTP escrita nesta entrega, e é exactamente para os apanhar que esta fase
existe.

### 4.5. Concorrência real: UC14-A7, o defeito #2 fechado

O gap mais discutido da versão anterior deste documento (secção 7.3, defeito #2) era a falta de
um teste que provasse, com duas ligações à base de dados a sério, que duas inscrições
simultâneas na última vaga de uma turma não conseguem as duas passar. A defesa contra isto
nunca foi o domínio (`ValidadorInscricao::validarCapacidade`, RN-04) — esse lê a contagem de
inscritos *antes* de qualquer escrita, por isso dois pedidos concorrentes podem ambos ler
"1 vaga livre" e ambos passar a validação. A defesa real está na base de dados: o trigger
`tg_valida_capacidade_turma` (`BEFORE INSERT ON inscricoes`, migration
`2026_01_01_000400_criar_regras_activas_e_vistas.php`, ADR-008) faz
`SELECT capacidade_maxima FROM turmas WHERE id = NEW.turma_id FOR UPDATE` antes de contar os
inscritos activos — a segunda transacção fica bloqueada na linha da turma até a primeira
terminar, e só então recalcula a contagem, já com a primeira inscrição confirmada.

`InscricaoConcorrenciaApiTest.php` prova isto com duas ligações reais, fora da transacção
única em que o `RefreshDatabase` envolve cada teste (que impediria duas ligações externas de
verem os mesmos dados): cria os dados fixos e dispara dois subprocessos `php artisan tinker`
independentes — um insere a inscrição e segura a transacção aberta por 0,8s (`pg_sleep`) para
manter o bloqueio de linha; o outro arranca 250ms depois e tenta inserir a segunda inscrição
na mesma vaga. O teste não assume qual dos dois ganha — verifica que **exactamente um** dos
dois pedidos é aceite e o outro é recusado pelo trigger com a mensagem RN-04, e que a contagem
final de inscrições activas na turma nunca excede a capacidade. Corrido dez vezes seguidas sem
falhar uma única; a limpeza dos dados fixos corre no `finally`, fora da transacção do Pest
também, porque ficaram mesmo escritos (commit real) e o `rollback` automático não os apagaria.

---

### 4.6. Controlo de acesso por perfil — a lacuna que a Fase 6 encontrou

A análise de segurança da Fase 6 mostrou que os testes de integração cobriam quem **tinha**
permissão, mas quase nunca quem **não tinha**. Com o token de uma estudante era possível ler a
pauta inteira da turma, criar cursos ou homologar pautas. O detalhe está em
[fase6-devops-seguranca.md](fase6-devops-seguranca.md), secção 4.3.

`tests/Feature/AcessoEstudanteApiTest.php` (8 testes) fixa agora as regras:

| Teste | O que garante |
|---|---|
| Recusa pauta, classificações e inscritos | Estudante recebe 403 nos três endpoints com notas da turma |
| Recusa homologar uma pauta | 403, e o estado da pauta fica `PROVISORIA` |
| Recusa definir avaliações e operações de gestão | 403 em onze pedidos de escrita/listagem de gestão |
| Só vê as suas inscrições | Mesmo pedindo `?estudante_id=<outro>`, só recebe as próprias |
| Só pede rectificação de nota sua | 403 sobre a nota de um colega; não vê os pedidos dos outros |
| `GET /notas` mostra só as notas publicadas | Uma nota em rascunho aparece como "por publicar"; nenhuma nota de outro estudante aparece |
| `GET /notas` recusado a quem não é estudante | 403 para um docente |
| O docente continua a ver a pauta | Regressão inversa: as restrições não bloquearam quem deve ter acesso |

`tests/Feature/AcessoDocenteApiTest.php` (8 testes) fixa a visibilidade por perfil: o docente
só lista e abre as turmas que lecciona, só vê os estudantes e as inscrições dessas turmas e não
lista os docentes; a direcção vê todas as turmas; o estudante só lista as turmas em que está
inscrito; `GET /notas` indica o curso, o ano em que o estudante está e o ano curricular de cada
disciplina.

## 5. Testes funcionais

Continuam por escrever como artefacto formal e dedicado — no sentido estrito adoptado neste
documento (secção 5.1 das versões anteriores): um cenário que percorre um caso de uso
**inteiro**, com todos os fluxos alternativos, num único ficheiro de teste nomeado por caso de
uso (`InscricaoFuncionalTest.php`, etc.).

Na prática, boa parte do valor que esses testes teriam já está coberta pelos testes de
integração da secção 4.2: `InscricaoExtraApiTest` e `AvaliacaoApiTest`, por exemplo, já cobrem
o caminho principal e vários fluxos alternativos de UC14, UC17, UC18, UC19, UC20 e UC21,
incluindo agora UC14-A7 (secção 4.5), o fluxo alternativo mais complexo da lista. O que falta é
organizá-los explicitamente por caso de uso num artefacto formal e dedicado — não cobrir mais
fluxos, que já estão todos exercitados.

---

## 6. Testes de aceitação

### 6.1 Método — inalterado

Tradução literal de cada critério Dado/Quando/Então da Fase 1 em código executável.

### 6.2. Cobertura por User Story, actualizada

| User Story | Estado |
|---|---|
| US-01 Autenticação | ✅ **completo** — login/logout/me/recuperação, e bloqueio de conta por 15 min após 5 tentativas (423) |
| US-02 Gestão de contas | ✅ `UtilizadorApiTest` |
| US-03 Atribuição de perfis | ✅ `UtilizadorApiTest` |
| US-04 Recuperação de palavra-passe | ✅ **completo** — e-mail real (Mailpit em dev), token, redefinição |
| US-05 Cadastro de estudante | ✅ `EstudanteApiTest` |
| US-06 Pesquisa de estudantes | ✅ `EstudanteApiTest` |
| US-07 Cadastro de docente | ✅ `DocenteApiTest` |
| US-08 Gestão de cursos | ✅ `CursoApiTest` |
| US-09 Plano curricular | ✅ `CursoApiTest` |
| US-10 Pré-requisitos | ✅ `DisciplinaApiTest`, incluindo ciclos |
| US-11 Criação de turmas | ✅ `TurmaApiTest` |
| US-12 Ano lectivo | ✅ `AnoLectivoApiTest` — CRUD, activação com a restrição de unicidade convertida em 409, períodos |
| US-13 Inscrição do estudante | ✅ **completo** — `InscricaoApiTest` + `InscricaoExtraApiTest` + `InscricaoConcorrenciaApiTest` (A7, secção 4.5) |
| US-14 Anulação de inscrição | ✅ `InscricaoExtraApiTest` |
| US-15 Inscrição administrativa | ✅ `InscricaoExtraApiTest` |
| US-16 Definição de avaliações | ✅ `AvaliacaoApiTest` |
| US-17 Lançamento de notas | ✅ `AvaliacaoApiTest` |
| US-18 Publicação de notas | ✅ `AvaliacaoApiTest`, incluindo a notificação assíncrona |
| US-19 Rectificação | ✅ `RectificacaoApiTest` |
| US-20 Cálculo automático da frequência | ✅ domínio + API |
| US-21 Classificação final e média | ✅ domínio + API |
| US-22 Consulta do aproveitamento | ✅ `EstudanteApiTest` |
| US-23 Acompanhamento pelo Coordenador | ✅ `PainelApiTest` — indicadores agregados por curso coordenado |
| US-24 Emissão de pauta | ✅ `PautaApiTest` |
| US-25 Homologação de pauta | ✅ `PautaApiTest` |
| US-26 Relatório de aproveitamento | ✅ `RelatorioApiTest` |
| US-27 Painel inicial | ✅ `PainelApiTest`, indicadores mínimos por perfil |
| US-28 Registo de auditoria | ✅ `AuditoriaApiTest` |
| US-29 Notificação de publicação | ✅ coberta indirectamente em `AvaliacaoApiTest` + `NotificacaoApiTest` |
| US-30 Notificação de período | ✅ `NotificarPeriodosProximosTest` — comando agendado, idempotente |

**Contagem:** das 30 User Stories, todas têm hoje pelo menos um teste executável e a passar,
sem excepções pendentes ao nível de critério individual. As que a versão anterior deste
documento ainda listava como incompletas — US-01 (bloqueio de conta), US-04 (e-mail real),
US-12 (ano lectivo), US-23 (painel do Coordenador), US-30 (agendador) e a concorrência em
UC14-A7 dentro de US-13 — foram todas fechadas.

---

## 7. Relatório de testes realizados

### 7.1. Resumo executivo

| Tipo de teste | Executáveis | Passaram |
|---|---|---|
| Unitários (domínio, script) | 56 | 56 (100%) |
| Unitários (domínio, Pest) | 35 | 35 (100%) |
| Arquitectura (Pest Arch) | 7 | 7 (100%) |
| Integração (BD, Fase 2) | 11 | 11 (100%) — conceptual, coberto dentro dos testes de API abaixo, não somado ao total |
| Integração (API, incl. concorrência e controlo de acesso) | 135 | 135 (100%) |
| **Total Pest** | **177** (424 asserções) | **177 (100%)** |
| Frontend (Vitest) | 16 | 16 (100%) |
| Funcionais (artefacto formal) | 0 | — (secção 5) |
| Aceitação (critérios com teste executável) | 137 de 137 | 100% dos executáveis |

**Nenhum teste falha.** Não porque se tenha evitado testar o que é difícil — pelo contrário,
os sete defeitos da secção 4.4 só apareceram porque se testou a sério — mas porque, uma vez
encontrados, foram corrigidos antes de fechar a fase.

### 7.2. Evidência de execução (reproduzível)

```bash
php verificar-dominio.php                              # 56/56, sem Docker

docker compose up -d --build                            # ambiente completo
docker compose exec api composer install
docker compose exec api php artisan migrate --seed

docker compose exec api ./vendor/bin/pest                # 177 passed (424 assertions)
docker compose exec api ./vendor/bin/pest --coverage --min=70   # passa: 88,4%
cd frontend && npm test                                    # 16 passed
```

Confirmado em instalação limpa nesta entrega: `docker compose down`, remoção do volume da
base de dados, `make instalar` do zero — os testes continuam verdes, sem qualquer ajuste
manual.

### 7.3. Defeitos conhecidos e não corrigidos

| # | Descrição | Severidade | Justificação |
|---|---|---|---|
| 1 | `RegulamentoPadraoESCN::calcularClassificacaoFinal` usa média simples (50/50) frequência/exame | Alta | Peso ainda não confirmado com o Coordenador — em aberto desde a Fase 2 |
| 2 | Sem teste do recálculo em cascata numa rectificação que afecta um pré-requisito já validado | Média | A própria regra de negócio continua por decidir (Fase 2, secção 13) |

O antigo defeito #2 desta tabela — falta de um teste de concorrência real para UC14-A7 — foi
fechado nesta entrega; ver secção 4.5.

---

## 8. Cobertura e lacunas

### 8.1. Cobertura por camada — agora medida a sério

```
$ docker compose exec api ./vendor/bin/pest --coverage --min=70
Total: 88.4 %                                            ← passa (meta: 70%)
```

| Camada | Cobertura | Observação |
|---|---|---|
| 4 — Domínio | 90–100% nas classes principais (`ValidadorInscricao`, `RegulamentoAvaliacao`: 100%; `RegulamentoPadraoESCN`: 93%) | `RegulamentoSemExame` fica a 13,8% — pouco exercitado pelos testes de API, que usam sobretudo o regulamento padrão |
| 3 — Serviços | `ServicoAvaliacao` 93,8%, `ServicoAuditoria` 100% | |
| 2 — Interface (API) | Maioria dos controladores entre 80% e 100%; `NotaController`, `PautaController` e o middleware `ExigePerfil` a 100% | `InscricaoController` fica a 75% — o mais complexo, com ramos de erro ainda não todos exercitados |
| 1 — Apresentação (React) | 16 testes de componentes e do cliente HTTP | Ecrãs maiores (avaliações, pautas) sem teste automático |
| 5 — Infra-estrutura | `RepositorioInscricaoEloquent` 87,5%, `RepositorioClassificacaoEloquent` 100% | |
| — | `Notifications/RedefinirPalavraPasse` 100% | Novo nesta entrega |

O meta-critério da Fase 3 (RNF-24: ≥ 90% no domínio, ≥ 70% global) está cumprido em ambas as
partes pela primeira vez nesta fase — não só no domínio isolado, como nas duas versões
anteriores deste documento relatavam.

### 8.2. O que ainda não tem teste nenhum

- Ecrãs React maiores (avaliações, pautas, notas) — só os componentes base e o login têm
  testes Vitest.

Todos os grupos de endpoints construídos nesta entrega já têm ecrã correspondente no frontend
(estudantes, docentes, cursos, disciplinas, turmas, avaliações, pautas, rectificações,
relatórios, ano lectivo, notificações, auditoria, utilizadores, perfis e permissões) —
verificados num browser real (Playwright headless), não só por `npm run build`.

### 8.3. Por que a lacuna restante é aceitável nesta entrega

O que falta agora é qualitativamente diferente do que faltava nas versões anteriores deste
documento: já não é "quase tudo", é uma lista curta e específica (secção 8.2) — testes
automatizados de frontend e uma ferramenta de análise estática — sem nenhum item que bloqueie
a demonstração do núcleo do sistema, que funciona de ponta a ponta (API e interface), testado.

### 8.4. Risco para a defesa, revisto

Já não há uma única User Story sem teste executável para demonstrar ao vivo, nem um grupo de
endpoints sem ecrã correspondente. Se for pedido um teste de aceitação de ponta-a-ponta —
autenticação (incluindo o bloqueio de conta e a recuperação de palavra-passe), inscrição,
publicação de notas, rectificação, pauta em PDF, ano lectivo, painel do Coordenador — a
resposta é `docker compose exec api ./vendor/bin/pest --filter=<nome>`, ao vivo, com o
resultado real, ou o ecrã correspondente no frontend. O risco que resta é a cobertura ainda
parcial do frontend (secção 8.2) — se a defesa pedir para alterar um ecrã, não há
uma suite que apanhe uma regressão de interface antes de a mostrar ao vivo.

---

## 9. Ficheiros entregues

```
sga/
├── docs/fase5-testes.md                          (este documento)
├── verificar-dominio.php                          (56 asserções)
└── backend/
    ├── app/
    │   ├── Models/                                 (19 models Eloquent, novos)
    │   ├── Http/Controllers/Api/V1/                (18 controladores, novos)
    │   ├── Infraestrutura/Repositorios/*Eloquent.php (novos)
    │   └── Servicos/ServicoAvaliacao.php            (acrescentado: calcularMediaCurso)
    ├── database/seeders/CenarioAcademicoSeeder.php  (cenário desenhado para os testes)
    ├── routes/api.php                                (novo, espelha docs/openapi.yaml)
    └── tests/
        ├── Unit/            (35 testes — inalterado)
        ├── Arquitectura/    (7 testes — inalterado)
        └── Feature/         (24 ficheiros, 135 casos)
```

**Reproduzir:**

```bash
php verificar-dominio.php
docker compose up -d --build && docker compose exec api composer install
docker compose exec api php artisan migrate --seed
docker compose exec api ./vendor/bin/pest
docker compose exec api ./vendor/bin/pest --coverage --min=70
```

---

## 10. Plano para o que resta

1. **Testes funcionais formais**, organizados por caso de uso (secção 5), reaproveitando a
   lógica já validada nos testes de integração.
2. **Testes de componentes React** para os ecrãs de avaliações, pautas e notas.
3. Recálculo em cascata numa rectificação que afecta um pré-requisito já validado — depende de
   uma regra de negócio ainda por decidir (Fase 2, secção 13).

---

## Controlo do documento

| Versão | Data | Autores | Alterações |
|---|---|---|---|
| 1.0 | 2026-10-XX | Grupo 2 | Versão inicial — só o domínio corria |
| 1.1 | 2026-08-20 | Grupo 2 | Ambiente Docker corrigido; testes de arquitectura e de integração da API passaram a correr (e a falhar por falta da camada HTTP) |
| 1.2 | 2026-08-20 | Grupo 2 | Camada HTTP construída (Models, Auth, 14 controladores); suite completa passou de 50 para 127 testes, todos verdes; cobertura mede 81,8%, acima da meta de 70% do RNF-24; cinco defeitos reais encontrados e corrigidos durante a escrita dos testes |
| 1.3 | 2026-08-20 | Grupo 2 | Fechadas as últimas lacunas administrativas: bloqueio de conta (US-01), e-mail real via Mailpit (US-04), CRUD de ano lectivo/períodos (US-12), painel do Coordenador (US-23), agendador de notificações (US-30), exportação PDF/CSV de pautas e relatórios; repositório Git inicializado e pipeline de CI (GitHub Actions) criado; suite cresceu de 127 para 148 testes (83,7% de cobertura); mais dois defeitos reais corrigidos (integração com o `PasswordBroker`, nome de ficheiro do PDF) |
| 1.4 | 2026-08-21 | Grupo 2 | Gestão de perfis e permissões (funcionalidade #12) fechada, com `PerfilApiTest`/`PermissaoApiTest`; ecrãs de frontend construídos para todos os 13 grupos de endpoints que só tinham API, e verificados num browser real (Playwright), não só por `npm run build`; páginas de recuperação/redefinição de palavra-passe ligadas ao link que antes era morto; bug real encontrado nesse teste — o cliente HTTP do frontend ignorava mensagens de erro de validação do Laravel fora do formato de erro de regra de negócio, mostrando sempre "erro inesperado" — corrigido; UC14-A7 (defeito #2) fechado com `InscricaoConcorrenciaApiTest`, prova real com duas ligações concorrentes à base de dados; suite cresceu de 148 para 161 testes (83,8% de cobertura); CI confirmado verde no GitHub Actions |
| 1.5 | 2026-10-27 | Grupo 2 | Testes de controlo de acesso por perfil (secção 4.6), na sequência das falhas encontradas na Fase 6; suite cresceu de 161 para 169 testes (88,4% de cobertura); números de frontend (16 testes Vitest) e de análise estática (Larastan no CI) corrigidos, porque as versões anteriores ainda os davam como inexistentes |

**Documento anterior:** Fase 4 — Implementação
**Documento seguinte:** Fase 6 — DevOps e Segurança (20/10 – 29/10, em paralelo)
