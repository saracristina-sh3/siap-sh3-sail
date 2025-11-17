# Análise Crítica: Arquitetura Monólito Modular (Laravel Modules) – Revisada para o SH3-SIAP

**Data:** 05 de Novembro de 2025 (atualizada)

A decisão de adotar a arquitetura de **Monólito Modular** utilizando **Laravel Modules (nwidart/laravel-modules)** é uma excelente escolha e representa o *trade-off* ideal para:

- o tamanho da equipe (4 devs),
- o contexto de **migração de legado**,
- e a necessidade de **evoluir rápido** sem explodir a complexidade de infraestrutura.

Essa arquitetura combina:

- a **simplicidade operacional** de um monólito (um único app Laravel, um único deploy),
- com a **separação de domínios** via módulos (`Modules/AuthCore`, `Modules/ClientCore`, futuros módulos de Frota, Patrimônio, etc.),
- e o **multi-tenant avançado** via **schemas do PostgreSQL** (`common`, `santa_cruz_de_minas`, `barroso`, etc).

---

## 1. Avaliação da Arquitetura Atual (Monólito Modular com Laravel Modules)

| Característica           | Microserviços / Monólito distribuído (proposta antiga) | **Monólito Modular (arquitetura atual)**         |
| ------------------------ | -------------------------------------------------------- | ------------------------------------------------- |
| **Isolamento de Deploy** | Alto (vários serviços / contêineres)                    | **Baixo** (um único app Laravel)                 |
| **Isolamento de Falhas** | Alto (queda de um serviço não derruba os outros)        | **Baixo** (falha grave pode afetar todo o app)   |
| **Separação de Código**  | Alta (repositórios separados)                           | **Alta** (módulos isolados em `Modules/*`)       |
| **Complexidade de Infra**| Alta (orquestração de vários serviços)                  | **Baixa** (Docker com app + db + nginx)          |
| **Velocidade de Entrega**| Menor (mais devops, mais glue code)                     | **Maior** (um repositório, um pipeline)          |
| **Aderência à Equipe**   | Exige time maior e maduro em DevOps                     | **Ideal para equipe pequena e foco em migração** |

### Conclusão

Para o contexto do SH3-SIAP hoje:

- ✅ **Monólito Modular (Laravel Modules) é a escolha certa.**
- ✅ Garante **organização por domínio** (AuthCore, ClientCore, futuros módulos de Frota, etc.).
- ✅ Mantém **infra simples** (um app, um DB, um nginx).
- ✅ Permite crescer o código sem virar “bolo de espaguete”.

O ponto fraco natural continua sendo:

- Menor isolamento de falhas e deploy em comparação com microserviços.

Mas, para a fase atual (migração + validação de modelo), o **benefício de simplicidade e velocidade** supera esse risco.

---

## 2. Organização da Lógica por Módulos (Laravel Modules)

Em vez de separar cada domínio em um **repositório Composer externo** (pacotes privados), o projeto adota **módulos internos**:

- `Modules/AuthCore` – autenticação, usuários, autarquias, schemas, suporte.
- `Modules/ClientCore` – cadastros base (municípios, autarquias, estruturas iniciais).
- Futuro: `Modules/Frota`, `Modules/Patrimonio`, `Modules/Orcamento`, etc.

### 2.1. Por que ficar em Monólito Modular (por enquanto)?

1. **Menos atrito operacional**  
   - Um único `composer.json`, um único repositório Git.
   - Menos dor de cabeça com versionamento entre pacotes.

2. **Migração de legado mais rápida**  
   - Fica mais fácil ir trazendo funcionalidades por módulo, sem ter que pensar em publicar/atualizar pacotes o tempo todo.

3. **Menos devops, mais entrega**  
   - Docker simples: `app` (PHP-FPM + Laravel) + `db` (Postgres) + `nginx`.

4. **Separa o que importa:**  
   - O que muda: **módulos** (`Modules/*`), rotas, migrations por domínio.
   - O que fica estável: **infra + core de multi-tenant** (SchemaManager, TenantResolver, middleware).

### 2.2. Evolução possível: Pacotes Composer Privados (Futuro)

A ideia de transformar alguns módulos em **pacotes Composer privados** continua sendo boa, mas como **evolução**, não como desenho atual.

Exemplo de futuro (não implementado ainda):

| Módulo                 | Repositório futuro (possível)       |
| ---------------------- | ------------------------------------ |
| AuthCore               | `sh3/auth-core`                      |
| ClientCore             | `sh3/client-core`                    |
| Frota                  | `sh3/frota-module`                   |
| Departamento Pessoal   | `sh3/dp-module`                      |

Hoje, porém, **tudo reside em um único repositório** do Laravel, com módulos internos em `Modules/*`.

---

## 3. Diagrama de Arquitetura (Monólito Modular Multi-Tenant)

Abaixo, um diagrama mais alinhado com o que estamos implementando:

- Um **único app Laravel**.
- Módulos organizados em `Modules/*`.
- Multi-tenant via **schemas no PostgreSQL**.
- Nginx na frente como proxy.

```mermaid
graph LR
    subgraph Cliente
        A[Usuário / Frontend (Inertia + Vue 3)]
    end

    subgraph Infraestrutura
        B[Nginx / Reverse Proxy]
        C[(PostgreSQL)]
    end

    subgraph Aplicacao["Laravel 12 - Monólito Modular"]
        direction LR
        D[App Laravel (HTTP Kernel, Middleware, SchemaManager)]

        subgraph Modules["Modules/* (Laravel Modules)"]
            direction TB
            M1[AuthCore<br/>• Login / Logout<br/>• Usuários<br/>• Autarquias<br/>• Suporte (modo contexto)]
            M2[ClientCore<br/>• Municípios<br/>• Estrutura base]
            M3[Frota (futuro)]
            M4[Patrimônio (futuro)]
            M5[Orçamento (futuro)]
            M6[Tesouraria (futuro)]
        end

        D --> M1
        D --> M2
        D --> M3
        D --> M4
        D --> M5
        D --> M6
    end

    subgraph Schemas_PostgreSQL["Schemas do PostgreSQL (Multi-tenant)"]
        direction TB
        S0[common<br/>• users<br/>• municipios<br/>• autarquias<br/>• modulos<br/>• user_autarquia<br/>• cache/sessions/jobs]
        S1[santa_cruz_de_minas<br/>• pref_santa_cruz_de_minas_*<br/>• cam_santa_cruz_de_minas_*<br/>• saude_santa_cruz_de_minas_*]
        S2[barroso<br/>• pref_barroso_*<br/>• cam_barroso_*<br/>• saude_barroso_*]
        S3[tiradentes<br/>• pref_tiradentes_*<br/>• cam_tiradentes_*<br/>• saude_tiradentes_*]
    end

    A --> B
    B --> D
    D --> C
    C --> S0
    C --> S1
    C --> S2
    C --> S3

    %% Destaques
    style D fill:#E0F7FA,stroke:#00BCD4,stroke-width:2px
    style Modules fill:#F3E5F5,stroke:#7B1FA2
    style S0 fill:#FFF9C4,stroke:#FBC02D
    style S1 fill:#E3F2FD,stroke:#1976D2
    style S2 fill:#E8F5E9,stroke:#388E3C
    style S3 fill:#FCE4EC,stroke:#C2185B
