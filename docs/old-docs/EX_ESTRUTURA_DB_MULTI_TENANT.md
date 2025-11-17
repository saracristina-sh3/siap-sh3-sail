# Exemplo de Estrutura de Banco de Dados: Schema-per-Autarquia

**Cliente Exemplo:** Município de Santa Cruz
**SGBD:** PostgreSQL

A estratégia de **Schema-per-Autarquia** garante o isolamento de dados operacionais (Business Services) e a centralização de dados de controle (CORE Service) dentro de um único banco de dados PostgreSQL.

---

## 1. Estrutura do Banco de Dados

Para o Município de Santa Cruz, teremos um único banco de dados PostgreSQL. Dentro deste banco, a separação será feita por **Schemas**.

| Tipo de Schema | Nome do Schema (Exemplo) | Serviço Responsável | Conteúdo | Isolamento |
| :--- | :--- | :--- | :--- | :--- |
| **Schema Global (CORE)** | `public` (ou `core`) | `core-service` (Auth Suite) | Dados compartilhados e de controle: Usuários, Autarquias, Permissões, Pessoas Físicas/Jurídicas, Produtos, Centros de Custo. | Compartilhado (acessível por todos os serviços) |
| **Schema de Tenant (Autarquia)** | `pref_santacruz` | `financeiro-service`, `logistica-service` | Dados operacionais da Prefeitura: Lançamentos Contábeis, Bens Patrimoniais, Pedidos de Compra, Frotas. | Isolado (acessível apenas via *Schema Switching*) |
| **Schema de Tenant (Autarquia)** | `camara_santacruz` | `financeiro-service`, `logistica-service` | Dados operacionais da Câmara: Lançamentos Contábeis, Bens Patrimoniais, Pedidos de Compra, Frotas. | Isolado (acessível apenas via *Schema Switching*) |
| **Schema de Tenant (Autarquia)** | `aguas_santacruz` | `financeiro-service`, `logistica-service` | Dados operacionais da Autarquia de Água e Esgoto: Lançamentos Contábeis, Bens Patrimoniais, Pedidos de Compra, Frotas. | Isolado (acessível apenas via *Schema Switching*) |

---

## 2. Detalhamento dos Schemas

### 2.1. Schema Global (`common`)

Este *schema* é o único que contém a coluna `autarquia_id` em algumas tabelas, apenas para fins de controle e relacionamento, e é acessado diretamente pelo `core-service`.

| Tabela | Serviço Responsável | Descrição |
| :--- | :--- | :--- |
| `users` | `core-service` | Dados de login, CPF, senha, `autarquia_ativa_id`. |
| `autarquias` | `core-service` | Lista de autarquias (Prefeitura, Câmara, Água), com o nome do *schema* correspondente (e.g., `pref_santacruz`). |
| `modulos` | `core-service` | Lista de módulos disponíveis. |
| `usuario_autarquia` | `core-service` | Tabela pivot N:N entre usuários e autarquias. |
| `pessoas_fisicas` | `core-service` | Cadastro mestre de PF (CPF). |
| `pessoas_juridicas` | `core-service` | Cadastro mestre de PJ (CNPJ). |
| `produtos` | `core-service` | Cadastro de produtos/serviços (dados que podem ser comuns a todas as autarquias). |

### 2.2. Schemas de Tenant (Exemplo: `pref_santacruz`)

Estes *schemas* contêm as tabelas operacionais. **Nenhuma destas tabelas terá a coluna `autarquia_id`**, pois o isolamento é garantido pelo próprio *schema*.

| Tabela | Domínio (Serviço) | Descrição |
| :--- | :--- | :--- |
| `lancamentos_contabeis` | `financeiro-service` | Lançamentos exclusivos da Prefeitura. |
| `contas_a_pagar` | `financeiro-service` | Contas a pagar exclusivas da Prefeitura. |
| `bens_patrimoniais` | `logistica-service` | Bens patrimoniais exclusivos da Prefeitura. |
| `frotas` | `logistica-service` | Veículos e manutenção exclusivos da Prefeitura. |
| `pedidos_compra` | `logistica-service` | Pedidos de compra exclusivos da Prefeitura. |

**Observação Crítica:** O `financeiro-service` e o `logistica-service` acessam o *schema* `pref_santacruz` quando o usuário está no contexto da Prefeitura, e o *schema* `camara_santacruz` quando o usuário está no contexto da Câmara.

---

## 3. Fluxo de Acesso (Schema Switching)

O *Schema Switching* é a chave para o isolamento e funciona da seguinte forma:

1.  **Login:** O usuário faz login no `core-service`.
2.  **Seleção de Autarquia:** O usuário seleciona a **Prefeitura** como sua autarquia ativa.
3.  **Recuperação do Schema:** O `core-service` consulta a tabela `autarquias` no *schema* `public` e recupera o nome do *schema* de destino: `pref_santacruz`.
4.  **Chamada ao Módulo:** O usuário acessa o módulo "Contabilidade" (`financeiro-service`).
5.  **Schema Switching:** O `financeiro-service` (antes de qualquer consulta ao BD) executa o comando SQL:

    ```sql
    SET search_path TO pref_santacruz, public;
    ```

6.  **Consulta Isolada:** O `financeiro-service` executa a consulta `SELECT * FROM lancamentos_contabeis;`. O PostgreSQL, devido ao `search_path`, **automaticamente** busca os dados apenas na tabela `lancamentos_contabeis` dentro do *schema* `pref_santacruz`.

Este mecanismo garante que **não há risco de acessar dados de outra autarquia** por falha de um filtro `WHERE`, pois o isolamento é feito no nível do banco de dados.

[1]: /home/ubuntu/Analise_Critica_Intermediaria.md
[2]: /home/ubuntu/Lista_de_Tarefas_Ajustada_Core_Service.md
