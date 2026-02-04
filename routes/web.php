<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InstanceController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;
use App\Models\Credential;
use App\Http\Controllers\Admin\ClienteLeadController;
use App\Http\Controllers\Admin\LeadEmpresaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\LibraryEntryController;
use App\Http\Controllers\LeadController; 
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\TokensController;
use App\Http\Controllers\Admin\DashboardController; 
use App\Http\Controllers\Admin\LessonController as AdminLessonController; 
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendaPublicaController; 
use App\Http\Controllers\LessonPublicController;
use App\Http\Controllers\ProxyBanController;
use App\Http\Controllers\Admin\WebhookRequestController;
use App\Http\Controllers\Admin\InstanceReportController;
use App\Http\Controllers\Admin\SystemErrorLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AssistantLeadController;
use App\Http\Controllers\UazapiController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PromptHelpController;
use App\Http\Controllers\Admin\LogFileController;
use App\Http\Controllers\Agencia\AgenciaAssistantController;
use App\Http\Controllers\Agencia\AgenciaClienteController;
use App\Http\Controllers\Agencia\AgenciaConexaoController;
use App\Http\Controllers\Agencia\AgenciaCredentialController;
use App\Http\Controllers\Agencia\AgenciaProfileController;
use App\Http\Controllers\Agencia\AgenciaSettingsController;
use App\Http\Controllers\Agencia\AgenciaSequenceController;
use App\Http\Controllers\Agencia\AgenciaTagController;
use App\Http\Controllers\Agencia\ClienteLeadController as AgenciaClienteLeadController;
use App\Http\Controllers\Agencia\ImageController as AgenciaImageController;
use App\Http\Controllers\Agencia\LibraryEntryController as AgenciaLibraryEntryController;
use App\Http\Controllers\Agencia\OpenAIController as AgenciaOpenAIController;
use App\Http\Controllers\Cliente\ClienteAuthController;
use App\Http\Controllers\Cliente\ClienteDashboardController;
use App\Http\Controllers\Cliente\ClienteLeadController as ClienteClienteLeadController;
use App\Http\Controllers\Cliente\ConexaoClienteController;
use App\Http\Controllers\Cliente\ClienteAssistantController;
use App\Http\Controllers\Cliente\LibraryClienteController;


Route::get('/conv/{conv_id}', [ProfileController::class, 'conv']);

//PÁGINAS PUBLICAS
Route::get('/', function () { return redirect()->route('login'); })->name('homepage');
Route::get('/politica', function () {return view('homepage.politica');})->name('politica');
Route::get('/bio', function () {return view('homepage.bio');});
Route::get('/grupo-black', function () {return view('homepage.blackfriday');});
Route::get('/lessons/for-page', [LessonPublicController::class, 'forPage'])->name('lessons.for-page');

//PÁGINAS COM OS PLANOS
Route::get('/facilitai', function () {return redirect()->route('lp4');});
Route::get('/planos', function () {return redirect()->route('lp4');});

//MODELOS DE PÁGINAS COM PLANOS
Route::get('/lp-1', function () {return view('homepage.lp1');})->name('lp1');
Route::get('/lp-2', function () {return view('homepage.lp2');})->name('lp2');
Route::get('/lp-3', function () {return view('homepage.lp3');})->name('lp3');
Route::get('/lp-4', function () {return view('homepage.lp4');})->name('lp4');

// Nova página pública de planos com o layout FacilitAI
Route::get('/facilitai-pricing', [HomepageController::class, 'facilitaiPricing'])->name('facilitai.pricing');

// Landing page por cidade
Route::get('/cidade/{city}', function (string $city) {
    return view('cidade', ['city' => $city]);
})->name('cidade.show');

