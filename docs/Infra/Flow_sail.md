# Fluxos Atualizados para Laravel Sail

Este documento atualiza os fluxos (diagramação) para ambiente containerizado.

---

# 🔐 Fluxo de Autenticação (Sail)

```mermaid
flowchart LR
    Browser --> Nginx
    Nginx --> PHPFPM
    PHPFPM --> Laravel
    Laravel --> DB[(PostgreSQL)]
    Laravel --> Browser
```

---

# 🛠 Fluxo de Modo Suporte

```mermaid
flowchart LR
    User --> Login
    Login --> Laravel
    Laravel --> DB
    Laravel --> TokenSuporte
    TokenSuporte --> Frontend
    Frontend --> AutarquiaContexto
    AutarquiaContexto --> Laravel
```

---

# 🏛 Fluxo Multi-Tenant

```mermaid
flowchart LR
    Token --> MiddlewareSchema
    MiddlewareSchema --> SchemaManager
    SchemaManager --> PostgreSQL
    PostgreSQL --> SchemaTenant
```
