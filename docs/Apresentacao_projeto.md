# 📘 **DOCUMENTO DE APRESENTAÇÃO — Arquitetura SH3-SIAP com Laravel Sail, Docker, Multi-Tenant e Módulos**

## 📌 **1. Visão Geral da Proposta**

A nova arquitetura do **SH3-SIAP** foi construída com foco em:

* **Escalabilidade real**
* **Padronização da infraestrutura**
* **Modularidade completa**
* **Ambiente isolado e igual para todos os desenvolvedores**
* **Multi-tenant profissional baseado em schemas PostgreSQL**
* **Facilidade de manutenção e implantação**

Essa arquitetura unifica práticas modernas do Laravel, containerização com Docker, Laravel Sail, módulos NWIDART e um modelo multi-tenant sólido.

O resultado é um ambiente altamente previsível, seguro, rápido de desenvolver, simples de manter e preparado para crescer.

---

# 📌 **2. Componentes Principais da Arquitetura**

A arquitetura do novo SH3-SIAP é composta por quatro pilares:

---

## **2.1 Laravel Sail + Docker (Ambiente Padronizado)**

O Sail fornece um ambiente Linux completo, com:

* PHP-FPM 8.4
* Nginx
* Node 22
* Composer
* PostgreSQL 18
* Redis (opcional)
* Vite para desenvolvimento frontend

Tudo isso isolado em containers, garantindo que:

✔ Todo desenvolvedor roda **exatamente o mesmo ambiente**
✔ Não existe “funciona na minha máquina”
✔ Zero configuração manual local
✔ Deploys tornam-se mais previsíveis

---

## **2.2 Arquitetura Modular (Laravel Modules NWIDART)**

A aplicação é dividida em módulos independentes, por exemplo:

* AuthCore
* ClientCore
* Frota
* Patrimônio
* Compras
* Tesouraria
* Orçamento
* DP
* Contabilidade

Cada módulo possui:

* Controllers próprios
* Migrations próprias
* Models e Services isolados
* Regras de responsabilidade únicas
* Separação limpa do domínio

Benefícios:

✔ Código mais organizado
✔ Escalabilidade por módulo
✔ Equipes trabalhando em paralelo
✔ Deploy de funcionalidades isoladas
✔ Testes unitários mais simples

---

## **2.3 Multi-Tenant via Schemas PostgreSQL**

Cada município/autarquia recebe seu **próprio schema**, porém usando a **mesma base de dados**.

Exemplo:

```
common
santa_cruz.prefeitura
santa_cruz.camara
tiradentes.prefeitura
```

O Laravel controla o schema ativo por request através:

* Middleware SetClientSchema
* Service SchemaManager
* Termos de search_path em cada conexão

Benefícios:

✔ Segurança total: dados não se misturam
✔ Múltiplos municípios usando o mesmo sistema
✔ Zero impacto de um cliente no outro
✔ Migrações por schema (automático)
✔ Simples de restaurar/exportar dados

---

## **2.4 Fluxos Inteligentes: Autenticação, Contexto e Modo Suporte**

A arquitetura define fluxos formais para:

* Autenticação de usuários
* Troca de autarquias
* Modo suporte (operando com permissões elevadas)
* Acesso seguro aos schemas corretos
* Auditoria de contexto

Benefícios:

✔ Operação segura no contexto do cliente
✔ Zero risco de acessar dados errados
✔ Suporte técnico sólido e controlado
✔ Auditoria e rastreabilidade garantidas

---

# 📌 **3. Benefícios Técnicos da Arquitetura**

## 🔒 **3.1 Segurança Reforçada**

* Isolamento por schema
* Tokens independentes (modo suporte)
* Nginx configurado no container
* Nada roda diretamente no host da máquina
* Migrações organizadas por módulo

## ⚡ **3.2 Performance e Escalabilidade**

* PostgreSQL otimizado por schema
* PHP-FPM com workers escaláveis
* Cache Redis (quando habilitado)
* Conexões independentes por autarquia

## 🧩 **3.3 Manutenção Simplificada**

* Cada módulo pode ser atualizado sem afetar os outros
* Logs organizados por ambiente
* Migrations isoladas por módulo
* Estrutura previsível e limpa

## 👥 **3.4 Time de Desenvolvimento mais Produtivo**

* Mesmos containers para todos
* Comandos simples: `sail up`, `sail artisan`, `sail npm run dev`
* Hot Reload instantâneo com Vite
* Menos falhas e menos retrabalho

---

# 📌 **4. Benefícios Estratégicos**

A arquitetura não é apenas técnica: ela fortalece o **produto** como um todo.

## 🏛 **4.1 Atende múltiplos municípios ao mesmo tempo**

Com segurança e isolamento.

## 🚀 **4.2 Modularidade permite crescimento ilimitado**

Novos módulos podem ser criados sem retrabalho.

## 🧱 **4.3 Stack moderna e padronizada**

Garante longevidade tecnológica.

## 🔄 **4.4 Facilita DevOps, testes e automação**

CI/CD torna-se mais simples.
Backups idem.
Deploy idem.

## 🔧 **4.5 Preparado para SaaS (Sistema como Serviço)**

Essa arquitetura é exatamente a base de soluções SaaS como:

* Conta Azul
* Omie
* Keet
* E-Notas
* ERPNext
* Melhor Envio

O SH3-SIAP agora tem a mesma base de sustentação.

---

# 📌 **5. Conclusão – Por que Essa Arquitetura é Ideal?**

A combinação:

* Laravel Sail + Docker
* Multi-tenant com PostgreSQL
* Módulos separados
* Fluxos definidos (autenticação, suporte, troca de autarquias)
* Padronização do ambiente de desenvolvimento

…coloca o SH3-SIAP em um **novo nível de robustez, organização e capacidade de expansão**.

É uma arquitetura:

✔ sólida
✔ moderna
✔ segura
✔ escalável
✔ preparada para o futuro
✔ ideal para sistemas públicos multi-município

---
