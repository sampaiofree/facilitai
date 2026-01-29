<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead; // Importe o modelo Lead
use Illuminate\Validation\ValidationException;

class LeadController extends Controller
{
    /**
     * Store a newly created lead in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // 1. Validação dos dados
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'whatsapp' => 'required|string|max:20', // Ajuste o max de acordo com o formato do seu WhatsApp
                'origin' => 'nullable|string|max:500', // Campo opcional para origem
            ]);

            // 2. Limpa o número para salvar apenas dígitos
            $validatedData['whatsapp'] = preg_replace('/\D/', '', $validatedData['whatsapp']);

            // 3. Cria ou atualiza o Lead pelo número do WhatsApp
            $lead = Lead::updateOrCreate(
                ['whatsapp' => $validatedData['whatsapp']], // condição para evitar duplicidade
                [
                    'name'    => $validatedData['name'],
                    'origin'  => $validatedData['origin'] ?? null
                ]
            );

            // Só executa se for o primeiro cadastro
            /*if ($lead->wasRecentlyCreated) {
                $nome = $validatedData['name'];
                $open = new ConversationsService(
                    "[Notificação do Sistema: {$nome} acaba de fazer o seu cadastrar na Landing Page, dê boas vindas e incentive ele entrar no grupo do WhatsAPP]",
                    "55".$validatedData['whatsapp'],
                    "28"
                );
                $open->enviarMSG();
            }*/

            // 3. Resposta de sucesso (você pode redirecionar ou retornar JSON)
            // Se for AJAX, retorne JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Lead cadastrado com sucesso!', 'lead' => $lead], 201);
            }

            // Se for formulário tradicional, redirecione com uma mensagem flash
            return redirect()->back()->with('success', 'Cadastro realizado com sucesso! Em breve você receberá o guia no seu WhatsApp.');

        } catch (ValidationException $e) {
            // Se for AJAX, retorne erros JSON
            if ($request->expectsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            // Se for formulário tradicional, redirecione de volta com erros
            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            // Capture outras exceções inesperadas
            \Log::error('Erro ao salvar lead: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ocorreu um erro ao processar seu pedido. Por favor, tente novamente.', 'error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Ocorreu um erro ao processar seu pedido. Por favor, tente novamente.')->withInput();
        }
    }
}
