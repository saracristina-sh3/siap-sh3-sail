# Gerenciamento de Módulos do Sistema

## Visão Geral

O sistema de gerenciamento de módulos foi projetado para permitir controle granular sobre quais funcionalidades cada autarquia e usuário podem acessar. Os módulos são **fixos** (definidos por seed) e não podem ser criados ou removidos dinamicamente pela interface.

## Arquitetura

### Estrutura de 3 Camadas

```
┌─────────────────────────────────────────┐
│  MÓDULOS FIXOS (Seed)                   │
│  - 11 módulos do sistema                │
│  - Gerenciados via ModulosSeeder        │
│  - Campo 'ativo' = disponível global    │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  LIBERAÇÃO POR AUTARQUIA                │
│  - Tabela: autarquia_modulo             │
│  - Define quais módulos a autarquia tem │
│  - Baseado em contratos/planos          │
│  - Campo 'ativo' = autarquia tem acesso │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  PERMISSÕES POR USUÁRIO                 │
│  - Tabela: usuario_modulo_permissao     │
│  - Permissões granulares por usuário    │
│  - Dentro de cada autarquia             │
└─────────────────────────────────────────┘
```

---

## Módulos do Sistema

### Lista de Módulos Fixos

Os módulos são definidos em [ModulosSeeder.php](../backend/database/seeders/ModulosSeeder.php):

| ID  | Nome                  | Descrição                                              | Ícone                        |
|-----|-----------------------|--------------------------------------------------------|------------------------------|
| 1   | Gestão de Frota       | Controle e gestão da frota de veículos municipais      | frota_button                 |
| 2   | Departamento Pessoal  | Gestão de funcionários, folha e benefícios             | departamento_pessoal_button  |
| 3   | Almoxarifado          | Controle de estoque e requisições de materiais         | almoxarifado_button          |
| 4   | Contabilidade         | Controle financeiro, empenhos e prestação de contas    | contabilidade_button         |
| 5   | Compras               | Sistema de compras e licitações                        | compras_button               |
| 6   | Patrimônio            | Gestão de bens e inventário patrimonial                | patrimonio_button            |
| 7   | Orçamento             | Planejamento orçamentário de entidades públicas        | orcamento_button             |
| 8   | Tesouraria            | Controle de caixa e movimentações financeiras          | tesouraria_button            |
| 9   | Requisição Interna    | Controle de requisições internas entre departamentos   | requisicao_interna_button    |
| 10  | Diárias               | Controle de diárias e viagens a serviço                | diarias_button               |
| 11  | Controle Interno      | Controle e auditoria de processos internos             | controle_interno_button      |

### Características dos Módulos

**Fixos:**
- ✅ Criados automaticamente pelo seeder
- ✅ IDs fixos (1 a 11)
- ✅ Não podem ser criados via interface
- ✅ Não podem ser deletados via interface
- ✅ Ícones SVG personalizados em `/assets/icons/`

**Gerenciáveis:**
- ✅ Campo `ativo` pode ser ativado/desativado globalmente
- ✅ Descrições e nomes podem ser atualizados
- ✅ Ícones podem ser trocados (atualizando arquivo SVG)

---

## Interface de Gerenciamento

### AdminManagementView - Aba Módulos

**Localização:** [AdminManagementView.vue](../frontend/src/views/suporte/AdminManagementView.vue)

**Funcionalidades:**

1. **Visualização em Grid**
   - Cards visuais com ícone de cada módulo
   - Informações: Nome, descrição, status
   - Layout responsivo (grid auto-fill)

2. **Ativar/Desativar Global**
   - Toggle switch para cada módulo
   - Afeta disponibilidade em TODO o sistema
   - Confirmação visual de alteração

3. **Modo Somente Leitura**
   - Tag "Somente Leitura" no cabeçalho
   - Sem botões de criar/deletar
   - Mensagem explicativa

**Comportamento:**

```
Módulo.ativo = false
    ↓
Módulo DESAPARECE de TODAS as autarquias
    ↓
Útil para: manutenção, descontinuação, atualizações
```

---

## Ícones dos Módulos

### Sistema de Ícones

Os ícones são armazenados como arquivos SVG no frontend:

**Localização:** `/frontend/src/assets/icons/`

