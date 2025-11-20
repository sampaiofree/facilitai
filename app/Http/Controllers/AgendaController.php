<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agenda;
use App\Models\Disponibilidade;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // Importar Rule

class AgendaController extends Controller
{
    /**
     * Exibe a lista de agendas do usuário autenticado.
     */
    public function index()
    {
        $user = Auth::user();
        $availableSlots = $user->availableAgendaSlots();
        $agendas = Agenda::where('user_id', Auth::id())->latest()->get();
        return view('agendas.index', compact('agendas', 'availableSlots'));
    }

    /**
     * Armazena uma nova agenda.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:500',
            // Validação customizada para slots disponíveis
            'available_slots_check' => [
                Rule::when($user->availableAgendaSlots() <= 0, ['required'], ['size:0', 'prohibited']),
                function ($attribute, $value, $fail) use ($user) {
                    if ($user->availableAgendaSlots() <= 0) {
                        $fail('Você não tem slots disponíveis para criar uma nova agenda.');
                    }
                },
            ]
        ], [
            'available_slots_check.prohibited' => 'Você não tem slots disponíveis para criar uma nova agenda.',
            'available_slots_check.required' => 'Você não tem slots disponíveis para criar uma nova agenda.',
        ]);

        $agenda = Agenda::create([
            'user_id' => Auth::id(),
            'titulo' => $request->titulo,
            'descricao' => $request->descricao,
            'slug' => Str::slug($request->titulo) . '-' . Str::random(5),
        ]);

        return redirect()->route('agendas.index')->with('success', 'Agenda criada com sucesso!');
    }

    /**
     * Exibe o formulário para gerar novas disponibilidades.
     * @param Agenda $agenda A agenda para a qual gerar disponibilidades.
     */
    public function showGerarDisponibilidades(Agenda $agenda)
    {
        // Autorização: garante que o usuário logado é o dono da agenda.
        if ($agenda->user_id !== Auth::id()) {
            abort(403, 'Você não tem permissão para acessar esta agenda.');
        }
        return view('agendas.gerar_disponibilidades', compact('agenda'));
    }

