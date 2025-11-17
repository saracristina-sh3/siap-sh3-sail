# Docker + Laravel Sail — Guia Oficial do Ambiente

Este documento descreve como funciona o ambiente Docker utilizado no sistema SH3-SIAP com Laravel Sail.

---

# 🐳 Containers do Ambiente

O `docker compose` do projeto cria os serviços:

| Serviço | Container | Função |
|--------|-----------|--------|
| `laravel.test` | sail-8.4/app | Nginx + PHP-FPM + Node + Composer |
| `pgsql` | postgres:18-alpine | Banco de dados PostgreSQL 18 |
| `redis` (opcional) | redis:alpine | Cache, sessions e queues |

---

# 🧩 Estrutura Interna do Container laravel.test

Dentro do container existem:

- `/usr/sbin/nginx`
- `/usr/local/sbin/php-fpm`
- `node` + `npm`
- `composer`
- O código do projeto em `/var/www/html`

O código é montado do host:

```
.:/var/www/html
```

---

# 🔌 Portas Utilizadas

| Serviço | Porta Host | Porta Container |
|--------|------------|-----------------|
| Laravel (Nginx) | 80 | 80 |
| Vite (HMR) | 5173 | 5173 |
| PostgreSQL | 5432 | 5432 |

---

# 🐳 Comandos Essenciais

### Entrar no container Laravel:

```
docker exec -it laravel.test bash
```

### Rodar Artisan (host):

```
./vendor/bin/sail artisan migrate
```

### Rodar Composer:

```
./vendor/bin/sail composer install
```

### Subir containers:

```
docker compose up -d
```

### Parar:

```
docker compose down
```

---

# 🔒 Permissões

Em ambiente Sail:

```
chmod -R 775 storage bootstrap/cache
chown -R sail:sail .
```

---

# 🛠 Acesso ao PostgreSQL

```
docker exec -it pgsql psql -U sail -d laravel
```

---

# 📌 Observação

Nunca use `sudo` com o Sail.