**Estrutura:**
```
frontend/src/assets/icons/
├── frota_button.svg
├── departamento_pessoal_button.svg
├── almoxarifado_button.svg
├── contabilidade_button.svg
├── compras_button.svg
├── patrimonio_button.svg
├── orcamento_button.svg
├── tesouraria_button.svg
├── requisicao_interna_button.svg
├── diarias_button.svg
└── controle_interno_button.svg
```

### Mapeamento de Ícones

**Backend (ModulosSeeder.php):**
```php
'icone' => 'frota_button'  // Nome do arquivo sem .svg
```

**Frontend (useModulos.ts):**
```typescript
const iconMap = {
  'frota_button': FrotaIcon,           // Componente Vue
  'compras_button': ComprasIcon,
  // ...
}
```

**Renderização:**
```vue
<!-- Componente do ícone -->
<FrotaIcon />

<!-- Ou diretamente o SVG -->
<img src="/src/assets/icons/frota_button.svg" />
```

### Como Adicionar/Trocar Ícones

1. **Criar arquivo SVG**
   ```bash
   /frontend/src/assets/icons/novo_modulo_button.svg
   ```

2. **Criar componente Vue** (opcional)
   ```vue
   <!-- IconNovoModulo.vue -->
   <template>
     <img src="@/assets/icons/novo_modulo_button.svg" alt="Novo Módulo" />
   </template>
   ```

3. **Atualizar mapeamento**
   ```typescript
   // useModulos.ts
   import NovoModuloIcon from '@/components/icons/IconNovoModulo.vue'

   const iconMap = {
     'novo_modulo_button': NovoModuloIcon,
     // ...
   }
   ```

4. **Atualizar seeder**
   ```php
   // ModulosSeeder.php
   [
     'id' => 12,
     'nome' => 'Novo Módulo',
     'icone' => 'novo_modulo_button',
   ]
   ```

---

## Backend - Seeders

### ModulosSeeder

**Arquivo:** [ModulosSeeder.php](../backend/database/seeders/ModulosSeeder.php)

**Características:**
- Seeder de **produção** (sempre executado)
- Usa `updateOrInsert` para evitar duplicatas
- Pode ser executado múltiplas vezes
- Não afeta dados existentes

**Executar seeder:**
```bash
# Apenas módulos
php artisan db:seed --class=ModulosSeeder

# Todos os seeders
php artisan db:seed
```

**Adicionar novo módulo:**

1. Editar `ModulosSeeder.php`:
```php
[
    'id' => 12,
    'nome' => 'Novo Módulo',
    'descricao' => 'Descrição do módulo',
    'icone' => 'novo_modulo_button',
    'ativo' => true
],
```

2. Executar seeder:
```bash
php artisan db:seed --class=ModulosSeeder
```

3. Resultado:
```
✅ Módulos fixos do sistema criados/atualizados com sucesso!

📦 Módulos disponíveis (12):
   1. Gestão de Frota
   2. Departamento Pessoal
   ...
   12. Novo Módulo
```

### DatabaseSeeder

**Arquivo:** [DatabaseSeeder.php](../backend/database/seeders/DatabaseSeeder.php)

**Ordem de execução:**
```php
// Seeders de produção (sempre executados)
$this->call(ModulosSeeder::class);        // 1. Módulos fixos
$this->call(SuperAdminSeeder::class);     // 2. Superadmin SH3

// Seeders de desenvolvimento/teste (opcional)
$this->call(ControlePorAutarquiaSeeder::class);
```

**Nota:** O `ControlePorAutarquiaSeeder` **não cria mais módulos**, apenas referencia os IDs existentes criados pelo `ModulosSeeder`.

---

## Liberação de Módulos por Autarquia

### Tabela: autarquia_modulo

**Estrutura:**
```sql
autarquia_modulo (
    id,
    autarquia_id,          -- FK para autarquias
    modulo_id,             -- FK para modulos
    data_liberacao,        -- Quando foi liberado
    data_vencimento,       -- Quando expira (opcional)
    ativo,                 -- Autarquia tem acesso?
    created_at,
    updated_at
)
```