    /**
     * Gera disponibilidades para uma agenda específica.
     * @param Request $request Os dados da requisição.
     * @param Agenda $agenda A agenda para a qual gerar disponibilidades.
     */
    public function gerarDisponibilidades(Request $request, Agenda $agenda)
    {
        // 🔒 Autorização
        if ($agenda->user_id !== Auth::id()) {
            abort(403, 'Você não tem permissão para gerar disponibilidades para esta agenda.');
        }

        // ✅ Validação
        $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:' . now()->year . '|max:' . (now()->year + 2),
            'dias_semana' => 'required|array|min:1',
            'dias_semana.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            'intervalo' => 'required|integer|min:5|max:240',
        ]);

        // 🗓️ Cria o período de geração
        $inicio = Carbon::create($request->ano, $request->mes, 1)->startOfDay();
        $fim = $inicio->copy()->endOfMonth();
        $horaInicio = Carbon::createFromTimeString($request->hora_inicio);
        $horaFim = Carbon::createFromTimeString($request->hora_fim);
        $intervalo = (int) $request->intervalo;
        $hoje = now()->startOfDay();

        $duracaoExpedienteEmMinutos = $horaInicio->diffInMinutes($horaFim);
        if ($intervalo > $duracaoExpedienteEmMinutos) {
            return back()->withErrors([
                'intervalo' => 'O intervalo não pode ser maior que a duração total do expediente.'
            ])->withInput();
        }

        // 🔢 Limite máximo por horário (vem da agenda)
        $limitePorHorario = $agenda->limite_por_horario ?? 1;

        // 📅 Gera todos os dias válidos do mês
        $diasDoMes = $inicio->daysUntil($fim->copy());
        $novas = 0;
        $conflitos = [];

        foreach ($diasDoMes as $dia) {
            // ❌ Pula dias passados
            if ($dia->lt($hoje)) {
                continue;
            }

            // 🧭 Verifica se o dia faz parte dos dias selecionados
            if (!in_array(strtolower($dia->englishDayOfWeek), $request->dias_semana)) {
                continue;
            }

            // ⏱️ Gera os intervalos de tempo
            for ($hora = $horaInicio->copy(); $hora->lt($horaFim); $hora->addMinutes($intervalo)) {
                $slotFim = $hora->copy()->addMinutes($intervalo);

                // Pula se o slot ultrapassar o expediente
                if ($slotFim->gt($horaFim) && $slotFim->ne($horaFim)) {
                    continue;
                }

                // 🔍 Conta quantas disponibilidades já existem para este mesmo horário
                $qtdExistente = Disponibilidade::where('agenda_id', $agenda->id)
                    ->where('data', $dia->toDateString())
                    ->where('inicio', $hora->format('H:i'))
                    ->count();

                // ⚠️ Se já atingiu o limite, não cria mais
                if ($qtdExistente >= $limitePorHorario) {
                    $conflitos[] = "⏱️ {$hora->format('H:i')} em {$dia->translatedFormat('d/m/Y')} já atingiu o limite ({$limitePorHorario}).";
                    continue;
                }

                // ✅ Cria a nova disponibilidade
                Disponibilidade::create([
                    'agenda_id' => $agenda->id,
                    'data' => $dia->toDateString(),
                    'inicio' => $hora->format('H:i'),
                    'fim' => $slotFim->format('H:i'),
                    'ocupado' => false,
                    'nome' => null,
                    'telefone' => null,
                ]);

                $novas++;
            }
        }

        // 🧾 Mensagem final
        $mensagem = "{$novas} disponibilidades geradas com sucesso!";
        if (!empty($conflitos)) {
            $mensagem .= " " . count($conflitos) . " conflito(s) ignorado(s).";
            return back()->with('warning', $mensagem)
                        ->withErrors(['conflitos' => $conflitos]);
        }

        return back()->with('success', $mensagem);
    }



    /**
     * Exclui uma agenda.
     * @param Agenda $agenda A agenda a ser excluída.
     */
    public function destroy(Agenda $agenda)
    {
        // Autorização
        if ($agenda->user_id !== Auth::id()) {
            abort(403, 'Você não tem permissão para excluir esta agenda.');
        }
        $agenda->delete();
        return redirect()->route('agendas.index')->with('success', 'Agenda excluída com sucesso.');
    }

    /**
     * Exclui uma disponibilidade específica.
     * @param int $id O ID da disponibilidade a ser excluída.
     */
    public function destroyDisponibilidade($id)
    {
        $disponibilidade = Disponibilidade::findOrFail($id);

        // Autorização
        if ($disponibilidade->agenda->user_id !== Auth::id()) {
            abort(403, 'Você não tem permissão para excluir esta disponibilidade.');
        }

        $disponibilidade->delete();
        return back()->with('success', 'Disponibilidade excluída com sucesso.');
    }

    /**
     * Exibe e gerencia as disponibilidades de uma agenda.
     * Inclui filtros e paginação.
     * @param Agenda $agenda A agenda a ser gerenciada.
     * @param Request $request Os dados da requisição para filtros.
     */
    public function gerenciar(Agenda $agenda, Request $request)
    {
        // Autorização
        if ($agenda->user_id !== Auth::id()) {
            abort(403, 'Você não tem permissão para acessar esta agenda.');
        }

        // Lista de meses disponíveis (YYYY-MM-01) para o grid
        $meses = Disponibilidade::where('agenda_id', $agenda->id)
            ->selectRaw('DATE_FORMAT(data, "%Y-%m-01") as mes_ref')
            ->groupBy('mes_ref')
            ->orderBy('mes_ref', 'asc')
            ->pluck('mes_ref')
            ->map(fn($d) => \Carbon\Carbon::parse($d));

        // Se o usuário ainda não escolheu mês, mostramos só o grid de meses
        $mesSelecionado = $request->query('mes'); // formato esperado: YYYY-MM
        $disponibilidades = collect(); // default vazio

        if ($mesSelecionado) {
            // Define início/fim do mês selecionado
            try {
                [$y, $m] = explode('-', $mesSelecionado);
                $inicio = \Carbon\Carbon::createFromDate((int)$y, (int)$m, 1)->startOfMonth();
                $fim    = $inicio->copy()->endOfMonth();
            } catch (\Throwable $e) {
                return back()->with('warning', 'Mês inválido.');
            }

            $disponibilidades = $agenda->disponibilidades()
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->when($request->filtro_data, fn($q) => $q->whereDate('data', $request->filtro_data))
                ->when($request->filtro_status === 'livre', fn($q) => $q->where('ocupado', false))
                ->when($request->filtro_status === 'ocupado', fn($q) => $q->where('ocupado', true))
                ->when($request->filtro_busca, function ($q) use ($request) {
                    $q->where(function ($query) use ($request) {
                        $query->where('nome', 'like', '%' . $request->filtro_busca . '%')
                            ->orWhere('telefone', 'like', '%' . $request->filtro_busca . '%');
                    });
                })
                ->orderBy('data')
                ->orderBy('inicio')
                ->paginate(20)
                ->withQueryString(); // mantém ?mes=... nos links de paginação
        }

        return view('agendas.gerenciar', compact('agenda', 'meses', 'mesSelecionado', 'disponibilidades'));
    }

    /**
     * Atualiza uma agenda existente (título, descrição, slug, limite por horário)
     */
    public function update(Request $request, Agenda $agenda)
    {
        // Autorização
        if ($agenda->user_id !== Auth::id()) {
            abort(403, 'Você não tem permissão para editar esta agenda.');
        }

        // Validação dos campos
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:500',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('agendas')->ignore($agenda->id),
            ],
            'limite_por_horario' => 'required|integer|min:1|max:20',
        ]);

        // Atualiza o slug se o título for alterado e o usuário não tiver definido manualmente
        if ($request->titulo !== $agenda->titulo && empty($request->slug)) {
            $validated['slug'] = Str::slug($request->titulo) . '-' . Str::random(5);
        }

        $agenda->update($validated);

        return redirect()
            ->route('agendas.index')
            ->with('success', 'Agenda atualizada com sucesso!');
    }



    /**
     * Atualiza uma disponibilidade específica.
     * @param Request $request Os dados da requisição.
     * @param int $id O ID da disponibilidade a ser atualizada.
     */
    public function atualizarDisponibilidade(Request $request, $id)
    {
        $disponibilidade = Disponibilidade::findOrFail($id);

        // Autorização
        if ($disponibilidade->agenda->user_id !== Auth::id()) {
            abort(403, 'Você não tem permissão para editar esta disponibilidade.');
        }

        // Validação dos dados de atualização
        $request->validate([
            'nome' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:20', // Ajuste o max de acordo com o padrão do seu telefone
            'ocupado' => 'boolean',
        ]);

        $disponibilidade->update([
            'nome' => $request->ocupado ? $request->nome : null, // Limpa nome/telefone se desocupado
            'telefone' => $request->ocupado ? $request->telefone : null,
            'ocupado' => $request->has('ocupado'),
        ]);

        return back()->with('success', 'Disponibilidade atualizada com sucesso.');
    }

    /**
     * Realiza ações em massa (excluir, ocupar, desocupar) em várias disponibilidades.
     * @param Request $request Os dados da requisição (ids selecionados e a ação).
     */
    public function acoesEmMassa(Request $request)
{
    // IDs podem vir como array ou como string "1,2,3"
    $ids = $request->input('disponibilidade_ids', []);
    if (empty($ids)) {
        $ids = explode(',', (string) $request->input('selecionadas', ''));
    }
    $ids = array_values(array_filter(array_map('intval', $ids)));

    if (empty($ids)) {
        return back()->with('warning', 'Nenhuma disponibilidade selecionada.');
    }

    // Carrega apenas do usuário autenticado
    $disponibilidades = Disponibilidade::with('agenda')
        ->whereIn('id', $ids)
        ->whereHas('agenda', fn($q) => $q->where('user_id', Auth::id()))
        ->get();

    if ($disponibilidades->count() !== count($ids)) {
        return back()->with('error', 'Você não tem permissão para uma ou mais disponibilidades selecionadas.');
    }

    $count = count($ids);
    $action = $request->input('action');
    $message = '';

    switch ($action) {
        case 'excluir':
            Disponibilidade::whereIn('id', $ids)->delete();
            $message = "{$count} disponibilidades excluídas com sucesso.";
            break;
        case 'ocupar':
            Disponibilidade::whereIn('id', $ids)->update(['ocupado' => true]);
            $message = "{$count} disponibilidades marcadas como ocupadas.";
            break;
        case 'desocupar':
            Disponibilidade::whereIn('id', $ids)->update(['ocupado' => false, 'nome' => null, 'telefone' => null]);
            $message = "{$count} disponibilidades marcadas como livres.";
            break;
        default:
            return back()->with('warning', 'Ação inválida.');
    }

    return back()->with('success', $message);
}

}