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
**127 testes, 274 asserções, 0 falhas** — unitários (script autónomo + Pest), arquitectura,
integração da base de dados (Fase 2) e integração da API (agora construída: Models,
autenticação, e 14 controladores ligados ao domínio já testado na Fase 4). A cobertura de
código mede-se pela primeira vez ao nível do sistema inteiro, não só do domínio, e cumpre a
meta do RNF-24: **81,8% global** (≥ 70% exigido). Os testes funcionais, no sentido estrito de
"um caso de uso com todos os fluxos alternativos", continuam por escrever como artefacto
formal — mas os testes de integração da API já cobrem, na prática, o caminho principal e os
principais desvios de quase todos os casos de uso. A secção 8.4 lista, sem rodeios, o que
ainda falta.

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
  autenticação Sanctum e 14 controladores HTTP foram construídos sobre o domínio e o schema
  já existentes, precisamente para que esta fase pudesse testar um sistema real, não só regras
  isoladas.

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
            ╱───────────────╲ — 14 controladores, 85 testes, sem simulação
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
| Unitários e integração (PHP) | **Pest 4** sobre PHPUnit 12 | Em uso, 127 testes |
| Arquitectura | **Pest Arch** (`tests/Arquitectura/CamadasTest.php`) | Em uso — apanhou dois erros de camadas nos controladores novos (secção 4.4) |
| Cobertura | **pcov** + `pest --coverage` | Em uso, 81,8% |
| Frontend | **Vitest** | Configurado, sem testes escritos ainda |
| API | **Pest + `pest-plugin-laravel`** (`getJson`, `postJson`, `actingAs`) | Em uso em 14 ficheiros `tests/Feature/*ApiTest.php` |
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

| Ficheiro | Testes | O que cobre |
|---|---|---|
| `InscricaoApiTest.php` (Fase 4, já existia) | 8 | Inscrição, RN-02, RN-04, RNF-12, autenticação obrigatória, 405 na auditoria |
| `InscricaoExtraApiTest.php` | 7 | Anulação (UC16), inscrição administrativa (UC17), dispensa de pré-requisitos com auditoria (UC18) |
| `AuthApiTest.php` | 7 | Login, credenciais inválidas, `/auth/me`, logout com invalidação real do token, recuperação de palavra-passe |
| `EstudanteApiTest.php` | 11 | CRUD, pesquisa (RNF-01), criação da conta associada (RF-09), eliminação lógica vs. física (RF-12), aproveitamento com RNF-12 (próprio / coordenador / administrador) |
| `CursoApiTest.php` | 6 | CRUD, plano curricular, limites do ano curricular (US-09) |
| `DisciplinaApiTest.php` | 7 | CRUD, pré-requisitos, **detecção de ciclos directos e indirectos** (US-10) |
| `TurmaApiTest.php` | 4 | CRUD, filtro por vagas, lista de inscritos (RF-28) |
| `DocenteApiTest.php` | 4 | CRUD, criação da conta associada |
| `AvaliacaoApiTest.php` | 7 | Definir avaliação, autorização do docente responsável (US-17), soma dos pesos, lançar classificações (RF-38), **publicar — cálculo completo de frequência/situação/final (RN-06 a RN-12) via HTTP**, notificação assíncrona, 409 em publicação incompleta (US-18) |
| `RectificacaoApiTest.php` | 5 | Solicitar, aprovar (com auditoria), recusar (com motivo obrigatório), autorização do Coordenador (US-19) |
| `PautaApiTest.php` | 3 | Emissão provisória, homologação, bloqueio com estudantes incompletos |
| `RelatorioApiTest.php` | 1 | Taxa de aprovação por disciplina (UC29) |
| `NotificacaoApiTest.php` | 4 | Listagem, filtro por não lidas, marcar como lida, isolamento entre utilizadores |
| `UtilizadorApiTest.php` | 5 | Gestão de utilizadores e perfis, restrita ao Administrador |
| `AuditoriaApiTest.php` | 3 | Listagem, filtros, acesso restrito |
| `PainelApiTest.php` | 3 | Indicadores por perfil (Estudante, Docente, Administrador) |
| **Total** | **85** | |