**Exemplo:**
```sql
-- Prefeitura X tem acesso a 3 módulos
INSERT INTO autarquia_modulo VALUES
(1, 2, 1, NOW(), NULL, true),  -- Gestão de Frota
(2, 2, 2, NOW(), NULL, true),  -- Departamento Pessoal
(3, 2, 3, NOW(), NULL, true);  -- Almoxarifado
```

### Comportamento

**Módulo liberado (`ativo = true`):**
- ✅ Aparece na lista de módulos da autarquia
- ✅ Usuários da autarquia podem acessar (se tiverem permissão)

**Módulo não liberado ou desativado (`ativo = false`):**
- ❌ Não aparece para a autarquia
- ❌ Usuários não conseguem acessar
- 💡 Útil para: suspensão por inadimplência, fim de contrato

### Interface (Em Desenvolvimento)

**AdminManagementView - Aba Liberações**

Funcionalidades planejadas:
1. Selecionar autarquia
2. Ver módulos disponíveis
3. Ativar/desativar módulos para aquela autarquia
4. Definir data de liberação/vencimento
5. Visualizar histórico de liberações

---

## Permissões de Usuários

### Tabela: usuario_modulo_permissao

**Estrutura:**
```sql
usuario_modulo_permissao (
    id,
    user_id,               -- FK para users
    modulo_id,             -- FK para modulos
    autarquia_id,          -- FK para autarquias
    permissao_leitura,     -- Pode visualizar?
    permissao_escrita,     -- Pode criar/editar?
    permissao_exclusao,    -- Pode deletar?
    permissao_admin,       -- Admin do módulo?
    data_concessao,        -- Quando foi concedida
    ativo,                 -- Permissão ativa?
    created_at,
    updated_at
)
```

**Exemplo:**
```sql
-- João tem permissões completas em Gestão de Frota
INSERT INTO usuario_modulo_permissao VALUES
(1, 5, 1, 2, true, true, true, true, NOW(), true);
-- user_id=5, modulo_id=1, autarquia_id=2
```

### Hierarquia de Permissões

```
1. Módulo Global (modulos.ativo)
   ↓
2. Liberação Autarquia (autarquia_modulo.ativo)
   ↓
3. Permissão Usuário (usuario_modulo_permissao)
```

**Todas as condições devem ser TRUE:**
- ✅ `modulos.ativo = true` (módulo disponível globalmente)
- ✅ `autarquia_modulo.ativo = true` (autarquia tem acesso)
- ✅ `usuario_modulo_permissao.ativo = true` (usuário tem permissão)

Se qualquer uma for `false`, usuário NÃO acessa.

---

## Frontend - Composables

### useModulos

**Arquivo:** [useModulos.ts](../frontend/src/composables/useModulos.ts)

**Responsabilidades:**
1. Carregar módulos da autarquia do usuário
2. Mapear ícones para componentes Vue
3. Mapear rotas para navegação
4. Filtrar apenas módulos ativos
5. Adicionar campos de UI (key, title, description)

**Fluxo:**

```typescript
useModulos() chamado
    ↓
Obter usuário do localStorage
    ↓
Verificar autarquia_id
    ↓
Chamar API: GET /modulos?autarquia_id=X
    ↓
Backend retorna módulos liberados
    ↓
Filtrar apenas módulos ativos
    ↓
Mapear ícones e rotas
    ↓
Retornar array de ModuloWithUI
```

**Uso em componentes:**

```vue
<script setup>
import { useModulos } from '@/composables/useModulos'

const { modulos, loading, error, reload } = useModulos()
</script>

<template>
  <div v-if="loading">Carregando módulos...</div>
  <div v-else-if="error">{{ error }}</div>
  <div v-else>
    <div v-for="modulo in modulos" :key="modulo.id">
      <component :is="modulo.icon" />
      <h3>{{ modulo.nome }}</h3>
      <p>{{ modulo.descricao }}</p>
      <router-link :to="modulo.route">Acessar</router-link>
    </div>
  </div>
</template>
```

### Mapeamento de Ícones e Rotas

**Icon Map:**
```typescript
const iconMap: Record<string, any> = {
  'frota_button': FrotaIcon,
  'compras_button': ComprasIcon,
  // ... todos os módulos
}
```

**Route Map:**
```typescript
const routeMap: Record<string, string> = {
  'Gestão de Frota': '/frota',
  'Compras': '/compras',
  'Departamento Pessoal': '/departamento-pessoal',
  // ... todos os módulos
}
```

