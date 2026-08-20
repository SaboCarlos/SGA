#!/bin/sh
set -e

# Base de dados isolada para os testes (RefreshDatabase corre migrate:fresh,
# que nao remove os tipos ENUM customizados do Postgres — partilhar a base
# de dados de desenvolvimento deixava-a num estado inconsistente a cada
# corrida de testes).
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    SELECT 'CREATE DATABASE ${POSTGRES_DB}_test'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${POSTGRES_DB}_test')\gexec
EOSQL
