# Fase 1 — Engenharia de Requisitos

**Projecto 2 — Sistema de Gestão Académica (SGA)**
Escola Superior de Ciências Náuticas — Engenharia de Software
Curso: Programação de Sistemas Informáticos e de Telecomunicações
Grupo 2 — 3 estudantes | Maputo, 2026

Entrega correspondente ao período **18/08 – 27/08** do cronograma da disciplina.

---

## Índice

1. [Descrição do problema](#1-descrição-do-problema)
2. [Identificação dos stakeholders](#2-identificação-dos-stakeholders)
3. [Técnicas de levantamento de requisitos](#3-técnicas-de-levantamento-de-requisitos)
4. [Premissas, restrições e regras de negócio](#4-premissas-restrições-e-regras-de-negócio)
5. [Requisitos funcionais](#5-requisitos-funcionais)
6. [Requisitos não funcionais](#6-requisitos-não-funcionais)
7. [User Stories e critérios de aceitação](#7-user-stories-e-critérios-de-aceitação)
8. [Product Backlog priorizado](#8-product-backlog-priorizado)
9. [Matriz de rastreabilidade](#9-matriz-de-rastreabilidade)
10. [Riscos identificados](#10-riscos-identificados)
11. [Glossário](#11-glossário)

---

## 1. Descrição do problema

### 1.1. Contexto

A gestão académica de uma instituição de ensino superior envolve o tratamento contínuo de
grandes volumes de informação relativa a estudantes, docentes, cursos, disciplinas,
inscrições e avaliações. Numa instituição de dimensão média, cada ano lectivo gera
milhares de registos de inscrição e de notas, distribuídos por vários cursos, níveis e
semestres.

Em muitas instituições, este processo é conduzido com recurso a ferramentas genéricas
(folhas de cálculo, documentos de texto, formulários em papel) mantidas de forma
descentralizada por diferentes serviços: a Secretaria Académica, os coordenadores de curso
e cada docente individualmente.

### 1.2. Problema

A ausência de um sistema de informação centralizado origina um conjunto de problemas
recorrentes:

| # | Problema | Consequência |
|---|---|---|
| P1 | Duplicação e dispersão de dados do mesmo estudante em vários ficheiros | Inconsistência da informação e dificuldade em determinar qual o registo válido |
| P2 | Cálculo manual de médias e de situações de aprovação | Erros de cálculo, reclamações de estudantes e retrabalho |
| P3 | Pautas produzidas em folhas de cálculo individuais por cada docente | Formatos heterogéneos, perda de ficheiros e ausência de histórico |
| P4 | Inexistência de controlo de acesso à informação | Qualquer pessoa com acesso ao ficheiro pode ler ou alterar notas |
| P5 | Ausência de registo de quem alterou o quê e quando | Impossibilidade de auditar alterações de notas |
| P6 | Consulta do aproveitamento depende de deslocação à Secretaria | Sobrecarga dos serviços e demora na resposta ao estudante |
| P7 | Verificação manual de pré-requisitos e limites de inscrição | Inscrições irregulares detectadas tardiamente |
| P8 | Produção de relatórios de gestão feita caso a caso | Decisões de coordenação tomadas sem dados fiáveis e actualizados |

### 1.3. Solução proposta

Desenvolver o **Sistema de Gestão Académica (SGA)**, uma aplicação web com arquitectura em
camadas e API REST, que centraliza numa única base de dados relacional toda a informação
académica e disponibiliza, a cada perfil de utilizador, apenas as operações e os dados que
lhe competem.

O sistema automatiza o cálculo de médias segundo um regulamento de avaliação configurável,
emite pautas em formato normalizado, valida as regras de inscrição no momento em que esta é
efectuada e mantém um registo de auditoria de todas as alterações a classificações.

### 1.4. Objectivos

**Objectivo geral:** implementar uma plataforma de gestão académica que assegure a
integridade, a rastreabilidade e a acessibilidade controlada da informação académica de uma
instituição de ensino superior.

**Objectivos específicos:**

1. Centralizar num único repositório os dados de estudantes, docentes, cursos, disciplinas,
   inscrições e avaliações.
2. Automatizar o cálculo de médias, de frequência e da situação final do estudante em cada
   disciplina, eliminando o cálculo manual.
3. Garantir que cada utilizador acede apenas à informação correspondente ao seu perfil,
   através de autenticação e de autorização baseada em papéis.
4. Reduzir o tempo de emissão de uma pauta e de consulta do aproveitamento académico de
   dias para segundos.
5. Assegurar a rastreabilidade de todas as alterações de classificações através de um
   registo de auditoria imutável.
6. Disponibilizar aos coordenadores relatórios agregados de aproveitamento e de ocupação
   como apoio à decisão.

### 1.5. Âmbito

**Dentro do âmbito (in scope):**

- Gestão de estudantes, docentes, cursos, disciplinas, planos curriculares e turmas.
- Inscrições em disciplinas por ano lectivo e semestre.
- Registo de avaliações, lançamento e publicação de notas.
- Cálculo de médias, frequência e situação final.
- Emissão de pautas e consulta do aproveitamento académico.
- Gestão de utilizadores, perfis e permissões.
- Relatórios de gestão académica.
- Registo de auditoria.
- *(Extensão opcional)* Notificações a estudantes e docentes.

**Fora do âmbito (out of scope):**

- Processamento de pagamentos, taxas e emolumentos.
- Gestão de recursos humanos e processamento salarial.
- Plataforma de ensino a distância (conteúdos, fóruns, entrega de trabalhos).
- Gestão de horários e de salas de aula.
- Emissão de certificados e diplomas com valor legal.
- Gestão de candidaturas e exames de admissão.
- Aplicação móvel nativa.

---

## 2. Identificação dos stakeholders

### 2.1. Stakeholders directos (utilizadores do sistema)

| ID | Stakeholder | Perfil no sistema | Interesse / Necessidade principal | Influência |
|---|---|---|---|---|
| SH-01 | Estudante | Estudante | Inscrever-se em disciplinas e consultar notas, médias e situação académica a qualquer momento | Média |
| SH-02 | Docente | Docente | Lançar notas das suas turmas com rapidez e sem erros de cálculo; consultar a lista dos seus estudantes | Alta |
| SH-03 | Coordenador de curso | Coordenador | Acompanhar o aproveitamento do curso, validar pautas e obter relatórios agregados | Alta |
| SH-04 | Técnico da Secretaria Académica | Administrador | Registar estudantes, gerir inscrições, corrigir dados e emitir documentos | Alta |
| SH-05 | Administrador de sistemas / TI | Administrador | Gerir utilizadores e perfis, garantir disponibilidade, segurança e cópias de segurança | Alta |

### 2.2. Stakeholders indirectos

| ID | Stakeholder | Interesse / Necessidade principal | Influência |
|---|---|---|---|
| SH-06 | Direcção da instituição | Dados fiáveis para decisão estratégica e para reporte institucional | Alta |
| SH-07 | Conselho Pedagógico / Científico | Análise de taxas de aprovação e reprovação por disciplina | Média |
| SH-08 | Entidade reguladora do ensino superior | Conformidade dos registos académicos e reporte estatístico | Média |
| SH-09 | Encarregados de educação | Acompanhamento do percurso do estudante (acesso não previsto nesta versão) | Baixa |
| SH-10 | Equipa de desenvolvimento (Grupo 2) | Entregar uma solução funcional, documentada e defensável no prazo | Alta |
| SH-11 | Docente da disciplina de Engenharia de Software | Avaliar a aplicação correcta do processo de engenharia de software | Alta |

### 2.3. Matriz Poder × Interesse

| | **Interesse baixo** | **Interesse alto** |
|---|---|---|
| **Poder alto** | SH-08 (entidade reguladora) — *manter satisfeito* | SH-03, SH-04, SH-05, SH-06, SH-11 — *gerir de perto* |
| **Poder baixo** | SH-09 (encarregados) — *monitorizar* | SH-01, SH-02, SH-07 — *manter informado* |

### 2.4. Utilizadores-chave para validação

Para validação dos requisitos ao longo do semestre, foram designados como interlocutores
prioritários: **SH-04 (Secretaria Académica)**, por deter o conhecimento operacional dos
processos, e **SH-03 (Coordenador de curso)**, por definir as regras académicas aplicáveis.

---

## 3. Técnicas de levantamento de requisitos

| Técnica | Aplicação no projecto | Stakeholders envolvidos | Resultado |
|---|---|---|---|
| Análise documental | Análise do regulamento académico, do regulamento de avaliação e dos modelos de pauta em uso | SH-03, SH-04 | Regras de negócio RN-01 a RN-12 |
| Entrevista semi-estruturada | Sessões individuais sobre o fluxo de inscrição e de lançamento de notas | SH-02, SH-04 | Requisitos RF-21 a RF-32 |
| Observação directa | Acompanhamento do processo actual de emissão de pautas | SH-02, SH-04 | Identificação dos problemas P2, P3 e P5 |
| Questionário | Recolha de dificuldades sentidas na consulta de notas | SH-01 | Requisitos RF-33 a RF-36 |
| Prototipagem em papel | Validação dos ecrãs de lançamento de notas e de consulta de aproveitamento | SH-01, SH-02 | Refinamento dos critérios de aceitação |
| *Brainstorming* interno | Definição da arquitectura e das extensões opcionais | SH-10 | Épico E08 (notificações) |

---

## 4. Premissas, restrições e regras de negócio

### 4.1. Premissas

| ID | Premissa |
|---|---|
| PR-01 | A instituição organiza o ano lectivo em dois semestres. |
| PR-02 | Cada disciplina é oferecida numa ou mais **turmas** por semestre, cada uma com um docente responsável. |
| PR-03 | A escala de classificação é de 0 a 20 valores, com uma casa decimal. |
| PR-04 | Existe conectividade à Internet, embora com largura de banda limitada e instável. |
| PR-05 | Os dados de estudantes e docentes já existentes serão migrados a partir de ficheiros CSV. |
| PR-06 | O sistema não substitui o arquivo físico oficial da instituição nesta versão. |

### 4.2. Restrições

| ID | Restrição | Tipo |
|---|---|---|
| RE-01 | O desenvolvimento decorre entre 28/07 e 19/11 de 2026, por uma equipa de 3 estudantes em regime parcial | Prazo / Recursos |
| RE-02 | Deve ser utilizado software de código aberto, sem custos de licenciamento | Orçamento |
| RE-03 | A aplicação deve ser executável através de Docker, com um único comando de arranque | Técnica |
| RE-04 | A base de dados deve ser relacional (PostgreSQL) | Técnica |
| RE-05 | A comunicação entre frontend e backend deve ser feita exclusivamente por API REST | Arquitectural |
| RE-06 | O código-fonte deve ser mantido num repositório Git público com histórico de commits | Processo |
| RE-07 | A aplicação deve funcionar nos navegadores actuais, sem instalação de software cliente | Operacional |

### 4.3. Regras de negócio

| ID | Regra de negócio |
|---|---|
| RN-01 | Um estudante só pode inscrever-se em disciplinas pertencentes ao plano curricular do seu curso. |
| RN-02 | Um estudante só pode inscrever-se numa disciplina se tiver aprovação em todas as disciplinas definidas como pré-requisito. |
| RN-03 | Um estudante não pode inscrever-se mais de uma vez na mesma disciplina no mesmo ano lectivo e semestre. |
| RN-04 | O número de inscrições numa turma não pode exceder a sua capacidade máxima. |
| RN-05 | Só é possível inscrever-se em disciplinas de turmas de um ano lectivo cujo período de inscrição esteja aberto. |
| RN-06 | A soma dos pesos das avaliações de uma turma deve ser exactamente 100%. |
| RN-07 | A nota de frequência resulta da média ponderada das avaliações de frequência, arredondada a uma casa decimal. |
| RN-08 | O estudante obtém dispensa de exame se a nota de frequência for igual ou superior a 14,0 valores. |
| RN-09 | O estudante é admitido a exame se a nota de frequência estiver entre 10,0 e 13,9 valores; abaixo de 10,0 valores é reprovado por frequência. |
| RN-10 | Considera-se aprovado na disciplina o estudante com classificação final igual ou superior a 10,0 valores. |
| RN-11 | Uma nota publicada não pode ser alterada pelo docente; a alteração exige autorização do coordenador e fica registada na auditoria com justificação. |
| RN-12 | A média do curso é a média das classificações finais das disciplinas aprovadas, ponderada pelos respectivos créditos. |

> **Nota de implementação:** as regras RN-06 a RN-10 e RN-12 são específicas de um
> regulamento de avaliação. Devem ser encapsuladas numa estratégia de cálculo configurável
> (padrão *Strategy*) e não codificadas de forma rígida no domínio, de modo a permitir
> regulamentos distintos por curso — decisão a justificar na Fase 3.

---

## 5. Requisitos funcionais

Legenda de prioridade (MoSCoW): **M** = *Must have*, **S** = *Should have*, **C** = *Could have*, **W** = *Won't have (nesta versão)*.

### 5.1. Autenticação, utilizadores e permissões

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-01 | O sistema deve permitir a autenticação de utilizadores mediante credenciais (e-mail institucional e palavra-passe). | Todos | M |
| RF-02 | O sistema deve permitir o encerramento de sessão e invalidar o token de acesso correspondente. | Todos | M |
| RF-03 | O sistema deve permitir ao Administrador criar, consultar, editar, activar e desactivar contas de utilizador. | Administrador | M |
| RF-04 | O sistema deve permitir ao Administrador atribuir e remover perfis (papéis) a um utilizador, admitindo mais de um perfil por utilizador. | Administrador | M |
| RF-05 | O sistema deve permitir a recuperação de palavra-passe através de ligação temporária enviada por e-mail. | Todos | S |
| RF-06 | O sistema deve permitir ao utilizador alterar a sua própria palavra-passe, exigindo a palavra-passe actual. | Todos | S |
| RF-07 | O sistema deve gerir perfis e as permissões associadas a cada perfil. | Administrador | M |

### 5.2. Gestão de estudantes

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-08 | O sistema deve permitir o cadastro de estudantes com nome completo, número de estudante, documento de identificação, data de nascimento, sexo, contactos, curso e ano de ingresso. | Administrador | M |
| RF-09 | O sistema deve gerar automaticamente o número de estudante segundo o padrão `AAAA/NNNN`, garantindo unicidade. | Administrador | M |
| RF-10 | O sistema deve permitir consultar, pesquisar e filtrar estudantes por nome, número, curso, ano e estado. | Administrador, Coordenador | M |
| RF-11 | O sistema deve permitir editar os dados de um estudante e alterar o seu estado (activo, suspenso, transferido, graduado, desistente). | Administrador | M |
| RF-12 | O sistema deve impedir a eliminação física de estudantes com histórico académico, permitindo apenas a inactivação lógica. | Administrador | M |
| RF-13 | O sistema deve permitir a importação de estudantes a partir de ficheiro CSV, apresentando um relatório de erros por linha. | Administrador | C |

### 5.3. Gestão de docentes

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-14 | O sistema deve permitir o cadastro de docentes com nome, código, grau académico, categoria, área científica e contactos. | Administrador | M |
| RF-15 | O sistema deve permitir consultar, pesquisar, editar e inactivar docentes. | Administrador | M |
| RF-16 | O sistema deve permitir consultar as turmas atribuídas a um docente num determinado ano lectivo e semestre. | Administrador, Coordenador, Docente | M |
| RF-17 | O sistema deve associar cada docente à respectiva conta de utilizador com o perfil Docente. | Administrador | M |

### 5.4. Gestão de cursos e planos curriculares

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-18 | O sistema deve permitir o cadastro de cursos com designação, código, grau, duração em anos, regime e total de créditos. | Administrador | M |
| RF-19 | O sistema deve permitir consultar, editar e inactivar cursos. | Administrador | M |
| RF-20 | O sistema deve permitir atribuir um docente como Coordenador de um curso. | Administrador | M |
| RF-21 | O sistema deve permitir definir o plano curricular de um curso, associando disciplinas a um ano e semestre curriculares. | Administrador, Coordenador | M |
| RF-22 | O sistema deve permitir versionar o plano curricular, mantendo o plano aplicável a cada coorte de ingresso. | Administrador | C |

### 5.5. Gestão de disciplinas e turmas

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-23 | O sistema deve permitir o cadastro de disciplinas com designação, código, créditos, carga horária e tipo (obrigatória ou opcional). | Administrador | M |
| RF-24 | O sistema deve permitir definir disciplinas como pré-requisito de outra disciplina. | Administrador, Coordenador | M |
| RF-25 | O sistema deve permitir consultar, editar e inactivar disciplinas. | Administrador | M |
| RF-26 | O sistema deve permitir criar turmas, associando uma disciplina a um ano lectivo, semestre, docente responsável e capacidade máxima. | Administrador, Coordenador | M |
| RF-27 | O sistema deve permitir gerir anos lectivos e respectivos períodos de inscrição e de lançamento de notas. | Administrador | M |
| RF-28 | O sistema deve apresentar, para cada turma, o número de inscritos e as vagas disponíveis. | Administrador, Coordenador, Docente | S |

### 5.6. Inscrições

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-29 | O sistema deve permitir ao estudante inscrever-se em disciplinas das turmas abertas do seu curso. | Estudante | M |
| RF-30 | O sistema deve validar, no momento da inscrição, as regras RN-01 a RN-05 e apresentar mensagem explícita quando a inscrição for recusada. | Estudante, Administrador | M |
| RF-31 | O sistema deve permitir ao Administrador inscrever ou desinscrever um estudante em nome deste, registando o autor da operação. | Administrador | M |
| RF-32 | O sistema deve permitir ao estudante anular a sua inscrição enquanto o período de inscrição estiver aberto e não existirem notas lançadas. | Estudante | S |
| RF-33 | O sistema deve permitir consultar as inscrições de um estudante por ano lectivo e semestre. | Todos | M |
| RF-34 | O sistema deve permitir ao Coordenador validar ou recusar inscrições que excedam o número máximo de disciplinas por semestre. | Coordenador | C |

### 5.7. Avaliações e notas

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-35 | O sistema deve permitir ao docente definir as avaliações de uma turma, indicando designação, tipo, peso e data. | Docente, Coordenador | M |
| RF-36 | O sistema deve validar que a soma dos pesos das avaliações de uma turma é igual a 100% (RN-06). | Docente | M |
| RF-37 | O sistema deve permitir ao docente lançar as classificações de cada avaliação para os estudantes inscritos na turma. | Docente | M |
| RF-38 | O sistema deve validar que cada classificação se situa no intervalo de 0 a 20 valores. | Docente | M |
| RF-39 | O sistema deve permitir gravar as classificações em estado de rascunho e publicá-las posteriormente numa acção explícita. | Docente | M |
| RF-40 | O sistema deve impedir a alteração de classificações publicadas, admitindo apenas pedidos de rectificação sujeitos a autorização do Coordenador com justificação obrigatória (RN-11). | Docente, Coordenador | M |
| RF-41 | O sistema deve impedir o lançamento de notas fora do período de lançamento definido para o ano lectivo. | Docente | S |
| RF-42 | O sistema deve permitir o lançamento de notas em bloco através de importação de ficheiro CSV. | Docente | C |
| RF-43 | O sistema deve permitir registar a falta de comparência a uma avaliação, distinguindo-a da classificação zero. | Docente | S |

### 5.8. Médias, frequência e aproveitamento

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-44 | O sistema deve calcular automaticamente a nota de frequência de cada estudante por turma, segundo os pesos definidos (RN-07). | — (automático) | M |
| RF-45 | O sistema deve determinar automaticamente a situação de frequência (dispensado, admitido a exame, reprovado por frequência) segundo RN-08 e RN-09. | — (automático) | M |
| RF-46 | O sistema deve calcular a classificação final da disciplina e determinar a situação final (aprovado ou reprovado) segundo RN-10. | — (automático) | M |
| RF-47 | O sistema deve calcular a média ponderada do curso por créditos e o total de créditos obtidos (RN-12). | — (automático) | M |
| RF-48 | O sistema deve permitir ao estudante consultar o seu aproveitamento académico, por ano lectivo e acumulado. | Estudante | M |
| RF-49 | O sistema deve permitir ao Coordenador e ao Administrador consultar o aproveitamento de qualquer estudante do curso. | Coordenador, Administrador | M |
| RF-50 | O sistema deve recalcular automaticamente as médias afectadas quando uma classificação for rectificada. | — (automático) | M |

### 5.9. Pautas

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-51 | O sistema deve emitir a pauta de uma turma, contendo a identificação da instituição, curso, disciplina, ano lectivo, docente, e a lista de estudantes com as classificações parcelares, a nota de frequência, a classificação final e a situação. | Docente, Coordenador, Administrador | M |
| RF-52 | O sistema deve permitir exportar a pauta em formato PDF. | Docente, Coordenador, Administrador | M |
| RF-53 | O sistema deve permitir ao Coordenador aprovar (homologar) a pauta, após o que esta é marcada como definitiva. | Coordenador | S |
| RF-54 | O sistema deve numerar as pautas emitidas e manter o histórico das versões emitidas. | — (automático) | C |

### 5.10. Relatórios

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-55 | O sistema deve gerar o relatório de taxas de aprovação e reprovação por disciplina e por ano lectivo. | Coordenador, Administrador | M |
| RF-56 | O sistema deve gerar o relatório de estudantes inscritos por curso, ano e semestre. | Coordenador, Administrador | M |
| RF-57 | O sistema deve gerar o relatório de ocupação das turmas (inscritos face à capacidade). | Coordenador, Administrador | S |
| RF-58 | O sistema deve gerar a lista de estudantes em risco de exclusão (com reprovações repetidas na mesma disciplina). | Coordenador | C |
| RF-59 | O sistema deve permitir exportar qualquer relatório em PDF e CSV. | Coordenador, Administrador | S |
| RF-60 | O sistema deve apresentar um painel inicial (*dashboard*) com indicadores adequados ao perfil do utilizador autenticado. | Todos | S |

### 5.11. Auditoria

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-61 | O sistema deve registar em log de auditoria todas as operações de criação, alteração e eliminação de classificações e de inscrições, guardando autor, data e hora, valor anterior e valor novo. | — (automático) | M |
| RF-62 | O sistema deve registar as tentativas de autenticação falhadas. | — (automático) | S |
| RF-63 | O sistema deve permitir ao Administrador consultar e filtrar o registo de auditoria, sem permitir a sua alteração ou eliminação. | Administrador | S |

### 5.12. Notificações *(extensão opcional)*

| ID | Requisito | Perfis | Prior. |
|---|---|---|---|
| RF-64 | O sistema deve notificar o estudante quando as classificações de uma disciplina em que está inscrito forem publicadas. | Estudante | C |
| RF-65 | O sistema deve notificar o estudante da abertura e do encerramento do período de inscrições. | Estudante | C |
| RF-66 | O sistema deve notificar o docente quando o período de lançamento de notas estiver a terminar com pautas incompletas. | Docente | C |
| RF-67 | O sistema deve manter um centro de notificações na aplicação, com distinção entre notificações lidas e não lidas. | Todos | C |
| RF-68 | O sistema deve permitir ao utilizador configurar os canais de notificação (aplicação e e-mail). | Todos | W |

---

## 6. Requisitos não funcionais

Requisitos organizados segundo as características de qualidade da norma **ISO/IEC 25010**.
Cada requisito é enunciado de forma verificável, com métrica e método de verificação.

### 6.1. Desempenho e eficiência

| ID | Requisito | Métrica / Critério | Verificação |
|---|---|---|---|
| RNF-01 | O tempo de resposta das operações de consulta corrente deve ser reduzido. | ≤ 2 s para 95% dos pedidos, com 100 utilizadores simultâneos | Teste de carga (k6 ou JMeter) |
| RNF-02 | A emissão de uma pauta de turma deve ser rápida. | ≤ 5 s para uma turma de 60 estudantes | Medição em teste funcional |
| RNF-03 | O sistema deve suportar a carga prevista da instituição. | 5 000 estudantes, 200 docentes, 300 000 registos de nota | Teste de volume com dados sintéticos |
| RNF-04 | As listagens devem ser paginadas no servidor. | Máximo de 50 registos por página; nenhum *endpoint* devolve colecções não paginadas | Revisão de código e testes de API |
| RNF-05 | O peso inicial das páginas deve ser adequado a ligações de baixa largura de banda. | ≤ 1,5 MB no primeiro carregamento | Auditoria Lighthouse |

### 6.2. Segurança

| ID | Requisito | Métrica / Critério | Verificação |
|---|---|---|---|
| RNF-06 | As palavras-passe devem ser armazenadas apenas sob a forma de resumo criptográfico com *salt*. | Algoritmo bcrypt ou Argon2; nenhuma palavra-passe legível na base de dados | Inspecção da base de dados |
| RNF-07 | Toda a comunicação deve ser cifrada. | HTTPS/TLS 1.2 ou superior em produção | Verificação de configuração |
| RNF-08 | O acesso a todos os recursos deve ser autorizado com base no perfil do utilizador. | Nenhum *endpoint* acessível sem o papel exigido; verificação também no servidor | Testes automatizados de autorização por perfil |
| RNF-09 | O sistema deve estar protegido contra as vulnerabilidades do OWASP Top 10. | Ausência de vulnerabilidades de severidade alta ou crítica | Análise estática e teste manual dirigido |
| RNF-10 | Toda a entrada de dados deve ser validada no servidor, independentemente da validação no cliente. | 100% dos *endpoints* com validação declarada | Revisão de código e testes de API |
| RNF-11 | As sessões devem expirar por inactividade. | Token válido por 60 minutos; renovação por *refresh token* | Teste funcional |
| RNF-12 | Os dados pessoais e as classificações são informação confidencial e o seu acesso deve ser restringido ao estritamente necessário. | Um estudante nunca acede a dados de outro estudante; um docente acede apenas às suas turmas | Testes automatizados de controlo de acesso |
| RNF-13 | Os segredos de configuração não podem constar do repositório. | Utilização de variáveis de ambiente; ficheiro `.env` excluído por `.gitignore` | Inspecção do repositório |

### 6.3. Fiabilidade e disponibilidade

| ID | Requisito | Métrica / Critério | Verificação |
|---|---|---|---|
| RNF-14 | O sistema deve estar disponível nos períodos críticos de inscrição e lançamento de notas. | Disponibilidade ≥ 99% em horário lectivo | Monitorização |
| RNF-15 | Devem existir cópias de segurança da base de dados. | Cópia diária automática, com procedimento de restauro documentado e testado | Ensaio de restauro |
| RNF-16 | As operações que envolvam múltiplas escritas devem ser transaccionais. | Nenhum estado inconsistente após falha; *rollback* garantido | Teste de integração com falha simulada |
| RNF-17 | Os erros apresentados ao utilizador não devem expor detalhes internos do sistema. | Nenhum *stack trace* ou nome de tabela em respostas de erro | Testes de API |

### 6.4. Usabilidade

| ID | Requisito | Métrica / Critério | Verificação |
|---|---|---|---|
| RNF-18 | A interface deve estar integralmente em língua portuguesa. | 100% dos rótulos e mensagens em português | Revisão de interface |
| RNF-19 | A interface deve ser utilizável em ecrãs de telemóvel. | Layout adaptativo a partir de 360 px de largura | Teste em dispositivos e emulador |
| RNF-20 | Um docente deve conseguir lançar as notas de uma turma sem formação prévia. | Tarefa concluída em ≤ 10 minutos para 40 estudantes, por utilizador não treinado | Teste de usabilidade com 3 utilizadores |
| RNF-21 | As mensagens de erro devem indicar a causa e a acção corretiva. | Nenhuma mensagem genérica do tipo "erro ao gravar" | Revisão de interface |

### 6.5. Manutenibilidade

| ID | Requisito | Métrica / Critério | Verificação |
|---|---|---|---|
| RNF-22 | O sistema deve seguir uma arquitectura em camadas com separação clara de responsabilidades. | Nenhuma regra de negócio em controladores ou em componentes de interface | Revisão de código |
| RNF-23 | O código deve respeitar os princípios SOLID e as convenções de Clean Code. | Ausência de violações graves detectadas em revisão por pares | Revisão por pares em cada *pull request* |
| RNF-24 | A cobertura de testes automatizados deve ser adequada. | ≥ 70% de cobertura global; ≥ 90% nos módulos de cálculo de médias e de autorização | Relatório de cobertura no CI |
| RNF-25 | O código deve seguir um estilo uniforme, verificado automaticamente. | *Linter* e formatador executados no pipeline; construção falha em caso de violação | Pipeline de CI |
| RNF-26 | A API deve estar documentada. | Especificação OpenAPI actualizada e acessível | Inspecção da documentação |

### 6.6. Portabilidade e implantação

| ID | Requisito | Métrica / Critério | Verificação |
|---|---|---|---|
| RNF-27 | A aplicação deve ser integralmente containerizada. | Arranque completo com um único comando `docker compose up` | Ensaio em máquina limpa |
| RNF-28 | A aplicação deve funcionar nas duas últimas versões dos navegadores Chrome, Firefox e Edge. | Ausência de erros funcionais | Teste manual multi-navegador |
| RNF-29 | O sistema deve ser independente do sistema operativo do servidor. | Execução verificada em Linux e em Windows com Docker | Ensaio de implantação |
| RNF-30 | Devem existir dados de demonstração (*seeders*) para arranque imediato do ambiente. | Um comando cria utilizadores dos 4 perfis e dados de exemplo | Ensaio em máquina limpa |

### 6.7. Conformidade e rastreabilidade

| ID | Requisito | Métrica / Critério | Verificação |
|---|---|---|---|
| RNF-31 | Toda a alteração a uma classificação deve ser rastreável ao seu autor. | 100% das alterações com autor, data/hora, valor anterior e valor novo | Testes de integração sobre a auditoria |
| RNF-32 | O registo de auditoria não pode ser alterado nem eliminado através da aplicação. | Ausência de *endpoints* de escrita ou eliminação sobre a auditoria | Revisão de código e testes de API |
| RNF-33 | Os dados pessoais recolhidos devem limitar-se aos necessários à finalidade académica. | Justificação documentada de cada campo pessoal recolhido | Revisão do modelo de dados |
| RNF-34 | As datas e horas devem ser registadas com fuso horário explícito. | Armazenamento em UTC; apresentação em Africa/Maputo | Testes unitários |

---

## 7. User Stories e critérios de aceitação

### 7.1. Épicos

| ID | Épico | Requisitos abrangidos |
|---|---|---|
| E01 | Acesso e gestão de utilizadores | RF-01 a RF-07 |
| E02 | Cadastros estruturantes (estudantes, docentes, cursos, disciplinas) | RF-08 a RF-28 |
| E03 | Inscrições em disciplinas | RF-29 a RF-34 |
| E04 | Avaliações e lançamento de notas | RF-35 a RF-43 |
| E05 | Médias e aproveitamento académico | RF-44 a RF-50 |
| E06 | Pautas | RF-51 a RF-54 |
| E07 | Relatórios e painéis | RF-55 a RF-60 |
| E08 | Auditoria e notificações | RF-61 a RF-68 |

### 7.2. Épico E01 — Acesso e gestão de utilizadores

---

**US-01 — Autenticação**
*Como* utilizador do sistema, *quero* autenticar-me com o meu e-mail institucional e palavra-passe, *para que* possa acessar as funcionalidades correspondentes ao meu perfil.

**Critérios de aceitação**
- **Dado** que sou um utilizador activo com credenciais válidas, **quando** submeto o formulário de autenticação, **então** sou redireccionado para o painel do meu perfil e recebo um token de acesso.
- **Dado** que introduzo uma palavra-passe incorrecta, **quando** submeto o formulário, **então** é apresentada a mensagem "Credenciais inválidas", sem indicar se o erro está no e-mail ou na palavra-passe.
- **Dado** que a minha conta está desactivada, **quando** me tento autenticar com credenciais correctas, **então** o acesso é recusado com a mensagem "Conta inactiva. Contacte a Secretaria Académica".
- **Dado** que registei 5 tentativas falhadas nos últimos 15 minutos, **quando** tento novamente, **então** o acesso é bloqueado temporariamente durante 15 minutos.
- Todas as tentativas, bem ou mal sucedidas, são registadas na auditoria.

---

**US-02 — Gestão de contas de utilizador**
*Como* Administrador, *quero* criar, editar, activar e desactivar contas de utilizador, *para que* apenas pessoas autorizadas acedam ao sistema.

**Critérios de aceitação**
- É possível criar uma conta indicando nome, e-mail e um ou mais perfis.
- O e-mail é único no sistema; a tentativa de duplicação apresenta erro no próprio campo.
- A conta criada recebe por e-mail uma ligação de definição de palavra-passe, válida por 24 horas.
- A desactivação de uma conta impede a autenticação, mas preserva todos os registos históricos associados.
- Não é possível desactivar a própria conta com que se está autenticado.

---

**US-03 — Atribuição de perfis**
*Como* Administrador, *quero* atribuir e remover perfis a um utilizador, *para que* as permissões reflictam as funções efectivamente exercidas.

**Critérios de aceitação**
- Estão disponíveis os perfis Administrador, Coordenador, Docente e Estudante.
- Um utilizador pode acumular perfis (por exemplo, Docente e Coordenador) e alternar o contexto activo na interface.
- A alteração de perfis produz efeito no acesso subsequente, sem necessidade de intervenção técnica.
- O sistema impede que reste zero utilizadores com o perfil Administrador.
- Toda a alteração de perfis é registada na auditoria.

---

**US-04 — Recuperação de palavra-passe**
*Como* utilizador, *quero* recuperar o acesso quando esquecer a palavra-passe, *para que* não dependa da equipa de TI.

**Critérios de aceitação**
- **Dado** que indico um e-mail registado, **quando** solicito a recuperação, **então** recebo uma ligação com token de uso único válido por 60 minutos.
- **Dado** que indico um e-mail não registado, **quando** solicito a recuperação, **então** é apresentada a mesma mensagem de confirmação, para não revelar quais os e-mails existentes.
- Um token já utilizado ou expirado é recusado com mensagem explícita.
- A nova palavra-passe deve ter no mínimo 8 caracteres, com letras e algarismos.

---

### 7.3. Épico E02 — Cadastros estruturantes

---

**US-05 — Cadastro de estudante**
*Como* técnico da Secretaria Académica, *quero* registar um novo estudante, *para que* este possa inscrever-se em disciplinas e ter percurso académico registado.

**Critérios de aceitação**
- Os campos nome completo, documento de identificação, data de nascimento, curso e ano de ingresso são obrigatórios.
- **Dado** que submeto um cadastro válido, **quando** gravo, **então** o sistema gera o número de estudante no formato `AAAA/NNNN` e cria automaticamente a conta de utilizador com o perfil Estudante.
- **Dado** que existe já um estudante com o mesmo número de documento de identificação, **quando** gravo, **então** o sistema alerta sobre o possível duplicado e exige confirmação explícita.
- A data de nascimento não pode ser futura e o estudante deve ter pelo menos 16 anos.
- O número de estudante gerado não é editável.

---

**US-06 — Pesquisa de estudantes**
*Como* técnico da Secretaria Académica, *quero* pesquisar e filtrar estudantes, *para que* encontre rapidamente um registo específico.

**Critérios de aceitação**
- A pesquisa aceita parte do nome ou do número de estudante.
- É possível filtrar cumulativamente por curso, ano curricular e estado.
- Os resultados são paginados, com 25 registos por página.
- A pesquisa por nome é insensível a maiúsculas e a acentuação.
- Com 5 000 estudantes na base de dados, o resultado é devolvido em menos de 2 segundos.

---

**US-07 — Cadastro de docente**
*Como* Administrador, *quero* registar docentes e as suas qualificações, *para que* possam ser atribuídos a turmas.

**Critérios de aceitação**
- Os campos nome, código de docente, grau académico e e-mail institucional são obrigatórios.
- O sistema cria automaticamente a conta de utilizador com o perfil Docente.
- Um docente inactivo não figura na lista de selecção ao criar novas turmas.
- Não é possível inactivar um docente com turmas activas e pautas não homologadas no semestre em curso.

---

**US-08 — Gestão de cursos**
*Como* Administrador, *quero* registar os cursos da instituição, *para que* os estudantes possam ser associados a um curso.

**Critérios de aceitação**
- Os campos designação, código, grau, duração em anos e total de créditos são obrigatórios.
- O código do curso é único.
- É possível atribuir a um curso um docente como Coordenador, o qual passa a acumular o perfil Coordenador.
- Não é possível eliminar um curso com estudantes associados; apenas inactivá-lo.

---

**US-09 — Definição do plano curricular**
*Como* Coordenador de curso, *quero* definir as disciplinas do plano curricular por ano e semestre, *para que* as inscrições respeitem a estrutura do curso.

**Critérios de aceitação**
- É possível associar disciplinas ao curso indicando ano curricular, semestre e tipo (obrigatória ou opcional).
- **Dado** que a duração do curso é de 4 anos, **quando** tento associar uma disciplina ao 5.º ano, **então** a operação é recusada.
- O sistema apresenta o total de créditos por ano e o total do curso, sinalizando divergência face ao total definido no curso.
- A mesma disciplina não pode figurar duas vezes no mesmo plano curricular.

---

**US-10 — Definição de pré-requisitos**
*Como* Coordenador de curso, *quero* definir pré-requisitos entre disciplinas, *para que* o sistema impeça inscrições irregulares.

**Critérios de aceitação**
- É possível associar uma ou mais disciplinas como pré-requisito de outra.
- **Dado** que A é pré-requisito de B, **quando** tento definir B como pré-requisito de A, **então** o sistema recusa a operação por criar dependência circular.
- O sistema detecta e recusa dependências circulares indirectas (A → B → C → A).
- Uma disciplina não pode ser pré-requisito de si mesma.

---

**US-11 — Criação de turmas**
*Como* Coordenador de curso, *quero* criar as turmas de cada disciplina para um ano lectivo e semestre, *para que* estudantes e docentes lhes possam ser associados.

**Critérios de aceitação**
- Uma turma é definida por disciplina, ano lectivo, semestre, designação, docente responsável e capacidade máxima.
- A combinação disciplina + ano lectivo + semestre + designação é única.
- A capacidade máxima é um número inteiro positivo.
- O sistema apresenta, para cada turma, o número de inscritos e as vagas remanescentes.
- Não é possível reduzir a capacidade para um valor inferior ao número de inscritos.

---

**US-12 — Gestão do ano lectivo**
*Como* Administrador, *quero* definir os anos lectivos e os seus períodos de inscrição e de lançamento de notas, *para que* as operações ocorram apenas nas janelas temporais previstas.

**Critérios de aceitação**
- Um ano lectivo é definido por designação, data de início, data de fim e estado (planeado, activo, encerrado).
- Para cada semestre são definidas as datas de abertura e encerramento do período de inscrições e do período de lançamento de notas.
- Existe no máximo um ano lectivo no estado activo.
- Num ano lectivo encerrado não são admitidas inscrições nem lançamento de notas.

---

### 7.4. Épico E03 — Inscrições

---

**US-13 — Inscrição do estudante em disciplinas**
*Como* estudante, *quero* inscrever-me nas disciplinas do semestre, *para que* fique registado como frequentando essas disciplinas e possa ser avaliado.

**Critérios de aceitação**
- **Dado** que o período de inscrições está aberto, **quando** acedo à área de inscrições, **então** vejo apenas as turmas de disciplinas do plano curricular do meu curso com vagas disponíveis.
- **Dado** que não tenho aprovação num pré-requisito, **quando** tento inscrever-me na disciplina dependente, **então** a inscrição é recusada com a mensagem que identifica a disciplina em falta.
- **Dado** que a turma está com a capacidade esgotada, **quando** tento inscrever-me, **então** a inscrição é recusada e a turma apresenta-se marcada como sem vagas.
- **Dado** que já estou inscrito na disciplina neste semestre, **quando** tento inscrever-me novamente, **então** a operação é recusada.
- **Dado** que o período de inscrições está encerrado, **quando** acedo à área de inscrições, **então** é apresentada mensagem com as datas do período e nenhuma acção de inscrição está disponível.
- Concluída a inscrição, recebo um comprovativo com a lista das disciplinas e a data e hora do registo.

---

**US-14 — Anulação de inscrição**
*Como* estudante, *quero* anular uma inscrição feita por erro, *para que* não seja avaliado numa disciplina que não frequento.

**Critérios de aceitação**
- A anulação só é possível enquanto o período de inscrições estiver aberto.
- **Dado** que já existem classificações publicadas na disciplina, **quando** tento anular, **então** a operação é recusada e sou orientado a contactar a Secretaria Académica.
- A anulação liberta uma vaga na turma.
- A anulação é registada na auditoria com identificação do autor.

---

**US-15 — Inscrição administrativa**
*Como* técnico da Secretaria Académica, *quero* inscrever ou desinscrever um estudante em nome deste, *para que* casos excepcionais possam ser resolvidos.

**Critérios de aceitação**
- É possível inscrever um estudante fora do período regular, mediante justificação obrigatória.
- É possível dispensar a validação de pré-requisitos, mediante justificação obrigatória e registo em auditoria.
- A operação identifica sempre o técnico que a executou, não o estudante.
- A dispensa de validação exige confirmação num diálogo explícito.

---

### 7.5. Épico E04 — Avaliações e lançamento de notas

---

**US-16 — Definição das avaliações da turma**
*Como* docente, *quero* definir as avaliações da minha turma e os respectivos pesos, *para que* o sistema calcule automaticamente a nota de frequência.

**Critérios de aceitação**
- Cada avaliação tem designação, tipo (teste, trabalho, prática, exame), peso em percentagem e data prevista.
- **Dado** que a soma dos pesos das avaliações de frequência é diferente de 100%, **quando** tento activar a estrutura de avaliação, **então** a operação é recusada com indicação da soma actual.
- Não é possível alterar o peso de uma avaliação que já tenha classificações publicadas.
- Só o docente responsável pela turma, o Coordenador do curso ou o Administrador podem definir as avaliações.

---

**US-17 — Lançamento de notas**
*Como* docente, *quero* lançar as classificações dos meus estudantes, *para que* fiquem registadas e sejam calculadas as médias.

**Critérios de aceitação**
- **Dado** que acedo a uma avaliação da minha turma, **quando** abro o ecrã de lançamento, **então** vejo a lista dos estudantes inscritos ordenada por número de estudante, com um campo de classificação por estudante.
- **Dado** que introduzo uma classificação de 21, **quando** tento gravar, **então** o valor é recusado com a indicação do intervalo admitido (0 a 20).
- As classificações admitem uma casa decimal e aceitam vírgula ou ponto como separador decimal.
- É possível gravar parcialmente e retomar posteriormente; as notas gravadas permanecem em estado de rascunho e não são visíveis ao estudante.
- É possível assinalar um estudante como faltoso, situação distinta da classificação zero.
- **Dado** que tento acessar uma turma que não me está atribuída, **quando** submeto o pedido, **então** recebo resposta de acesso proibido (403).

---

**US-18 — Publicação das notas**
*Como* docente, *quero* publicar as notas numa acção explícita, *para que* os estudantes só as vejam depois de as ter revisto.

**Critérios de aceitação**
- **Dado** que existem estudantes sem classificação nem marca de falta, **quando** tento publicar, **então** o sistema alerta e enumera os estudantes em falta, exigindo confirmação.
- Após a publicação, as classificações tornam-se visíveis ao estudante e os campos passam a estar em modo de leitura para o docente.
- A publicação desencadeia o cálculo da nota de frequência e da situação de todos os estudantes da turma.
- A publicação é registada na auditoria com data, hora e autor.
- *(Extensão)* A publicação gera notificação para os estudantes inscritos.

---

**US-19 — Rectificação de nota publicada**
*Como* docente, *quero* solicitar a correcção de uma nota já publicada, *para que* erros de lançamento possam ser reparados de forma controlada.

**Critérios de aceitação**
- **Dado** que uma nota está publicada, **quando** tento alterá-la directamente, **então** a alteração é recusada e é-me proposto criar um pedido de rectificação.
- O pedido de rectificação exige o novo valor e uma justificação com pelo menos 20 caracteres.
- O pedido fica pendente de decisão do Coordenador do curso.
- **Dado** que o Coordenador aprova o pedido, **quando** a decisão é registada, **então** a nota é actualizada, as médias afectadas são recalculadas e a auditoria guarda o valor anterior, o novo valor, a justificação e ambos os intervenientes.
- **Dado** que o Coordenador recusa o pedido, **quando** a decisão é registada, **então** a nota mantém-se e o docente é informado do motivo.

---

### 7.6. Épico E05 — Médias e aproveitamento

---

**US-20 — Cálculo automático da nota de frequência e da situação**
*Como* docente, *quero* que o sistema calcule a nota de frequência e a situação de cada estudante, *para que* não seja necessário qualquer cálculo manual.

**Critérios de aceitação**
- A nota de frequência é a média das classificações das avaliações de frequência, ponderada pelos pesos definidos, arredondada a uma casa decimal.
- **Dado** que a nota de frequência é 14,0 ou superior, **então** a situação é "Dispensado".
- **Dado** que a nota de frequência está entre 10,0 e 13,9, **então** a situação é "Admitido a exame".
- **Dado** que a nota de frequência é inferior a 10,0, **então** a situação é "Reprovado por frequência" e o estudante não é admitido a exame.
- Um estudante marcado como faltoso a uma avaliação tem essa avaliação contabilizada como zero para efeitos de frequência.
- O cálculo é coberto por testes unitários, incluindo os casos de fronteira 9,95 / 10,0 / 13,94 / 13,95 / 14,0.

---

**US-21 — Cálculo da classificação final e da média do curso**
*Como* estudante, *quero* que a minha classificação final e a média do curso sejam calculadas automaticamente, *para que* conheça a minha situação sem depender da Secretaria.

**Critérios de aceitação**
- A classificação final é determinada segundo o regulamento aplicável (dispensa ou exame) e arredondada a uma casa decimal.
- É aprovado na disciplina o estudante com classificação final igual ou superior a 10,0 valores.
- A média do curso é a média das classificações finais das disciplinas aprovadas, ponderada pelos créditos respectivos.
- O total de créditos obtidos é apresentado face ao total de créditos do curso, com a percentagem de conclusão.
- As disciplinas em curso não são consideradas no cálculo da média.

---

**US-22 — Consulta do aproveitamento académico**
*Como* estudante, *quero* consultar o meu aproveitamento por ano lectivo e acumulado, *para que* possa acompanhar o meu percurso.

**Critérios de aceitação**
- **Dado** que estou autenticado como estudante, **quando** acedo ao meu aproveitamento, **então** vejo, por ano lectivo e semestre, as disciplinas, as classificações parcelares publicadas, a nota de frequência, a classificação final e a situação.
- São apresentados a média do curso, o total de créditos obtidos e a percentagem de conclusão.
- As classificações em estado de rascunho não são apresentadas.
- **Dado** que tento acessar o aproveitamento de outro estudante alterando o identificador no pedido, **quando** este é submetido, **então** recebo resposta de acesso proibido (403).
- É possível exportar o aproveitamento em PDF.

---

**US-23 — Acompanhamento do aproveitamento pelo Coordenador**
*Como* Coordenador de curso, *quero* consultar o aproveitamento dos estudantes do meu curso, *para que* possa identificar situações que exijam intervenção.

**Critérios de aceitação**
- Tenho acesso apenas aos estudantes dos cursos que coordeno.
- É possível ordenar a lista por média e filtrar por ano curricular e situação.
- São sinalizados os estudantes com duas ou mais reprovações na mesma disciplina.
- É possível exportar a lista em CSV.

---

### 7.7. Épico E06 — Pautas

---

**US-24 — Emissão da pauta**
*Como* docente, *quero* emitir a pauta da minha turma, *para que* possa entregá-la à Secretaria Académica em formato normalizado.

**Critérios de aceitação**
- A pauta apresenta o cabeçalho institucional, o curso, a disciplina, o ano lectivo, o semestre, a turma e o docente responsável.
- Cada linha contém o número e o nome do estudante, as classificações parcelares, a nota de frequência, a classificação final e a situação.
- A pauta inclui o resumo estatístico: número de inscritos, aprovados, reprovados e a taxa de aprovação.
- A pauta é exportável em PDF, com data e hora de emissão e número sequencial.
- **Dado** que existem notas em rascunho, **quando** emito a pauta, **então** esta é assinalada como provisória.

---

**US-25 — Homologação da pauta**
*Como* Coordenador de curso, *quero* homologar a pauta de uma turma, *para que* as classificações se tornem definitivas.

**Critérios de aceitação**
- Só é possível homologar pautas com todos os estudantes classificados ou marcados como faltosos.
- Após a homologação, a pauta é marcada como definitiva e qualquer alteração exige pedido de rectificação.
- A pauta homologada registada apresenta a identificação do Coordenador e a data e hora da homologação.
- É mantido o histórico das versões emitidas da pauta.

---

### 7.8. Épico E07 — Relatórios e painéis

---

**US-26 — Relatório de aproveitamento por disciplina**
*Como* Coordenador de curso, *quero* obter as taxas de aprovação e reprovação por disciplina, *para que* possa identificar disciplinas críticas.

**Critérios de aceitação**
- O relatório apresenta, por disciplina e ano lectivo, o número de inscritos, aprovados, reprovados, a taxa de aprovação e a média das classificações.
- É filtrável por ano lectivo, semestre e ano curricular.
- É exportável em PDF e CSV.
- Com 300 000 registos de nota, o relatório é gerado em menos de 5 segundos.

---

**US-27 — Painel inicial por perfil**
*Como* utilizador autenticado, *quero* ver um painel com a informação relevante para o meu perfil, *para que* identifique imediatamente o que exige a minha atenção.

**Critérios de aceitação**
- O estudante vê as disciplinas em que está inscrito no semestre em curso, as notas recém-publicadas e o estado do período de inscrições.
- O docente vê as suas turmas, com o número de avaliações pendentes de lançamento e o prazo de lançamento de notas.
- O Coordenador vê os indicadores agregados do curso e as pautas pendentes de homologação.
- O Administrador vê o total de estudantes activos, o estado do ano lectivo e as últimas operações de auditoria.
- O painel carrega em menos de 2 segundos.

---

### 7.9. Épico E08 — Auditoria e notificações

---

**US-28 — Registo de auditoria**
*Como* Administrador, *quero* consultar o registo de todas as alterações sensíveis, *para que* seja possível apurar responsabilidades.

**Critérios de aceitação**
- Toda a criação, alteração e eliminação de classificações e de inscrições gera um registo com autor, data e hora, entidade, valor anterior e valor novo.
- O registo é filtrável por autor, tipo de operação, entidade e intervalo de datas.
- Não existe qualquer funcionalidade de alteração ou eliminação de registos de auditoria.
- **Dado** que uma nota é rectificada, **quando** consulto a auditoria, **então** vejo o valor anterior, o novo valor, a justificação e os dois intervenientes.

---

**US-29 — Notificação de publicação de notas** *(opcional)*
*Como* estudante, *quero* ser notificado quando as notas de uma disciplina forem publicadas, *para que* não tenha de verificar repetidamente o sistema.

**Critérios de aceitação**
- A publicação das notas de uma turma gera uma notificação para cada estudante inscrito.
- A notificação identifica a disciplina e a avaliação, sem incluir a classificação.
- As notificações são apresentadas num centro de notificações, com distinção entre lidas e não lidas.
- O envio é assíncrono e uma falha no envio não impede a publicação das notas.

---

**US-30 — Notificação do período de inscrições** *(opcional)*
*Como* estudante, *quero* ser notificado da abertura e do encerramento do período de inscrições, *para que* não perca o prazo.

**Critérios de aceitação**
- É gerada uma notificação na abertura do período e outra 48 horas antes do encerramento.
- A notificação de aviso é enviada apenas a estudantes activos ainda sem inscrições no semestre.
- Não são geradas notificações duplicadas para o mesmo evento e destinatário.

---

## 8. Product Backlog priorizado

### 8.1. Critérios de priorização

A priorização combina três factores: **valor para o utilizador** (1–5), **risco técnico**
(1–5) e **dependências**. Itens de valor elevado e risco elevado são antecipados, de modo a
expor cedo os problemas mais difíceis (cálculo de médias e autorização por perfil).
A estimativa usa **pontos de história** na escala de Fibonacci (1, 2, 3, 5, 8, 13).

Capacidade estimada da equipa: **3 estudantes × 8 h/semana ≈ 20 pontos por sprint de
duas semanas**.

### 8.2. Backlog

| Ord. | ID | User Story | Épico | MoSCoW | Valor | Risco | Pontos | Dependências | Sprint |
|---|---|---|---|---|---|---|---|---|---|
| 1 | US-01 | Autenticação | E01 | M | 5 | 3 | 5 | — | S1 |
| 2 | US-02 | Gestão de contas de utilizador | E01 | M | 5 | 2 | 5 | US-01 | S1 |
| 3 | US-03 | Atribuição de perfis | E01 | M | 5 | 4 | 8 | US-02 | S1 |
| 4 | US-08 | Gestão de cursos | E02 | M | 4 | 1 | 3 | US-03 | S1 |
| 5 | US-07 | Cadastro de docente | E02 | M | 4 | 2 | 3 | US-02 | S2 |
| 6 | US-05 | Cadastro de estudante | E02 | M | 5 | 2 | 5 | US-08 | S2 |
| 7 | US-06 | Pesquisa de estudantes | E02 | M | 4 | 1 | 3 | US-05 | S2 |
| 8 | US-09 | Definição do plano curricular | E02 | M | 5 | 3 | 5 | US-08 | S2 |
| 9 | US-10 | Definição de pré-requisitos | E02 | M | 4 | 4 | 5 | US-09 | S3 |
| 10 | US-12 | Gestão do ano lectivo | E02 | M | 4 | 2 | 3 | — | S3 |
| 11 | US-11 | Criação de turmas | E02 | M | 5 | 3 | 5 | US-09, US-12 | S3 |
| 12 | US-13 | Inscrição do estudante em disciplinas | E03 | M | 5 | 5 | 13 | US-11, US-10 | S3 |
| 13 | US-16 | Definição das avaliações da turma | E04 | M | 5 | 3 | 5 | US-11 | S4 |
| 14 | US-17 | Lançamento de notas | E04 | M | 5 | 4 | 8 | US-16, US-13 | S4 |
| 15 | US-20 | Cálculo da frequência e da situação | E05 | M | 5 | 5 | 8 | US-17 | S4 |
| 16 | US-18 | Publicação das notas | E04 | M | 5 | 3 | 5 | US-17, US-20 | S4 |
| 17 | US-21 | Classificação final e média do curso | E05 | M | 5 | 4 | 5 | US-20 | S5 |
| 18 | US-22 | Consulta do aproveitamento académico | E05 | M | 5 | 2 | 5 | US-21 | S5 |
| 19 | US-24 | Emissão da pauta | E06 | M | 5 | 3 | 8 | US-20, US-21 | S5 |
| 20 | US-28 | Registo de auditoria | E08 | M | 4 | 3 | 5 | US-17, US-13 | S5 |
| 21 | US-19 | Rectificação de nota publicada | E04 | S | 4 | 4 | 8 | US-18, US-28 | S6 |
| 22 | US-14 | Anulação de inscrição | E03 | S | 3 | 2 | 3 | US-13 | S6 |
| 23 | US-15 | Inscrição administrativa | E03 | S | 3 | 3 | 5 | US-13 | S6 |
| 24 | US-23 | Acompanhamento pelo Coordenador | E05 | S | 4 | 2 | 5 | US-21 | S6 |
| 25 | US-26 | Relatório de aproveitamento por disciplina | E07 | S | 4 | 2 | 5 | US-21 | S6 |
| 26 | US-25 | Homologação da pauta | E06 | S | 3 | 2 | 3 | US-24 | S7 |
| 27 | US-27 | Painel inicial por perfil | E07 | S | 3 | 2 | 5 | US-21, US-13 | S7 |
| 28 | US-04 | Recuperação de palavra-passe | E01 | S | 3 | 2 | 3 | US-01 | S7 |
| 29 | US-29 | Notificação de publicação de notas | E08 | C | 3 | 3 | 5 | US-18 | S7 |
| 30 | US-30 | Notificação do período de inscrições | E08 | C | 2 | 2 | 3 | US-12, US-29 | S7 |

**Total estimado:** 163 pontos distribuídos por 7 sprints.

### 8.3. Alinhamento dos sprints com o cronograma da disciplina

| Sprint | Período | Foco | Entrega da disciplina |
|---|---|---|---|
| S0 | 28/07 – 13/08 | Definição do problema, stakeholders, constituição do grupo | Definição inicial do projecto |
| S1 | 18/08 – 27/08 | **Requisitos, User Stories, backlog** + base de acesso e autenticação | **Fase 1 (este documento)** |
| S2 | 01/09 – 10/09 | Modelação UML e modelo de dados; cadastros estruturantes | Fase 2 — Modelação |
| S3 | 22/09 – 01/10 | Arquitectura, API, turmas e inscrições | Fase 3 — Arquitectura e Design |
| S4 | 06/10 – 15/10 | Avaliações, lançamento de notas, cálculo de médias | Fase 4 — Implementação |
| S5 | 20/10 – 29/10 | Aproveitamento, pautas, auditoria, testes, Docker, CI/CD | Fase 5 e 6 — Testes, DevOps e segurança |
| S6 | 03/11 | Rectificações, relatórios, revisão geral e verificação dos requisitos | Revisão geral |
| S7 | 10/11 – 12/11 | Painéis, notificações, documentação e ensaio da defesa | Fase 7 — Documentação e apresentação |

### 8.4. Definição de MVP

O produto mínimo viável corresponde às histórias **US-01 a US-24, US-28** (todas as
classificadas como *Must have*). Este conjunto cobre integralmente as 13 funcionalidades
mínimas exigidas no enunciado do Projecto 2 e permite executar o ciclo académico completo:
registar um estudante, inscrevê-lo numa disciplina, lançar e publicar notas, calcular a
média e emitir a pauta.

### 8.5. Definição de Pronto (*Definition of Done*)

Uma história só é considerada concluída quando:

1. Todos os critérios de aceitação são verificáveis e verificados;
2. Existem testes automatizados que cobrem o caminho principal e, no mínimo, um caminho de
   excepção;
3. O código foi revisto por outro membro do grupo através de *pull request* aprovado;
4. O pipeline de CI é executado com sucesso (*linter*, testes e construção da imagem);
5. As autorizações por perfil foram testadas, incluindo a tentativa de acesso indevido;
6. A documentação da API foi actualizada;
7. A funcionalidade foi demonstrada e aceite pelo grupo em revisão de sprint.

---

## 9. Matriz de rastreabilidade

### 9.1. Objectivos → Requisitos → User Stories

| Objectivo | Requisitos funcionais | User Stories |
|---|---|---|
| O1 — Centralizar dados académicos | RF-08 a RF-28 | US-05 a US-12 |
| O2 — Automatizar cálculos | RF-44 a RF-50 | US-20, US-21 |
| O3 — Controlar o acesso por perfil | RF-01 a RF-07, RNF-08, RNF-12 | US-01, US-02, US-03 |
| O4 — Reduzir o tempo de emissão de pautas e de consulta | RF-48, RF-51 a RF-54 | US-22, US-24, US-25 |
| O5 — Garantir a rastreabilidade | RF-61 a RF-63, RNF-31, RNF-32 | US-19, US-28 |
| O6 — Apoiar a decisão dos coordenadores | RF-55 a RF-60 | US-23, US-26, US-27 |

### 9.2. Problemas → Requisitos

| Problema | Requisitos que o resolvem |
|---|---|
| P1 — Duplicação e dispersão de dados | RF-08, RF-09, RF-12, RNF-16 |
| P2 — Cálculo manual de médias | RF-44 a RF-47, RF-50 |
| P3 — Pautas heterogéneas | RF-51 a RF-54 |
| P4 — Ausência de controlo de acesso | RF-01 a RF-07, RNF-08, RNF-12 |
| P5 — Ausência de registo de alterações | RF-40, RF-61 a RF-63 |
| P6 — Consulta dependente da Secretaria | RF-33, RF-48 |
| P7 — Verificação manual de regras de inscrição | RF-24, RF-30, RF-34 |
| P8 — Relatórios produzidos caso a caso | RF-55 a RF-60 |

### 9.3. Funcionalidades mínimas do enunciado → Requisitos

| Funcionalidade mínima exigida | Requisitos |
|---|---|
| Cadastro de estudantes | RF-08 a RF-13 |
| Cadastro de docentes | RF-14 a RF-17 |
| Gestão de cursos | RF-18 a RF-22 |
| Gestão de disciplinas | RF-23 a RF-28 |
| Inscrição de estudantes em disciplinas | RF-29 a RF-34 |
| Gestão de avaliações | RF-35, RF-36, RF-43 |
| Registo de notas | RF-37 a RF-42 |
| Cálculo de médias | RF-44 a RF-47, RF-50 |
| Emissão de pautas | RF-51 a RF-54 |
| Consulta do aproveitamento académico | RF-48, RF-49 |
| Gestão de utilizadores | RF-03, RF-04 |
| Gestão de perfis e permissões | RF-07, RF-04 |
| Geração de relatórios | RF-55 a RF-60 |
| *Extensão opcional:* notificações | RF-64 a RF-68 |

---

## 10. Riscos identificados

| ID | Risco | Prob. | Impacto | Mitigação |
|---|---|---|---|---|
| R1 | Regras de avaliação mal compreendidas, levando a cálculos incorrectos | Alta | Alto | Documentar as regras RN-06 a RN-12 antes de codificar; validar com o Coordenador; cobrir com testes unitários de fronteira |
| R2 | Modelação inadequada da relação disciplina–turma–inscrição, impedindo o histórico por ano lectivo | Média | Alto | Introduzir a entidade Turma desde a Fase 2; revisão do modelo pelos três membros |
| R3 | Concentração do conhecimento num único membro do grupo | Alta | Alto | Revisão cruzada de *pull requests*; rotação de tarefas; sessões internas de apresentação |
| R4 | Atraso na implementação por acumulação de trabalho no fim do semestre | Alta | Alto | Sprints de duas semanas alinhados com o cronograma; Docker e pipeline de CI montados já em S1/S2 |
| R5 | Autorização verificada apenas na interface, deixando a API exposta | Média | Alto | Verificação obrigatória no servidor; testes automatizados de acesso indevido por perfil |
| R6 | Âmbito excessivo, com histórias opcionais a comprometerem o MVP | Média | Médio | MVP explicitamente delimitado (secção 8.4); histórias *Could have* apenas após o MVP concluído |
| R7 | Dados de teste insuficientes para demonstrar desempenho | Média | Baixo | *Seeders* com volume realista desde S2 |
| R8 | Indisponibilidade de um membro do grupo por período prolongado | Baixa | Alto | Documentação técnica no repositório; ambiente reproduzível por Docker |

---

## 11. Glossário

| Termo | Definição |
|---|---|
| **Ano lectivo** | Período anual de funcionamento da instituição, dividido em dois semestres. |
| **Ano curricular** | Ano do plano de estudos em que uma disciplina é ministrada (1.º, 2.º, …). |
| **Aproveitamento académico** | Conjunto das classificações, créditos e situações do estudante ao longo do percurso. |
| **Avaliação** | Instrumento de aferição de conhecimentos com peso definido na nota de frequência. |
| **Classificação final** | Nota final da disciplina, resultante da frequência ou do exame, na escala de 0 a 20. |
| **Coorte** | Conjunto dos estudantes que ingressaram no mesmo ano lectivo e a que se aplica uma dada versão do plano curricular. |
| **Créditos** | Unidade de medida do volume de trabalho associado a uma disciplina. |
| **Dispensa** | Situação do estudante que, pela nota de frequência, fica isento de exame. |
| **Frequência** | Nota resultante da média ponderada das avaliações realizadas durante o semestre. |
| **Homologação** | Acto pelo qual o Coordenador valida a pauta, tornando as classificações definitivas. |
| **Inscrição** | Registo formal do estudante numa turma de uma disciplina, num ano lectivo e semestre. |
| **Pauta** | Documento oficial com a lista dos estudantes de uma turma e as respectivas classificações. |
| **Perfil (papel)** | Conjunto de permissões atribuído a um utilizador (Administrador, Coordenador, Docente, Estudante). |
| **Plano curricular** | Estrutura de disciplinas de um curso, organizada por ano e semestre. |
| **Pré-requisito** | Disciplina cuja aprovação é condição de inscrição noutra disciplina. |
| **Rectificação** | Alteração autorizada de uma classificação já publicada, sujeita a justificação e auditoria. |
| **Turma** | Instância de oferta de uma disciplina num ano lectivo e semestre, com docente responsável e capacidade máxima. |

---

## Controlo do documento

| Versão | Data | Autores | Alterações |
|---|---|---|---|
| 1.0 | 2026-08-17 | Grupo 2 | Versão inicial da Fase 1 — Engenharia de Requisitos |

**Aprovação:** documento sujeito a validação pelo docente da disciplina e pelos
stakeholders SH-03 e SH-04.