---

## Segurança

### Arquitetura de Segurança

```
┌─────────────────────────────────────┐
│  Frontend (LocalStorage)            │
│  - Apenas controla UI/UX            │
│  - Mostra/esconde botões            │
│  - NÃO é fonte de verdade           │
└──────────────┬────────────────────── ┘
               │
          Token JWT
               │
               ↓
┌─────────────────────────────────────┐
│  Backend (Sanctum + Policies)       │
│  - SEMPRE valida token              │
│  - Verifica permissões reais        │
│  - Fonte de verdade                 │
│  - Ignora dados do localStorage     │
└─────────────────────────────────────┘
```

### Camadas de Proteção

**1. Autenticação (Sanctum)**
```php
Route::middleware(['auth:sanctum'])->group(function () {
    // Todas as rotas de módulos
});
```

**2. Autorização (Policies/Middleware)**
```php
// Verificar se usuário tem acesso ao módulo
if (!$user->hasModuleAccess($moduloId)) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

**3. Validação de Dados**
```php
$request->validate([
    'modulo_id' => 'required|exists:modulos,id',
    'autarquia_id' => 'required|exists:autarquias,id',
]);
```

**4. Logs de Auditoria**
```php
\Log::info('Acesso ao módulo', [
    'user_id' => $user->id,
    'modulo_id' => $moduloId,
    'autarquia_id' => $user->autarquia_id,
    'timestamp' => now()
]);
```

### Perguntas Frequentes de Segurança

**Q: Posso manipular o localStorage para ver módulos de outras autarquias?**
R: Você pode alterar o localStorage, mas:
- ✅ Frontend mostrará os módulos visualmente
- ❌ Backend rejeitará todas as requisições
- ❌ API sempre valida token JWT
- ❌ Backend verifica permissões reais

**Q: E se alguém hackear o token JWT?**
R: Tokens JWT são:
- ✅ Assinados criptograficamente
- ✅ Verificados pelo backend
- ✅ Expiram automaticamente
- ✅ Podem ser revogados
- ❌ Impossível falsificar sem a chave secreta

**Q: Usuário pode acessar módulos que não tem permissão?**
R: Não, porque:
1. Frontend usa `useModulos()` que busca módulos do backend
2. Backend retorna APENAS módulos liberados para aquela autarquia
3. API sempre valida permissões antes de retornar dados
4. Tentativas de acesso não autorizado retornam 403

---

## Modo Suporte SH3

### Funcionamento

Ver documentação completa: [SUPORTE_MODO_CONTEXTO.md](./SUPORTE_MODO_CONTEXTO.md)

**Resumo:**
- Usuários superadmin SH3 podem assumir contexto de qualquer autarquia
- Sistema modifica temporariamente dados do usuário no localStorage
- Usuário se comporta como admin daquela autarquia
- Ao sair, dados originais são restaurados

**Por que é seguro:**
- Token JWT é gerado pelo backend especificamente para aquele contexto
- Backend valida o token e retorna apenas módulos daquela autarquia
- LocalStorage é apenas para UI, backend continua controlando tudo

---

## API Endpoints

### Listar Módulos

```http
GET /api/modulos
GET /api/modulos?autarquia_id=2
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Lista de módulos recuperada com sucesso",
  "data": [
    {
      "id": 1,
      "nome": "Gestão de Frota",
      "descricao": "Módulo para controle...",
      "icone": "frota_button",
      "ativo": true
    }
  ]
}
```

### Atualizar Módulo

```http
PUT /api/modulos/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "nome": "Gestão de Frota",
  "descricao": "Nova descrição",
  "icone": "frota_button",
  "ativo": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Módulo atualizado com sucesso",
  "data": { ... }
}
```

---

## Fluxos de Uso Comuns

### 1. Novo Cliente Contrata Sistema

```
1. Admin SH3 acessa AdminManagementView
2. Cria nova autarquia (Prefeitura X)
3. Acessa aba "Liberações" (futura)
4. Seleciona módulos contratados:
   - Gestão de Frota ✅
   - Contabilidade ✅
   - Almoxarifado ✅
