# Manual de Utilização — SGA

**Projecto 2 — Sistema de Gestão Académica (SGA)**
Escola Superior de Ciências Náuticas — Engenharia de Software — Grupo 2

Este manual explica como usar o SGA no dia a dia, perfil a perfil. Para instalar o sistema,
ver o `README.md`.

---

## Índice

1. [Entrar no sistema](#1-entrar-no-sistema)
2. [O ecrã principal](#2-o-ecrã-principal)
3. [Estudante](#3-estudante)
4. [Docente](#4-docente)
5. [Coordenador do curso](#5-coordenador-do-curso)
6. [Administrador](#6-administrador)
7. [Mensagens de erro frequentes](#7-mensagens-de-erro-frequentes)

---

## 1. Entrar no sistema

1. Abra o endereço do SGA no browser (em desenvolvimento, `http://localhost:8000`).
2. Escreva o e-mail institucional e a palavra-passe e carregue em **Entrar**.

**Esqueceu-se da palavra-passe?** Carregue em **Esqueceu-se da palavra-passe?**, escreva o
e-mail e siga o link que vai receber. O link só funciona uma vez. Por segurança, a mensagem de
confirmação é sempre a mesma, exista ou não uma conta com esse e-mail.

**Conta bloqueada.** Depois de 5 tentativas falhadas seguidas, a conta fica bloqueada durante
15 minutos. O ecrã indica quanto tempo falta.

**Fim de sessão.** A sessão termina sozinha ao fim de 60 minutos. Para sair antes, use
**Terminar sessão**, no canto superior direito.

---

## 2. O ecrã principal

- **Barra lateral (à esquerda):** só mostra as opções do seu perfil. No telemóvel, abre-se com o
  botão **Menu**.
- **Cabeçalho:** o seu nome e perfil, o botão de tema (claro, escuro ou o do sistema) e
  **Terminar sessão**.
- **Painel:** a página inicial, com indicadores e atalhos próprios do seu perfil. Quem tem mais
  do que um perfil (por exemplo, Docente e Coordenador) vê os indicadores de todos.
- **Notificações:** avisos do sistema, como a publicação de notas ou a abertura de um período de
  inscrições. Os avisos por ler têm contorno dourado; ao clicar num aviso, fica marcado como lido.

---

## 3. Estudante

### 3.1. Inscrever-se em disciplinas

Menu **Inscrições**. Só funciona enquanto o período de inscrições do semestre estiver aberto.

1. A lista mostra apenas as turmas a que pode concorrer. Ficam de fora as disciplinas que não
   são do seu plano curricular, as que têm pré-requisitos ainda por fazer e as turmas sem vagas.
2. Seleccione as turmas pretendidas.
3. Carregue em **Inscrever (n)**.
4. Aparece o comprovativo da inscrição. Guarde-o.

Se a inscrição for recusada, a mensagem indica o motivo e o código da regra (por exemplo,
`RN-04` quando a turma esgotou entretanto).

### 3.2. Ver as minhas notas

Menu **As minhas notas**. No topo aparecem o curso, **o ano em que está** (1.º, 2.º…) e o ano
lectivo. Por baixo, as **disciplinas desse ano**; as de anos lectivos anteriores ficam numa
secção à parte. Cada disciplina tem:

- cada avaliação (testes, trabalhos, exame), o seu peso e a nota obtida;
- a nota de frequência, a situação (Admitido a exame, Dispensado ou Excluído) e a classificação
  final;
- o resultado da disciplina: Em curso, Aprovado ou Reprovado.

Uma nota aparece como **por publicar** enquanto o docente não a publicar. Só vê as suas notas;
as dos colegas nunca são mostradas.

### 3.3. Pedir a rectificação de uma nota

1. Em **As minhas notas**, carregue em **Pedir rectificação** ao lado da nota em causa.
2. Indique o valor que considera correcto e escreva a justificação (mínimo de 20 caracteres).
3. Carregue em **Solicitar**.

Acompanhe o pedido no menu **Rectificações**: fica **PENDENTE** até o Coordenador do curso o
aprovar ou recusar. Se for aprovado, a nota é actualizada automaticamente.

---

## 4. Docente

O menu **As minhas disciplinas** mostra apenas as disciplinas que lecciona. Em cada linha tem
três opções: **Alunos** (lista dos alunos inscritos), **Lançar notas** (definir as avaliações e
lançar as notas) e **Pauta**. Disciplinas de outros docentes não aparecem nem podem ser abertas.

### 4.1. Definir as avaliações de uma turma

1. Menu **As minhas disciplinas** e, na linha da disciplina, **Lançar notas**.
2. Carregue em **Nova avaliação** e indique a designação (por exemplo, "Teste 1"), o tipo
   (Teste, Trabalho, Prática ou Exame), o peso em percentagem e a data prevista.
3. Carregue em **Definir avaliação**.

No topo aparece a soma dos pesos. A estrutura só é válida quando somar **100%**.

Só o docente responsável pela turma (ou o Administrador) pode definir avaliações e lançar notas.

### 4.2. Lançar e publicar notas

1. Na lista de avaliações, carregue em **Lançar notas** na avaliação pretendida. Aparece a lista
   de todos os alunos inscritos.
2. Escreva a nota de cada aluno (0 a 20) ou marque **Faltou**.
3. **Gravar notas** guarda as notas sem as mostrar aos alunos. Pode voltar quantas vezes quiser
   e alterá-las; a avaliação fica "Em lançamento".
4. **Publicar** grava o que está na grelha, torna as notas visíveis aos alunos e calcula
   automaticamente a nota de frequência e a situação de cada um.

Nas avaliações já publicadas, o botão passa a **Ver notas** e a grelha abre só para consulta.

Se houver estudantes sem nota nem falta, o sistema pergunta se quer publicar mesmo assim.

**Uma nota publicada não pode ser editada.** Para a corrigir, é preciso um pedido de
rectificação (menu **Rectificações**), decidido pelo Coordenador do curso, e a alteração fica
registada na auditoria.

### 4.3. Pauta da turma

Menu **As minhas disciplinas** e, na linha da disciplina, **Pauta**. Mostra todos os inscritos com a nota de
frequência e a situação, o total de aprovados e a taxa de aprovação.

- **Descarregar PDF** gera a pauta para imprimir ou arquivar.
- **Homologar** torna a pauta definitiva. Só é possível quando todos os estudantes tiverem nota
  ou marca de falta.

---

## 5. Coordenador do curso

O Coordenador tem tudo o que o Docente tem, mais o seguinte.

### 5.1. Decidir pedidos de rectificação

Menu **Rectificações**. O coordenador vê os pedidos de todas as disciplinas do curso que
coordena; cada pedido mostra o estudante, a disciplina, a avaliação, a nota anterior, a nota
pedida e a justificação. Os botões de decisão só aparecem a quem pode decidir esse pedido. Os
outros docentes vêem apenas os pedidos das suas disciplinas, só para consulta.

- **Aprovar** altera a nota e regista a alteração na auditoria.
- **Recusar** exige que escreva o motivo no campo **Motivo**.

Só o Coordenador do curso a que a turma pertence pode decidir.

### 5.2. Estudantes e aproveitamento

Menu **Estudantes**. Pode pesquisar por nome ou número e registar novos estudantes (**Novo
estudante**). Ao registar, o sistema cria a conta de acesso do estudante e atribui o número.

O link **Aproveitamento** de cada estudante mostra a média ponderada, os créditos obtidos, a
percentagem de conclusão do curso e o histórico por ano lectivo e semestre.

### 5.3. Relatórios e auditoria

- **Relatórios:** aproveitamento por disciplina (inscritos, aprovados, reprovados, taxa de
  aprovação e média), que pode descarregar em **PDF** ou **CSV**.
- **Auditoria:** registo das acções sensíveis (quem, quando, valor anterior e novo). Pode
  filtrar por entidade, por exemplo `classificacao`. A auditoria só pode ser consultada; ninguém
  a pode alterar.

---

## 6. Administrador

O Administrador tem acesso a todas as funcionalidades acima e ainda à gestão da instituição.

| Menu | Para que serve |
|---|---|
| **Docentes** | Registar docentes (cria também a conta de acesso) |
| **Cursos** | Criar cursos e montar o plano curricular (que disciplinas, em que ano e semestre) |
| **Disciplinas** | Criar disciplinas (créditos, carga horária) e definir pré-requisitos |
| **Turmas** | Criar turmas para um período lectivo, com docente e capacidade máxima |
| **Ano lectivo** | Criar o ano lectivo, os períodos (semestres, com datas de inscrição) e **Activar** ou **Encerrar** o ano |
| **Utilizadores** | Criar contas, atribuir perfis e desactivar contas (a desactivação termina de imediato todas as sessões abertas) |
| **Perfis e permissões** | Criar perfis e permissões novos e atribuí-los ou retirá-los |

**Ordem recomendada no início de um ano lectivo:**

1. **Ano lectivo** → criar o ano e os períodos, com as datas de inscrição, e activá-lo.
2. **Disciplinas** e **Cursos** → confirmar o plano curricular.
3. **Docentes** → registar quem falta.
4. **Turmas** → abrir as turmas do semestre.
5. **Estudantes** → registar os novos estudantes.

A partir daí, os estudantes inscrevem-se sozinhos durante o período de inscrições.

---

## 7. Mensagens de erro frequentes

| Mensagem | O que significa | O que fazer |
|---|---|---|
| "O período de inscrições não está aberto." | Fora das datas definidas no ano lectivo | Aguardar ou contactar a secretaria |
| `RN-04` — turma esgotada | Alguém ocupou a última vaga antes de si | Escolher outra turma |
| "Conta bloqueada temporariamente…" | 5 tentativas de login falhadas | Esperar 15 minutos ou recuperar a palavra-passe |
| "O seu perfil não permite esta operação." | A acção não está disponível para o seu perfil | Pedir a quem tem o perfil adequado |
| "Estas notas já foram publicadas…" | A avaliação já foi publicada | Usar um pedido de rectificação |
| "Existem N estudantes sem classificação nem marca de falta." | A pauta ainda não está completa | Lançar as notas em falta antes de homologar |
