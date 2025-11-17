  Visão Geral do Projeto SH3 SIAP

  🏗️ Stack Tecnológica

  - Framework: Laravel 12.0 (PHP 8.4)
  - Database: PostgreSQL 17 com multi-tenancy baseado em schemas
  - Módulos: nwidart/laravel-modules v12.0
  - Autenticação: Laravel Sanctum + JWT (tymon/jwt-auth)
  - Container: Docker Compose (SAIL + PostgreSQL)

  ---
  📦 Arquitetura Modular

  Atualmente existe 1 módulo ativo: AuthCore em Modules/AuthCore/

  Modules/AuthCore/
  ├── app/
  │   ├── Http/Controllers/     (8 controllers)
  │   ├── Models/              (User, Autarquia, Modulo, Role, Permission)
  │   ├── Services/            (Lógica de negócio)
  │   ├── Repositories/        (Acesso a dados)
  │   └── Providers/
  ├── database/migrations/     (10 migrations)
  ├── routes/api.php
  └── module.json

  ---
  🐘 Multi-Tenancy com PostgreSQL Schemas

  Estratégia de Isolamento:
  - Schema common: Dados compartilhados (usuários, roles, autarquias, municípios)
  - Schemas por Autarquia: Dados isolados (ex: pref_santa_cruz_de_minas)

  Resolução de Tenant (app/Http/Middleware/TenantResolver.php):
  1. Session autarquia_ativa_id (usuário troca de autarquia)
  2. SuperAdmin em modo suporte
  3. User autarquia_preferida_id (fallback)
  4. Schema common (sem autarquia)

  Gestão de Schemas (app/Services/SchemaManager.php):
  SchemaManager::setSchema($schema);  // SET search_path TO "$schema", public

  ---
  🐳 Configuração Docker

  docker-compose.yaml define 3 serviços:

  services:
    db:                    # PostgreSQL 17
      port: 5432
      volume: db_data

    app:                   # PHP-FPM 8.4
      port: 9000
      user: suporteSH3:suporteSH3 (1000:1000)
      entrypoint: .docker/start.sh

    nginx:                 # Nginx
      port: 3080:80
      proxy: /api/* → app:9000 (FastCGI)
             /* → SPA frontend

  Dockerfile instala:
  - PHP 8.4-FPM com extensões: pdo_pgsql, zip
  - Node.js 22 LTS
  - Composer latest
  - Locale pt_BR.UTF-8

  ---
  🔌 APIs RESTful

  Principais endpoints (routes/api.php + Modules/AuthCore/routes/api.php):

  POST   /api/auth/login           - Login (CPF + senha)
  POST   /api/auth/refresh         - Renovar token
  POST   /api/auth/logout          - Logout
  GET    /api/auth/me              - Dados do usuário

  GET    /api/users                - Listar usuários
  POST   /api/users                - Criar usuário
  GET    /api/users/{id}           - Detalhe
  PUT    /api/users/{id}           - Atualizar
  POST   /api/users/{id}/autarquias/attach  - Vincular autarquias

  GET    /api/autarquias           - Listar autarquias
  GET    /api/autarquias/{id}/modulos - Módulos da autarquia

  GET    /api/session/active-autarquia  - Obter autarquia ativa
  POST   /api/session/active-autarquia  - Definir autarquia ativa

  ---
  🔐 Autenticação & Autorização

  Fluxo de Login:
  1. POST /api/auth/login com CPF + senha
  2. AuthenticationService valida credenciais
  3. TokenService gera JWT access_token + refresh_token
  4. Cliente usa Authorization: Bearer {token} nas requisições

  Middleware Stack (bootstrap/app.php:109-115):
  $middleware->api(append: [
      TenantResolver::class,          // 1. Resolve autarquia ativa
      SetSchemaConnection::class,     // 2. Define schema PostgreSQL
  ]);

  $middleware->alias([
      'check_permission' => CheckPermission::class,
      'validate_autarquia' => ValidateActiveAutarquia::class,
  ]);

  Sistema de Permissões:
  - Roles (admin, user, etc.)
  - Permissions granulares por módulo
  - Pivot table user_autarquia com metadados

  ---
  🗄️ Models Principais

  User (Modules/AuthCore/app/Models/User.php):
  - cpf, nome, email, password
  - is_suporteSH3, is_active
  - municipio_id, autarquia_preferida_id
  - refresh_token, refresh_token_expires_at

  Relationships:
  - roles(), permissions(), autarquias()
  - municipio(), autarquiaPreferida()

  Autarquia (Modules/AuthCore/app/Models/Autarquia.php):
  - municipio_id, nome, cnpj, tipo
  - schema_name ← CRÍTICO para multi-tenancy
  - is_active

  Relationships:
  - municipio(), usuarios(), modulos()

  ---
  📊 Fluxo de Requisição

  1. Request → Nginx:3080
     ↓
  2. Nginx:
     - /api/* → PHP-FPM:9000
     - /* → Frontend SPA
     ↓
  3. Middleware:
     - TenantResolver → resolve autarquia ativa
     - SetSchemaConnection → SET search_path
     - auth:sanctum → valida token
     - validate_autarquia → valida header
     ↓
  4. Controller → Service → Repository
     ↓
  5. Query ao PostgreSQL (schema correto)
     ↓
  6. Response JSON

  ---
  🔧 Comandos Úteis

  # Setup completo
  docker-compose up -d
  docker-compose exec app php artisan migrate

  # Desenvolvimento
  docker-compose exec app php artisan tinker
  docker-compose exec app php artisan route:list
  docker-compose logs -f app

  # Testes
  docker-compose exec app php artisan test

  ---
  📁 Arquivos Críticos

  | Arquivo                                      | Descrição                 |
  |----------------------------------------------|---------------------------|
  | app/Http/Middleware/TenantResolver.php:17-92 | Resolução de tenant       |
  | app/Services/SchemaManager.php:7-49          | Gestão schemas PostgreSQL |
  | Modules/AuthCore/routes/api.php:6-76         | Definição rotas API       |
  | bootstrap/app.php:109-115                    | Configuração middleware   |
  | docker-compose.yaml:1-89                     | Orquestração serviços     |
  | Modules/AuthCore/database/migrations/        | Estrutura do banco        |

  ---
  O projeto utiliza uma arquitetura modular bem estruturada com isolamento de dados por schema PostgreSQL, ideal para sistemas multi-tenant onde cada autarquia (prefeitura, câmara, etc.)
   tem seus dados completamente separados.
