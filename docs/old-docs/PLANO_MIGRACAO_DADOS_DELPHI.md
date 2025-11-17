# Plano Definitivo de Migração: Firebird → Auth-Suite PostgreSQL

**Data:** 02 de Novembro de 2025
**Baseado em:** Análise real do banco de produção

---

## 1. Estrutura Real Identificada

### 1.1. Fluxo de Dados no Sistema Delphi

```
PESSOA (5.269 registros) ← Cadastro Base
├── STR_T_CAD = 'C' (4.720) - Cadastro Comum/Corrente/Ativo
└── STR_T_CAD = 'D' (549) - Duplicado/Desativado/Desligado
    │
    ├─→ especializa em CREDOR (13.252 registros)
    │   ├── TP_CDR = 'PF' (7.647) - Fornecedores PF
    │   └── TP_CDR = 'PJ' (5.605) - Fornecedores PJ
    │
    ├─→ especializa em PESSOA_FISICA (68 registros)
    │   └── Funcionários públicos com dados completos
    │
    └─→ usado em LICITANTE (licitações)
        └── Referencia CREDOR

USUARIOS (3 registros) ← INDEPENDENTE
└── Usuários do sistema (login, senha, nome)
```

### 1.2. Descobertas Importantes

✅ **PESSOA_FISICA:**
- 68 funcionários públicos
- 100% relacionados com PESSOA (INT_PSSOA = PESSOA.PESSOA_ID)
- Dados completos: CPF, RG, endereço, filiação, documentos

✅ **USUARIOS:**
- Apenas 3 usuários
- **INDEPENDENTE** - não se relaciona com PESSOA ou PESSOA_FISICA
- Campos: STR_NM_USR (login), STR_SH_USR (senha), NM_USR (nome)

✅ **CREDOR:**
- 13.252 registros (fornecedores, credores, licitantes)
- Usado principalmente para módulos de negócio (Compras, Licitações)
- **NÃO É NECESSÁRIO MIGRAR AGORA** para Auth-Suite

---

## 2. Decisão Final: Migração em 2 Fases

### FASE 1: Auth-Suite (AGORA) ⭐

**Objetivo:** Sistema de autenticação e autorização funcional

**Dados a Migrar:**
- `USUARIOS` (3) → `users`
- `PESSOA_FISICA` (68) → `pessoas_fisicas`

**Total:** 71 registros

**Duração:** 1-2 dias

**Riscos:** MUITO BAIXO
- Poucos registros
- Dados limpos
- Sem relacionamentos complexos

---

### FASE 2: Cadastros Comuns (FUTURO)

**Objetivo:** Base de pessoas para módulos de negócio

**Dados a Migrar:**
- `CREDOR` (13.252) → `pessoas_fisicas` + `pessoas_juridicas`
- Deduplicar com PESSOA_FISICA existente

**Quando:** Ao implementar módulos de Compras, Patrimônio, Licitações

**Duração:** 5-7 dias

---

## 3. FASE 1 - Detalhamento Completo

### 3.1. Estrutura das Tabelas Firebird

#### USUARIOS (3 registros)

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `STR_NM_USR` (PK) | VARCHAR(20) | Login do usuário |
| `STR_SH_USR` | VARCHAR(8) | Senha (hash ou texto) |
| `NM_USR` | VARCHAR(45) | Nome completo |
| `STR_STOR_USR` | VARCHAR(40) | ? (investigar) |
| `STR_FCAO_USR` | VARCHAR(40) | Função/Cargo |
| `STR_ID_USR` | VARCHAR(2) | ID do usuário |

#### PESSOA_FISICA (68 registros)

| Coluna | Tipo | Observação |
|--------|------|------------|
| `INT_PSSOA` | INTEGER | ID (referencia PESSOA.PESSOA_ID) |
| `NM_PSSOA` | VARCHAR(50) | Nome completo |
| `CPF_PSSOA` | BIGINT | CPF numérico (11 dígitos) |
| `D_NSCMNTO_PSSOA` | VARCHAR(50) | ⚠️ Data como string |
| `SX_PSSOA` | VARCHAR(50) | Sexo |
| `STR_RG_NMRO` | INTEGER | RG numérico |
| `STR_RG_ORGAO_EMSSOR` | VARCHAR(50) | Órgão emissor |
| `D_RG_EXPDCAO` | VARCHAR(50) | ⚠️ Data como string |
| `STR_RG_UF` | VARCHAR(50) | UF |
| `CEP_ENDRCO_PSSOA` | INTEGER | CEP numérico |
| `STR_ENDRCO_LGRDRO_PSSOA` | VARCHAR(50) | Logradouro |
| `STR_ENDRCO_NMRO_PSSOA` | VARCHAR(50) | Número |
| `STR_ENDRCO_BRRO_PSSOA` | VARCHAR(50) | Bairro |
| `STR_ENDRCO_CMPLMNTO_PSSOA` | VARCHAR(50) | Complemento |
| `STR_TLFNE_PSSOA` | BIGINT | Telefone numérico |
| `STR_CLLAR_PSSOA` | BIGINT | Celular numérico |
| `STR_EMAIL_PSSOA` | VARCHAR(50) | Email |
| `NM_MAE_PSSOA` | VARCHAR(50) | Nome da mãe |
| `NM_PAI_PSSOA` | VARCHAR(50) | Nome do pai |

