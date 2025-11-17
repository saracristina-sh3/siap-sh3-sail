### Plano de Implementação: Parte 1 - Município Teste: Santa Cruz de Minas

#### **Objetivo da POC**:
Implementar e testar a parte do sistema multi-tenant específico para o município de Santa Cruz de Minas, como um ponto de partida para a implementação em outros municípios.

### **Lista de Tarefas**

1. **Configuração Inicial**
- [ ] Validar as dependências necessárias (PHP, banco de dados, etc.)
- [ ] Configurar o ambiente de desenvolvimento local
- [ ] Instalar e configurar Composer
- [ ] Criar um novo projeto Laravel ou clonar o repositório existente

2. **Criação da Autarquia Santa Cruz de Minas**
- [ ] Inserir a autarquia de Santa Cruz de Minas no banco de dados
- [ ] Verificar se o schema para esta autarquia foi criado corretamente

3. **Configuração do SchemaManager**
- [ ] Adicionar o schema e prefixo da autarquia Santa Cruz de Minas no `SchemaManager`
- [ ] Implementar métodos adicionais para lidar com schemas específicos
- [ ] Testar a configuração inicial

4. **Middleware SetTenantContext**
- [ ] Atualizar o middleware para incluir a lógica específica do município de Santa Cruz de Minas
- [ ] Configurar a autarquia preferida ou modo suporte conforme necessário
- [ ] Testar o funcionamento do middleware em diferentes cenários

5. **Middleware EnsureSchemaIsSet**
- [ ] Garantir que este middleware esteja ativo e funcione corretamente
- [ ] Verificar se ele configura o schema padrão (Santa Cruz de Minas) quando necessário
- [ ] Testar em diferentes cenários

6. **Atualização do Model User**
- [ ] Atualizar a model `User` para que possa ser associada à autarquia Santa Cruz de Minas
- [ ] Implementar os relacionamentos necessários entre o usuário e a autarquia
- [ ] Testar a associação correta dos usuários com suas respectivas autarquias

7. **Ajustes para Inertia (SupportBar)**
- [ ] Atualizar as informações do modo suporte na barra de suporte
- [ ] Verificar se a autarquia correta é exibida
- [ ] Testar em diferentes cenários

8. **Implementação das Tabelas e Schema para Santa Cruz de Minas**
- [ ] Criar as tabelas necessárias no schema da Santa Cruz de Minas
- [ ] Exemplo: `pref_santa_cruz_de_minas_veiculos`
- [ ] Verificar a criação correta dos schemas e tabelas

9. **Atualização das Queries**
- [ ] Atualizar as queries para usarem o schema da Santa Cruz de Minas
- [ ] Exemplo: `FROM pref_santa_cruz_de_minas_veiculos`
- [ ] Testar consultas em diferentes cenários

10. **Testes Unitários e Funcionais**
 - [ ] Implementar testes unitários para as classes do SchemaManager, middleware e model User
 - [ ] Criar testes funcionais para verificar o comportamento completo do sistema
- [ ] Testar a navegação entre schemas
- [ ] Testar consultas em diferentes schemas

11. **Documentação**
 - [ ] Documentar a implementação, instruções de instalação e configuração
 - [ ] Adicionar notas importantes sobre os passos realizados e problemas encontrados

### **Timeline**

| Tarefa | Tempo Estimado |
|--------|----------------|
| Configuração Inicial | 1h |
| Criação da Autarquia Santa Cruz de Minas | 30m |
| Configuração do SchemaManager | 2h |
| Middleware SetTenantContext | 1.5h |
| Middleware EnsureSchemaIsSet | 30m |
| Atualização do Model User | 1h |
| Ajustes para Inertia (SupportBar) | 40m |
| Implementação das Tabelas e Schema para Santa Cruz de Minas | 2h |
| Atualização das Queries | 1h |
| Testes Unitários e Funcionais | 3h |
| Documentação | 1.5h |

**Total Estimado**: 14h

### **Responsabilidades**

- **Desenvolvedor Principal**: Responsável por configurar o ambiente, implementar as classes do SchemaManager, middleware e model User, criar as tabelas, e realizar os testes.
- **Analista de Requisitos**: Responsável por revisar e aprovar a documentação e a funcionalidade final.
- **Tester**: Responsável por executar os testes unitários e funcionais.

### **Revisões**

- **Primeira Revisão**: Depois da conclusão das tarefas básicas, realizar uma revisão para identificar problemas e fazer ajustes necessários.
- **Segunda Revisão**: Antes de encerrar a POC, revisar o projeto inteiramente e preparar um documento final detalhado.

### **Conclusão**

A realização dessa POC será fundamental para validar a viabilidade do sistema multi-tenant no SH3-SIAP. Ao concluir com sucesso, os resultados podem ser utilizados como base para 
implementações futuras em outros municípios e autarquias.