5. Define data de liberação e vencimento
6. Cria usuários para aquela autarquia
7. Define permissões de cada usuário nos módulos
```

### 2. Cliente Solicita Novo Módulo

```
1. Cliente entra em contato com suporte
2. Admin SH3 acessa AdminManagementView
3. Vai para aba "Liberações"
4. Seleciona a autarquia do cliente
5. Ativa o novo módulo solicitado
6. Define data de liberação
7. Cliente já pode acessar o novo módulo
```

### 3. Cliente Inadimplente

```
1. Sistema financeiro detecta inadimplência
2. Admin SH3 acessa AdminManagementView
3. Vai para aba "Liberações"
4. Seleciona a autarquia
5. Desativa todos os módulos (ativo = false)
6. Cliente perde acesso imediatamente
7. Ao regularizar, reativa os módulos
```

### 4. Manutenção de Módulo

```
1. Equipe precisa fazer manutenção em "Contabilidade"
2. Admin SH3 acessa AdminManagementView
3. Vai para aba "Módulos"
4. Desativa "Contabilidade" globalmente
5. TODAS as autarquias perdem acesso temporariamente
6. Manutenção é realizada
7. Reativa o módulo
8. Todas as autarquias voltam a ter acesso
```

### 5. Suporte Precisa Intervir

```
1. Cliente relata problema no módulo
2. Admin SH3 acessa AdminManagementView
3. Seleciona autarquia do cliente
4. Clica em "Acessar" (modo suporte)
5. Sistema redireciona para home com módulos do cliente
6. Admin investiga e resolve o problema
7. Clica em "Sair do Modo Suporte"
8. Retorna ao contexto SH3 original
```

---

## Testes

### Testar Criação de Módulos via Seeder

```bash
# Fresh migration com seed
php artisan migrate:fresh --seed

# Verificar módulos criados
php artisan tinker
>>> \App\Models\Modulo::all()->pluck('nome', 'id')
=> [
     1 => "Gestão de Frota",
     2 => "Departamento Pessoal",
     ...
   ]
```

### Testar Liberação para Autarquia

```bash
php artisan tinker

# Criar relação autarquia-módulo
>>> DB::table('autarquia_modulo')->insert([
...     'autarquia_id' => 2,
...     'modulo_id' => 1,
...     'data_liberacao' => now(),
...     'ativo' => true,
... ]);

# Verificar módulos da autarquia
>>> $autarquia = \App\Models\Autarquia::find(2);
>>> $autarquia->modulos->pluck('nome');
=> ["Gestão de Frota"]
```

### Testar Frontend

```bash
# Iniciar servidor dev
npm run dev

# Acessar como superadmin
# Login: admin@empresa.com / senha123

# Verificar console do navegador
# Deve mostrar: "✅ Módulos carregados para autarquia: X"
```

---

## Troubleshooting

### Módulo não aparece para usuário

**Verificar em ordem:**

1. **Módulo está ativo globalmente?**
```sql
SELECT id, nome, ativo FROM modulos WHERE id = 1;
```

2. **Autarquia tem o módulo liberado?**
```sql
SELECT * FROM autarquia_modulo
WHERE autarquia_id = 2 AND modulo_id = 1;
```

3. **Usuário tem permissão?**
```sql
SELECT * FROM usuario_modulo_permissao
WHERE user_id = 5 AND modulo_id = 1;
```

4. **Cache do navegador?**
```bash
# Limpar localStorage
localStorage.clear()
location.reload()
```

### Ícone não aparece

1. **Arquivo SVG existe?**
```bash
ls frontend/src/assets/icons/frota_button.svg
```

2. **Componente Vue criado?**
```bash
ls frontend/src/components/icons/IconFrota.vue
```

3. **Mapeamento correto?**
```typescript
// useModulos.ts
'frota_button': FrotaIcon  // Nome deve bater com icone do banco
```

### Seeder não atualiza módulos

**Problema:** Executar seeder não atualiza dados

**Causa:** Timestamps `updated_at` podem não estar mudando

**Solução:** Forçar atualização
```php
// ModulosSeeder.php - usar updateOrInsert
DB::table('modulos')->updateOrInsert(
    ['id' => $modulo['id']],
    [
        'nome' => $modulo['nome'],
        'updated_at' => now(), // Força timestamp
    ]
);
```

---
