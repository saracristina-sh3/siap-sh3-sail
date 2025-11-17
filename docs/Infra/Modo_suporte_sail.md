# Modo Suporte no Ambiente Laravel Sail

---

# 🧩 Como funciona

O modo suporte funciona normalmente dentro de containers, pois:

- tokens são independentes
- cada request é isolado
- o search_path é definido por conexão

---

# 🔐 Endpoints

```
POST /api/support/assume-context
POST /api/support/exit-context
```

---

# 🛡 Logs

Armazenados em:

```
storage/logs/laravel.log
```

---

# 🧠 Frontend

O token de suporte continua funcionando com Vite → Inertia.
