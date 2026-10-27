# Implantacao do SGA

Arquitectura de producao:

```
   Browser
      |
      |  https://sga.vercel.app          (SPA React, ficheiros estaticos)
      v
   Vercel  ──── chamadas XHR ────►  https://api.SEU-DOMINIO.com
                                            |
                                     Apache do host (TLS, Let's Encrypt)
                                            |  http://127.0.0.1:8100
                                            v
                                     Nginx (contentor sga-prod-proxy)
                                            |  fastcgi
                                            v
                                     PHP-FPM (sga-prod-api) ──► Postgres, Redis
                                                                 worker, scheduler
```

Duas notas que explicam porque e que esta separacao e simples neste projecto:

- a autenticacao usa **token Bearer em `localStorage`**, nao cookies de
  sessao. Frontend e API em dominios diferentes nao levantam problemas de
  `SameSite` nem de CSRF; basta autorizar a origem no CORS;
- **nada da stack fica exposto directamente**: o `docker-compose.prod.yml`
  publica a porta apenas em `127.0.0.1`. A unica porta de entrada e o
  Apache, que ja serve os outros projectos da VPS.

---

## Parte 1 — Backend na VPS

### 1.1 Pre-requisitos

```bash
docker --version          # >= 24
docker compose version    # >= 2.20
```

Se o Docker nao estiver instalado:

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER   # sair e voltar a entrar na sessao
```

### 1.2 Clonar o projecto

```bash
sudo mkdir -p /opt/sga && sudo chown $USER:$USER /opt/sga
git clone <URL-DO-REPOSITORIO> /opt/sga
cd /opt/sga
```

### 1.3 Configurar

Sao dois ficheiros distintos, com papeis diferentes:

| Ficheiro | Para que serve |
|---|---|
| `.env.producao` | variaveis do **Docker Compose** (porta, credenciais do contentor Postgres) |
| `backend/.env.producao` | configuracao do **Laravel** |

```bash
cp .env.producao.example .env.producao
cp backend/.env.producao.example backend/.env.producao

# Palavra-passe da base de dados: tem de ser a MESMA nos dois ficheiros.
SENHA=$(openssl rand -hex 24)
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$SENHA|" .env.producao
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$SENHA|" backend/.env.producao
```

Editar `backend/.env.producao` e preencher:

- `APP_URL` — `https://api.SEU-DOMINIO.com`
- `CORS_ORIGINS` — o dominio do Vercel (preencher depois da Parte 2)
- `MAIL_*` — servidor SMTP real (o mailpit so existe em desenvolvimento)

Se a porta `8100` ja estiver ocupada por outro projecto, mudar `PORTA_HTTP`
em `.env.producao` **e** o `ProxyPass` em `deploy/apache-sga.conf`.

Gerar a chave da aplicacao:

```bash
make chave-producao
```

> `APP_KEY` cifra dados em repouso. Depois de existirem dados, trocar a
> chave torna-os ilegiveis — por isso o comando recusa-se a substituir uma
> chave ja definida.

### 1.4 Arrancar

```bash
make instalar-producao
```

Levanta a stack, corre as migracoes e carrega o cenario inicial. Verificar:

```bash
make estado-producao
curl -i http://127.0.0.1:8100/up      # deve responder 200
```

### 1.5 Expor via Apache

```bash
sudo a2enmod proxy proxy_http headers ssl
sudo cp deploy/apache-sga.conf /etc/apache2/sites-available/sga.conf
sudo sed -i 's/api.SEU-DOMINIO.com/api.oteudominio.com/g' \
     /etc/apache2/sites-available/sga.conf
sudo a2ensite sga
sudo apache2ctl configtest      # confirmar "Syntax OK" ANTES de recarregar
sudo systemctl reload apache2
```

Este vhost e um ficheiro novo e independente: nao toca em nenhum dos
projectos que ja estao na VPS.

Antes deste passo, o subdominio `api.oteudominio.com` tem de ter um registo
DNS `A` a apontar para o IP da VPS (a propagacao pode levar minutos).

Certificado TLS:

```bash
sudo certbot --apache -d api.oteudominio.com
```

O certbot reescreve `sga.conf` sozinho: cria o bloco `:443` e converte o
`:80` num redireccionamento. **Depois disto, nao voltar a copiar
`deploy/apache-sga.conf` por cima** — perder-se-ia a configuracao TLS.

Confirmar de fora da VPS:

```bash
curl -i https://api.oteudominio.com/up
```