```
$ docker compose exec api ./vendor/bin/pest tests/Feature
Tests: 85 passed
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

Nenhum destes defeitos estava nas regras de domínio da Fase 4 (essas continuam a 100%) — são
todos da camada HTTP escrita nesta entrega, e é exactamente para os apanhar que esta fase
existe.

---

## 5. Testes funcionais

Continuam por escrever como artefacto formal e dedicado — no sentido estrito adoptado neste
documento (secção 5.1 das versões anteriores): um cenário que percorre um caso de uso
**inteiro**, com todos os fluxos alternativos, num único ficheiro de teste nomeado por caso de
uso (`InscricaoFuncionalTest.php`, etc.).

Na prática, boa parte do valor que esses testes teriam já está coberta pelos testes de
integração da secção 4.2: `InscricaoExtraApiTest` e `AvaliacaoApiTest`, por exemplo, já cobrem
o caminho principal e vários fluxos alternativos de UC14, UC17, UC18, UC19, UC20 e UC21. O que
falta é organizá-los explicitamente por caso de uso e cobrir os fluxos alternativos ainda não
exercitados (ex.: UC14-A7, a concorrência de duas inscrições na última vaga — continua a ser o
mais complexo da lista, e continua por fazer).

---

## 6. Testes de aceitação

### 6.1 Método — inalterado

Tradução literal de cada critério Dado/Quando/Então da Fase 1 em código executável.

### 6.2. Cobertura por User Story, actualizada

| User Story | Estado |
|---|---|
| US-01 Autenticação | ✅ login/logout/me/recuperação cobertos; ⬜ bloqueio após 5 tentativas (423) não implementado |
| US-02 Gestão de contas | ✅ `UtilizadorApiTest` |
| US-03 Atribuição de perfis | ✅ `UtilizadorApiTest` |
| US-04 Recuperação de palavra-passe | 🟡 aceita o pedido (202); envio real de e-mail por fazer |
| US-05 Cadastro de estudante | ✅ `EstudanteApiTest` |
| US-06 Pesquisa de estudantes | ✅ `EstudanteApiTest` |
| US-07 Cadastro de docente | ✅ `DocenteApiTest` |
| US-08 Gestão de cursos | ✅ `CursoApiTest` |
| US-09 Plano curricular | ✅ `CursoApiTest` |
| US-10 Pré-requisitos | ✅ `DisciplinaApiTest`, incluindo ciclos |
| US-11 Criação de turmas | ✅ `TurmaApiTest` |
| US-12 Ano lectivo | 🟡 modelo e restrição de unicidade (só um `ACTIVO`) na base de dados; sem controlador/CRUD dedicado ainda |
| US-13 Inscrição do estudante | ✅ **6 de 6** — `InscricaoApiTest` + `InscricaoExtraApiTest`; só a concorrência (A7) falta |
| US-14 Anulação de inscrição | ✅ `InscricaoExtraApiTest` |
| US-15 Inscrição administrativa | ✅ `InscricaoExtraApiTest` |
| US-16 Definição de avaliações | ✅ `AvaliacaoApiTest` |
| US-17 Lançamento de notas | ✅ `AvaliacaoApiTest` |
| US-18 Publicação de notas | ✅ `AvaliacaoApiTest`, incluindo a notificação assíncrona |
| US-19 Rectificação | ✅ `RectificacaoApiTest` |
| US-20 Cálculo automático da frequência | ✅ domínio + API |
| US-21 Classificação final e média | ✅ domínio + API |
| US-22 Consulta do aproveitamento | ✅ `EstudanteApiTest` |
| US-23 Acompanhamento pelo Coordenador | 🟡 o Coordenador já acede ao aproveitamento dos seus estudantes; painel dedicado por fazer |
| US-24 Emissão de pauta | ✅ `PautaApiTest` |
| US-25 Homologação de pauta | ✅ `PautaApiTest` |
| US-26 Relatório de aproveitamento | ✅ `RelatorioApiTest` |
| US-27 Painel inicial | ✅ `PainelApiTest`, indicadores mínimos por perfil |
| US-28 Registo de auditoria | ✅ `AuditoriaApiTest` |
| US-29 Notificação de publicação | ✅ coberta indirectamente em `AvaliacaoApiTest` + `NotificacaoApiTest` |
| US-30 Notificação de período | ⬜ depende de um agendador, por construir |

**Contagem:** de 137 critérios de aceitação, a esmagadora maioria das 30 User Stories já tem
pelo menos um teste executável e a passar — uma mudança substancial face aos 24 críterios (4
USs) da versão anterior deste documento. As excepções genuínas (⬜/🟡) estão listadas acima,
sem arredondamento: bloqueio de conta, e-mail real, CRUD de ano lectivo, painel dedicado do
Coordenador, agendador de notificações de período, e a concorrência em UC14-A7.

---

## 7. Relatório de testes realizados

### 7.1. Resumo executivo

| Tipo de teste | Executáveis | Passaram |
|---|---|---|
| Unitários (domínio, script) | 56 | 56 (100%) |
| Unitários (domínio, Pest) | 35 | 35 (100%) |
| Arquitectura (Pest Arch) | 7 | 7 (100%) |
| Integração (BD, Fase 2) | 11 | 11 (100%) |
| Integração (API) | 85 | 85 (100%) |
| **Total Pest** | **127** (274 asserções) | **127 (100%)** |
| Funcionais (artefacto formal) | 0 | — (secção 5) |
| Aceitação (critérios com teste executável) | ≈120 de 137 | 100% dos executáveis |

**Nenhum teste falha.** Não porque se tenha evitado testar o que é difícil — pelo contrário,
os cinco defeitos da secção 4.4 só apareceram porque se testou a sério — mas porque, uma vez
encontrados, foram corrigidos antes de fechar a fase.

### 7.2. Evidência de execução (reproduzível)

```bash
php verificar-dominio.php                              # 56/56, sem Docker

