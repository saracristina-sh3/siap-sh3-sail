# Diagramas de Fluxo - Auth Suite

Este documento contém os principais diagramas de fluxo do sistema Auth Suite, ilustrando processos de autenticação, modo suporte e estrutura de dados.

## Índice

- [Fluxo de Autenticação](#fluxo-de-autenticação)
- [Fluxo de Modo Suporte](#fluxo-de-modo-suporte)
- [Troca de Autarquia](#troca-de-autarquia)
- [Refresh Token Automático](#refresh-token-automático)
- [Estrutura de Dados](#estrutura-de-dados)
  - [Relacionamentos de Autarquias](#relacionamentos-de-autarquias)
  - [Permissões e Módulos](#permissões-e-módulos)

---

## Fluxo de Autenticação

### Login Completo

```mermaid
sequenceDiagram
    actor User
    participant LoginView
    participant AuthService
    participant API
    participant Backend
    participant Database
    participant TokenStorage
    participant Router

    User->>LoginView: Insere credenciais
    LoginView->>AuthService: login(email, password)

    AuthService->>API: POST /api/login
    API->>Backend: AuthController@login
    Backend->>Database: Valida credenciais

    alt Credenciais válidas
        Database-->>Backend: Usuário encontrado
        Backend->>Database: Busca autarquias do usuário
        Backend->>Database: Busca módulos da autarquia preferida
        Backend->>Backend: Gera JWT token (1h)
        Backend->>Backend: Gera refresh token (7d)
        Backend->>Backend: Salva refresh token no BD
        Backend->>Backend: Cria sessão com autarquia_ativa

        Backend-->>API: {token, user, autarquias, modulos}
        API-->>AuthService: 200 OK

        AuthService->>TokenStorage: setToken(token)
        AuthService->>TokenStorage: setRefreshToken(refreshToken)
        AuthService->>localStorage: user_data
        AuthService->>localStorage: autarquias
        AuthService->>localStorage: modulos

        AuthService-->>LoginView: Login bem-sucedido
        LoginView->>Router: Redireciona para /dashboard
        Router-->>User: Exibe Dashboard

    else Credenciais inválidas
        Database-->>Backend: Usuário não encontrado
        Backend-->>API: 401 Unauthorized
        API-->>AuthService: Erro
        AuthService-->>LoginView: "Credenciais inválidas"
        LoginView-->>User: Exibe mensagem de erro
    end
```

### Verificação de Autenticação

```mermaid
flowchart TD
    A[Usuário acessa rota protegida] --> B{Token existe?}
    B -->|Não| C[Redireciona para /login]
    B -->|Sim| D{Token válido?}

    D -->|Não expirou| E[Permite acesso]
    D -->|Expirou| F{Refresh token válido?}

    F -->|Sim| G[Chama /api/refresh]
    G --> H{Refresh bem-sucedido?}
    H -->|Sim| I[Atualiza token]
    I --> E

    H -->|Não| J[Remove tokens]
    J --> C

    F -->|Não| C

    E --> K[Carrega página]
```

---

## Fluxo de Modo Suporte

### Assumir Contexto de Autarquia

```mermaid
sequenceDiagram
    actor Superadmin
    participant SupportView
    participant SupportService
    participant API
    participant Backend
    participant Session
    participant Database
    participant TokenStorage

    Note over Superadmin: Superadmin em modo normal

    Superadmin->>SupportView: Clica em "Assumir Autarquia X"
    SupportView->>SupportService: assumeAutarquiaContext(autarquiaId)

    SupportService->>TokenStorage: Salva token original
    SupportService->>localStorage: Salva user_data original

    SupportService->>API: POST /api/support/assume-context
    Note over API: Header: Authorization Bearer {token}
    API->>Backend: SupportController@assumeContext

    Backend->>Backend: Verifica se é superadmin
    alt Não é superadmin
        Backend-->>API: 403 Forbidden
        API-->>SupportService: Erro
        SupportService-->>SupportView: "Acesso negado"
    else É superadmin
        Backend->>Database: Busca autarquia
        Backend->>Database: Busca módulos da autarquia
        Backend->>Backend: Gera token temporário de suporte
        Backend->>Session: Define autarquia_ativa_id
        Backend->>Session: Define support_mode = true

        Backend-->>API: {token, context}
        Note over Backend,API: context = {autarquia, modulos, permissions}

        API-->>SupportService: 200 OK

        SupportService->>TokenStorage: setToken(support_token)
        SupportService->>localStorage: support_context
        SupportService->>localStorage: support_mode = true

        SupportService-->>SupportView: Contexto assumido
        SupportView->>SupportView: Exibe card "Trabalhando como Autarquia X"
        SupportView->>SupportView: Atualiza menu com módulos da autarquia
        SupportView-->>Superadmin: Agora operando como Autarquia X
    end
```

### Sair do Modo Suporte

```mermaid
sequenceDiagram
    actor Superadmin
    participant SupportCard
    participant SupportService
    participant API
    participant Backend
    participant Session
    participant TokenStorage
    participant Router

    Note over Superadmin: Em modo suporte

    Superadmin->>SupportCard: Clica em "Sair do Modo Suporte"
    SupportCard->>SupportService: exitAutarquiaContext()

    SupportService->>API: POST /api/support/exit-context
    Note over API: Header: Authorization Bearer {support_token}

    API->>Backend: SupportController@exitContext
    Backend->>Session: Remove support_mode
    Backend->>Session: Restaura autarquia_ativa original
    Backend->>Backend: Gera novo token normal

    Backend-->>API: {token, user}
    API-->>SupportService: 200 OK

    SupportService->>TokenStorage: Restaura token original
    SupportService->>localStorage: Remove support_context
    SupportService->>localStorage: Restaura user_data original
    SupportService->>localStorage: support_mode = false

    SupportService-->>SupportCard: Contexto restaurado
    SupportCard->>Router: Recarrega página
    Router-->>Superadmin: Volta ao contexto normal de superadmin
```

### Fluxo de Trabalho em Modo Suporte

```mermaid
flowchart TD
    A[Superadmin Lista Autarquias] --> B[Seleciona Autarquia]
    B --> C[Assume Contexto]

    C --> D[Backend cria token temporário]
    D --> E[Backend define autarquia_ativa na sessão]
    E --> F[Frontend salva contexto de suporte]

    F --> G[Superadmin trabalha como autarquia]
    G --> H{Tipo de operação}

    H -->|CRUD Usuários| I[Gerencia usuários da autarquia]
    H -->|CRUD Módulos| J[Gerencia módulos da autarquia]
    H -->|Permissões| K[Ajusta permissões]
    H -->|Visualização| L[Vê dados da autarquia]

    I --> M{Continua trabalhando?}
    J --> M
    K --> M
    L --> M

    M -->|Sim| G
    M -->|Não| N[Clica em Sair do Modo Suporte]

    N --> O[Backend remove support_mode]
    O --> P[Backend restaura contexto original]
    P --> Q[Frontend restaura dados originais]
    Q --> R[Volta ao painel de superadmin]
```

---

## Troca de Autarquia

### Usuário Normal Trocando de Autarquia

```mermaid
sequenceDiagram
    actor User
    participant AutarquiaSelector
    participant SessionService
    participant API
    participant Backend
    participant Session
    participant Database
    participant Router

    Note over User: Usuário tem múltiplas autarquias

    User->>AutarquiaSelector: Seleciona nova autarquia
    AutarquiaSelector->>SessionService: setActiveAutarquia(autarquiaId)

    SessionService->>API: POST /api/session/set-autarquia
    API->>Backend: SessionController@setAutarquia

    Backend->>Database: Verifica se usuário tem acesso

    alt Usuário não tem acesso
        Backend-->>API: 403 Forbidden
        API-->>SessionService: Erro
        SessionService-->>AutarquiaSelector: "Acesso negado"
    else Usuário tem acesso
        Backend->>Session: Atualiza autarquia_ativa_id
        Backend->>Database: Busca módulos da nova autarquia
        Backend->>Database: Busca permissões do usuário

        Backend-->>API: {autarquia, modulos, permissions}
        API-->>SessionService: 200 OK

        SessionService->>localStorage: Atualiza user_data.autarquia_ativa_id
        SessionService->>localStorage: Atualiza modulos
        SessionService->>localStorage: Atualiza permissions

        SessionService-->>AutarquiaSelector: Troca bem-sucedida
        AutarquiaSelector->>Router: Recarrega página
        Router-->>User: Página atualizada com nova autarquia
    end
```

### Fluxo de Decisão: Autarquia Preferida vs Ativa

```mermaid
flowchart TD
    A[Usuário faz Login] --> B{Tem autarquia_preferida_id?}

    B -->|Sim| C[Define autarquia_ativa_id = autarquia_preferida_id]
    B -->|Não| D[Define autarquia_ativa_id = primeira autarquia]

    C --> E[Carrega módulos da autarquia ativa]
    D --> E

    E --> F[Usuário trabalha no sistema]
    F --> G{Troca de autarquia?}

    G -->|Não| F
    G -->|Sim| H[Atualiza autarquia_ativa_id na sessão]

    H --> I[autarquia_preferida_id permanece a mesma]
    I --> J[Carrega módulos da nova autarquia ativa]
    J --> F

    F --> K[Logout]
    K --> L[Próximo login: volta para autarquia_preferida_id]
```

---

## Refresh Token Automático

### Auto-Refresh de Token

```mermaid
sequenceDiagram
    participant App
    participant Interceptor
    participant API
    participant AuthService
    participant TokenStorage
    participant Backend

    Note over App: Token expira em 1 hora

    App->>API: Requisição qualquer
    API->>Interceptor: Intercepta request

    Interceptor->>Interceptor: Verifica expiração do token

    alt Token válido (> 5min restantes)
        Interceptor->>API: Prossegue com request
        API->>Backend: Executa requisição
        Backend-->>API: Resposta
        API-->>App: Dados

    else Token expirando (< 5min)
        Note over Interceptor: Previne múltiplas tentativas
        Interceptor->>AuthService: refreshToken()

        AuthService->>TokenStorage: getRefreshToken()
        TokenStorage-->>AuthService: refresh_token

        AuthService->>Backend: POST /api/refresh
        Note over AuthService,Backend: Body: {refresh_token}

        alt Refresh válido
            Backend->>Backend: Valida refresh token
            Backend->>Backend: Gera novo access token
            Backend->>Backend: Gera novo refresh token
            Backend-->>AuthService: {token, refresh_token}

            AuthService->>TokenStorage: setToken(new_token)
            AuthService->>TokenStorage: setRefreshToken(new_refresh)

            AuthService-->>Interceptor: Token renovado
            Interceptor->>API: Retenta request original
            API->>Backend: Executa requisição
            Backend-->>API: Resposta
            API-->>App: Dados

        else Refresh inválido
            Backend-->>AuthService: 401 Unauthorized
            AuthService->>TokenStorage: clearTokens()
            AuthService->>App: Redireciona para /login
        end

    else Token expirado
        Interceptor->>Backend: Tenta requisição
        Backend-->>Interceptor: 401 Unauthorized

        Interceptor->>AuthService: refreshToken()
        Note over AuthService: Mesmo fluxo acima
    end
```

### Gerenciamento de Refresh Token

```mermaid
flowchart TD
    A[Sistema Iniciado] --> B{Token existe?}

    B -->|Não| C[Redireciona /login]
    B -->|Sim| D{Token válido?}

    D -->|Sim, > 5min| E[Usa token normalmente]
    D -->|Expirando < 5min| F[Inicia auto-refresh]
    D -->|Expirado| G{Refresh token válido?}

    F --> H[POST /api/refresh]
    H --> I{Refresh bem-sucedido?}

    I -->|Sim| J[Atualiza tokens]
    J --> E

    I -->|Não| K[Remove tokens]
    K --> C

    G -->|Sim| H
    G -->|Não| C

    E --> L[Usuário trabalha]
    L --> M{Faz requisição?}

    M -->|Sim| N[Interceptor verifica token]
    N --> D

    M -->|Não| L
```

---

## Estrutura de Dados

### Relacionamentos de Autarquias

```mermaid
erDiagram
    USERS ||--o{ USUARIO_AUTARQUIA : possui
    AUTARQUIAS ||--o{ USUARIO_AUTARQUIA : contém
    AUTARQUIAS ||--o{ AUTARQUIA_MODULO : possui
    MODULOS ||--o{ AUTARQUIA_MODULO : disponível_em
    USERS ||--o{ USUARIO_MODULO_PERMISSAO : tem
    MODULOS ||--o{ USUARIO_MODULO_PERMISSAO : referente_a
    AUTARQUIAS ||--|| USERS : preferida_por

    USERS {
        bigint id PK
        string name
        string email
        string cpf
        string role "user|admin|superadmin"
        boolean is_superadmin
        boolean is_active
        bigint autarquia_preferida_id FK
        string refresh_token
        timestamp refresh_token_expires_at
        timestamp created_at
        timestamp updated_at
    }

    AUTARQUIAS {
        bigint id PK
        string nome
        string cnpj
        string cidade
        string estado
        boolean ativo
        timestamp created_at
        timestamp updated_at
    }

    USUARIO_AUTARQUIA {
        bigint id PK
        bigint usuario_id FK
        bigint autarquia_id FK
        string role "user|admin"
        boolean is_admin
        boolean is_default
        boolean ativo
        timestamp created_at
    }

    MODULOS {
        bigint id PK
        string nome
        string slug
        string descricao
        boolean ativo
        timestamp created_at
    }

    AUTARQUIA_MODULO {
        bigint id PK
        bigint autarquia_id FK
        bigint modulo_id FK
        boolean ativo
        date data_ativacao
        timestamp created_at
    }

    USUARIO_MODULO_PERMISSAO {
        bigint id PK
        bigint usuario_id FK
        bigint modulo_id FK
        bigint autarquia_id FK
        boolean view
        boolean create
        boolean edit
        boolean delete
        timestamp created_at
        timestamp updated_at
    }
```

### Estrutura de Dados: Autarquia Preferida vs Ativa

```mermaid
flowchart LR
    subgraph "Tabela: users"
        A[id: 1<br/>name: João Silva<br/>autarquia_preferida_id: 10<br/>role: user]
    end

    subgraph "Tabela: usuario_autarquia Pivot"
        B[usuario_id: 1<br/>autarquia_id: 10<br/>is_default: true<br/>role: admin]
        C[usuario_id: 1<br/>autarquia_id: 20<br/>is_default: false<br/>role: user]
        D[usuario_id: 1<br/>autarquia_id: 30<br/>is_default: false<br/>role: user]
    end

    subgraph "Sessão Laravel"
        E[autarquia_ativa_id: 10<br/>support_mode: false]
    end

    subgraph "Comportamento"
        F[Login: autarquia_ativa = 10<br/>Primeira autarquia]
        G[Usuário troca para 20<br/>autarquia_ativa = 20]
        H[Logout e Login novamente<br/>autarquia_ativa = 10<br/>Volta para preferida]
    end

    A -->|Define padrão permanente| B
    A -->|Pode acessar| C
    A -->|Pode acessar| D

    B -->|No login inicial| E
    E -->|Durante sessão| F
    F -->|Pode trocar| G
    G -->|Próximo login| H

    style B fill:#90EE90
    style E fill:#FFD700
    style F fill:#87CEEB
    style G fill:#FFA07A
    style H fill:#DDA0DD
```

### Permissões e Módulos

```mermaid
flowchart TD
    A[Autarquia] --> B{Tem módulo ativado?}

    B -->|Não| C[Módulo não aparece para usuários]
    B -->|Sim| D[Módulo disponível]

    D --> E[Usuário da Autarquia]
    E --> F{Tem permissões<br/>no módulo?}

    F -->|Não| G[Módulo aparece mas<br/>todas ações bloqueadas]
    F -->|Sim| H{Quais permissões?}

    H -->|view| I[Pode visualizar]
    H -->|create| J[Pode criar registros]
    H -->|edit| K[Pode editar registros]
    H -->|delete| L[Pode deletar registros]

    I --> M[Interface exibe dados]
    J --> N[Botão Novo aparece]
    K --> O[Botão Editar aparece]
    L --> P[Botão Deletar aparece]

    subgraph "Tabela: autarquia_modulo"
        Q[Controla se módulo<br/>está ativo na autarquia]
    end

    subgraph "Tabela: usuario_modulo_permissao"
        R[Controla permissões<br/>específicas do usuário<br/>naquele módulo]
    end

    B -.->|Verifica| Q
    F -.->|Verifica| R
```

### Fluxo de Verificação de Permissões

```mermaid
sequenceDiagram
    actor User
    participant Frontend
    participant Backend
    participant PermissionCheck
    participant Database

    User->>Frontend: Acessa página de Módulo X
    Frontend->>Backend: GET /api/modulos/X/check-permission

    Backend->>PermissionCheck: Verifica permissões
    PermissionCheck->>Database: SELECT autarquia_modulo<br/>WHERE autarquia_id = ? AND modulo_id = ?

    alt Módulo não ativo na autarquia
        Database-->>Backend: Módulo inativo
        Backend-->>Frontend: 403 Forbidden
        Frontend-->>User: "Módulo não disponível"
    else Módulo ativo
        Database-->>PermissionCheck: Módulo ativo

        PermissionCheck->>Database: SELECT usuario_modulo_permissao<br/>WHERE usuario_id = ? AND modulo_id = ?
        Database-->>PermissionCheck: {view, create, edit, delete}

        PermissionCheck-->>Backend: Permissões encontradas
        Backend-->>Frontend: {view: true, create: false, edit: true, delete: false}

        Frontend->>Frontend: Renderiza interface baseado em permissões
        Frontend-->>User: Exibe módulo com ações permitidas
    end
```

---

## Modo Suporte: Hierarquia de Acesso

```mermaid
flowchart TD
    A[Tipo de Usuário] --> B{Role}

    B -->|superadmin| C[Superadmin]
    B -->|admin| D[Admin de Autarquia]
    B -->|user| E[Usuário Normal]

    C --> F[Acesso Total]
    F --> G[Pode assumir contexto<br/>de qualquer autarquia]
    G --> H[Modo Suporte Ativado]

    H --> I{Operações em Modo Suporte}
    I -->|CRUD| J[Gerencia usuários da autarquia]
    I -->|CRUD| K[Gerencia módulos da autarquia]
    I -->|Leitura| L[Vê todos os dados]
    I -->|Config| M[Ajusta permissões]

    H --> N[Pode sair a qualquer momento]
    N --> O[Volta ao contexto de superadmin]

    D --> P[Acesso à SUA Autarquia]
    P --> Q[Gerencia usuários<br/>da própria autarquia]
    P --> R[Gerencia módulos<br/>da própria autarquia]
    P --> S[NÃO pode assumir<br/>outras autarquias]

    E --> T[Acesso Limitado]
    T --> U[Vê apenas suas<br/>permissões]
    T --> V[Usa módulos permitidos]
    T --> W[NÃO gerencia outros usuários]

    style C fill:#FF6B6B
    style D fill:#4ECDC4
    style E fill:#95E1D3
    style H fill:#FFD93D
```

---

## Notas Técnicas

### Sobre Tokens

- **Access Token**: JWT válido por 1 hora, armazenado em `localStorage` como `auth_token`
- **Refresh Token**: String aleatória válida por 7 dias, armazenado em `localStorage` como `refresh_token`
- **Support Token**: JWT temporário gerado ao assumir contexto, substitui o access token durante modo suporte

### Sobre Autarquias

- **autarquia_preferida_id**: Campo na tabela `users`, define qual autarquia será selecionada automaticamente no login
- **autarquia_ativa_id**: Armazenada na sessão Laravel (server-side), representa a autarquia que o usuário está trabalhando no momento
- **Troca de autarquia**: Atualiza apenas `autarquia_ativa_id` na sessão, `autarquia_preferida_id` permanece inalterada

### Sobre Modo Suporte

- Apenas superadmins podem ativar modo suporte
- Token temporário é gerado com informações da autarquia assumida
- Contexto original é salvo para restauração ao sair
- Backend identifica modo suporte pela sessão `support_mode = true`

---

**Última atualização**: 2024-10-27
