<?php

namespace Database\Seeders;

use App\Models\Assistant;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExampleUserDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::orderBy('id')->first();

        if (!$user) {
            $this->command->info('Nenhum usuário encontrado para criar dados fictícios.');
            return;
        }

        $assistantsConfig = [
            'Assistente Principal' => [
                'instructions' => 'Atende usuários em geral com foco em orientação clara e paciente.',
                'systemPrompt' => 'Você é um assistente virtual educado e objetivo.',
                'delay' => 1,
                'modelo' => 'gpt-4.1-mini',
                'version' => '1.0',
                'prompt_gerenciar_agenda' => 'Responda sempre com linguagem natural e ajude com agendamentos.',
            ],
            'Assistente Financeiro' => [
                'instructions' => 'Foca em dúvidas de cobrança e envio de boletos.',
                'systemPrompt' => 'Seja formal, claro e cite opções de pagamento.',
                'delay' => 2,
                'modelo' => 'gpt-4o-mini',
                'version' => '1.0',
                'prompt_gerenciar_agenda' => 'Ajuda na confirmação de horários para atendimento de arrecadação.',
            ],
        ];

        $assistants = [];

        foreach ($assistantsConfig as $name => $config) {
            $assistants[$name] = Assistant::updateOrCreate([
                'user_id' => $user->id,
                'name' => $name,
            ], array_merge([
                'user_id' => $user->id,
                'name' => $name,
                'payment_id' => null,
                'credential_id' => null,
                'openai_assistant_id' => 'dev-' . Str::slug($name),
                'developerPrompt' => 'Seja proativo.',
                'prompt_notificar_adm' => 'Informe o administrador quando necessário.',
                'prompt_buscar_get' => 'Busque informações externas se o cliente pedir.',
                'prompt_enviar_media' => 'Envie mídia sempre quando requisitado.',
                'prompt_registrar_info_chat' => 'Registre novos dados relevantes.',
            ], $config));
        }

        $instancesConfig = [
            'Instância Principal' => $assistants['Assistente Principal'],
            'Instância Financeira' => $assistants['Assistente Financeiro'],
        ];

        $instances = [];

        foreach ($instancesConfig as $label => $assistant) {
            $instances[$label] = Instance::updateOrCreate([
                'user_id' => $user->id,
                'name' => $label,
            ], [
                'user_id' => $user->id,
                'name' => $label,
                'model' => $assistant->modelo ?? 'gpt-4.1-mini',
                'status' => 'active',
                'default_assistant_id' => $assistant->id,
                'agenda_id' => null,
            ]);
        }

        $chatConfigs = [
            [
                'instance' => $instances['Instância Principal'],
                'assistant' => $assistants['Assistente Principal'],
                'contact' => '5511999000011',
                'nome' => 'Cliente Teste A',
            ],
            [
                'instance' => $instances['Instância Financeira'],
                'assistant' => $assistants['Assistente Financeiro'],
                'contact' => '5511999000022',
                'nome' => 'Cliente Teste B',
            ],
        ];

        /*
        foreach ($chatConfigs as $config) {
            Chat::updateOrCreate([
                'instance_id' => $config['instance']->id,
                'contact' => $config['contact'],
            ], [
                'user_id' => $user->id,
                'instance_id' => $config['instance']->id,
                'assistant_id' => $config['assistant']->id,
                'contact' => $config['contact'],
                'conv_id' => 'conv_' . Str::lower(Str::random(8)),
                'version' => $config['assistant']->version ?? '1.0',
                'nome' => $config['nome'],
                'informacoes' => 'Chat de exemplo criado para testes.',
                'bot_enabled' => true,
            ]);
        }
        */
    }
}
