# Modo Suporte - Sistema de Contexto de Autarquia

## Visão Geral

O **Modo Suporte** permite que usuários da equipe de suporte SH3 (superadmin) assumam temporariamente o contexto de qualquer autarquia do sistema, obtendo acesso administrativo completo a todos os módulos e funcionalidades daquela autarquia, sem necessidade de criar usuários separados ou modificar permissões permanentemente.

## Objetivo

Facilitar o trabalho de suporte técnico, permitindo que a equipe SH3 acesse e intervenha em sistemas de autarquias específicas de forma ágil e segura, mantendo logs completos de auditoria.

---

## Arquitetura

### Componentes Backend

#### 1. AuthController - Endpoints de Suporte

**Arquivo:** `backend/app/Http/Controllers/Api/AuthController.php`

##### Endpoint: Assumir Contexto
```php
POST /api/support/assume-context
```

**Autenticação:** Requerida (Sanctum)
**Permissão:** Apenas usuários com `is_superadmin = true`

**Request Body:**
```json
{
  "autarquia_id": 2
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Contexto assumido: Prefeitura Municipal X",
  "token": "2|CFlmhGlGp4MowJnGjsBwKcbOl7HMizBUu8aEG9i4583b6899",
  "context": {
    "autarquia": {
      "id": 2,
      "nome": "Prefeitura Municipal X",
      "ativo": true
    },
    "support_mode": true,
    "is_admin": true,
    "modulos": [
      {
        "id": 1,
        "nome": "Gestão de Frota",
        "descricao": "Módulo para controle e gestão da frota de veículos municipais",
        "icone": "truck",
        "ativo": true
      }
    ],
    "permissions": {
      "view": true,
      "create": true,
      "edit": true,
      "delete": true,
      "manage_users": true,
      "manage_modules": true
    }
  }
}
```

**Response (Error - 403):**
```json
{
  "success": false,
  "message": "Acesso negado. Apenas usuários de suporte podem usar esta funcionalidade."
}
```

**Response (Error - 400):**
```json
{
  "success": false,
  "message": "Esta autarquia está inativa."
}
```

##### Endpoint: Sair do Contexto
```php
POST /api/support/exit-context
```

