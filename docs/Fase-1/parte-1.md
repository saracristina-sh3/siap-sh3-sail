Vamos começar pelo **núcleo** do projeto:

> ✅ Lista de arquivos **+** conteúdo completo
> ✅ Organizado por partes
> ✅ OPÇÃO A (cada sistema é um módulo separado)
> ✅ Multi-município + multi-autarquia + multi-sistemas

---

## 🟦 PARTE 1 – Núcleo Laravel (App/Core)

### Visão geral dos arquivos desta parte

Crie estes arquivos:

1. `app/Services/SchemaManager.php`
2. `app/Models/BaseModel.php`
3. `app/Providers/SchemaManagerServiceProvider.php`
4. `app/Http/Middleware/SetTenantContext.php`
5. `app/Http/Middleware/EnsureTenantSelected.php`
6. `app/Helpers/sh3_helpers.php`

Depois, vamos conectar tudo no `Kernel` e `composer.json` (isso entra mais à frente na PARTE 8, mas já deixo um spoilerzinho no fim).

---

### 1️⃣ `app/Services/SchemaManager.php`

> Responsável por:
>
> * controlar o schema do município (`santa_cruz_de_minas`, `barroso`, `tiradentes`, etc.)
> * controlar o prefixo da autarquia (`pref_santa_cruz_de_minas`, `cam_barroso` etc.)
> * gerar o nome completo das tabelas por autarquia (`pref_santa_cruz_de_minas_veiculos`)

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SchemaManager
{
    public const COMMON_SCHEMA = 'common';

    private const SESSION_MUNICIPIO_SCHEMA = 'tenant_municipio_schema';
    private const SESSION_AUTARQUIA_PREFIX = 'tenant_autarquia_prefix';

    /**
     * Define o schema "common" como padrão.
     */
    public static function setCommon(): void
    {
        DB::statement('SET search_path TO '.self::COMMON_SCHEMA.', public');
        Session::forget(self::SESSION_MUNICIPIO_SCHEMA);
    }

    /**
     * Define o schema do município (santa_cruz_de_minas, barroso, tiradentes, etc.).
     */
    public static function setMunicipioSchema(string $schema): void
    {
        Session::put(self::SESSION_MUNICIPIO_SCHEMA, $schema);

        DB::statement(sprintf(
            'SET search_path TO %s, %s, public',
            $schema,
            self::COMMON_SCHEMA
        ));
    }

    /**
     * Define o prefixo da autarquia (ex: pref_santa_cruz_de_minas, cam_barroso).
     */
    public static function setAutarquiaPrefix(?string $prefix): void
    {
        if ($prefix) {
            Session::put(self::SESSION_AUTARQUIA_PREFIX, $prefix);
        } else {
            Session::forget(self::SESSION_AUTARQUIA_PREFIX);
        }
    }

    /**
     * Retorna o schema atual do município.
     */
    public static function currentMunicipioSchema(): string
    {
        return Session::get(self::SESSION_MUNICIPIO_SCHEMA, self::COMMON_SCHEMA);
    }

    /**
     * Retorna o prefixo atual da autarquia.
     */
    public static function currentAutarquiaPrefix(): ?string
    {
        return Session::get(self::SESSION_AUTARQUIA_PREFIX);
    }

    /**
     * Gera o nome completo da tabela para uma autarquia:
     * ex: pref_santa_cruz_de_minas_veiculos
     */
    public static function fullTable(string $baseTableName): string
    {
        $prefix = self::currentAutarquiaPrefix();

        if (! $prefix) {
            throw new \RuntimeException('Autarquia não definida no contexto da sessão.');
        }

        return $prefix.'_'.$baseTableName;
    }

    /**
     * Limpa o contexto de tenant e volta para common.
     */
    public static function clear(): void
    {
        Session::forget(self::SESSION_MUNICIPIO_SCHEMA);
        Session::forget(self::SESSION_AUTARQUIA_PREFIX);

        self::setCommon();
    }
}
```

---

### 2️⃣ `app/Models/BaseModel.php`

> Base para todos os models dos módulos.
> Quando você marcar `$usesAutarquiaPrefix = true` num model, ele monta a tabela como `pref_santa_cruz_de_minas_veiculos`.

```php
<?php

namespace App\Models;

use App\Services\SchemaManager;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * Se true, o model usará o prefixo da autarquia no nome da tabela.
     *
     * Exemplo:
     *   tabela base: veiculos
     *   prefixo: pref_santa_cruz_de_minas
     *   tabela final: pref_santa_cruz_de_minas_veiculos
     */
    protected bool $usesAutarquiaPrefix = false;

    public function getTable()
    {
        $table = parent::getTable();

        if ($this->usesAutarquiaPrefix) {
            return SchemaManager::fullTable($table);
        }

        return $table;
    }
}
```

> Nos módulos (Frota, Patrimônio etc.), é só fazer:
>
> ```php
> class Veiculo extends BaseModel {
>     protected $table = 'veiculos';
>     protected bool $usesAutarquiaPrefix = true;
> }
> ```

---

### 3️⃣ `app/Providers/SchemaManagerServiceProvider.php`

> Garante que:
>
> * no console (migrations/seeders) o schema `common` está setado
> * em requests HTTP, quem manda é o middleware (não forçamos nada aqui)

```php
<?php