---

## Parte 2 — Frontend no Vercel

1. **Importar o repositorio** em <https://vercel.com/new>.

2. **Root Directory: `frontend`** — este e o passo que mais falha. O repo e
   um monorepo; sem isto o Vercel tenta construir a raiz e nao encontra
   `package.json`. O resto (framework Vite, `npm ci`, `dist`) ja vem de
   `frontend/vercel.json`.

3. **Environment Variables** — adicionar, para Production, Preview e
   Development:

   | Nome | Valor |
   |---|---|
   | `VITE_API_URL` | `https://api.oteudominio.com/api/v1` |

   O `/api/v1` no fim faz parte do valor: o cliente concatena os caminhos
   directamente (`${BASE_URL}/auth/login`).

   As `VITE_*` sao embutidas no bundle **durante o build** — mudar o valor
   exige um redeploy, e nada aqui pode ser secreto (fica visivel no codigo
   servido ao browser).

4. **Deploy**, e tomar nota do dominio atribuido (ex.: `sga.vercel.app`).

5. **Fechar o circulo do CORS**, na VPS:

   ```bash
   cd /opt/sga
   sed -i 's|^CORS_ORIGINS=.*|CORS_ORIGINS=https://sga.vercel.app|' \
       backend/.env.producao
   make publicar
   ```

   Enquanto `CORS_PERMITIR_PREVIEWS_VERCEL=true`, qualquer subdominio
   `*.vercel.app` e aceite — comodo para testar branches. Convem passar a
   `false` quando o dominio final estiver fixado.

### Verificacao final

Abrir o site do Vercel, entrar com `admin@escn.ac.mz` / `palavra-passe`, e
confirmar na consola do browser que nao ha erros de CORS. Trocar a
palavra-passe do administrador logo a seguir.

---

## Operacao corrente

| Comando | O que faz |
|---|---|
| `make publicar` | `git pull`, reconstroi, migra e reinicia |
| `make estado-producao` | estado dos contentores |
| `make registos-producao` | registos em tempo real |
| `make copia-seguranca` | dump comprimido da base de dados para `copias/` |
| `make parar-producao` | para a stack (os dados persistem nos volumes) |

O frontend nao precisa de nada: cada push para `main` dispara um deploy
automatico no Vercel.

### Copias de seguranca

`make copia-seguranca` gera o dump, mas nao o agenda. Para uma copia diaria:

```bash
crontab -e
# 0 3 * * * cd /opt/sga && make copia-seguranca >> /var/log/sga-copias.log 2>&1
```

Restaurar:

```bash
gunzip -c copias/sga-AAAAMMDD-HHMMSS.sql.gz | \
  docker compose -f docker-compose.prod.yml --env-file .env.producao \
  exec -T db psql -U sga -d sga
```

---

## Diagnostico

**502 no Apache** — a stack esta em baixo ou a porta nao bate certo.
Comparar `PORTA_HTTP` em `.env.producao` com o `ProxyPass` do vhost, e
correr `make estado-producao`.

**Erro de CORS na consola do browser** — a origem do Vercel nao consta em
`CORS_ORIGINS`. Confirmar com:

```bash
curl -i -X OPTIONS https://api.oteudominio.com/api/v1/auth/login \
     -H "Origin: https://sga.vercel.app" \
     -H "Access-Control-Request-Method: POST"
```

Uma resposta com `Access-Control-Allow-Origin` significa que o backend esta
correcto. Sem esse cabecalho, corrigir `CORS_ORIGINS` e `make publicar`.

**Alteracao ao `.env.producao` sem efeito** — em producao o arranque corre
`config:cache`, e a config em cache so e reconstruida quando o contentor
reinicia. `make publicar` ja o faz; em alternativa:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.producao restart api worker scheduler
```

Pela mesma razao, **nenhum codigo fora de `config/` pode chamar `env()`** —
devolveria sempre o valor por omissao. Usar `config()`. O PHPStan (larastan)
apanha estes casos: `make lint`.

**Mudancas no frontend nao aparecem** — o `VITE_API_URL` e embutido no
build. Depois de o alterar no Vercel, e preciso um redeploy.

**As duas stacks** — `docker-compose.yml` (desenvolvimento, projecto `sga`)
e `docker-compose.prod.yml` (producao, projecto `sga-producao`) usam nomes
de projecto, de contentores e de volumes distintos, e podem coexistir na
mesma maquina sem interferencia.
