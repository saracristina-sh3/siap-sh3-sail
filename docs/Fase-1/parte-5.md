Perfeito, Sara. Agora chegamos na **PARTE MAIS IMPORTANTE E SENSÍVEL DO SIAP**:

# 💠 **PARTE 5 — SchemaManager + Tenant Middleware Final**

É aqui que acontece a mágica do sistema:

> **Cada request do usuário é automaticamente redirecionada para o schema correto do município e prefixo correto da autarquia usada.**

E também:

> **O modo SUPORTE (SupportCore) sobrescreve tudo para assumir qualquer autarquia.**

Esta é a camada que torna o SH3-SIAP realmente **multi-tenant**, **multi-município**, **multi-autarquia**, **multi-sistema** — tudo dentro de **um único banco PostgreSQL**.

Vamos criar:

---

# 📁 **ESTRUTURA COMPLETA DO PARTE 5**

```
app/
├── Services/
│   └── SchemaManager.php
│
└── Http/
    └── Middleware/
        ├── SetTenantContext.php
        └── EnsureSchemaIsSet.php   (middleware opcional de validação)
```

E também:

* Configuração no **Kernel**
* Ajuste final no **User model**
* Helpers para acessar o tenant atual

---

# 🟦 1. SchemaManager (SERVIÇO PRINCIPAL)

📄 **app/Services/SchemaManager.php**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchemaManager
{
    // Schema atual: santa_cruz_de_minas / barroso / tiradentes
    protected static ?string $currentSchema = null;

    // Prefixo atual da autarquia: pref_, cam_, saude_, aguaesgoto_
    protected static ?string $currentPrefix = null;

    // -------------------------------------------
    //  SETTERS
    // -------------------------------------------

    public static function setCommon(): void
    {
        static::$currentSchema = 'common';
        DB::statement('SET search_path TO common, public');
    }

    public static function setMunicipioSchema(string $schema): void
    {
        static::$currentSchema = $schema;

        DB::statement("SET search_path TO {$schema}, public");
    }

    public static function setAutarquiaPrefix(string $prefix): void
    {
        static::$currentPrefix = $prefix;
    }

    public static function clear(): void
    {
        static::$currentSchema = null;
        static::$currentPrefix = null;

        DB::statement('SET search_path TO common, public');
    }

    // -------------------------------------------
    //  GETTERS
    // -------------------------------------------

    public static function schema(): ?string
    {
        return static::$currentSchema;
    }

    public static function prefix(): ?string
    {
        return static::$currentPrefix;
    }

    public static function fullTable(string $table): string
    {
        if (! static::$currentPrefix) {
            return $table;
        }

        return static::$currentPrefix . '_' . $table;
    }
}
```

---

# 🟩 2. Middleware: SetTenantContext

Este MIDDLEWARE é o **cérebro** do multi-tenant.

Ele:

### ✔ Se o usuário estiver em `support_mode = true`, usa o `support_autarquia_id`

### ✔ Se não, usa o `autarquia_preferida_id`

### ✔ Ajusta o search_path para o SJHEMA correto

### ✔ Ajusta o prefixo para as tabelas da autarquia

📄 **app/Http/Middleware/SetTenantContext.php**

```php
<?php

namespace App\Http\Middleware;

use App\Models\Autarquia;
use App\Services\SchemaManager;
use Closure;
use Illuminate\Http\Request;

class SetTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Request sem autenticação → usa apenas schema common
        if (!$user) {
            SchemaManager::setCommon();
            return $next($request);
        }

        // ============================
        // 1. MODO SUPORTE
        // ============================
        if ($user->support_mode && $user->support_autarquia_id) {
            $autarquia = Autarquia::with('municipio')
                ->where('ativo', true)
                ->find($user->support_autarquia_id);

            if ($autarquia) {
                SchemaManager::setMunicipioSchema($autarquia->municipio->schema_name);
                SchemaManager::setAutarquiaPrefix($autarquia->schema_prefix);

                return $next($request);
            }
        }

        // ============================
        // 2. CONTEXTO NORMAL DO USUÁRIO
        // ============================

        $autarquia = null;

        if ($user->autarquia_preferida_id) {
            $autarquia = Autarquia::with('municipio')
                ->where('ativo', true)
                ->find($user->autarquia_preferida_id);
        }

        if ($autarquia) {
            SchemaManager::setMunicipioSchema($autarquia->municipio->schema_name);
            SchemaManager::setAutarquiaPrefix($autarquia->schema_prefix);
        } else {
            SchemaManager::setCommon();
        }

        return $next($request);
    }
}
```

Esse arquivo é **responsável por 100% do isolamento multi-tenant**.

---

# 🟪 3. Middleware opcional: EnsureSchemaIsSet

Recomendado em produção:

📄 **app/Http/Middleware/EnsureSchemaIsSet.php**

```php
<?php

namespace App\Http\Middleware;

use App\Services\SchemaManager;
use Closure;

class EnsureSchemaIsSet
{
    public function handle($request, Closure $next)
    {
        if (! SchemaManager::schema()) {
            SchemaManager::setCommon();
        }

        return $next($request);
    }
}
```

---

# 🟧 4. Registrar Middlewares no Kernel

📄 **app/Http/Kernel.php** (trecho)

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\EnsureSchemaIsSet::class,
];

protected $middlewareGroups = [
    'web' => [
        // ...
        \App\Http\Middleware\SetTenantContext::class,
    ],

    'api' => [
        // ...
        \App\Http\Middleware\SetTenantContext::class,
    ],
];
```

---

# 🟩 5. Atualização do Model User

Precisamos adicionar métodos úteis:

📄 **app/Models/User.php**

```php
public function autarquias()
{
    return $this->belongsToMany(
        \Modules\ClientCore\Models\Autarquia::class,
        'common.user_autarquia',
        'user_id',
        'autarquia_id'
    );
}

public function autarquiaPreferida()
{
    return $this->belongsTo(
        \Modules\ClientCore\Models\Autarquia::class,
        'autarquia_preferida_id'
    );
}

public function supportAutarquia()
{
    return $this->belongsTo(
        \Modules\ClientCore\Models\Autarquia::class,
        'support_autarquia_id'
    );
}
```

---

# 🟦 6. Ajuste global para Inertia (SupportBar)

📄 **app/Http/Middleware/HandleInertiaRequests.php**

(adicione no `share()`)

```php
'support' => $user && $user->support_mode ? [
    'active'    => true,
    'autarquia' => optional($user->supportAutarquia)->nome,
] : [
    'active' => false
],
```

Agora o componente **SupportBar.vue** exibe corretamente o modo suporte.

---

# 🟩 **7. Como funciona o SchemaManager na prática?**

### Quando o usuário faz login e escolhe a autarquia:

```
SET search_path TO santa_cruz_de_minas, public
prefix = pref_santa_cruz_de_minas
```

Então, quando o módulo FROTA faz uma query assim:

```
->from(SchemaManager::fullTable('veiculos'))
```

O sistema transforma para:

```
FROM pref_santa_cruz_de_minas_veiculos
```

Isso garante:

* total isolamento de dados
* cada autarquia tem SUAS tabelas
* mas sem precisar criar milhares de schemas por autarquia
* município = schema
* autarquia = prefixo

Arquitetura perfeita para prefeitura.

---