namespace App\Providers;

use App\Services\SchemaManager;
use Illuminate\Support\ServiceProvider;

class SchemaManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->runningInConsole()) {
            // Em comandos Artisan, usar sempre o schema common por segurança.
            SchemaManager::setCommon();
        }
    }
}
```

> Depois vamos registrar este provider em `config/app.php` (mais pra frente, na parte de configs).

---

### 4️⃣ `app/Http/Middleware/SetTenantContext.php`

> Esse é o middleware que:
>
> * lê o usuário autenticado
> * pega `municipio` e `autarquia preferida`
> * configura o schema e prefixo de autarquia no `SchemaManager`

Vou assumir que você terá (depois, no AuthCore):

* `User` relacionado a `Municipio` e `Autarquia`
* Campos:

  * `$user->municipio?->schema_name` → ex: `santa_cruz_de_minas`
  * `$user->autarquiaPreferida?->schema_prefix` → ex: `pref_santa_cruz_de_minas`

```php
<?php

namespace App\Http\Middleware;

use App\Services\SchemaManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            // Usuário não autenticado → contexto common
            SchemaManager::setCommon();
            return $next($request);
        }

        $municipio = $user->municipio ?? null;
        $autarquia = $user->autarquiaPreferida ?? null;

        if ($municipio && $municipio->schema_name) {
            SchemaManager::setMunicipioSchema($municipio->schema_name);
        } else {
            // fallback para common se algo estiver inconsistente
            SchemaManager::setCommon();
        }

        if ($autarquia && $autarquia->schema_prefix) {
            SchemaManager::setAutarquiaPrefix($autarquia->schema_prefix);
        } else {
            SchemaManager::setAutarquiaPrefix(null);
        }

        return $next($request);
    }
}
```

> No AuthCore/ClientCore a gente depois cria os relacionamentos `municipio` e `autarquiaPreferida`.

---

### 5️⃣ `app/Http/Middleware/EnsureTenantSelected.php`

> Middleware para garantir que, após login:
>
> * se o usuário ainda não escolheu município/autarquia, manda para uma tela de seleção via Inertia
> * se já escolheu tudo, segue o fluxo normal

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Aqui vamos assumir que o usuário tem:
        // - municipio_preferido_id
        // - autarquia_preferida_id
        // Esses campos serão criados depois nas migrations + AuthCore.

        if (! $user->municipio_preferido_id || ! $user->autarquia_preferida_id) {
            return Inertia::render('Auth/SelectTenant', [
                'municipios'  => $user->municipios()->get(['id', 'nome']),
                'autarquias'  => $user->autarquias()->get(['id', 'nome', 'municipio_id']),
            ]);
        }

        return $next($request);
    }
}
```

> A página `Auth/SelectTenant.vue` entra depois na parte de **Inertia/AuthCore**.

---

### 6️⃣ `app/Helpers/sh3_helpers.php`

> Helpers globais para usar em qualquer lugar (Blade, Controllers, Módulos).

> Não esquece: esse arquivo precisa estar registrado em `composer.json` (autoload.files) – faço isso na parte de configs mais pra frente, mas já deixo pronto aqui.

```php
<?php

use App\Services\SchemaManager;
use Illuminate\Support\Facades\Auth;

if (! function_exists('tenant_municipio_schema')) {
    function tenant_municipio_schema(): string
    {
        return SchemaManager::currentMunicipioSchema();
    }
}

if (! function_exists('tenant_autarquia_prefix')) {
    function tenant_autarquia_prefix(): ?string
    {
        return SchemaManager::currentAutarquiaPrefix();
    }
}

if (! function_exists('tenant_table')) {
    /**
     * Gera o nome completo da tabela para a autarquia atual.
     * Ex: tenant_table('veiculos') => pref_santa_cruz_de_minas_veiculos
     */
    function tenant_table(string $baseTableName): string
    {
        return SchemaManager::fullTable($baseTableName);
    }
}

if (! function_exists('current_user')) {
    function current_user(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return Auth::user();
    }
}
```

---

## 🔗 (Spoiler rápido) – Onde isso se encaixa

Mais à frente (na parte de configs), vamos:

* Registrar o `SchemaManagerServiceProvider` no `config/app.php`
* Registrar os helpers no `composer.json`:

```jsonc
"autoload": {
  "files": [
    "app/Helpers/sh3_helpers.php"
  ]
}
```

* Adicionar os middlewares no `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \App\Http\Middleware\SetTenantContext::class,
    ],
];

protected $routeMiddleware = [
    // ...
    'tenant.selected' => \App\Http\Middleware\EnsureTenantSelected::class,
];
```

---

## ✅ Recap PARTE 1 (concluída)

Você agora tem:

* ✅ `SchemaManager` multi-nível (common + município + prefixo de autarquia)
* ✅ `BaseModel` que sabe usar prefixo por autarquia
* ✅ Middleware para aplicar contexto (schema + autarquia)
* ✅ Middleware para garantir seleção de município/autarquia
* ✅ Helpers globais para facilitar tudo

---

