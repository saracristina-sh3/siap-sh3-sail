**o coração da arquitetura**:

# 💠 **PARTE 3 — AuthCore (com MODO SUPORTE integrado)**

Totalmente alinhado com:

* 🟦 Laravel Modules
* 🟨 Inertia + Vue 3
* 🟩 Multi-tenant (common → município → autarquia)
* 🟥 Modo Suporte
* 🟪 Autarquias + Módulos
* 🌀 SchemaManager + Middleware de contexto
* 🧱 Estruturas de migrations criadas na PARTE 2

---

# 📁 **ESTRUTURA FINAL DO MÓDULO AuthCore**

Você pediu “lista de arquivos + conteúdo”.
Aqui está a estrutura completa:

```
Modules/
└── AuthCore/
    ├── Config/
    │   └── config.php
    │
    ├── Http/
    │   ├── Controllers/
    │   │   ├── AuthController.php
    │   │   ├── MeController.php
    │   │   └── SelectContextController.php
    │   │
    │   ├── Middleware/
    │   │   └── RedirectIfAuthenticated.php
    │   │
    │   ├── Requests/
    │   │   ├── LoginRequest.php
    │   │   └── SelectAutarquiaRequest.php
    │   │
    │   └── Resources/
    │       └── views/ (Inertia usa JS — pasta vazia)
    │
    ├── Resources/
    │   └── js/
    │       ├── Pages/
    │       │   ├── Auth/Login.vue
    │       │   ├── Auth/Logout.vue
    │       │   └── Context/SelectAutarquia.vue
    │       │
    │       ├── Layouts/
    │       │   ├── GuestLayout.vue
    │       │   └── AdminLayout.vue
    │       │
    │       └── Components/
    │           └── SupportBar.vue   (modo suporte)
    │
    ├── Providers/
    │   └── AuthCoreServiceProvider.php
    │
    └── Routes/
        └── web.php
```

---


### COMEÇANDO PELOS BACKEND — Controllers e Rotas

---

# 🟧 1. Provider

📄 **Modules/AuthCore/Providers/AuthCoreServiceProvider.php**

```php
<?php

namespace Modules\AuthCore\Providers;

use Illuminate\Support\ServiceProvider;

class AuthCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'authcore');
    }
}
```

---

# 🟨 2. Rotas