**Autenticação:** Requerida (Sanctum)

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Retornado ao contexto original",
  "token": "3|NewTokenForOriginalContext",
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@empresa.com",
    "cpf": "12345678901",
    "role": "superadmin",
    "autarquia_id": 1,
    "autarquia": {
      "id": 1,
      "nome": "SH3 - Suporte",
      "ativo": true
    },
    "is_active": true,
    "is_superadmin": true
  }
}
```

#### 2. Rotas API

**Arquivo:** `backend/routes/api.php`

```php
Route::middleware(['auth:sanctum'])->group(function () {
    // Suporte: Assumir contexto de autarquia (apenas para superadmin/Sh3)
    Route::post('/support/assume-context', [AuthController::class, 'assumeAutarquiaContext']);
    Route::post('/support/exit-context', [AuthController::class, 'exitAutarquiaContext']);
});
```

#### 3. Segurança e Validações

- ✅ **Middleware Sanctum:** Todas as rotas protegidas por autenticação
- ✅ **Verificação de Superadmin:** Apenas usuários com `is_superadmin = true`
- ✅ **Validação de Autarquia:** Verifica se a autarquia existe e está ativa
- ✅ **Tokens Isolados:** Cada sessão de suporte gera um novo token
- ✅ **Logs Completos:** Todas as operações são logadas para auditoria

---

### Componentes Frontend

#### 1. Serviço de Suporte

**Arquivo:** `frontend/src/services/support.service.ts`

**Responsabilidades:**
- Comunicação com endpoints de suporte
- Gerenciamento de contexto no localStorage
- Atualização de tokens de autenticação
- Verificação de permissões em modo suporte

**Principais Métodos:**

##### `assumeAutarquiaContext(autarquiaId: number): Promise<SupportContext>`
Assume o contexto de uma autarquia específica.

```typescript
const context = await supportService.assumeAutarquiaContext(2)
// Retorna o contexto de suporte com autarquia, módulos e permissões
```

##### `exitAutarquiaContext(): Promise<void>`
Retorna ao contexto original do usuário.

```typescript
await supportService.exitAutarquiaContext()
// Remove o contexto de suporte e restaura o usuário original
```

##### `isInSupportMode(): boolean`
Verifica se está em modo suporte.

```typescript
const inSupportMode = supportService.isInSupportMode()
// true se estiver em modo suporte, false caso contrário
```

##### `getSupportContext(): SupportContext | null`
Obtém o contexto de suporte atual.

```typescript
const context = supportService.getSupportContext()
// Retorna o contexto ou null se não estiver em modo suporte
```

##### `getCurrentAutarquia(): Autarquia | null`
Obtém a autarquia do contexto atual.

```typescript
const autarquia = supportService.getCurrentAutarquia()
// Retorna a autarquia ativa no modo suporte
```

##### `getCurrentModulos(): Modulo[]`
Obtém os módulos disponíveis no contexto.

```typescript
const modulos = supportService.getCurrentModulos()
// Retorna array de módulos da autarquia
```

##### `hasPermission(permission: string): boolean`
Verifica se tem permissão específica.

```typescript
const canEdit = supportService.hasPermission('edit')
// Retorna true/false baseado nas permissões do contexto
```

#### 2. Interface de Gerenciamento

**Arquivo:** `frontend/src/views/suporte/AdminManagementView.vue`

**Funcionalidades:**
- Seleção de autarquia via Dropdown PrimeVue
- Visualização de autarquias ativas/inativas
- Botão para assumir contexto
- Barra de alerta indicando modo suporte ativo
- Botão para sair do modo suporte
- Redirecionamento automático para home após assumir contexto

**Componentes PrimeVue Utilizados:**
- `Card` - Container para seleção de autarquia
- `Dropdown` - Seletor de autarquias
- `Message` - Barra de alerta de modo suporte ativo
- `Button` - Ações de acessar e sair
- `Tag` - Indicadores de status (Ativa/Inativa)

---

## Fluxo de Uso

### 1. Acesso Inicial

```
Usuário SH3 Superadmin
    ↓
Login no Sistema
    ↓
Acesso à rota /suporteSH3
    ↓
AdminManagementView é carregado
```

### 2. Assumir Contexto

```
Usuário visualiza lista de autarquias
    ↓
Seleciona autarquia no dropdown
    ↓
Clica em "Acessar"
    ↓
Frontend chama supportService.assumeAutarquiaContext()
    ↓
POST /api/support/assume-context
    ↓
Backend valida superadmin e autarquia
    ↓
Backend cria novo token com contexto
    ↓
Backend retorna contexto completo
    ↓
Frontend atualiza token e salva contexto
    ↓
Redirecionamento para home (/)
    ↓
Usuário acessa módulos da autarquia com permissões admin
```

### 3. Sair do Contexto

```
Usuário clica em "Sair do Modo Suporte"
    ↓
Confirmação de saída
    ↓
Frontend chama supportService.exitAutarquiaContext()
    ↓
POST /api/support/exit-context
    ↓
Backend revoga token de suporte
    ↓
Backend cria novo token normal
    ↓
Backend retorna dados do usuário original
    ↓
Frontend atualiza token e remove contexto
    ↓
