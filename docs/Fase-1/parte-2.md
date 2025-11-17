**PARTE 2 — MIGRATIONS BASE**, que formam a estrutura do **schema common**, do **schema dos municípios** e dos **schemas das autarquias dentro dos municípios**, tudo compatível com o modelo:

> **common → município (schema) → autarquia (prefixo) → sistema (módulos)**

Essa base é crucial — ela sustenta todo o SH3-SIAP.

---

# 🟩 **PARTE 2 – MIGRATIONS BASE**

Você terá três tipos de migrations:

1️⃣ **common/** → tabelas compartilhadas entre todos os municípios/autarquias
2️⃣ **migrations de schema** → criam os schemas dos municípios (santa_cruz_de_minas, barroso…)
3️⃣ **autarquias/** → tabelas internas de gerenciamento de autarquias e módulos habilitados

Cada arquivo está listando:

* 📄 *caminho do arquivo*
* 🧱 *conteúdo completo para copiar e criar*

---

# 📁 **ESTRUTURA FINAL DAS MIGRATIONS**

```
database/
├── migrations/
│   ├── common/
│   │   ├── 0001_create_municipios_table.php
│   │   ├── 0002_create_autarquias_table.php
│   │   ├── 0003_create_autarquia_modulos_table.php
│   │   ├── 0004_create_users_table.php
│   │   ├── 0005_create_user_autarquia_table.php
│   │   └── 0006_create_modulos_table.php
│   │
│   ├── schemas/
│   │   └── 0001_create_municipio_schemas.php
│   │
│   ├── autarquias/
│   │   └── 0001_create_autarquia_prefix_structures.php
│
└── seeders/
    ├── CreateSchemasSeeder.php
    ├── MunicipioSeeder.php
    └── AutarquiaSeeder.php
```

Agora vamos gerar **cada arquivo**, começando por **common/**.

---

# 🟦 [COMMON] 0001_create_municipios_table.php

📄 `database/migrations/common/0001_create_municipios_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.municipios", function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('schema_name')->unique(); // santa_cruz_de_minas
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.municipios");
    }
};

```

---

# 🟧 [COMMON] 0002_create_autarquias_table.php

📄 `database/migrations/common/0002_create_autarquias_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.autarquias", function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained("{$this->schema}.municipios");

            $table->string('nome');
            $table->string('slug');

            // Ex: pref_santa_cruz_de_minas
            $table->string('schema_prefix')->unique();

            $table->boolean('ativo')->default(true);

            $table->timestamps();

            $table->unique(['municipio_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.autarquias");
    }
};

```

---

# 🟨 [COMMON] 0003_create_modulos_table.php

📄 `database/migrations/common/0003_create_modulos_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.modulos", function (Blueprint $table) {
            $table->id();
            $table->string('nome');   // Frota, Patrimônio, Contabilidade...
            $table->string('slug')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.modulos");
    }
};

```

---

# 🟩 [COMMON] 0004_create_users_table.php

📄 `database/migrations/common/0004_create_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.users", function (Blueprint $table) {
            $table->id();

            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password');

            // Preferências de tenant
            $table->foreignId('municipio_preferido_id')->nullable()
                ->constrained("{$this->schema}.municipios");

            $table->foreignId('autarquia_preferida_id')->nullable()
                ->constrained("{$this->schema}.autarquias");

            // Controle de suporte
            $table->boolean('is_superadmin')->default(false);
            $table->boolean('support_mode')->default(false);

            $table->foreignId('support_autarquia_id')
                ->nullable()
                ->constrained("{$this->schema}.autarquias");

            $table->foreignId('support_original_autarquia_id')
                ->nullable()
                ->constrained("{$this->schema}.autarquias");

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.users");
    }
};

```

---

# 🟪 [COMMON] 0005_create_user_autarquia_table.php

📄 `database/migrations/common/0005_create_user_autarquia_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.user_autarquia", function (Blueprint $table) {

            $table->foreignId('user_id')->constrained("{$this->schema}.users");
            $table->foreignId('autarquia_id')->constrained("{$this->schema}.autarquias");

            $table->string('role')->default('user');
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->primary(['user_id', 'autarquia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.user_autarquia");
    }
};

```

---

# 🟥 [COMMON] 0006_create_autarquia_modulos_table.php

📄 `database/migrations/common/0006_create_autarquia_modulos_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.autarquia_modulos", function (Blueprint $table) {

            $table->id();
            $table->foreignId('autarquia_id')->constrained("{$this->schema}.autarquias");
            $table->foreignId('modulo_id')->constrained("{$this->schema}.modulos");

            $table->boolean('ativo')->default(true);

            $table->timestamps();

            $table->unique(['autarquia_id', 'modulo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.autarquia_modulos");
    }
};

```
# 🟥 [COMMON] 0007_create_support_logs_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.support_logs", function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained("{$this->schema}.users");
            $table->foreignId('autarquia_id')->constrained("{$this->schema}.autarquias");

            $table->string('action', 50); // assume_context | exit_context
            $table->jsonb('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.support_logs");
    }
};
```

# 🟥 [COMMON] 0008_create_sessions_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.sessions", function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.sessions");
    }
};
```
# 🟥 [COMMON] 0009_create_cache_table.php
```php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.cache", function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.cache");
    }
};
```

# 🟥 [COMMON] 0010_create_cache_locks_table.php
```php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.cache_locks", function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.cache_locks");
    }
};
```

# 🟥 [COMMON] 0011_create_jobs_table.php
```php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    protected string $schema = 'common';

    public function up(): void
    {
        Schema::create("{$this->schema}.jobs", function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->tinyInteger('attempts')->unsigned();
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("{$this->schema}.jobs");
    }
};
```

# 🟥 [COMMON] 0012_create_failed_jobs_table.php
```php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    protected string $schema = 'common';

    public function up()
    {
        Schema::create("{$this->schema}.failed_jobs", function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists("{$this->schema}.failed_jobs");
    }
};
```

---

# 🟦 [SCHEMAS] 0001_create_municipio_schemas.php

📄 `database/migrations/schemas/0001_create_municipio_schemas.php`

Essa migration **cria os schemas** no banco automaticamente.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected string $schema = 'common';

    public function up(): void
    {
        $municipios = DB::table("{$this->schema}.municipios")->get();

        foreach ($municipios as $m) {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$m->schema_name}");
        }
    }

    public function down(): void
    {
        // não removemos schemas por segurança
    }
};
```

---

# 🟧 [AUTARQUIAS] 0001_create_autarquia_prefix_structures.php

📄 `database/migrations/autarquias/0001_create_autarquia_prefix_structures.php`

Essa migration apenas valida a existência dos prefixos:

```php
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up(): void
    {
        // Aqui você pode futuramente criar estrutura padrão:
        // - tabelas de logs
        // - tabelas de auditoria
        // deixei vazio de propósito.
    }

    public function down(): void
    {
        //
    }
};
```

---

# 🟧 SEEDERS ESSENCIAIS

## 1. CreateSchemasSeeder

📄 `database/seeders/CreateSchemasSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateSchemasSeeder extends Seeder
{
    public function run(): void
    {
        $municipios = [
            ['nome' => 'Santa Cruz de Minas', 'slug' => 'santa_cruz_de_minas', 'schema_name' => 'santa_cruz_de_minas'],
            ['nome' => 'Barroso', 'slug' => 'barroso', 'schema_name' => 'barroso'],
            ['nome' => 'Tiradentes', 'slug' => 'tiradentes', 'schema_name' => 'tiradentes'],
        ];

        foreach ($municipios as $m) {
            DB::table('common.municipios')->insert($m);

            DB::statement("CREATE SCHEMA IF NOT EXISTS {$m['schema_name']}");
        }
    }
}
```

---

# 🟩 MunicípioSeeder

📄 `database/seeders/MunicipioSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('common.municipios')->insert([
            ['nome' => 'Santa Cruz de Minas', 'slug' => 'santa_cruz_de_minas', 'schema_name' => 'santa_cruz_de_minas'],
            ['nome' => 'Barroso', 'slug' => 'barroso', 'schema_name' => 'barroso'],
            ['nome' => 'Tiradentes', 'slug' => 'tiradentes', 'schema_name' => 'tiradentes'],
        ]);
    }
}
```

---

# 🟨 AutarquiaSeeder

📄 `database/seeders/AutarquiaSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AutarquiaSeeder extends Seeder
{
    public function run(): void
    {
        $autarquias = [
            // Santa Cruz de Minas
            ['municipio_id' => 1, 'nome' => 'Prefeitura', 'slug' => 'prefeitura', 'schema_prefix' => 'pref_santa_cruz_de_minas'],
            ['municipio_id' => 1, 'nome' => 'Câmara Municipal', 'slug' => 'camara', 'schema_prefix' => 'cam_santa_cruz_de_minas'],
            ['municipio_id' => 1, 'nome' => 'Saúde', 'slug' => 'saude', 'schema_prefix' => 'saude_santa_cruz_de_minas'],
            ['municipio_id' => 1, 'nome' => 'Água e Esgoto', 'slug' => 'aguaesgoto', 'schema_prefix' => 'aguaesgoto_santa_cruz_de_minas'],

            // Barroso
            ['municipio_id' => 2, 'nome' => 'Prefeitura', 'slug' => 'prefeitura', 'schema_prefix' => 'pref_barroso'],
            ['municipio_id' => 2, 'nome' => 'Câmara Municipal', 'slug' => 'camara', 'schema_prefix' => 'cam_barroso'],
            ['municipio_id' => 2, 'nome' => 'Saúde', 'slug' => 'saude', 'schema_prefix' => 'saude_barroso'],
            ['municipio_id' => 2, 'nome' => 'Água e Esgoto', 'slug' => 'aguaesgoto', 'schema_prefix' => 'aguaesgoto_barroso'],

            // Tiradentes
            ['municipio_id' => 3, 'nome' => 'Prefeitura', 'slug' => 'prefeitura', 'schema_prefix' => 'pref_tiradentes'],
            ['municipio_id' => 3, 'nome' => 'Câmara Municipal', 'slug' => 'camara', 'schema_prefix' => 'cam_tiradentes'],
            ['municipio_id' => 3, 'nome' => 'Saúde', 'slug' => 'saude', 'schema_prefix' => 'saude_tiradentes'],
            ['municipio_id' => 3, 'nome' => 'Água e Esgoto', 'slug' => 'aguaesgoto', 'schema_prefix' => 'aguaesgoto_tiradentes'],
        ];

        DB::table('common.autarquias')->insert($autarquias);
    }
}
```