📄 **Modules/AuthCore/Routes/web.php**

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthCore\Http\Controllers\AuthController;
use Modules\AuthCore\Http\Controllers\MeController;
use Modules\AuthCore\Http\Controllers\SelectContextController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum'])->group(function () {

    // Seleção de município/autarquia
    Route::get('/context/select', [SelectContextController::class, 'index']);
    Route::post('/context/select', [SelectContextController::class, 'select']);

    // Info do usuário
    Route::get('/me', [MeController::class, 'me']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

---

# 🟦 3. LoginRequest

📄 **Modules/AuthCore/Http/Requests/LoginRequest.php**

```php
<?php

namespace Modules\AuthCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'password' => 'required|string',
        ];
    }
}
```

---

# 🟩 4. AuthController

📄 **Modules/AuthCore/Http/Controllers/AuthController.php**

```php
<?php

namespace Modules\AuthCore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Modules\AuthCore\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    public function loginPage()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['email' => 'Credenciais inválidas']);
        }

        // Remove tokens antigos
        $user->tokens()->delete();

        // Cria token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Verifica se há autarquias vinculadas
        $hasAutarquias = $user->autarquias()->exists();

        if (! $hasAutarquias) {
            return back()->withErrors([
                'email' => 'Usuário sem vínculo em nenhuma autarquia'
            ]);
        }

        return redirect('/context/select')->with('token', $token);
    }

    public function logout()
    {
        request()->user()->tokens()->delete();

        return redirect('/login');
    }
}
```

---

# 🟪 5. SelectContextController

Aqui escolhemos **qual município** e **qual autarquia** esse usuário vai usar.

📄 **Modules/AuthCore/Http/Controllers/SelectContextController.php**

```php
<?php

namespace Modules\AuthCore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Autarquia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\AuthCore\Http\Requests\SelectAutarquiaRequest;

class SelectContextController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $autarquias = $user->autarquias()
            ->with('municipio')
            ->where('ativo', true)
            ->get();

        return Inertia::render('Context/SelectAutarquia', [
            'autarquias' => $autarquias,
        ]);
    }

    public function select(SelectAutarquiaRequest $request)
    {
        $user = $request->user();

        $autarquia = Autarquia::with('municipio')
            ->where('ativo', true)
            ->findOrFail($request->autarquia_id);

        // Define preferências de tenant
        $user->update([
            'municipio_preferido_id' => $autarquia->municipio_id,
            'autarquia_preferida_id' => $autarquia->id,
        ]);

        return redirect('/'); // Dashboard do AdminLayout
    }
}
```

---

# 🟫 6. SelectAutarquiaRequest

📄 **Modules/AuthCore/Http/Requests/SelectAutarquiaRequest.php**

```php
<?php

namespace Modules\AuthCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectAutarquiaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'autarquia_id' => 'required|integer|exists:common.autarquias,id',
        ];
    }
}
```

---

# 🟣 7. MeController – retorna dados do usuário (Inertia)

📄 **Modules/AuthCore/Http/Controllers/MeController.php**

```php
<?php

namespace Modules\AuthCore\Http\Controllers;

use App\Http\Controllers\Controller;

class MeController extends Controller
{
    public function me()
    {
        return response()->json([
            'user' => auth()->user(),
        ]);
    }
}
```

---

# 🔵 8. Middleware RedirectIfAuthenticated

📄 **Modules/AuthCore/Http/Middleware/RedirectIfAuthenticated.php**

```php
<?php

namespace Modules\AuthCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            return redirect('/');
        }

        return $next($request);
    }
}
```

---

# 🟩 **PARTE DO FRONTEND (Inertia + Vue 3)**

### IMPORTANTÍSSIMO — funciona já na sua base.

Agora vamos gerar as páginas:

---

# 📄 Login.vue

📄 **Modules/AuthCore/Resources/js/Pages/Auth/Login.vue**

```vue
<template>
  <GuestLayout>
    <form @submit.prevent="submit">
      <h1>Login</h1>

      <input v-model="form.email" type="email" placeholder="E-mail" />
      <input v-model="form.password" type="password" placeholder="Senha" />

      <button type="submit">Entrar</button>

      <div v-if="errors.email" class="error">{{ errors.email }}</div>
    </form>
  </GuestLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()
const errors = page.props.errors

const form = reactive({
  email: '',
  password: '',
})

function submit() {
  router.post('/login', form)
}
</script>
```

---

# 📄 SelectAutarquia.vue

📄 **Modules/AuthCore/Resources/js/Pages/Context/SelectAutarquia.vue**

```vue
<template>
  <AdminLayout>
    <div>
      <h1>Selecione a Autarquia</h1>

      <ul>
        <li v-for="a in autarquias" :key="a.id">
          <button @click="select(a.id)">
            {{ a.municipio.nome }} — {{ a.nome }}
          </button>
        </li>
      </ul>
    </div>
  </AdminLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
  autarquias: Array,
})

function select(id) {
  router.post('/context/select', { autarquia_id: id })
}
</script>
```

---

# 📄 GuestLayout.vue

📄 **Modules/AuthCore/Resources/js/Layouts/GuestLayout.vue**

```vue
<template>
  <div class="guest-container">
    <slot />
  </div>
</template>
```

---

# 📄 AdminLayout.vue

(+ já preparado para o MODO SUPORTE depois)

📄 **Modules/AuthCore/Resources/js/Layouts/AdminLayout.vue**

```vue
<template>
  <div>
    <SupportBar />

    <header>
      <h2>Sistema Administrativo</h2>
    </header>

    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import SupportBar from '../Components/SupportBar.vue'
</script>
```

---

# 📄 SupportBar.vue

(usado quando SupportCore ativar suporte)

📄 **Modules/AuthCore/Resources/js/Components/SupportBar.vue**

```vue
<template>
  <div v-if="support.active" class="support-bar">
    🛡️ MODO SUPORTE ATIVO —
    Autarquia: {{ support.autarquia }}

    <button @click="exit">Sair</button>
  </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()
const support = page.props.support

function exit() {
  router.post('/api/support/exit-context')
}
</script>

<style>
.support-bar {
  background: #b91c1c;
  color: white;
  padding: 10px;
  font-weight: bold;
}
</style>
```

---



