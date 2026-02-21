# Planejamento - Agendamento de Mensagens (Agencia Conversas)

## 1. Objetivo
- Implementar agendamento de mensagens no fluxo `agencia/conversas` mantendo o modelo atual do produto: a mensagem e sempre uma instrucao para IA.
- Garantir confiabilidade operacional para volume moderado com preparo para alto volume.
- Garantir visibilidade simples para usuario final sobre mensagens agendadas.

## 2. Diretriz de Arquitetura Aprovada
- Banco de dados como fonte da verdade do agendamento.
- Redis + Horizon para execucao de fila.
- Scheduler (cron + `schedule:run`) para despachar pendencias no tempo correto.

## 3. Escopo (MVP evolutivo)
- Criar agendamento (data/hora futura) no modal de envio manual do lead.
- Persistir registro do agendamento no banco com status.
- Despachar para fila no momento correto.
- Atualizar status de ciclo de vida do agendamento.
- Exibir de forma simples no modal se existe mensagem agendada.
- Permitir cancelamento de agendamento pendente.
- Expor painel tecnico admin para filas em `adm/horizon`.
- Expor painel admin de negocio para registros em `adm/agendamentos`.

## 4. Fora de Escopo Inicial
- Editor avancado de campanhas.
- Recorrencia (diaria/semanal/mensal).
- Reagendamento em lote.
- Multi-canal (email/sms/etc).

## 5. Regras de Negocio

### 5.1 Regra funcional principal
- A mensagem agendada e tratada como instrucao para IA (nao como disparo de texto bruto obrigatorio).

### 5.2 Permissao e ownership
- Usuario autenticado da agencia so pode criar, listar e cancelar agendamentos de leads pertencentes aos seus clientes.

### 5.3 Validacoes de criacao
- `assistant_id` obrigatorio e deve estar associado ao lead.
- `mensagem` obrigatoria, nao vazia, limite maximo de caracteres (alinhar com regra atual, hoje 2000).
- `scheduled_for` obrigatorio para agendamento e deve ser data futura valida.
- Lead precisa ter bot habilitado no momento da criacao.
- Lead precisa ter telefone valido no momento da criacao.
- Deve existir conexao valida para cliente + assistente no momento da criacao.

### 5.4 Fuso horario
- Fuso oficial de negocio: `agency_settings.timezone` com fallback `America/Sao_Paulo`.
- Entradas de data/hora no front devem ser interpretadas nesse fuso.
- Persistencia recomendada: UTC no banco + conversao em exibicao.

### 5.5 Ciclo de status
- `pending`: agendamento criado e aguardando horario.
- `queued`: despachado para fila de processamento.
- `sent`: fluxo concluido com envio bem-sucedido.
- `failed`: falha apos politicas de tentativa.
- `canceled`: cancelado manualmente antes do despacho.

### 5.6 Cancelamento
- Somente status `pending` pode ser cancelado.
- `queued`, `sent`, `failed` e `canceled` nao podem ser cancelados.

### 5.7 Idempotencia e duplicidade
- Cada agendamento deve possuir identificador unico de evento para evitar disparo duplicado.
- Despachante deve usar lock/estrategia atomica para evitar corrida em execucoes paralelas.

### 5.8 Retentativa
- Falhas transientes devem seguir politica de retry controlada.
- Ao exceder limite de tentativas, marcar como `failed`.

## 6. Plano de Execucao por Etapas

## Etapa 0 - Fechamento de regra (pre-implementacao)
- Consolidar e validar este documento com stakeholder.
- Definir limite maximo de agendamento (ex.: 90 dias).
- Definir regra final de retry (tentativas e intervalo).
- Definir regra de retencao de historico.
- Saida esperada: regras fechadas para implementacao sem ambiguidade.

## Etapa 1 - Operacao de fila com Horizon
- Instalar/configurar Horizon em producao.
- Configurar supervisores por fila (`webhook`, `processarconversa`, `processosinternos`).
- Definir concorrencia inicial e politicas de retry por fila.
- Publicar painel tecnico em `adm/horizon` com acesso restrito a admin.
- Criar runbook de start/stop/restart/deploy.
- Saida esperada: observabilidade operacional ativa de filas Redis.

## Etapa 2 - Modelo de dados de agendamento
- Criar tabela de agendamentos de mensagem.
- Campos minimos sugeridos:
  - `id`
  - `cliente_lead_id`
  - `assistant_id`
  - `conexao_id`
  - `mensagem`
  - `scheduled_for` (UTC)
  - `status`
  - `event_id` (unico)
  - `queued_at`
  - `sent_at`
  - `failed_at`
  - `canceled_at`
  - `error_message`
  - `attempts`
  - `created_by_user_id`
  - `created_at` / `updated_at`