Usuário retorna ao contexto SH3 original
```

---

## Estrutura de Dados

### SupportContext Interface

```typescript
interface SupportContext {
  autarquia: {
    id: number
    nome: string
    ativo: boolean
  }
  support_mode: boolean
  is_admin: boolean
  modulos: Array<{
    id: number
    nome: string
    descricao: string
    icone: string
    ativo: boolean
  }>
  permissions: {
    view: boolean
    create: boolean
    edit: boolean
    delete: boolean
    manage_users: boolean
    manage_modules: boolean
  }
}
```

### Armazenamento

**LocalStorage Keys:**
- `auth_token` - Token JWT de autenticação
- `support_context` - Contexto de suporte ativo (JSON)
- `user_data` - Dados do usuário

---

## Logs e Auditoria

Todos os eventos de suporte são logados no Laravel:

### Eventos Logados

1. **Tentativa de Assumir Contexto**
```
🔄 Tentativa de assumir contexto de autarquia
{
  user_id: 1,
  user_role: "superadmin",
  is_superadmin: true,
  autarquia_id: 2
}
```

2. **Sucesso ao Assumir Contexto**
```
✅ Contexto de autarquia assumido com sucesso
{
  user_id: 1,
  autarquia_id: 2,
  autarquia_nome: "Prefeitura Municipal X",
  modulos_count: 3
}
```

3. **Acesso Negado**
```
❌ Acesso negado - usuário não é superadmin
{
  user_id: 5,
  role: "admin"
}
```

4. **Saída do Contexto**
```
🔙 Saindo do contexto de autarquia
{
  user_id: 1
}

✅ Retornado ao contexto original
{
  user_id: 1,
  autarquia_original_id: 1
}
```

---

## Segurança

### Validações Implementadas

1. **Autenticação**
   - Middleware Sanctum em todas as rotas
   - Verificação de token válido
   - Verificação de usuário autenticado

2. **Autorização**
   - Apenas usuários com `is_superadmin = true`
   - Verificação em cada requisição
   - Token específico para sessão de suporte

3. **Validação de Dados**
   - Autarquia deve existir
   - Autarquia deve estar ativa
   - IDs devem ser numéricos válidos

4. **Auditoria**
   - Logs detalhados de todas as operações
   - Registro de usuário, data/hora e ação
   - Histórico de tokens criados

### Boas Práticas

- ✅ Não expor informações sensíveis nos logs
- ✅ Revogar tokens antigos ao sair do contexto
- ✅ Validar autarquia ativa antes de assumir contexto
- ✅ Limitar permissões mesmo em modo suporte
- ✅ Implementar timeout de sessão
- ✅ Monitorar uso excessivo

---

## Exemplos de Uso

### Exemplo 1: Assumir Contexto no Frontend

```typescript
import { supportService } from '@/services/support.service'

async function accessAutarquia(autarquiaId: number) {
  try {
    const context = await supportService.assumeAutarquiaContext(autarquiaId)

    console.log('Autarquia:', context.autarquia.nome)
    console.log('Módulos disponíveis:', context.modulos.length)
    console.log('Permissões:', context.permissions)

    // Redirecionar para home
    router.push({ name: 'home' })
  } catch (error) {
    console.error('Erro ao assumir contexto:', error)
  }
}
```

### Exemplo 2: Verificar Modo Suporte

```typescript
import { supportService } from '@/services/support.service'

// Em qualquer componente
const inSupportMode = supportService.isInSupportMode()

if (inSupportMode) {
  const autarquia = supportService.getCurrentAutarquia()
  console.log('Modo suporte ativo para:', autarquia?.nome)
}
```

### Exemplo 3: Verificar Permissões

```typescript
import { supportService } from '@/services/support.service'

// Verificar se pode editar
if (supportService.hasPermission('edit')) {
  // Mostrar botão de editar
}

// Verificar se pode deletar
if (supportService.hasPermission('delete')) {
  // Mostrar botão de deletar
}
```

### Exemplo 4: Sair do Modo Suporte

```typescript
import { supportService } from '@/services/support.service'