⚠️ **ATENÇÃO:** Muitos campos estão como VARCHAR(50) que deveriam ser DATE, INTEGER, etc.

### 3.2. Mapeamento para Laravel

#### USUARIOS → users

```sql
users (Auth-Suite PostgreSQL)
├── id (AUTO)
├── name ← NM_USR
├── email ← construir: STR_NM_USR + '@dominio.com.br' (não tem email)
├── password ← bcrypt('senha_temporaria_123') -- forçar reset
├── login_legado ← STR_NM_USR (novo campo)
├── senha_legado ← STR_SH_USR (novo campo, para referência)
├── is_active ← TRUE (padrão)
├── is_superadmin ← FALSE (ajustar depois)
└── pessoa_fisica_id ← NULL (não tem relação)
```

**⚠️ PROBLEMA:** USUARIOS não tem email! Vamos criar email fictício baseado no login.

**Estratégia:**
- Email: `{STR_NM_USR}@prefeitura.gov.br` (temporário)
- Senha: `bcrypt('senha_temporaria_123')`
- Forçar reset de senha no primeiro login

#### PESSOA_FISICA → pessoas_fisicas

```sql
pessoas_fisicas (Auth-Suite PostgreSQL)
├── id (AUTO)
├── id_legado ← INT_PSSOA (novo campo)
├── nome ← NM_PSSOA
├── cpf ← formatar(CPF_PSSOA) -- BIGINT para string com zeros à esquerda
├── rg ← STR_RG_NMRO
├── rg_orgao_emissor ← STR_RG_ORGAO_EMSSOR (novo campo)
├── rg_data_emissao ← parseDate(D_RG_EXPDCAO) (novo campo)
├── rg_uf ← STR_RG_UF (novo campo)
├── data_nascimento ← parseDate(D_NSCMNTO_PSSOA)
├── sexo ← SX_PSSOA (novo campo)
├── nome_mae ← NM_MAE_PSSOA (novo campo)
├── nome_pai ← NM_PAI_PSSOA (novo campo)
├── endereco ← STR_ENDRCO_LGRDRO_PSSOA
├── numero ← STR_ENDRCO_NMRO_PSSOA (novo campo)
├── complemento ← STR_ENDRCO_CMPLMNTO_PSSOA (novo campo)
├── bairro ← STR_ENDRCO_BRRO_PSSOA (novo campo)
├── cep ← formatar(CEP_ENDRCO_PSSOA) -- INTEGER para string com zeros
├── cidade ← NULL (não temos no export)
├── uf ← STR_RG_UF (usar UF do RG)
├── telefone ← formatar(STR_TLFNE_PSSOA)
├── celular ← formatar(STR_CLLAR_PSSOA) (novo campo)
└── email ← STR_EMAIL_PSSOA
```

### 3.3. Campos Adicionais nas Migrations

**Migration: add_firebird_fields_to_users**

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('login_legado', 20)->nullable()->unique()->after('email');
    $table->string('senha_legado', 50)->nullable()->after('password')
        ->comment('Hash da senha do sistema antigo (apenas referência)');
    $table->boolean('precisa_reset_senha')->default(true)->after('senha_legado');
});
```

**Migration: add_firebird_fields_to_pessoas_fisicas**

```php
Schema::table('pessoas_fisicas', function (Blueprint $table) {
    // ID do sistema legado
    $table->bigInteger('id_legado')->nullable()->index()->after('id')
        ->comment('INT_PSSOA do Firebird');

    // Dados pessoais
    $table->char('sexo', 1)->nullable()->after('data_nascimento')
        ->comment('M=Masculino, F=Feminino');
    $table->string('nome_mae', 200)->nullable()->after('nome');
    $table->string('nome_pai', 200)->nullable()->after('nome_mae');

    // RG completo
    $table->string('rg_orgao_emissor', 50)->nullable()->after('rg');
    $table->date('rg_data_emissao')->nullable()->after('rg_orgao_emissor');
    $table->char('rg_uf', 2)->nullable()->after('rg_data_emissao');

    // Endereço detalhado
    $table->string('numero', 20)->nullable()->after('endereco');
    $table->string('complemento', 100)->nullable()->after('numero');
    $table->string('bairro', 100)->nullable()->after('complemento');

    // Telefones
    $table->string('celular', 20)->nullable()->after('telefone');
});
```

---

## 4. Scripts de Exportação

### 4.1. Exportar USUARIOS (Firebird)

```sql
-- Usar isql ou Flamerobin

