# 📊 Explicação da Arquitetura do Sistema Municipal

## 🎯 **Visão Geral**
Este diagrama representa uma **arquitetura de microserviços moderna** para um sistema municipal, projetada para atender múltiplas entidades governamentais (Prefeitura, Câmara, Água e Esgoto) de forma isolada e segura.

---

## 🏗️ **Arquitetura em Camadas**

### 1. **Camada de Apresentação** 🖥️
- **Portal Único Municipal**: Interface unificada para acesso aos serviços
- **Usuários**: Acessam via Web ou App móvel
- **Função**: Prover uma experiência consistente across todas as entidades

### 2. **Camada de Microserviços** ⚙️

#### **Serviços Core** 🔑
- **Auth-Core-Service**: Autenticação e autorização
- **Client-Core-service**: Cadastros básicos do munícípio, usuários, pessoa física, pessoa jurídica, produtos...
- **Entidade-Service**: Gerenciamento das organizações (tenants)
- **Auditoria-Service**: Logs e rastreabilidade de ações


#### **Serviços de Negócio** 💼
- **Módulo Frotas**: Gestão de veículos e frotas
- **Módulo Patrimônio**: Controle de bens patrimoniais
- **Módulo Orçamento**: Gestão orçamentária
- **Módulo Tesouraria**: Controle financeiro

#### **Serviços de Suporte** 🛠️
- **Relatórios-Service**: Geração de relatórios consolidados
- **Notificações-Service**: Sistema de notificações

---

## 🗄️ **Arquitetura de Banco de Dados**

### **Schema Common (Município)** 📚
- **Dados compartilhados**: Usuários, Papéis, Entidades, Logs de Auditoria
- **Posicionamento estratégico**: Acima dos demais schemas
- **Função**: Gerenciamento centralizado de identidade e configurações

### **Schemas Específicos por Entidade** 🏛️
- **Prefeitura**: Dados exclusivos da prefeitura municipal
- **Câmara**: Dados exclusivos da câmara municipal  
- **Água e Esgoto**: Dados da autarquia de água e esgoto

### **Schema Cross-Entidades** 🌉
- **Data Lake**: Views materializadas para análise
- **Relatórios DB**: Dados consolidados entre entidades
- **Função**: Permitir relatórios cross-tenant sem misturar dados operacionais

---

## 🔄 **Padrões Arquiteturais Implementados**

### **Multi-tenancy com Isolamento de Schema** 🏢
- Cada entidade tem seu próprio schema isolado
- Dados sensíveis são separados logicamente
- Compartilhamento seguro através do schema common

### **Schema Switching** 🔀
- Os microserviços alternam dinamicamente entre schemas
- Baseado na entidade do usuário autenticado
- Transparente para o usuário final

### **Security-by-Design** 🔐
- Validação JWT centralizada
- Tokens propagados entre microserviços
- Auditoria completa de todas as ações

### **Cross-Tenant Queries** 📈
- Relatórios consolidados sem comprometer isolamento
- Data Lake com dados agregados
- Acesso controlado via serviço especializado

---

## 🛡️ **Infraestrutura e Monitoramento**

### **Componentes de Suporte**
- **Monitoramento**: Métricas em tempo real (Prometheus/Grafana)
- **Logging**: Agregação e análise de logs (ELK Stack)
- **Cache**: Melhoria de performance (Redis Cluster)
- **Message Queue**: Comunicação assíncrona (RabbitMQ)

### **Benefícios da Arquitetura**

| Característica | Benefício |
|----------------|-----------|
| **Isolamento** | Dados de cada entidade separados e seguros |
| **Escalabilidade** | Cada microserviço escala independentemente |
| **Manutenibilidade** | Atualizações sem afetar todo o sistema |
| **Resiliência** | Circuit breakers e fallbacks |
| **Observabilidade** | Monitoramento completo da stack |

---

## 🎨 **Código de Cores e Símbolos**

- **🔵 Azul**: Serviços core e autenticação
- **🔴 Rosa/Vermelho**: Serviços de negócio
- **🟢 Verde**: Serviços de suporte e infraestrutura
- **🟠 Laranja**: Banco de dados e persistência
- **🟣 Roxo**: Gateways e componentes de rede

Esta arquitetura proporciona **segurança, escalabilidade e flexibilidade** para atender às necessidades de múltiplas entidades municipais mantendo o isolamento adequado dos dados.