- Criar indices por status + horario (`status`, `scheduled_for`).
- Saida esperada: base de persistencia pronta.

## Etapa 3 - Criacao de agendamento no fluxo agencia/conversas
- Evoluir endpoint de envio manual para aceitar opcionalmente agendamento.
- Quando `scheduled_for` for informado:
  - validar regras;
  - salvar registro `pending`;
  - retornar confirmacao de agendamento.
- Quando `scheduled_for` nao for informado:
  - manter envio imediato atual (sem regressao).
- Saida esperada: coexistencia de envio imediato e agendado.

## Etapa 4 - Despachante agendado (scheduler)
- Criar comando para processar agendamentos `pending` vencidos.
- Rodar a cada minuto no scheduler.
- Garantir atomia/lock para evitar duplo despacho.
- Atualizar status para `queued` ao despachar.
- Saida esperada: pendencias sendo enfileiradas no horario correto.

## Etapa 5 - Execucao e fechamento do ciclo
- Integrar job executor com pipeline atual de IA.
- Atualizar status para `sent` em sucesso.
- Tratar falhas e retry, com status `failed` quando exceder limite.
- Registrar contexto de erro para suporte.
- Saida esperada: ciclo de vida completo por registro.

## Etapa 6 - Visibilidade simples para usuario
- Exibir no modal do lead:
  - contagem de mensagens agendadas `pending`;
  - proxima data/hora agendada;
  - lista curta de pendentes (simples).
- Acao de cancelar para itens `pending`.
- Saida esperada: usuario enxerga e controla o basico.

## Etapa 7 - Painel ADM de Registros de Agendamento
- Criar rota `adm/agendamentos` para consulta de todos os registros.
- Exibir colunas essenciais: `id`, lead, cliente, assistente, conexao, status, horario agendado, tentativas, erro, criado em.
- Implementar filtros basicos: status, periodo, cliente, assistente, busca por telefone/nome.
- Permitir acoes administrativas seguras:
  - cancelar apenas `pending`;
  - reprocessar apenas `failed` (quando habilitado no fluxo).
- Exibir link auxiliar para painel tecnico `adm/horizon`.
- Saida esperada: admin consegue auditar e operar registros de agendamento de ponta a ponta.

## Etapa 8 - Hardening para escala
- Revisar indices e consultas.
- Implementar limpeza/arquivamento de historico antigo.
- Definir alertas operacionais (fila acumulada, taxa de falha, tempo medio).
- Testes de carga moderada.
- Saida esperada: prontidao para crescimento.

## 7. Criterios de Aceite Globais
- Nao regressao no envio imediato atual.
- Agendamento confiavel com rastreabilidade ponta a ponta.
- Sem disparo duplicado em concorrencia.
- Usuario final visualiza que existe agendamento.
- Operacao consegue monitorar e agir rapidamente em incidentes.

## 8. Riscos e Mitigacoes
- Risco: perda operacional de fila Redis por configuracao inadequada.
  - Mitigacao: Horizon + persistencia Redis correta + runbook.
- Risco: duplicidade por corrida de despachante.
  - Mitigacao: lock atomico + idempotencia por `event_id`.
- Risco: confusao de horario/fuso.
  - Mitigacao: regra unica de timezone e conversao centralizada.
- Risco: falta de visibilidade para suporte/usuario.
  - Mitigacao: status no banco + UI simples + logs estruturados.

## 9. Checklist de Execucao
- [x] Etapa 0 aprovada
- [x] Etapa 1 concluida
- [x] Etapa 2 concluida
- [x] Etapa 3 concluida
- [x] Etapa 4 concluida
- [x] Etapa 5 concluida
- [x] Etapa 6 concluida
- [x] Etapa 7 concluida
- [ ] Etapa 8 concluida

## 10. Decisoes Aprovadas
- [x] Limite maximo de agendamento: `90 dias`
- [x] Politica de retry:
  - `3 tentativas` para falhas transientes (`timeout`, `429`, `5xx`, rede)
  - Intervalos de retentativa: `+2 min`, `+10 min`, `+30 min`
  - Falhas de regra de negocio nao fazem retry (vai para `failed`)
- [x] Regra de retencao de historico:
  - `pending` permanece ate executar/cancelar
  - `sent`, `failed`, `canceled` mantidos por `180 dias`
  - Limpeza mensal automatica dos registros antigos
- [x] Nivel de detalhe da UI inicial:
  - Exibir no modal: quantidade pendente + proximo agendamento
  - Listar os `5` proximos `pending` (data/hora, assistente, preview curta)
  - Permitir cancelar apenas status `pending`