//LANDING PAGES
Route::get('/lp/adv', function () {return view('lp.adv1');});
Route::get('/lp/odonto', function () {return view('lp.ondonto');});
Route::get('/workshop', [LandingPageController::class, 'workshop'])->name('workshop');
Route::get('/workshop-b', function () {return view('lp.workshop-v10');});
Route::get('/exemplos/pizza', function () {return view('exemplos.pizza');});
Route::get('/biblioteca/{slug}', [LibraryEntryController::class, 'publicShow'])->name('library.public.show');
Route::get('/library/public/{token}', [LibraryEntryController::class, 'publicEditForm'])->name('library.public.edit');
Route::post('/library/public/{token}/auth', [LibraryEntryController::class, 'publicAuthenticate'])->middleware('throttle:5,1')->name('library.public.auth');
Route::post('/library/public/{token}/logout', [LibraryEntryController::class, 'publicLogout'])->name('library.public.logout');
Route::post('/library/public/{token}', [LibraryEntryController::class, 'publicUpdate'])->name('library.public.update');

//AGENDAS PÚBLICAS
// Rota para a página pública de agendamento (o ponto de entrada do seu agendador em Blade/Alpine)
Route::get('/agendamento/{slug}', [AgendaPublicaController::class, 'index'])->name('agenda.publica');
// API para buscar as disponibilidades (via JS)
Route::get('/api/agendamento/{slug}/disponibilidades', [AgendaPublicaController::class, 'showDisponibilidades']);
// API para salvar um novo agendamento (via JS)
Route::post('/api/agendamento/{slug}/agendar', [AgendaPublicaController::class, 'storeAgendamento']);
Route::get('/api/agendamento/{slug}/horarios', [App\Http\Controllers\AgendaPublicaController::class, 'getHorarios']);





// ROTA PARA CAPTURA DE LEADS
Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
Route::view('obrigado', 'homepage.obrigado-cadastro')->name('obrigado');


// =============================================================
// DASHBOARD CLIENTE FINAL
// =============================================================
Route::match(['get', 'post'], '/dash/{id}', [InstanceController::class, 'dashboardPublica'])->name('public.dashboard');
// Rota para buscar os dados do QR Code em formato JSON
Route::get('/instances/{instance}/qrcode-data', [InstanceController::class, 'getQrCodeData'])->name('instances.qrcode_data');
// Rota para buscar apenas o status da conexão em formato JSON
Route::get('/instances/{instance}/status-data', [InstanceController::class, 'getConnectionStatusData'])->name('instances.status_data');
    Route::post('/chats/{chat}/marcar-atendido', [ChatController::class, 'marcarAtendido'])->name('chat.marcarAtendido');
    Route::get('/chats/conv/{conv}', [ChatController::class, 'showByConv'])->name('chats.conv');



// =============================================================
// ROTAS DA ÁREA ADMINISTRATIVA
// =============================================================
// O grupo garante que o usuário esteja logado (auth) E que seja admin (admin).
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Rota de recurso para o CRUD de Leads de Empresas
    Route::resource('leads', LeadEmpresaController::class);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/exportUsers', [DashboardController::class, 'exportUsers'])->name('exportUsers');
    Route::get('/instances', [InstanceReportController::class, 'index'])->name('instances.index');
    Route::resource('lessons', AdminLessonController::class)->except(['show']);
    Route::get('/webhook-requests', [WebhookRequestController::class, 'index'])->name('webhook-requests.index');
    Route::delete('/webhook-requests', [WebhookRequestController::class, 'destroyAll'])->name('webhook-requests.destroyAll');
    Route::delete('/webhook-requests/{webhookRequest}', [WebhookRequestController::class, 'destroy'])->name('webhook-requests.destroy');
    Route::get('/system-error-logs', [SystemErrorLogController::class, 'index'])->name('system-error-logs.index');

});