SELECT
    STR_NM_USR || '|' ||
    COALESCE(NM_USR, '') || '|' ||
    COALESCE(STR_SH_USR, '') || '|' ||
    COALESCE(STR_FCAO_USR, '') || '|' ||
    COALESCE(STR_ID_USR, '')
FROM USUARIOS
ORDER BY STR_NM_USR;

-- Salvar como: usuarios_export.csv
```

### 4.2. Exportar PESSOA_FISICA (Firebird)

```sql
SELECT
    INT_PSSOA || '|' ||
    COALESCE(NM_PSSOA, '') || '|' ||
    COALESCE(CPF_PSSOA, 0) || '|' ||
    COALESCE(D_NSCMNTO_PSSOA, '') || '|' ||
    COALESCE(SX_PSSOA, '') || '|' ||
    COALESCE(STR_RG_NMRO, 0) || '|' ||
    COALESCE(STR_RG_ORGAO_EMSSOR, '') || '|' ||
    COALESCE(D_RG_EXPDCAO, '') || '|' ||
    COALESCE(STR_RG_UF, '') || '|' ||
    COALESCE(CEP_ENDRCO_PSSOA, 0) || '|' ||
    COALESCE(STR_ENDRCO_LGRDRO_PSSOA, '') || '|' ||
    COALESCE(STR_ENDRCO_NMRO_PSSOA, '') || '|' ||
    COALESCE(STR_ENDRCO_BRRO_PSSOA, '') || '|' ||
    COALESCE(STR_ENDRCO_CMPLMNTO_PSSOA, '') || '|' ||
    COALESCE(STR_TLFNE_PSSOA, 0) || '|' ||
    COALESCE(STR_CLLAR_PSSOA, 0) || '|' ||
    COALESCE(STR_EMAIL_PSSOA, '') || '|' ||
    COALESCE(NM_MAE_PSSOA, '') || '|' ||
    COALESCE(NM_PAI_PSSOA, '')
FROM PESSOA_FISICA
ORDER BY INT_PSSOA;

-- Salvar como: pessoa_fisica_export.csv
```

---

## 5. Seeder Laravel

```php
<?php
// database/seeders/MigracaoFirebirdSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\PessoaFisica;
use App\Models\User;
use Carbon\Carbon;

class MigracaoFirebirdSeeder extends Seeder
{
    private $stats = [
        'usuarios' => ['total' => 0, 'sucesso' => 0, 'erros' => 0],
        'pessoas_fisicas' => ['total' => 0, 'sucesso' => 0, 'erros' => 0],
    ];

    private $dominio = '@prefeitura.gov.br'; // Ajustar conforme necessário

