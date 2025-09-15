# Filament Events Manager - Guia Simples

🎯 **Automatize Tarefas na Sua Aplicação sem Saber Programar**

Este guia explica de forma simples como usar o Filament Events Manager para automatizar tarefas na sua aplicação web, mesmo sem conhecimentos de programação.

---

## 🤔 O Que É Isto?

O **Filament Events Manager** é como ter um assistente digital que vigia a sua aplicação 24 horas por dia e executa tarefas automáticas quando certas coisas acontecem.

### Imagine Isto...

- ✅ **Quando um cliente se regista** → Enviar automaticamente um email de boas-vindas
- ✅ **Quando uma venda ultrapassa 1000€** → Notificar o gestor por email
- ✅ **Quando o stock fica baixo** → Alertar a equipa de compras
- ✅ **Todos os dias às 9h** → Gerar relatório automático
- ✅ **Quando um produto é alterado** → Registar quem fez a alteração

**Tudo isto SEM escrever código!** Apenas configurando através de uma interface visual simples.

---

## 📸 Veja Como É Fácil!

### Interface Principal
![Dashboard](docs/screenshots/dashboard-overview.png)

### Lista de Automações Criadas
![Lista de Regras](docs/screenshots/event-rules-list.png)

### Criar Nova Automação
![Criar Regra](docs/screenshots/event-rule-create-form.png)

### Ver Histórico de Execuções
![Logs](docs/screenshots/event-logs-list.png)

---

## 🎨 Como Funciona?

### 1. Escolhe um "Gatilho" (O Que Vai Disparar a Automação)

**Exemplos de gatilhos:**
- Um cliente novo se regista
- Um produto é adicionado
- Uma venda é finalizada
- Um utilizador faz login
- Chega uma certa hora do dia

### 2. Define "Condições" (Quando Deve Acontecer)

**Exemplos de condições:**
- Se o valor da venda for maior que 500€
- Se o cliente for de Lisboa
- Se o produto não tiver stock
- Se for um utilizador administrador

### 3. Escolhe "Ações" (O Que Vai Acontecer)

**Exemplos de ações:**
- Enviar um email
- Criar uma notificação
- Registar uma atividade
- Chamar um sistema externo
- Enviar mensagem para Slack

---

## 📚 Exemplos Práticos

### Exemplo 1: Boas-vindas Automáticas
> **Problema**: Queremos dar as boas-vindas a todos os novos clientes automaticamente.

**Solução:**
1. **Gatilho**: Quando um utilizador se regista
2. **Condições**: Nenhuma (queremos para todos)
3. **Ação**: Enviar email de boas-vindas

**Como configurar:**
- Ir ao menu "Gestão de Eventos"
- Clicar "Criar Nova Regra"
- Nome: "Email de Boas-vindas"
- Gatilho: "Utilizador → Criado"
- Ação: "Enviar Email"
  - Para: *email do utilizador*
  - Assunto: "Bem-vindo à nossa plataforma!"
  - Mensagem: "Olá *nome do utilizador*, obrigado por se juntar a nós!"

### Exemplo 2: Alertas de Vendas Importantes
> **Problema**: Queremos ser notificados imediatamente quando há vendas elevadas.

**Solução:**
1. **Gatilho**: Quando uma venda é criada
2. **Condições**: Se o valor for maior que 1000€
3. **Ação**: Enviar email ao gestor

**Como configurar:**
- Nome: "Alerta Vendas Elevadas"
- Gatilho: "Venda → Criada"
- Condições: "Valor total > 1000"
- Ações:
  - Email para gestor@empresa.com
  - Assunto: "🚨 Venda Elevada - *número da venda*"

### Exemplo 3: Controlo de Stock
> **Problema**: Queremos saber quando produtos ficam com pouco stock.

**Solução:**
1. **Gatilho**: Quando um produto é alterado
2. **Condições**: Se o stock for menor ou igual a 5 unidades
3. **Ação**: Alertar equipa de compras

