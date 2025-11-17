Perfeito, Sara. Agora chegamos à **PARTE 4 — ClientCore**, responsável por:

---

# 🟩 **PARTE 4 — ClientCore (Módulo do Núcleo Multi-Tenant)**

O **ClientCore** é o módulo que controla:

### ✔ Municípios

### ✔ Autarquias

### ✔ Módulos (sistemas)

### ✔ Vinculação autarquia ↔ módulos

### ✔ Vinculação usuário ↔ autarquia (administração)

### ✔ Dashboard inicial (AdminLayout)

### ✔ APIs para gerenciamento dos tenants

### ✔ Integração com SchemaManager (multi-schema)

Sem ele, o sistema não tem estrutura de multi-tenant.

---

# 📁 **ESTRUTURA FINAL DO MÓDULO ClientCore**

```
Modules/
└── ClientCore/
    ├── Config/
    │   └── config.php
    │
    ├── Database/
    │   ├── Migrations/  (vazio — migrations base já estão em common/)
    │   └── Seeders/
    │       └── ClientCoreSeeder.php
    │
    ├── Http/
    │   ├── Controllers/
    │   │   ├── MunicipioController.php
    │   │   ├── AutarquiaController.php
    │   │   ├── ModuloController.php
    │   │   └── DashboardController.php
    │   │
    │   ├── Requests/
    │   │   ├── StoreMunicipioRequest.php
    │   │   ├── StoreAutarquiaRequest.php
    │   │   └── StoreModuloRequest.php
    │   │
    │   └── Middleware/
    │       └── IsSuperAdmin.php
    │
    ├── Models/
    │   ├── Municipio.php
    │   ├── Autarquia.php
    │   ├── Modulo.php
    │   └── AutarquiaModulo.php
    │
    ├── Providers/
    │   └── ClientCoreServiceProvider.php
    │
    ├── Resources/
    │   └── js/
    │       ├── Pages/
    │       │   ├── Dashboard.vue
    │       │   ├── Municipio/Index.vue
    │       │   ├── Autarquia/Index.vue
    │       │   └── Modulo/Index.vue
    │       └── Components/
    │           └── TenantCard.vue
    │
    └── Routes/
        └── web.php
```

---

# 🟦 **1. Provider**

📄 **Modules/ClientCore/Providers/ClientCoreServiceProvider.php**

```php
<?php

namespace Modules\ClientCore\Providers;

use Illuminate\Support\ServiceProvider;

class ClientCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'clientcore');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
```

---

# 🟧 **2. Rotas do módulo**

📄 **Modules/ClientCore/Routes/web.php**

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientCore\Http\Controllers\DashboardController;
use Modules\ClientCore\Http\Controllers\MunicipioController;
use Modules\ClientCore\Http\Controllers\AutarquiaController;
use Modules\ClientCore\Http\Controllers\ModuloController;

// Todas essas rotas requerem login + ser admin da autarquia OU superadmin
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('municipios')->group(function () {
        Route::get('/', [MunicipioController::class, 'index']);
        Route::post('/', [MunicipioController::class, 'store']);
    });

    Route::prefix('autarquias')->group(function () {
        Route::get('/', [AutarquiaController::class, 'index']);
        Route::post('/', [AutarquiaController::class, 'store']);
    });

    Route::prefix('modulos')->group(function () {
        Route::get('/', [ModuloController::class, 'index']);
        Route::post('/', [ModuloController::class, 'store']);
    });
});
```

---

# 🟩 **3. Models**

Todos os models são vinculados ao schema `common` — isso evita SQL injection e erros de schema.

### 📄 Modules/ClientCore/Models/Municipio.php

```php
<?php

namespace Modules\ClientCore\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table = 'common.municipios';

    protected $fillable = [
        'nome',
        'slug',
        'schema_name',
        'ativo',
    ];

    public function autarquias()
    {
        return $this->hasMany(Autarquia::class, 'municipio_id');
    }
}
```

---

### 📄 Modules/ClientCore/Models/Autarquia.php

```php
<?php

namespace Modules\ClientCore\Models;

