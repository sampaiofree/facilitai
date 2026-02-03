# Supervisor & Workers — Projeto FacilitAI

Este documento descreve **como e por que** o Supervisor foi configurado para o projeto FacilitAI, qual a lógica de separação de filas, e como cada worker se encaixa na arquitetura do sistema.

O objetivo é:

* evitar conflitos entre projetos no mesmo servidor
* garantir previsibilidade de execução
* impedir que tarefas lentas bloqueiem tarefas críticas
* permitir escala controlada

---


## 3. Arquitetura de filas do FacilitAI

O FacilitAI possui **três tipos principais de trabalho**, cada um com impacto diferente no sistema.

### 3.1 Fila `webhook`

**Responsabilidade:**

* receber eventos da API do WhatsApp
* validar e normalizar payload
* encaminhar para processamento real

**Características:**

* extremamente rápida
* sensível a tempo
* não pode executar lógica pesada

Regra:

> Webhook não pensa. Webhook despacha.

---

### 3.2 Fila `processarconversa`

**Responsabilidade:**

* tratar o payload recebido
* carregar contexto da conversa
* chamar API de IA
* decidir resposta
* enviar resposta ao WhatsApp

**Características:**

* tarefa mais pesada do sistema
* envolve chamadas externas
* tempo imprevisível

Essa fila representa o **cérebro do sistema**.

---

### 3.3 Fila `processosinternos`

**Responsabilidade:**

* aplicar tags
* atualizar agenda
* registrar métricas
* sincronizações internas

**Características:**

* não críticas para UX imediata
* podem atrasar sem impacto direto
* alto volume potencial

Nunca devem bloquear a conversa principal.

---

