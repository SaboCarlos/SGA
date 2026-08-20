# SGA — Sistema de Gestão Académica

Projecto 2 — Engenharia de Software — Escola Superior de Ciências Náuticas, 2026
Curso: Programação de Sistemas Informáticos e de Telecomunicações — Grupo 2

Este repositório é o início da **Fase 4 (Implementação)**, construído sobre os artefactos
das Fases 1–3: `docs/fase1-requisitos.md`, `docs/fase2-modelacao.md` (com `schema.sql`) e
`docs/fase3-arquitectura.md` (com `openapi.yaml`).

## Estado actual

O que já existe e está verificado, não apenas planeado:

| Peça | Estado | Como verificar |
|---|---|---|
| Domínio: regulamento de avaliação (RN-06 a RN-12) | Implementado, 46 asserções a passar | `php verificar-dominio.php` |
| Domínio: validação de inscrição (RN-01 a RN-05) | Implementado, coberto no mesmo script | `php verificar-dominio.php` |
| Migrações (a partir do `schema.sql` da Fase 2) | Escritas, sintaxe validada | `docker compose exec api php artisan migrate` |
| `docker-compose.yml` + Dockerfiles | Escrito, sintaxe e imagens-base válidas | `docker compose config` |
| Frontend (Vite + React + TS + Tailwind) | Compila e produz bundle de 54 KB gzip | `cd frontend && npm install && npm run build` |
| Testes de arquitectura (regra de camadas) | Escritos (Pest Arch) | `./vendor/bin/pest tests/Arquitectura` |
| Pipeline de CI | Escrito (GitHub Actions) | `.github/workflows/ci.yml` |

**O que ainda não existe:** os controladores HTTP, os modelos Eloquent, os `FormRequests` e
as `API Resources` da camada de interface (camada 2 da Fase 3). O domínio e a base de dados
— as duas partes onde reside o risco de negócio — estão prontos e testados; ligar a API REST
por cima é trabalho mecânico e é o próximo passo natural do backlog (US-16 em diante).

## Arrancar o ambiente

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec api composer install
docker compose exec api php artisan key:generate
docker compose exec api php artisan migrate --seed
```

Ou, com o `Makefile` incluído:

```bash
make instalar
```

O SGA fica acessível em `http://localhost:8000`.

## Verificar o domínio sem Docker

As regras de negócio não dependem de Laravel nem de base de dados — podem ser verificadas
apenas com PHP 8.3, o que é útil em qualquer máquina, mesmo sem Docker instalado:

```bash
php verificar-dominio.php
```

Deve terminar com `Total: 46 | Passou: 46 | Falhou: 0`.

## Estrutura

Ver `docs/fase3-arquitectura.md`, secção 5, para a estrutura completa e a justificação de
cada camada. Resumo:

```
backend/app/Dominio/          # regras de negócio puras, sem Laravel (camada 4)
backend/app/Servicos/         # casos de uso, orquestração (camada 3)
backend/app/Infraestrutura/   # implementações concretas de repositório (camada 5)
backend/app/Http/             # ainda por construir (camada 2)
backend/database/migrations/  # convertidas do schema.sql da Fase 2
backend/tests/Unit/           # regras de domínio, sem base de dados
backend/tests/Arquitectura/   # impede violação da regra de dependência das camadas
frontend/src/                 # SPA React (Vite + TypeScript + Tailwind)
docker/                       # Dockerfiles e configuração do Nginx
docs/                         # relatórios das Fases 1–3 e diagramas
```

## Próximos passos (backlog da Fase 1, secção 8.2)

1. `composer install` real e primeiro `php artisan migrate` contra PostgreSQL (validar as
   migrações da forma como o `schema.sql` já foi validado na Fase 2).
2. Módulo Acesso: `AuthController`, Sanctum, `PolicyAvaliacao`, `PolicyInscricao`.
3. Cadastros estruturantes: controladores CRUD simples (sem repositório — ver ADR-006).
4. `InscricaoController` sobre `ServicoInscricao` (já existe o `ValidadorInscricao`).
5. `AvaliacaoController` sobre `ServicoAvaliacao` (já existe, com o Strategy).
6. Ecrãs React: painel por perfil, inscrição, lançamento de notas.

## Nota sobre as constantes do regulamento

`app/Dominio/Regras/Regulamento/RegulamentoPadraoESCN.php` usa os limiares 14,0 e 10,0
como premissa da Fase 1 (PR-03), ainda por confirmar junto do Coordenador do curso. Alterar
estes valores não exige tocar em mais nenhum ficheiro — é precisamente o que o padrão
Strategy (ADR-004) foi desenhado para garantir.
