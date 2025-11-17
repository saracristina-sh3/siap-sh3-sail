# Multi-Tenant com PostgreSQL + Laravel Sail

Descreve como o multi-tenant schema-based funciona dentro do ambiente Docker/Sail.

---

# 🏛 Banco Multi-Tenant (vários schemas)

Exemplo:

```
public (ou common)
prefeitura_santacruz
camara_santacruz
agua_santacruz
```

---

# 🔀 Schema Switching dentro do Container

Cada request executa:

```sql
SET search_path TO prefeitura_santacruz, public;
```

---

# 🧱 Arquivos Responsáveis

### `/app/Http/Middleware/SetClientSchema.php`
Define o search_path a partir do token/autarquia ativa.

### `/app/Services/SchemaManager.php`
Responsável por:

- montar o search_path
- validar se o schema existe
- forçar retorno ao schema common quando necessário

---

# 📌 Funcionamento no Sail

- As conexões DB são abertas dentro do container
- Cada conexão tem search_path isolado
- Não há interferência entre requests
- Módulos podem acessar schemas certos automaticamente

---

# 🗂 Estrutura Recomendada
```
Modules/
 ├── AuthCore/
 ├── ClientCore/
 ├── Frota/
 ├── Patrimonio/
 └── …
```