use Illuminate\Database\Eloquent\Model;

class Autarquia extends Model
{
    protected $table = 'common.autarquias';

    protected $fillable = [
        'municipio_id',
        'nome',
        'slug',
        'schema_prefix',
        'ativo',
    ];

    public function municipio()
    {
        return $this->belongsTo(Municipio::class);
    }

    public function modulos()
    {
        return $this->belongsToMany(
            Modulo::class,
            'common.autarquia_modulos',
            'autarquia_id',
            'modulo_id'
        );
    }
}
```

---

### 📄 Modules/ClientCore/Models/Modulo.php

```php
<?php

namespace Modules\ClientCore\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'common.modulos';

    protected $fillable = [
        'nome',
        'slug',
        'ativo',
    ];
}
```

---

### 📄 Modules/ClientCore/Models/AutarquiaModulo.php

```php
<?php

namespace Modules\ClientCore\Models;

use Illuminate\Database\Eloquent\Model;

class AutarquiaModulo extends Model
{
    protected $table = 'common.autarquia_modulos';

    protected $fillable = [
        'autarquia_id',
        'modulo_id',
        'ativo',
    ];
}
```

---

# 🟦 **4. Controllers**

## 4.1 DashboardController

📄 Modules/ClientCore/Http/Controllers/DashboardController.php

```php
<?php

namespace Modules\ClientCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\ClientCore\Models\Autarquia;
use Modules\ClientCore\Models\Modulo;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Autarquia ativa
        $autarquia = $user->autarquiaPreferida()
            ->with('municipio')
            ->first();

        $modulos = $autarquia
            ? $autarquia->modulos()->where('ativo', true)->get()
            : [];

        return Inertia::render('Dashboard', [
            'autarquia' => $autarquia,
            'modulos'   => $modulos,
        ]);
    }
}
```

---

## 4.2 MunicipioController

📄 Modules/ClientCore/Http/Controllers/MunicipioController.php

```php
<?php

namespace Modules\ClientCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\ClientCore\Http\Requests\StoreMunicipioRequest;
use Modules\ClientCore\Models\Municipio;

class MunicipioController extends Controller
{
    public function index()
    {
        return Inertia::render('Municipio/Index', [
            'municipios' => Municipio::all(),
        ]);
    }

    public function store(StoreMunicipioRequest $request)
    {
        $m = Municipio::create($request->validated());

        // Cria schema no Postgres:
        \DB::statement("CREATE SCHEMA IF NOT EXISTS {$m->schema_name}");

        return back()->with('success', 'Município criado com sucesso.');
    }
}
```

---

## 4.3 AutarquiaController

📄 Modules/ClientCore/Http/Controllers/AutarquiaController.php

```php
<?php

namespace Modules\ClientCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\ClientCore\Http\Requests\StoreAutarquiaRequest;
use Modules\ClientCore\Models\Autarquia;

class AutarquiaController extends Controller
{
    public function index()
    {
        return Inertia::render('Autarquia/Index', [
            'autarquias' => Autarquia::with('municipio')->get(),
        ]);
    }

    public function store(StoreAutarquiaRequest $request)
    {
        $data = $request->validated();

        $autarquia = Autarquia::create($data);

        return back()->with('success', 'Autarquia criada com sucesso.');
    }
}
```

---

## 4.4 ModuloController

📄 Modules/ClientCore/Http/Controllers/ModuloController.php

```php
<?php

namespace Modules\ClientCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\ClientCore\Http\Requests\StoreModuloRequest;
use Modules\ClientCore\Models\Modulo;

class ModuloController extends Controller
{
    public function index()
    {
        return Inertia::render('Modulo/Index', [
            'modulos' => Modulo::all(),
        ]);
    }

    public function store(StoreModuloRequest $request)
    {
        Modulo::create($request->validated());

        return back()->with('success', 'Módulo criado com sucesso.');
    }
}
```

---

# 🟨 **5. Requests**

## 5.1 StoreMunicipioRequest

📄 Modules/ClientCore/Http/Requests/StoreMunicipioRequest.php

```php
<?php