Route::middleware(['auth', 'admin'])->prefix('adm')->name('adm.')->group(function () {
    Route::get('dashboard', function () {
        return view('adm.dashboard');
    })->name('dashboard');
    Route::get('conexoes', [App\Http\Controllers\Admin\ConexaoController::class, 'index'])->name('conexoes.index');
    Route::post('conexoes', [App\Http\Controllers\Admin\ConexaoController::class, 'store'])->name('conexoes.store');
    Route::patch('conexoes/{conexao}', [App\Http\Controllers\Admin\ConexaoController::class, 'update'])->name('conexoes.update');
    Route::delete('conexoes/{conexao}', [App\Http\Controllers\Admin\ConexaoController::class, 'destroy'])->name('conexoes.destroy');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/asaas-customer', [UserController::class, 'createAsaasCustomer'])->name('users.asaas-customer');
    Route::get('assistant-lead', [AssistantLeadController::class, 'index'])->name('assistant-lead.index');
    Route::delete('assistant-lead/{assistantLead}', [AssistantLeadController::class, 'destroy'])->name('assistant-lead.destroy');
    Route::get('openai/conv_id', [App\Http\Controllers\Admin\OpenAIController::class, 'convId'])->name('openai.conv_id');
    Route::get('ia-plataformas', [App\Http\Controllers\Admin\IaplataformaController::class, 'index'])->name('iaplataformas.index');
    Route::post('ia-plataformas', [App\Http\Controllers\Admin\IaplataformaController::class, 'store'])->name('iaplataformas.store');
    Route::patch('ia-plataformas/{iaplataforma}', [App\Http\Controllers\Admin\IaplataformaController::class, 'update'])->name('iaplataformas.update');
    Route::delete('ia-plataformas/{iaplataforma}', [App\Http\Controllers\Admin\IaplataformaController::class, 'destroy'])->name('iaplataformas.destroy');
    Route::get('ia-modelos', [App\Http\Controllers\Admin\IamodeloController::class, 'index'])->name('iamodelos.index');
    Route::post('ia-modelos', [App\Http\Controllers\Admin\IamodeloController::class, 'store'])->name('iamodelos.store');
    Route::patch('ia-modelos/{iamodelo}', [App\Http\Controllers\Admin\IamodeloController::class, 'update'])->name('iamodelos.update');
    Route::delete('ia-modelos/{iamodelo}', [App\Http\Controllers\Admin\IamodeloController::class, 'destroy'])->name('iamodelos.destroy');
    Route::get('whatsapp-api', [App\Http\Controllers\Admin\WhatsappApiController::class, 'index'])->name('whatsapp-api.index');
    Route::post('whatsapp-api', [App\Http\Controllers\Admin\WhatsappApiController::class, 'store'])->name('whatsapp-api.store');
    Route::patch('whatsapp-api/{whatsappApi}', [App\Http\Controllers\Admin\WhatsappApiController::class, 'update'])->name('whatsapp-api.update');
    Route::delete('whatsapp-api/{whatsappApi}', [App\Http\Controllers\Admin\WhatsappApiController::class, 'destroy'])->name('whatsapp-api.destroy');
    Route::get('cliente-lead', [ClienteLeadController::class, 'index'])->name('cliente-lead.index');
    Route::delete('cliente-lead/{clienteLead}', [ClienteLeadController::class, 'destroy'])->name('cliente-lead.destroy');
    Route::view('payload', 'admin.payload.index')->name('payload.index');
    Route::post('payload', [App\Http\Controllers\Admin\PayloadController::class, 'send'])->name('payload.send');
    Route::get('plano', [PlanController::class, 'index'])->name('plano.index');
    Route::post('plano', [PlanController::class, 'store'])->name('plano.store');
    Route::put('plano/{plan}', [PlanController::class, 'update'])->name('plano.update');
    Route::delete('plano/{plan}', [PlanController::class, 'destroy'])->name('plano.destroy');
    Route::get('prompt-ajuda', [PromptHelpController::class, 'index'])->name('prompt-ajuda.index');
    Route::post('prompt-ajuda/tipos', [PromptHelpController::class, 'storeTipo'])->name('prompt-ajuda.tipos.store');
    Route::patch('prompt-ajuda/tipos/{tipo}', [PromptHelpController::class, 'updateTipo'])->name('prompt-ajuda.tipos.update');
    Route::delete('prompt-ajuda/tipos/{tipo}', [PromptHelpController::class, 'destroyTipo'])->name('prompt-ajuda.tipos.destroy');
    Route::post('prompt-ajuda/sections', [PromptHelpController::class, 'storeSection'])->name('prompt-ajuda.sections.store');
    Route::patch('prompt-ajuda/sections/{section}', [PromptHelpController::class, 'updateSection'])->name('prompt-ajuda.sections.update');
    Route::delete('prompt-ajuda/sections/{section}', [PromptHelpController::class, 'destroySection'])->name('prompt-ajuda.sections.destroy');
    Route::post('prompt-ajuda/prompts', [PromptHelpController::class, 'storePrompt'])->name('prompt-ajuda.prompts.store');
    Route::patch('prompt-ajuda/prompts/{prompt}', [PromptHelpController::class, 'updatePrompt'])->name('prompt-ajuda.prompts.update');
    Route::delete('prompt-ajuda/prompts/{prompt}', [PromptHelpController::class, 'destroyPrompt'])->name('prompt-ajuda.prompts.destroy');
    Route::get('logs', [LogFileController::class, 'index'])->name('logs.index');
    Route::get('logs/{file}', [LogFileController::class, 'show'])->where('file', '[^/]+')->name('logs.show');
    Route::get('logs/{file}/download', [LogFileController::class, 'download'])->where('file', '[^/]+')->name('logs.download');
});