async function exitSupportMode() {
  try {
    await supportService.exitAutarquiaContext()
    console.log('Retornado ao contexto original')

    // Opcional: redirecionar para dashboard de suporte
    router.push({ name: 'suporte-sh3' })
  } catch (error) {
    console.error('Erro ao sair do contexto:', error)
  }
}
```

---

## Integração com Sistema Existente

### Como Usar em Novos Componentes

1. **Importar o serviço**
```typescript
import { supportService } from '@/services/support.service'
```

2. **Verificar modo suporte**
```typescript
const inSupportMode = supportService.isInSupportMode()
```

3. **Obter dados do contexto**
```typescript
const context = supportService.getSupportContext()
const autarquia = supportService.getCurrentAutarquia()
const modulos = supportService.getCurrentModulos()
```

4. **Verificar permissões**
```typescript
const canEdit = supportService.hasPermission('edit')
```

### Indicadores Visuais Recomendados

Adicione indicadores visuais em componentes importantes:

```vue
<template>
  <div>
    <!-- Barra de alerta de modo suporte -->
    <Message v-if="inSupportMode" severity="warn">
      <i class="pi pi-shield"></i>
      Modo Suporte: {{ currentAutarquia?.nome }}
    </Message>

    <!-- Conteúdo normal -->
    <div>...</div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { supportService } from '@/services/support.service'

const inSupportMode = computed(() => supportService.isInSupportMode())
const currentAutarquia = computed(() => supportService.getCurrentAutarquia())
</script>
```

---

## Troubleshooting

### Problema: Redirecionamento não funciona

**Solução:** Verifique se a rota existe no router
```typescript
// Verificar nome correto da rota
router.push({ name: 'home' }) // ✅ Correto
router.push({ name: 'suite-home' }) // ❌ Rota não existe
```

### Problema: Token não está sendo enviado

**Solução:** Verifique se o token está no localStorage
```javascript
const token = localStorage.getItem('auth_token')
console.log('Token:', token)
```

### Problema: Contexto não persiste após refresh

**Solução:** Verificar se o contexto está sendo carregado no `onMounted`
```typescript
onMounted(() => {
  supportContext.value = supportService.getSupportContext()
})
```

### Problema: Erro 403 ao assumir contexto

**Solução:** Verificar se o usuário é superadmin
```sql
SELECT id, name, email, is_superadmin FROM users WHERE id = 1;
```

### Problema: Autarquia não aparece na lista

**Solução:** Verificar se a autarquia está ativa
```sql
SELECT id, nome, ativo FROM autarquias;
UPDATE autarquias SET ativo = true WHERE id = 2;
```

---

## Manutenção e Extensibilidade

### Adicionar Novas Permissões

1. **Backend:** Atualizar o array de permissões no `AuthController`
```php
'permissions' => [
    'view' => true,
    'create' => true,
    'edit' => true,
    'delete' => true,
    'manage_users' => true,
    'manage_modules' => true,
    'export_data' => true, // Nova permissão
]
```

2. **Frontend:** Atualizar interface `SupportContext`
```typescript
interface SupportContext {
  permissions: {
    view: boolean
    create: boolean
    edit: boolean
    delete: boolean
    manage_users: boolean
    manage_modules: boolean
    export_data: boolean // Nova permissão
  }
}
```

### Adicionar Logs Personalizados

```php
\Log::info('📊 Ação customizada no modo suporte', [
    'user_id' => $user->id,
    'autarquia_id' => $context->autarquia_id,
    'action' => 'export_data',
    'timestamp' => now()
]);
```

---

## FAQ

**Q: Posso ter múltiplos contextos ativos simultaneamente?**
R: Não. Apenas um contexto de suporte pode estar ativo por vez. Ao assumir um novo contexto, o anterior é substituído.

**Q: O token de suporte expira?**
R: Sim, os tokens Sanctum têm expiração configurável. Consulte `config/sanctum.php` no backend.

**Q: Posso assumir contexto de uma autarquia inativa?**
R: Não. O sistema valida se a autarquia está ativa antes de permitir o acesso.

**Q: Os logs de suporte são permanentes?**
R: Sim, todos os logs ficam armazenados em `storage/logs/laravel.log` no backend.

**Q: Como adicionar mais usuários de suporte?**
R: Crie um usuário com `is_superadmin = true` e `autarquia_id` apontando para a autarquia SH3.

---