    public function run()
    {
        Log::info("🚀 Iniciando migração FASE 1: Auth-Suite");
        Log::info("📊 Esperado: 3 usuários + 68 funcionários = 71 registros");

        DB::beginTransaction();

        try {
            // 1. Migrar Pessoas Físicas (primeiro, pois usuários podem referenciar)
            $this->migrarPessoasFisicas();

            // 2. Migrar Usuários
            $this->migrarUsuarios();

            DB::commit();

            $this->printStats();

            Log::info("✅ Migração FASE 1 concluída com sucesso!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Erro na migração: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    private function migrarPessoasFisicas()
    {
        Log::info("👥 Migrando Pessoas Físicas (68 funcionários)...");

        $csvFile = storage_path('migration_data/pessoa_fisica_export.csv');

        if (!file_exists($csvFile)) {
            throw new \Exception("Arquivo não encontrado: $csvFile");
        }

        $handle = fopen($csvFile, 'r');

        // Se tiver cabeçalho, pule a primeira linha
        // fgetcsv($handle, 1000, '|');

        while (($row = fgetcsv($handle, 1000, '|')) !== false) {
            $this->stats['pessoas_fisicas']['total']++;

            try {
                // Parsear dados
                $intPssoa = (int)trim($row[0]);
                $nome = trim($row[1]);
                $cpfRaw = trim($row[2]);
                $dataNascimento = trim($row[3]);
                $sexo = strtoupper(trim($row[4]));
                $rgNumero = trim($row[5]);
                $rgOrgao = trim($row[6]);
                $rgDataEmissao = trim($row[7]);
                $rgUf = strtoupper(trim($row[8]));
                $cepRaw = trim($row[9]);
                $endereco = trim($row[10]);
                $numero = trim($row[11]);
                $bairro = trim($row[12]);
                $complemento = trim($row[13]);
                $telefoneRaw = trim($row[14]);
                $celularRaw = trim($row[15]);
                $email = strtolower(trim($row[16]));
                $nomeMae = trim($row[17]);
                $nomePai = trim($row[18]);

                // Formatar CPF (BIGINT para string com zeros à esquerda)
                $cpf = str_pad($cpfRaw, 11, '0', STR_PAD_LEFT);

                if (strlen($cpf) != 11 || $cpf == '00000000000') {
                    Log::warning("CPF inválido: $cpfRaw - Nome: $nome");
                    $this->stats['pessoas_fisicas']['erros']++;
                    continue;
                }

                // Validar CPF
                if (!$this->validarCPF($cpf)) {
                    Log::warning("CPF inválido (algoritmo): $cpf - Nome: $nome");
                    $this->stats['pessoas_fisicas']['erros']++;
                    continue;
                }

                // Formatar CEP
                $cep = str_pad($cepRaw, 8, '0', STR_PAD_LEFT);

                // Formatar telefones
                $telefone = $this->formatarTelefone($telefoneRaw);
                $celular = $this->formatarTelefone($celularRaw);

                // Parse de datas (VARCHAR para DATE)
                $dataNascParsed = $this->parseDate($dataNascimento);
                $rgDataParsed = $this->parseDate($rgDataEmissao);

                // Criar PessoaFisica
                PessoaFisica::updateOrCreate(
                    ['cpf' => $cpf],
                    [
                        'id_legado' => $intPssoa,
                        'nome' => $nome,
                        'nome_mae' => $nomeMae ?: null,
                        'nome_pai' => $nomePai ?: null,
                        'data_nascimento' => $dataNascParsed,
                        'sexo' => $sexo ?: null,
                        'rg' => $rgNumero ?: null,
                        'rg_orgao_emissor' => $rgOrgao ?: null,
                        'rg_data_emissao' => $rgDataParsed,
                        'rg_uf' => $rgUf ?: null,
                        'endereco' => $endereco ?: null,
                        'numero' => $numero ?: null,
                        'bairro' => $bairro ?: null,
                        'complemento' => $complemento ?: null,
                        'cep' => $cep ?: null,
                        'uf' => $rgUf ?: null, // Usar UF do RG
                        'cidade' => null, // Não temos
                        'telefone' => $telefone,
                        'celular' => $celular,
                        'email' => $email ?: null,
                    ]
                );

                $this->stats['pessoas_fisicas']['sucesso']++;

                if ($this->stats['pessoas_fisicas']['sucesso'] % 10 == 0) {
                    Log::info("  Progresso: {$this->stats['pessoas_fisicas']['sucesso']}/{$this->stats['pessoas_fisicas']['total']}");
                }

            } catch (\Exception $e) {
                Log::error("Erro ao migrar PF: {$row[1]} - {$e->getMessage()}");
                $this->stats['pessoas_fisicas']['erros']++;
            }
        }

        fclose($handle);

        Log::info("✅ Pessoas Físicas migradas: {$this->stats['pessoas_fisicas']['sucesso']}/{$this->stats['pessoas_fisicas']['total']}");
    }

    private function migrarUsuarios()
    {
        Log::info("🔑 Migrando Usuários (3 registros)...");

        $csvFile = storage_path('migration_data/usuarios_export.csv');

        if (!file_exists($csvFile)) {
            throw new \Exception("Arquivo não encontrado: $csvFile");
        }

        $handle = fopen($csvFile, 'r');

        while (($row = fgetcsv($handle, 1000, '|')) !== false) {
            $this->stats['usuarios']['total']++;

            try {
                $login = strtolower(trim($row[0]));
                $nome = trim($row[1]);
                $senhaLegado = trim($row[2]);
                $funcao = trim($row[3]);

                // Criar email fictício baseado no login
                $email = $login . $this->dominio;

                // Verificar se existe PessoaFisica com nome similar
                // (USUARIOS é independente, mas podemos tentar associar)
                $pessoaFisica = PessoaFisica::where('nome', 'LIKE', "%{$nome}%")->first();

                User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $nome,
                        'email' => $email,
                        'password' => Hash::make('senha_temporaria_123'),
                        'login_legado' => $login,
                        'senha_legado' => $senhaLegado,
                        'precisa_reset_senha' => true,
                        'pessoa_fisica_id' => $pessoaFisica?->id,
                        'is_active' => true,
                        'is_superadmin' => false, // Ajustar manualmente depois
                    ]
                );

                $this->stats['usuarios']['sucesso']++;

                Log::info("  ✓ Usuário criado: $email (login: $login)");

            } catch (\Exception $e) {
                Log::error("Erro ao migrar usuário: {$row[0]} - {$e->getMessage()}");
                $this->stats['usuarios']['erros']++;
            }
        }

        fclose($handle);

        Log::info("✅ Usuários migrados: {$this->stats['usuarios']['sucesso']}/{$this->stats['usuarios']['total']}");
    }

    // ========================================
    // FUNÇÕES AUXILIARES
    // ========================================

    private function formatarTelefone($valor)
    {
        if (empty($valor) || $valor == '0') return null;

        $telefone = str_pad($valor, 11, '0', STR_PAD_LEFT);

        // Formato: (99) 99999-9999 ou (99) 9999-9999
        if (strlen($telefone) == 11) {
            return sprintf('(%s) %s-%s',
                substr($telefone, 0, 2),
                substr($telefone, 2, 5),
                substr($telefone, 7, 4)
            );
        } elseif (strlen($telefone) == 10) {
            return sprintf('(%s) %s-%s',
                substr($telefone, 0, 2),
                substr($telefone, 2, 4),
                substr($telefone, 6, 4)
            );
        }

        return $telefone;
    }

    private function parseDate($valor)
    {
        if (empty($valor)) return null;

        try {
            // Firebird pode exportar como YYYY-MM-DD ou DD/MM/YYYY
            if (strpos($valor, '/') !== false) {
                // DD/MM/YYYY
                return Carbon::createFromFormat('d/m/Y', $valor)->format('Y-m-d');
            } elseif (strpos($valor, '-') !== false) {
                // YYYY-MM-DD
                return Carbon::parse($valor)->format('Y-m-d');
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("Erro ao parsear data: $valor");
            return null;
        }
    }

    private function validarCPF($cpf)
    {
        if (strlen($cpf) != 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false; // Todos iguais

        // Validação completa do CPF (algoritmo)
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    private function printStats()
    {
        echo "\n";
        echo "=" . str_repeat("=", 80) . "\n";
        echo "RELATÓRIO DE MIGRAÇÃO - FASE 1 (Auth-Suite)\n";
        echo "=" . str_repeat("=", 80) . "\n";

        foreach ($this->stats as $entidade => $stats) {
            echo "\n" . strtoupper($entidade) . ":\n";
            echo "  Total: {$stats['total']}\n";
            echo "  Sucesso: {$stats['sucesso']}\n";
            echo "  Erros: {$stats['erros']}\n";

            if ($stats['total'] > 0) {
                $taxa = round(($stats['sucesso'] / $stats['total']) * 100, 2);
                echo "  Taxa de Sucesso: {$taxa}%\n";
            }
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "⚠️  ATENÇÃO: Todos os usuários precisam RESETAR a senha no primeiro login!\n";
        echo "⚠️  Senhas padrão: 'senha_temporaria_123'\n";
        echo str_repeat("=", 80) . "\n\n";
    }
}
```

---

## 6. Cronograma de Execução

| Etapa | Tarefa | Duração | Responsável |
|-------|--------|---------|-------------|
| **1** | Criar migrations adicionais | 1h | Dev |
| **2** | Exportar USUARIOS do Firebird | 15min | Dev |
| **3** | Exportar PESSOA_FISICA do Firebird | 15min | Dev |
| **4** | Validar CSVs exportados | 30min | Dev |
| **5** | Criar MigracaoFirebirdSeeder | 2h | Dev |
| **6** | Testar em ambiente staging | 1h | Dev |
| **7** | Ajustar erros (se houver) | 1h | Dev |
| **8** | Executar em produção | 30min | Dev + DBA |
| **9** | Validar migração | 1h | Dev + QA |
| **10** | Ajustar permissões (is_superadmin) | 30min | Dev |

**TOTAL: 1-2 dias**

---