**Como configurar:**
- Nome: "Alerta Stock Baixo"
- Gatilho: "Produto → Atualizado"
- Condições: "Stock <= 5 E Stock foi alterado"
- Ação: Email para compras@empresa.com

### Exemplo 4: Relatórios Automáticos
> **Problema**: Queremos receber um relatório de vendas todas as segundas-feiras.

**Solução:**
1. **Gatilho**: Agenda (todas as segundas às 9h)
2. **Condições**: Nenhuma
3. **Ação**: Enviar relatório por email

**Como configurar:**
- Nome: "Relatório Semanal"
- Gatilho: "Agendado"
- Horário: "Segundas-feiras às 09:00"
- Ação: Email com relatório de vendas da semana

---

## 🖥️ Como Usar a Interface

### Passo 1: Aceder ao Menu
1. Fazer login na aplicação
2. Ir ao menu lateral
3. Procurar "Gestão de Eventos" ou "Events Manager"

### Passo 2: Criar Nova Regra
1. Clicar em "Criar Nova Regra"
2. Preencher os dados básicos:
   - **Nome**: Um nome descritivo (ex: "Email Boas-vindas")
   - **Descrição**: Explicação do que faz
   - **Ativa**: Sim (para a regra funcionar)

### Passo 3: Configurar o Gatilho
1. Escolher o tipo de gatilho:
   - **Eventos de Dados**: Quando dados são criados/alterados
   - **Eventos Agendados**: Em horários específicos
   - **Eventos Personalizados**: Eventos especiais da aplicação

2. Se escolheu "Eventos de Dados":
   - Escolher o tipo (Utilizador, Produto, Venda, etc.)
   - Escolher a ação (Criado, Atualizado, Eliminado)

### Passo 4: Definir Condições (Opcional)
1. Clicar "Adicionar Condição"
2. Escolher o campo (ex: "email", "total", "quantidade")
3. Escolher o operador:
   - **Igual a** (=)
   - **Diferente de** (≠)
   - **Maior que** (>)
   - **Menor que** (<)
   - **Contém** (texto)
   - **Foi alterado**
4. Inserir o valor para comparar

**Exemplo de condições:**
- `email` contém `@empresa.com`
- `total` maior que `1000`
- `stock` menor ou igual a `10`

### Passo 5: Configurar Ações
1. Clicar "Adicionar Ação"
2. Escolher o tipo de ação:
   - **Enviar Email**
   - **Criar Notificação**
   - **Registar Atividade**
   - **Chamar Sistema Externo**

3. Configurar os detalhes:
   - **Para emails**: Destinatário, assunto, mensagem
   - **Para notificações**: Título, mensagem, destinatários

### Passo 6: Testar a Regra
1. Usar o "Testador de Regras"
2. Simular dados de exemplo
3. Verificar se tudo funciona como esperado
4. Ativar a regra quando estiver satisfeito

---

## 📊 Monitorizar as Suas Automações

### Dashboard Principal
- **Regras Ativas**: Quantas automações estão a funcionar
- **Eventos Hoje**: Quantas vezes as regras foram ativadas
- **Tempo de Resposta**: Quão rápido as ações são executadas
- **Taxa de Sucesso**: Percentagem de ações bem-sucedidas

### Histórico de Atividade
- Ver todas as vezes que as regras foram ativadas
- Verificar que ações foram executadas
- Identificar possíveis problemas
- Filtrar por data, utilizador ou regra específica

### Relatórios
- Exportar dados para Excel
- Gerar relatórios de auditoria
- Analisar padrões de utilização

---

## ❓ Perguntas Frequentes

### **P: É seguro usar automações?**
**R:** Sim! Todas as ações são registadas e pode ver exatamente o que aconteceu e quando. Também pode desativar qualquer regra a qualquer momento.

### **P: Posso testar antes de ativar?**
**R:** Absolutamente! Existe um "Testador de Regras" que permite simular situações e ver o que aconteceria, sem executar as ações reais.

