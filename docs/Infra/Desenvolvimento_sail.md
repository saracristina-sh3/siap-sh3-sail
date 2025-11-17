# Guia de Desenvolvimento com Laravel Sail

---

# 🖥 Requisitos

- Docker
- Docker Compose
- Git
- VSCode recomendado

---

# 🏗 Inicializando o Ambiente

```
docker compose up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail artisan key:generate
```

---

# 🧩 Rodando Vite

```
./vendor/bin/sail npm run dev
```

---

# 🔥 Rodando Migrations Multi-Tenant

```
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan module:migrate AuthCore
```

---

# 👨‍💻 Rodando no Container

```
docker exec -it laravel.test bash
```

---

# 🧪 Testes

```
./vendor/bin/sail artisan test
```

---

# 🔥 Hot Reload (HMR)

Navegador → Vite → Container → Atualização automática.