Route::get('/dashboard', function () {
    return redirect()->route('agencia.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth', 'verified')->group(function () {
    Route::prefix('uazapi')->name('uazapi.')->group(function () {
        Route::get('/instancias', [UazapiController::class, 'index'])->name('instances');
        Route::post('/instancias/create', [UazapiController::class, 'store'])->name('instances.create');
        Route::patch('/instancias/{instance}', [UazapiController::class, 'update'])->name('instances.update');
        Route::post('/instancias/{instance}/connect', [UazapiController::class, 'connect'])->name('instances.connect');
        Route::get('/instancias/{instance}/status', [UazapiController::class, 'status'])->name('instances.status');
        Route::post('/instancias/{instance}/destroy', [UazapiController::class, 'destroy'])->name('instances.destroy');
        Route::post('/instancias/{instance}/assistant', [UazapiController::class, 'assignAssistant'])->name('instances.assignAssistant');
    });

    Route::middleware('admin')->group(function () {
        Route::get('/proxy-ban', [ProxyBanController::class, 'index'])->name('proxy-ban.index');
        Route::delete('/proxy-ban/{ban}', [ProxyBanController::class, 'destroy'])->name('proxy-ban.destroy');
    });
    
    // AGENDAS E DISPONIBILIDADES
    Route::post('/agendas/{agenda}/gerar-disponibilidades', [AgendaController::class, 'gerarDisponibilidades'])->name('agendas.gerarDisponibilidades');
    Route::get('/agendas/{agenda}/gerenciar', [AgendaController::class, 'gerenciar'])->name('agendas.gerenciar');
    Route::post('/agendas/acoes-em-massa', [AgendaController::class, 'acoesEmMassa'])->name('agendas.acoesEmMassa');
    Route::delete('/disponibilidades/{id}', [AgendaController::class, 'destroyDisponibilidade'])->name('disponibilidades.destroy');
    Route::patch('/disponibilidades/{id}', [AgendaController::class, 'atualizarDisponibilidade'])->name('disponibilidades.update');
    Route::post('/disponibilidades/acoes-massa', [AgendaController::class, 'acoesEmMassa'])->name('disponibilidades.acoes-massa');


    // Resource deve vir *por último*
    Route::resource('agendas', AgendaController::class)->except(['show']);

    //PROFILE
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //PASTAS DE IMAGENS
    Route::resource('folders', FolderController::class)->only(['store', 'update', 'destroy']);

    //BIBLIOTECA DE TEXTOS
    Route::resource('library', LibraryEntryController::class)->except(['show']);

    //CHATS
    Route::get('/chats/export', [ChatController::class, 'export'])->name('chats.export');
    Route::post('/chats/bulk-atendido', [ChatController::class, 'bulkMarkAttended'])->name('chats.bulk_attended');
    Route::post('/chats/import', [ChatController::class, 'import'])->name('chats.import');
    Route::post('/chats/{chat}/toggle-bot', [ChatController::class, 'toggleBot'])->name('chats.toggle_bot');
    Route::post('/chats/{chat}/tags', [ChatController::class, 'applyTags'])->name('chats.tags.apply');
    Route::delete('/chats/{chat}/tags/{tag}', [ChatController::class, 'removeTag'])->name('chats.tags.remove');
    Route::post('/chats/inscrever-sequencia', [ChatController::class, 'inscreverSequencia'])->name('chats.sequence.subscribe');
    Route::resource('chats', ChatController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);


    // INSTANCIAS
    // Nova rota POST para a criação direta
    Route::post('/instances/create-direct', [InstanceController::class, 'storeDirect'])->name('instances.store_direct');
    Route::post('/instances/{instance}/restart', [InstanceController::class, 'restart'])->name('instances.restart');
    Route::post('/instances/{instance}/logout', [InstanceController::class, 'logout'])->name('instances.logout');
    // Mantém as outras rotas do resource, mas remove a 'create' que não usamos mais
    Route::resource('instances', InstanceController::class)->except(['create']);

    //COMPRAR TOKENS
    Route::get('/tokens/comprar', [TokensController::class, 'comprar'])->name('tokens.comprar');;
    Route::post('/tokens/payment', [TokensController::class, 'createPayment'])->name('tokens.createPayment');
    Route::get('/payments', [TokensController::class, 'index'])->name('payments.index');

    //BUSCAR EMPRESAS
    Route::get('/empresas', [EmpresasController::class, 'index'])->name('empresas.index');
    Route::post('/empresas/buscar', [EmpresasController::class, 'buscar'])->name('empresas.buscar');

    // CREDENCIAIS
    Route::resource('credentials', CredentialController::class);
    // Nova rota para o JavaScript chamar e buscar assistentes de uma credencial

    // ASSISTENTES
    Route::get('/assistants', [AssistantController::class, 'index'])->name('assistants.index');
    //Route::post('/assistants/fetch', [AssistantController::class, 'fetchAssistants'])->name('assistants.fetch');
    Route::delete('/assistants/{assistant}', [AssistantController::class, 'destroy'])->name('assistants.destroy');
    // Rota GET para mostrar a página do assistente de criação (o quiz)
    Route::get('/assistant-builder', [AssistantController::class, 'showBuilder'])->name('assistants.builder');
    // Rota POST para receber os dados do quiz e criar o assistente
    Route::post('/assistant-builder', [AssistantController::class, 'storeFromBuilder'])->name('assistants.store_from_builder');
    // Novo wizard simples
    Route::get('/assistants/wizard', [AssistantController::class, 'wizard'])->name('assistants.wizard');
    Route::post('/assistants/wizard', [AssistantController::class, 'storeWizard'])->name('assistants.wizard.store');
    Route::get('/assistants/{assistant}/wizard', [AssistantController::class, 'editWizard'])->name('assistants.wizard.edit');
    Route::put('/assistants/{assistant}/wizard', [AssistantController::class, 'updateWizard'])->name('assistants.wizard.update');
    Route::resource('assistants', AssistantController::class)->except(['create', 'show', 'store']);

});

Route::get('/test-credential/{id}', function ($id) {
    // Busca a credencial sem verificar o dono (só para nosso teste)
    $credential = Credential::find($id);

    if (!$credential) {
        return "Credencial não encontrada.";
    }

    // Usa dd() (die and dump) para mostrar o conteúdo descriptografado e parar a execução
    dd($credential->token);
});

require __DIR__.'/auth.php';

Route::middleware('auth')->prefix('agencia')->name('agencia.')->group(function () {
    Route::get('dashboard', function () {
        return view('agencia.dashboard');
    })->name('dashboard');
    Route::get('clientes', [AgenciaClienteController::class, 'index'])->name('clientes.index');
    Route::post('clientes', [AgenciaClienteController::class, 'store'])->name('clientes.store');
    Route::patch('clientes/{cliente}', [AgenciaClienteController::class, 'update'])->name('clientes.update');
    Route::patch('clientes/{cliente}/restore', [AgenciaClienteController::class, 'restore'])->name('clientes.restore');
    Route::delete('clientes/{cliente}/force', [AgenciaClienteController::class, 'forceDelete'])->name('clientes.forceDelete');
    Route::delete('clientes/{cliente}', [AgenciaClienteController::class, 'destroy'])->name('clientes.destroy');
    Route::get('credenciais', [AgenciaCredentialController::class, 'index'])->name('credentials.index');
    Route::post('credenciais', [AgenciaCredentialController::class, 'store'])->name('credentials.store');
    Route::patch('credenciais/{credential}', [AgenciaCredentialController::class, 'update'])->name('credentials.update');
    Route::delete('credenciais/{credential}', [AgenciaCredentialController::class, 'destroy'])->name('credentials.destroy');
    Route::get('conexoes', [AgenciaConexaoController::class, 'index'])->name('conexoes.index');
    Route::post('conexoes', [AgenciaConexaoController::class, 'store'])->name('conexoes.store');
    Route::get('conexoes/{conexao}/status', [AgenciaConexaoController::class, 'status'])->name('conexoes.status');
    Route::post('conexoes/{conexao}/connect', [AgenciaConexaoController::class, 'connect'])->name('conexoes.connect');
    Route::patch('conexoes/{conexao}', [AgenciaConexaoController::class, 'update'])->name('conexoes.update');
    Route::delete('conexoes/{conexao}', [AgenciaConexaoController::class, 'destroy'])->name('conexoes.destroy');
    Route::get('assistant', [AgenciaAssistantController::class, 'index'])->name('assistant.index');
    Route::post('assistant', [AgenciaAssistantController::class, 'store'])->name('assistant.store');
    Route::patch('assistant/{assistant}', [AgenciaAssistantController::class, 'update'])->name('assistant.update');
    Route::get('profile', [AgenciaProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [AgenciaProfileController::class, 'update'])->name('profile.update');
    Route::get('agency-settings', [AgenciaSettingsController::class, 'edit'])->name('agency-settings.edit');
    Route::post('agency-settings', [AgenciaSettingsController::class, 'update'])->name('agency-settings.update');
    Route::get('sequence', [AgenciaSequenceController::class, 'index'])->name('sequences.index');
    Route::get('conversas', [AgenciaClienteLeadController::class, 'index'])->name('conversas.index');
    Route::post('conversas', [AgenciaClienteLeadController::class, 'store'])->name('conversas.store');
    Route::post('conversas/{clienteLead}/send-message', [AgenciaClienteLeadController::class, 'sendMessage'])->name('conversas.send-message');
    Route::post('conversas/import', [AgenciaClienteLeadController::class, 'import'])->name('conversas.import');
    Route::post('conversas/preview', [AgenciaClienteLeadController::class, 'preview'])->name('conversas.preview');
    Route::get('conversas/export', [AgenciaClienteLeadController::class, 'export'])->name('conversas.export');
    Route::get('openai/conv_id', [AgenciaOpenAIController::class, 'convId'])->name('openai.conv_id');
    Route::put('conversas/{clienteLead}', [AgenciaClienteLeadController::class, 'update'])->name('conversas.update');
    Route::delete('conversas/{clienteLead}', [AgenciaClienteLeadController::class, 'destroy'])->name('conversas.destroy');
    Route::post('sequences', [AgenciaSequenceController::class, 'store'])->name('sequences.store');
    Route::post('sequences/{sequence}/steps', [AgenciaSequenceController::class, 'storeStep'])->name('sequences.steps.store');
    Route::patch('sequences/{sequence}/steps/{step}', [AgenciaSequenceController::class, 'updateStep'])->name('sequences.steps.update');
    Route::delete('sequences/{sequence}/steps/{step}', [AgenciaSequenceController::class, 'destroyStep'])->name('sequences.steps.destroy');
    Route::get('clientes/{cliente}/conexoes', [AgenciaSequenceController::class, 'conexoes'])->name('sequences.cliente.conexoes');
    Route::get('clientes/{cliente}/sequences', [AgenciaSequenceController::class, 'sequences'])->name('sequences.cliente.sequences');
    Route::delete('sequence-chats/{sequenceChat}', [AgenciaSequenceController::class, 'destroySequenceChat'])->name('sequence-chats.destroy');
    Route::post('images/move', [AgenciaImageController::class, 'move'])->name('images.move');
    Route::delete('images/bulk-destroy', [AgenciaImageController::class, 'bulkDestroy'])->name('images.bulkDestroy');
    Route::resource('images', AgenciaImageController::class)->only(['index', 'store', 'destroy', 'update']);
    Route::resource('folders', FolderController::class)->only(['store', 'update', 'destroy']);
    Route::get('tags', [AgenciaTagController::class, 'index'])->name('tags.index');
    Route::post('tags', [AgenciaTagController::class, 'store'])->name('tags.store');
    Route::delete('tags/{tag}', [AgenciaTagController::class, 'destroy'])->name('tags.destroy');
    Route::get('library', [AgenciaLibraryEntryController::class, 'index'])->name('library.index');
    Route::post('library', [AgenciaLibraryEntryController::class, 'store'])->name('library.store');
    Route::put('library/{libraryEntry}', [AgenciaLibraryEntryController::class, 'update'])->name('library.update');
    Route::delete('library/{libraryEntry}', [AgenciaLibraryEntryController::class, 'destroy'])->name('library.destroy');
});

Route::prefix('cliente')->name('cliente.')->group(function () {
    Route::middleware('guest:client')->group(function () {
        Route::get('login', [ClienteAuthController::class, 'create'])->name('login');
        Route::post('login', [ClienteAuthController::class, 'store']);
    });

    Route::middleware('auth:client')->group(function () {
        Route::get('dashboard', [ClienteDashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [ClienteAuthController::class, 'destroy'])->name('logout');
        Route::get('assistant', [ClienteAssistantController::class, 'index'])->name('assistant.index');
        Route::patch('assistant/{assistant}', [ClienteAssistantController::class, 'update'])->name('assistant.update');
        Route::get('conexoes', [ConexaoClienteController::class, 'index'])->name('conexoes.index');
        Route::get('conexoes/{conexao}/status', [ConexaoClienteController::class, 'status'])->name('conexoes.status');
        Route::post('conexoes/{conexao}/connect', [ConexaoClienteController::class, 'connect'])->name('conexoes.connect');
        Route::get('conversas', [ClienteClienteLeadController::class, 'index'])->name('conversas.index');
        Route::post('conversas', [ClienteClienteLeadController::class, 'store'])->name('conversas.store');
        Route::post('conversas/import', [ClienteClienteLeadController::class, 'import'])->name('conversas.import');
        Route::post('conversas/preview', [ClienteClienteLeadController::class, 'preview'])->name('conversas.preview');
        Route::get('conversas/export', [ClienteClienteLeadController::class, 'export'])->name('conversas.export');
        Route::put('conversas/{clienteLead}', [ClienteClienteLeadController::class, 'update'])->name('conversas.update');
        Route::delete('conversas/{clienteLead}', [ClienteClienteLeadController::class, 'destroy'])->name('conversas.destroy');
        Route::get('library', [LibraryClienteController::class, 'index'])->name('library.index');
        Route::post('library', [LibraryClienteController::class, 'store'])->name('library.store');
        Route::put('library/{libraryEntry}', [LibraryClienteController::class, 'update'])->name('library.update');
        Route::delete('library/{libraryEntry}', [LibraryClienteController::class, 'destroy'])->name('library.destroy');
    });
});
