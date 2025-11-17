# Gerenciamento de Módulos com Nwidart em Ambiente Sail

---

# 📦 Criar um Módulo

```
./vendor/bin/sail artisan module:make Frota
```

---

# 🧩 Migrations do Módulo

```
./vendor/bin/sail artisan module:migrate Frota
```

---

# 🚀 Publicar Configs

```
./vendor/bin/sail artisan module:publish-config Frota
```

---

# 🧱 Estrutura

```
Modules/
 ├── Frota/
 │    ├── Config/
 │    ├── Http/
 │    ├── Models/
 │    ├── Routes/
 │    └── database/migrations
```