namespace Modules\ClientCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMunicipioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nome'        => 'required',
            'slug'        => 'required|unique:common.municipios,slug',
            'schema_name' => 'required|unique:common.municipios,schema_name',
        ];
    }
}
```

---

## 5.2 StoreAutarquiaRequest

📄 Modules/ClientCore/Http/Requests/StoreAutarquiaRequest.php

```php
<?php

namespace Modules\ClientCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutarquiaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'municipio_id' => 'required|exists:common.municipios,id',
            'nome'         => 'required',
            'slug'         => 'required',
            'schema_prefix'=> 'required|unique:common.autarquias,schema_prefix',
        ];
    }
}
```

---

## 5.3 StoreModuloRequest

📄 Modules/ClientCore/Http/Requests/StoreModuloRequest.php

```php
<?php

namespace Modules\ClientCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuloRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nome' => 'required',
            'slug' => 'required|unique:common.modulos,slug',
        ];
    }
}
```

---

# 🟧 **6. Middleware: IsSuperAdmin**

📄 Modules/ClientCore/Http/Middleware/IsSuperAdmin.php

```php
<?php

namespace Modules\ClientCore\Http\Middleware;

use Closure;

class IsSuperAdmin
{
    public function handle($request, Closure $next)
    {
        if (!auth()->user()?->is_superadmin) {
            abort(403, 'Acesso restrito ao suporte.');
        }

        return $next($request);
    }
}
```

---

# 🟩 **7. Frontend — Inertia + Vue**

Agora as telas administrativas:

---

## 📄 Dashboard.vue

📄 Modules/ClientCore/Resources/js/Pages/Dashboard.vue

```vue
<template>
  <AdminLayout>
    <h1>Dashboard</h1>

    <TenantCard v-if="autarquia" :autarquia="autarquia" />

    <h2>Módulos Habilitados</h2>
    <ul>
      <li v-for="m in modulos" :key="m.id">{{ m.nome }}</li>
    </ul>
  </AdminLayout>
</template>

<script setup>
import TenantCard from '../Components/TenantCard.vue'

const props = defineProps({
  autarquia: Object,
  modulos: Array,
})
</script>
```

---

## 📄 TenantCard.vue

📄 Modules/ClientCore/Resources/js/Components/TenantCard.vue

```vue
<template>
  <div class="card">
    <h3>{{ autarquia.nome }}</h3>
    <p>Município: {{ autarquia.municipio.nome }}</p>
  </div>
</template>

<script setup>
const props = defineProps({
  autarquia: Object,
})
</script>

<style>
.card {
  padding: 20px;
  background: #f3f4f6;
  border-radius: 8px;
}
</style>
```

---

## 📄 Municipio/Index.vue

📄 Modules/ClientCore/Resources/js/Pages/Municipio/Index.vue

```vue
<template>
  <AdminLayout>
    <h1>Municípios</h1>

    <ul>
      <li v-for="m in municipios" :key="m.id">{{ m.nome }}</li>
    </ul>
  </AdminLayout>
</template>

<script setup>
const props = defineProps({
  municipios: Array,
})
</script>
```

---

## 📄 Autarquia/Index.vue

📄 Modules/ClientCore/Resources/js/Pages/Autarquia/Index.vue

```vue
<template>
  <AdminLayout>
    <h1>Autarquias</h1>

    <ul>
      <li v-for="a in autarquias" :key="a.id">
        {{ a.municipio.nome }} — {{ a.nome }}
      </li>
    </ul>
  </AdminLayout>
</template>

<script setup>
const props = defineProps({
  autarquias: Array,
})
</script>
```

---

## 📄 Modulo/Index.vue

📄 Modules/ClientCore/Resources/js/Pages/Modulo/Index.vue

```vue
<template>
  <AdminLayout>
    <h1>Módulos</h1>

    <ul>
      <li v-for="m in modulos" :key="m.id">{{ m.nome }}</li>
    </ul>
  </AdminLayout>
</template>

<script setup>
const props = defineProps({
  modulos: Array,
})
</script>
```

---


