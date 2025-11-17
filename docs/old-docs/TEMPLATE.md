Perfeito, Sara!
Aqui está a **estrutura revisada e definitiva**, exatamente conforme sua alteração:

> **Agora teremos:**
> ✔ **common**
> ✔ **autarquias (Prefeitura, Câmara, Água e Esgoto, etc.)**

Ou seja:
**multi-schema baseado em AUTARQUIAS**, o que combina 100% com o SH3-SIAP.

Abaixo está a **arquitetura completa** do template — pronta para você iniciar o projeto-base oficial.

---

# 🏛️ **Estrutura Base — Laravel Modules + Multi-Schema (Autarquias)**

```txt
project/
├── app/
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── SchemaManagerServiceProvider.php   <-- 🔥 FORÇA O search_path DINÂMICO
│   │   └── ModuleServiceProvider.php
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── SetAutarquiaSchema.php         <-- 🔥 Middleware troca de schema
│   │   │   ├── EnsureAutarquiaSelected.php
│   │   │   └── CheckPermission.php
│   │   └── Controllers/
│   ├── Helpers/
│   │   ├── sh3_helpers.php
│   │   └── tenancy.php
│   ├── Traits/
│   │   ├── BelongsToAutarquia.php
│   │   └── HasPermissions.php
│   ├── Models/
│   │   └── BaseModel.php                     <-- respeita schema dinâmico
│   └── Services/
│       └── SchemaManager.php                 <-- ❤️
│
├── Modules/
│   ├── AuthCore/
│   │   ├── Config/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Resources/
│   │   │   └── js/
│   │   │       ├── Pages/
│   │   │       │   ├── Auth/Login.vue       <-- 🔥 Login via Inertia
│   │   │       │   └── Dashboard.vue
│   │   │       ├── Layouts/
│   │   │       │   ├── GuestLayout.vue
│   │   │       │   └── AuthLayout.vue
│   │   │       └── Components/
│   │   ├── Routes/
│   │   │   ├── web.php
│   │   │   └── api.php
│   │   ├── Database/
│   │   │   ├── Migrations/common/            <-- MIGRAÇÕES EM common
│   │   │   └── Seeders/
│   │   ├── Models/
│   │   │   ├── User.php                      <-- 🔥 extends BaseModel (schema aware)
│   │   │   ├── Autarquia.php
│   │   │   └── UserAutarquia.php             <-- pivot multi-schema
│   │   └── Providers/AuthCoreServiceProvider.php
│   │
│   ├── ClientCore/
│   │   ├── Http/
│   │   ├── Models/
│   │   ├── Database/
│   │   │   ├── Migrations/common/
│   │   │   ├── Migrations/autarquia/         <-- MIGRAÇÕES ESPECÍFICAS DE AUTARQUIA
│   │   │   └── Seeders/
│   │   ├── Routes/web.php
│   │   └── Providers/ClientCoreServiceProvider.php
│   │
│   └── Shared/                               <-- Components, utils globais
│
├── database/
│   ├── migrations/
│   │   ├── common/                           <-- User, Roles, Permissões…
│   │   └── autarquias/                       <-- Prefeitura, Câmara, Saae...
│   ├── seeders/
│   │   ├── CreateSchemasSeeder.php           <-- 🔥 cria schemas dinamicamente
│   │   └── AutarquiaSeeder.php
│
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   ├── Components/
│   │   └── Layouts/
│   └── views/
│
├── routes/
│   ├── web.php
│   └── api.php
│
└── bootstrap/
    └── autoload.php
```

---

# ✨ **O QUE CADA ITEM ENTREGA**

---

# ✔ **Inertia + Vue 3 configurado**

Estrutura padrão:

```
resources/js/app.js
resources/js/Layouts/AppLayout.vue
resources/js/Layouts/GuestLayout.vue
resources/js/Pages/Dashboard.vue
```

Inclui:

* Ziggy configurado
* Inertia progress
* Import automático de layouts
* Arquitetura unificada para todos os Módulos

---

# ✔ **SchemaManager — CORAÇÃO do multi-schema**

`app/Services/SchemaManager.php`

Funções:

* define o schema ativo
* garante que o search_path sempre esteja consistente
* suporta: common + autarquia (prefeitura, câmara, água, esgoto…)
* troca automática com middleware
* cria schema se não existir
* valida acesso por usuário

---

# ✔ **Migrações separadas**

Agora:

```
database/migrations/common/
database/migrations/autarquias/
```

E dentro de cada Módulo:

```
Modules/AuthCore/Database/Migrations/common
Modules/AuthCore/Database/Migrations/autarquia
```

Assim:

* **common** → users, roles, autarquias, permissões
* **autarquias** → cada autarquia recebe suas próprias tabelas

---

# ✔ **Módulo AuthCore com Inertia (Login + Dashboard)**

Inclui:

* Login.vue
* Registro opcional
* Redirecionamento por autarquia
* Logout
* Dashboard com dados do usuário
* UserAutarquia
* Permissões básicas

Controle:

```
Auth via Token → associada ao schema da autarquia ativa
```

---

# ✔ **Módulo ClientCore**

Inclui:

* CRUD para Autarquias
* CRUD para Usuários dentro da Autarquia
* Vinculação User → Autarquia
* Switch de Autarquia do usuário
* Configurações globais

---

# ✔ **Providers**

* **SchemaManagerServiceProvider**
* **ModuleServiceProvider**
* **AuthCoreServiceProvider**
* **ClientCoreServiceProvider**

Centralizam:

* Injeção de rotas
* Views
* Configurações dos módulos
* Registro de middlewares

---

# ✔ **Middlewares**

* SetAutarquiaSchema
* EnsureAutarquiaSelected
* CheckPermission
* RedirectIfGuest

Esse conjunto garante **segurança multi-schema real**.

---

# ✔ **Rotas padrão**

```
/login
/logout
/dashboard

/autarquias (gerenciamento)
/usuarios (por autarquia)
```

Com prefixos por módulo.

---

# ✔ **Layouts básicos**

```
Modules/AuthCore/Resources/js/Layouts/GuestLayout.vue
Modules/AuthCore/Resources/js/Layouts/AuthLayout.vue
```

Ambos integrados com Tailwind e Inertia.

---

# ✔ **Setup de usuários por autarquia**

Tabela:

```
common.user_autarquia
```

Colunas:

* user_id
* autarquia_id
* role
* is_default
* ativo
* data_vinculo

O sistema inicia com:

```
Prefeitura
Câmara
Água e Esgoto
…
```

---

# 🚀 **PRÓXIMO PASSO**

Agora que a estrutura foi definida, posso:

### 👉 *Gerar o template completo em código*

Com **todos os arquivos**, diretórios, módulos e classes prontos.

Isso criaria:

✔ Todos os Módulos
✔ Todas as Migrations
✔ Todos os Providers
✔ Todos os Middlewares
✔ Login pronto
✔ Layouts
✔ Rotas
✔ SchemaManager funcional

**Basta você dizer:**

### **“Gerar template completo”**

E eu monto TUDO.