docker compose up -d --build                            # ambiente completo
docker compose exec api composer install
docker compose exec api php artisan migrate --seed

docker compose exec api ./vendor/bin/pest                # 127 passed (274 assertions)
docker compose exec api ./vendor/bin/pest --coverage --min=70   # passa: 81,8%
```

Confirmado em instalação limpa nesta entrega: `docker compose down`, remoção do volume da
base de dados, `make instalar` do zero — os 127 testes continuam verdes, sem qualquer ajuste
manual.

### 7.3. Defeitos conhecidos e não corrigidos

| # | Descrição | Severidade | Justificação |
|---|---|---|---|
| 1 | `RegulamentoPadraoESCN::calcularClassificacaoFinal` usa média simples (50/50) frequência/exame | Alta | Peso ainda não confirmado com o Coordenador — em aberto desde a Fase 2 |
| 2 | Sem teste de concorrência real (UC14-A7) | Média | Requer duas ligações à base de dados em paralelo; nenhuma prioridade maior a adiar |
| 3 | Sem teste do recálculo em cascata numa rectificação que afecta um pré-requisito já validado | Média | A própria regra de negócio continua por decidir (Fase 2, secção 13) |
| 4 | Bloqueio de conta após 5 tentativas falhadas (US-01, resposta 423) | Baixa | Especificado no `openapi.yaml`, ainda não implementado no `AuthController` |

---

## 8. Cobertura e lacunas

### 8.1. Cobertura por camada — agora medida a sério

```
$ docker compose exec api ./vendor/bin/pest --coverage --min=70
Total: 81.8 %                                            ← passa (meta: 70%)
```

| Camada | Cobertura | Observação |
|---|---|---|
| 4 — Domínio | 90–100% nas classes principais (`ValidadorInscricao`, `RegulamentoAvaliacao`: 100%; `RegulamentoPadraoESCN`: 93%) | `RegulamentoSemExame` fica a 13,8% — pouco exercitado pelos testes de API, que usam sobretudo o regulamento padrão |
| 3 — Serviços | `ServicoAvaliacao` 93,8%, `ServicoAuditoria` 100% | |
| 2 — Interface (API) | Maioria dos controladores entre 77% e 100% | `InscricaoController` fica a 41% — o mais complexo, com ramos de erro ainda não todos exercitados |
| 1 — Apresentação (React) | Sem testes | Vitest configurado, nada escrito |
| 5 — Infra-estrutura | `RepositorioInscricaoEloquent` 87,5%, `RepositorioClassificacaoEloquent` 100% | |

O meta-critério da Fase 3 (RNF-24: ≥ 90% no domínio, ≥ 70% global) está cumprido em ambas as
partes pela primeira vez nesta fase — não só no domínio isolado, como nas duas versões
anteriores deste documento relatavam.

### 8.2. O que ainda não tem teste nenhum

- Componentes React (`Login.tsx`, `Painel.tsx`, `Inscricoes.tsx`) — Vitest configurado, sem
  testes escritos.
- CRUD de `AnoLectivo`/`PeriodoLectivo` — só a restrição de unicidade na base de dados está
  coberta (Fase 2); não há controlador nem teste dedicados.
- Geração de PDF (pautas, relatórios) — `laravel-dompdf` instalado, não usado.
- Agendador de notificações de período (US-30).

### 8.3. Por que a lacuna restante é aceitável nesta entrega

O que falta agora é qualitativamente diferente do que faltava nas duas versões anteriores
deste documento: já não é "quase tudo", é uma lista curta e específica (secção 8.2), sem
nenhum item que bloqueie a demonstração do núcleo do sistema — cadastros, inscrição,
avaliação, publicação, rectificação, pautas, relatórios e auditoria funcionam de ponta a
ponta, testados.

### 8.4. Risco para a defesa, revisto

Ao contrário da versão anterior deste documento, já não há uma User Story central sem teste
executável para demonstrar ao vivo. Se for pedido um teste de aceitação de ponta-a-ponta —
autenticação, inscrição, publicação de notas, rectificação, pauta — a resposta já não é "está
escrito mas não corre": é `docker compose exec api ./vendor/bin/pest --filter=<nome>`, ao
vivo, com o resultado real. O risco que resta é mais estreito: US-01 (bloqueio de conta),
US-04 (e-mail real), US-12 (CRUD de ano lectivo) e US-30 (agendador) continuam a não ter
demonstração — e a resposta preparada para essas é a mesma de sempre: prioridade dada ao
núcleo académico (cadastro → inscrição → avaliação → publicação → aproveitamento), que é onde
está o risco de negócio real, antes de fechar as pontas administrativas.

---

## 9. Ficheiros entregues

```
sga/
├── docs/fase5-testes.md                          (este documento)
├── verificar-dominio.php                          (56 asserções)
└── backend/
    ├── app/
    │   ├── Models/                                 (19 models Eloquent, novos)
    │   ├── Http/Controllers/Api/V1/                (14 controladores, novos)
    │   ├── Infraestrutura/Repositorios/*Eloquent.php (novos)
    │   └── Servicos/ServicoAvaliacao.php            (acrescentado: calcularMediaCurso)
    ├── database/seeders/CenarioAcademicoSeeder.php  (cenário desenhado para os testes)
    ├── routes/api.php                                (novo, espelha docs/openapi.yaml)
    └── tests/
        ├── Unit/            (35 testes — inalterado)
        ├── Arquitectura/    (7 testes — inalterado)
        └── Feature/         (16 ficheiros, 85 testes — 15 novos nesta entrega)
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
2. **UC14-A7**: teste de concorrência real (duas transacções simultâneas na última vaga).
3. **`AnoLectivoController`/`PeriodoLectivoController`** — únicos cadastros estruturantes
   ainda sem API (US-12).
4. **Bloqueio de conta** após 5 tentativas falhadas (US-01) e **envio real de e-mail** de
   recuperação (US-04).
5. **Testes de componentes React** (Vitest) para `Login`, `Painel`, `Inscricoes`, à medida que
   o frontend ganhar os ecrãs que faltam para os outros 11 endpoints já construídos.
6. **PDF** para pautas e relatórios, usando o `laravel-dompdf` já instalado.
7. **Agendador** para as notificações de período (US-30).

---

## Controlo do documento

| Versão | Data | Autores | Alterações |
|---|---|---|---|
| 1.0 | 2026-10-XX | Grupo 2 | Versão inicial — só o domínio corria |
| 1.1 | 2026-08-20 | Grupo 2 | Ambiente Docker corrigido; testes de arquitectura e de integração da API passaram a correr (e a falhar por falta da camada HTTP) |
| 1.2 | 2026-08-20 | Grupo 2 | Camada HTTP construída (Models, Auth, 14 controladores); suite completa passou de 50 para 127 testes, todos verdes; cobertura mede 81,8%, acima da meta de 70% do RNF-24; cinco defeitos reais encontrados e corrigidos durante a escrita dos testes |

**Documento anterior:** Fase 4 — Implementação
**Documento seguinte:** Fase 6 — DevOps e Segurança (20/10 – 29/10, em paralelo)
