.PHONY: ajuda arrancar parar instalar migrar seed testes cobertura lint dominio pauta-limpa testes-frontend

ajuda:            ## Lista os comandos disponiveis
	@grep -E '^[a-z-]+:.*##' $(MAKEFILE_LIST) | sed 's/:.*##/\t/'

arrancar:         ## Levanta todo o ambiente (RNF-27)
	docker compose up -d --build
	docker compose exec api composer install
	docker compose exec api chmod -R o+rwX storage bootstrap/cache
	docker compose exec api php artisan migrate --seed
	@echo "SGA disponivel em http://localhost:8000"

parar:            ## Para os contentores
	docker compose down

instalar:         ## Primeira instalacao numa maquina limpa
	cp -n .env.example .env || true
	cp -n backend/.env.example backend/.env || true
	docker compose up -d --build
	docker compose exec api composer install
	docker compose exec api chmod -R o+rwX storage bootstrap/cache
	docker compose exec api php artisan key:generate
	docker compose exec api php artisan migrate --seed

migrar:           ## Executa as migracoes
	docker compose exec api php artisan migrate

seed:             ## Carrega dados de demonstracao (RNF-30)
	docker compose exec api php artisan db:seed

testes:           ## Suite completa (unitarios, integracao, arquitectura)
	docker compose exec api ./vendor/bin/pest

cobertura:        ## Relatorio de cobertura (minimo 70% global, 90% no dominio)
	docker compose exec api ./vendor/bin/pest --coverage --min=70

lint:             ## Estilo de codigo e analise estatica (backend + frontend)
	docker compose exec api ./vendor/bin/pint --test
	docker compose exec api ./vendor/bin/phpstan analyse --memory-limit=512M
	cd frontend && npm run lint

testes-frontend:  ## Suite Vitest do frontend
	cd frontend && npm test

dominio:          ## Verificacao das regras de dominio sem dependencias
	php verificar-dominio.php

repor-dados:      ## Apaga tudo e volta aos dados iniciais da instituicao
	docker compose exec api php artisan migrate:fresh --drop-types --seed

# ---------------------------------------------------------------------------
# Producao (VPS). Ver docs/implantacao.md.
# Todos estes alvos usam docker-compose.prod.yml -- nunca tocam no ambiente
# de desenvolvimento acima.
# ---------------------------------------------------------------------------

PROD := docker compose -f docker-compose.prod.yml --env-file .env.producao

.PHONY: instalar-producao publicar chave-producao estado-producao registos-producao parar-producao copia-seguranca

instalar-producao: ## VPS: primeira instalacao (depois de preencher os .env.producao)
	@test -f .env.producao || { echo "Falta .env.producao (cp .env.producao.example .env.producao)"; exit 1; }
	@test -f backend/.env.producao || { echo "Falta backend/.env.producao"; exit 1; }
	$(PROD) up -d --build
	$(PROD) exec -T api php artisan migrate --force
	$(PROD) exec -T api php artisan db:seed --force
	@echo "SGA a correr em http://127.0.0.1 na porta definida em PORTA_HTTP"

publicar:         ## VPS: publica a versao mais recente (pull, rebuild, migrar)
	git pull --ff-only
	$(PROD) up -d --build
	$(PROD) exec -T api php artisan migrate --force
	@echo "Publicado. Estado:" && $(PROD) ps

chave-producao:   ## VPS: gera o APP_KEY e escreve-o em backend/.env.producao
	@grep -q '^APP_KEY=.\+' backend/.env.producao \
	  && echo "APP_KEY ja definido -- nao vou substituir (invalidaria dados cifrados)." \
	  || $(PROD) run --rm --no-deps api php artisan key:generate --force --show \
	     | tail -n1 | xargs -I{} sed -i 's|^APP_KEY=.*|APP_KEY={}|' backend/.env.producao
	@grep '^APP_KEY=' backend/.env.producao

estado-producao:  ## VPS: estado dos contentores
	$(PROD) ps

registos-producao: ## VPS: registos em tempo real (Ctrl-C para sair)
	$(PROD) logs -f --tail=100

parar-producao:   ## VPS: para a stack (os dados persistem nos volumes)
	$(PROD) down

copia-seguranca:  ## VPS: dump da base de dados para copias/
	@mkdir -p copias
	@# As credenciais vem de .env.producao, nao do ambiente da shell: sem
	@# este "set -a; . ...", um DB_USERNAME personalizado era ignorado e o
	@# pg_dump falhava com "role sga does not exist".
	@set -a; . ./.env.producao; set +a; \
	  $(PROD) exec -T db pg_dump -U "$$DB_USERNAME" "$$DB_DATABASE" \
	  | gzip > copias/sga-$$(date +%Y%m%d-%H%M%S).sql.gz
	@ls -lh copias | tail -n1