### **P: E se fizer algo errado?**
**R:** Não há problema! Pode sempre:
- Desativar a regra imediatamente
- Editar as configurações
- Ver o histórico do que aconteceu
- Voltar às configurações anteriores

### **P: Quantas regras posso criar?**
**R:** Não há limite! Pode criar tantas regras quantas precisar.

### **P: Funciona com emails externos?**
**R:** Sim! Pode enviar emails para qualquer endereço, interno ou externo.

### **P: Posso copiar regras entre diferentes ambientes?**
**R:** Sim! Existe uma função de exportar/importar que permite copiar regras entre diferentes instalações.

### **P: E se a aplicação estiver em baixo?**
**R:** As regras agendadas serão executadas quando a aplicação voltar a funcionar. Para eventos em tempo real, apenas os que acontecerem quando a aplicação estiver a funcionar serão processados.

---

## 🎯 Casos de Uso Comuns

### Para Comércio Eletrónico
- ✅ Emails de carrinho abandonado
- ✅ Confirmações de encomenda automáticas
- ✅ Alertas de stock baixo
- ✅ Notificações de produtos em promoção
- ✅ Seguimento pós-venda

### Para Gestão de Clientes
- ✅ Boas-vindas a novos clientes
- ✅ Emails de aniversário
- ✅ Notificações de atividade suspeita
- ✅ Lembretes de renovação
- ✅ Inquéritos de satisfação

### Para Equipas Internas
- ✅ Notificações de novos leads
- ✅ Alertas de tickets urgentes
- ✅ Relatórios automáticos
- ✅ Backup de dados importantes
- ✅ Monitorizaação de performance

### Para Compliance e Auditoria
- ✅ Registo automático de alterações
- ✅ Alertas de ações suspeitas
- ✅ Relatórios regulamentares
- ✅ Notificações de expiração
- ✅ Backup de dados críticos

---

## 🚀 Dicas para Começar

### 1. Comece Simples
- Crie primeiro uma regra simples, como "enviar email quando utilizador se regista"
- Teste bem antes de criar regras mais complexas
- Vá adicionando funcionalidades gradualmente

### 2. Use Nomes Descritivos
- **Bom**: "Email Boas-vindas Novos Clientes"
- **Mau**: "Regra 1"

### 3. Teste Sempre
- Use o testador antes de ativar
- Comece com a regra inativa
- Ative só quando tiver a certeza

### 4. Monitorizei Regularmente
- Verifique o dashboard semanalmente
- Analise o histórico de atividade
- Ajuste regras conforme necessário

### 5. Documente as Suas Regras
- Use a descrição para explicar o propósito
- Mantenha uma lista das regras ativas
- Partilhe conhecimento com a equipa

---

## 🆘 Quando Pedir Ajuda

Se precisar de ajuda técnica, contacte:
- A equipa de TI da sua empresa
- O administrador da aplicação
- O fornecedor do software

**Informação útil para fornecer:**
- Nome da regra que está a criar
- O que quer que aconteça
- Quando deve acontecer
- Mensagens de erro (se houver)
- Prints do ecrã da configuração

---

## 🎉 Benefícios das Automações

### ⏰ Poupança de Tempo
- Elimina tarefas repetitivas
- Reduz trabalho manual
- Liberta tempo para tarefas importantes

### ✅ Maior Precisão
- Elimina erros humanos
- Garante consistência
- Ações executadas sempre da mesma forma

### 📈 Melhor Experiência do Cliente
- Respostas mais rápidas
- Comunicação proativa
- Seguimento automático

### 💼 Melhor Gestão
- Visibilidade total das ações
- Relatórios automáticos
- Métricas em tempo real

### 🔒 Maior Segurança
- Registo completo de atividades
- Deteção automática de problemas
- Compliance facilitado

---

<div align="center">

**🚀 Comece a Automatizar Hoje Mesmo!**

*Transforme a sua aplicação num assistente inteligente que trabalha 24/7*

**💡 Lembre-se**: Cada automação que criar hoje é tempo que poupa amanhã!

</div>