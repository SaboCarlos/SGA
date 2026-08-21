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
