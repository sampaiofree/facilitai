<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InstanceController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;
use App\Models\Credential;
use App\Http\Controllers\Admin\LeadEmpresaController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\LibraryEntryController;
use App\Http\Controllers\LeadController; 
use App\Http\Controllers\TokensController;
use App\Http\Controllers\Admin\DashboardController; 
use App\Http\Controllers\Admin\LessonController as AdminLessonController; 
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\MassSendController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AgendaPublicaController; 
use App\Http\Controllers\SequenceController;
use App\Http\Controllers\LessonPublicController;


Route::get('/conv/{conv_id}', [ProfileController::class, 'conv']);

//PÁGINAS PUBLICAS
Route::get('/', function () {return redirect()->route('lp4');})->name('homepage');
Route::get('/politica', function () {return view('homepage.politica');})->name('politica');
Route::get('/bio', function () {return view('homepage.bio');});
Route::get('/grupo-black', function () {return view('homepage.blackfriday');});
Route::get('/lessons/for-page', [LessonPublicController::class, 'forPage'])->name('lessons.for-page');

//PÁGINAS COM OS PLANOS
Route::get('/facilitai', function () {return redirect()->route('lp4');});
Route::get('/planos', function () {return redirect()->route('lp4');});

//MODELOS DE PÁGINAS COM PLANOS
Route::get('/lp-1', function () {return view('homepage.lp4');})->name('lp1');
Route::get('/lp-2', function () {return view('homepage.lp4');})->name('lp2');
Route::get('/lp-3', function () {return view('homepage.lp4');})->name('lp3');
Route::get('/lp-4', function () {return view('homepage.lp4');})->name('lp4');

//LANDING PAGES
Route::get('/lp/adv', function () {return view('lp.adv1');});
Route::get('/lp/odonto', function () {return view('lp.ondonto');});
Route::get('/workshop', [LandingPageController::class, 'workshop'])->name('workshop');
Route::get('/workshop-b', function () {return view('lp.workshop-v10');});
Route::get('/exemplos/pizza', function () {return view('exemplos.pizza');});
Route::get('/biblioteca/{slug}', [LibraryEntryController::class, 'publicShow'])->name('library.public.show');

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



// =============================================================
// ROTAS DA ÁREA ADMINISTRATIVA
// =============================================================
// O grupo garante que o usuário esteja logado (auth) E que seja admin (admin).
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Rota de recurso para o CRUD de Leads de Empresas
    Route::resource('leads', LeadEmpresaController::class);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/exportUsers', [DashboardController::class, 'exportUsers'])->name('exportUsers');
    Route::resource('lessons', AdminLessonController::class)->except(['show']);

});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth', 'verified')->group(function () {
    
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

    //CRIAR IMAGENS
    Route::post('/images/move', [ImageController::class, 'move'])->name('images.move');
    Route::resource('images', ImageController::class)->only(['index', 'store', 'destroy']);

    //BIBLIOTECA DE TEXTOS
    Route::resource('library', LibraryEntryController::class)->except(['show']);

    //CHATS
    Route::get('/chats/export', [ChatController::class, 'export'])->name('chats.export');
    Route::post('/chats/bulk-atendido', [ChatController::class, 'bulkMarkAttended'])->name('chats.bulk_attended');
    Route::post('/chats/{chat}/toggle-bot', [ChatController::class, 'toggleBot'])->name('chats.toggle_bot');
    Route::post('/chats/{chat}/tags', [ChatController::class, 'applyTags'])->name('chats.tags.apply');
    Route::delete('/chats/{chat}/tags/{tag}', [ChatController::class, 'removeTag'])->name('chats.tags.remove');
    Route::post('/chats/inscrever-sequencia', [ChatController::class, 'inscreverSequencia'])->name('chats.sequence.subscribe');
    Route::resource('chats', ChatController::class)->only(['index', 'update', 'destroy']);
    Route::resource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('sequences', SequenceController::class);
    Route::delete('sequences/{sequence}/chats/{sequenceChat}', [SequenceController::class, 'removeChat'])
        ->name('sequences.chats.destroy');

    //DISPAROS EM MASSA
    Route::get('/disparos', [MassSendController::class, 'index'])->name('mass.index');
    Route::post('/disparos', [MassSendController::class, 'store'])->name('mass.store');
    Route::get('/disparos/historico', [MassSendController::class, 'historico'])
    ->name('mass.historico');
    Route::get('/disparos/{id}', [MassSendController::class, 'show'])
    ->name('mass.show');



    // INSTANCIAS
    // Nova rota POST para a criação direta
    Route::post('/instances/create-direct', [InstanceController::class, 'storeDirect'])->name('instances.store_direct');
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
    Route::get('/credentials/{credential}/assistants', [InstanceController::class, 'getAssistantsForCredential'])->name('credentials.assistants');

    // ASSISTENTES
    Route::get('/assistants', [AssistantController::class, 'index'])->name('assistants.index');
    //Route::post('/assistants/fetch', [AssistantController::class, 'fetchAssistants'])->name('assistants.fetch');
    Route::post('/assistants', [AssistantController::class, 'store'])->name('assistants.store');
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
    Route::resource('assistants', AssistantController::class)->except(['create', 'show']);

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
