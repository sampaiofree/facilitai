# Sequencias (Agencia)

## Arquivos envolvidos
- routes/web.php
- app/Http/Controllers/Agencia/AgenciaSequenceController.php
- resources/views/agencia/sequences/index.blade.php
- app/Models/Sequence.php
- app/Models/SequenceStep.php
- app/Models/SequenceChat.php
- app/Models/SequenceLog.php
- app/Models/Cliente.php
- app/Models/Conexao.php
- app/Models/Tag.php
- app/Models/ClienteLead.php
- app/Console/Commands/ProcessSequences.php
- app/Schedules/process_sequences.php
- app/Jobs/ProcessIncomingMessageJob.php

## Como funciona (resumo)
- A rota GET `/agencia/sequence` aponta para `AgenciaSequenceController@index`, que carrega clientes, tags, sequ�ncias, passos e logs e renderiza a view `agencia/sequences/index.blade.php`.
- A view permite criar/editar sequ�ncias e passos, e remover `SequenceChat` via rotas auxiliares no mesmo controller.
- Quando um lead � associado a uma sequ�ncia, � criado um `SequenceChat` com status `em_andamento`.
- O comando `sequences:process` (agendado em `app/Schedules/process_sequences.php`) percorre os `SequenceChat` eleg�veis, verifica janela/atraso/tags e enfileira o envio via `ProcessIncomingMessageJob`.
- O progresso � registrado em `SequenceLog` e o passo atual � avan�ado at� concluir ou cancelar.
