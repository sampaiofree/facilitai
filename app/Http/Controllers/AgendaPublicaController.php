<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Disponibilidade;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AgendaPublicaController extends Controller
{
    /*public function index($slug)
    {
        $agenda = Agenda::where('slug', $slug)->firstOrFail();
        return view('agendas.publica', compact('agenda'));
    }*/

public function index($slug)
{
    $agenda = Agenda::where('slug', $slug)->firstOrFail();

    // Busca as disponibilidades futuras e não ocupadas
    $disponibilidades = \App\Models\Disponibilidade::where('agenda_id', $agenda->id)
        ->where('ocupado', false)
        ->where('data', '>=', now()->startOfDay())
        ->orderBy('data')
        ->get(['data']);

    // Agrupa por mês/ano
    $grouped = $disponibilidades->groupBy(function ($item) {
        return \Carbon\Carbon::parse($item->data)->format('Y-m');
    });

    $months = [];

    foreach ($grouped as $ym => $items) {
        $date = \Carbon\Carbon::createFromFormat('Y-m', $ym);
        $days = $items->map(fn($d) => (int)\Carbon\Carbon::parse($d->data)->day)->unique()->values()->toArray();

        $months[] = [
            'id' => (int)$date->month,
            'year' => (int)$date->year,
            'name' => $date->translatedFormat('F Y'), // Ex: Outubro 2025
            'days' => $days,
        ];
    }

    return view('agendas.publica', compact('agenda', 'months'));
}

public function getHorarios(Request $request, $slug)
{
    $agenda = \App\Models\Agenda::where('slug', $slug)->firstOrFail();

    $date = $request->query('date');
    if (!$date) {
        return response()->json(['error' => 'Data não informada'], 400);
    }

    $disponibilidades = \App\Models\Disponibilidade::where('agenda_id', $agenda->id)
        ->whereDate('data', $date)
        ->where('ocupado', false)
        ->orderBy('inicio')
        ->get(['id', 'inicio', 'fim']);

    $horarios = $disponibilidades->map(function ($disp) {
        return [
            'id'   => $disp->id,
            'time' => \Carbon\Carbon::parse($disp->inicio)->format('H:i'),
        ];
    })->values();

    return response()->json(['horarios' => $horarios]);
}

   

    public function showDisponibilidades(Request $request, $slug)
    {
        $agenda = Agenda::where('slug', $slug)->firstOrFail();

        // Obter APENAS as disponibilidades futuras e NÃO ocupadas
        $disponibilidades = Disponibilidade::where('agenda_id', $agenda->id)
                                            ->where('ocupado', false) // Filtrar apenas os horários livres
                                            ->where('data', '>=', Carbon::today())
                                            ->orderBy('data')
                                            ->orderBy('inicio')
                                            ->get();

        $processedData = [];
        foreach ($disponibilidades as $disp) {
            $date = Carbon::parse($disp->data);
            $year = $date->year;
            $month = $date->month - 1; // Mês baseado em 0 para JS
            $day = $date->day;
            $time = Carbon::parse($disp->inicio)->format('H:i'); // Horário no formato HH:mm

            if (!isset($processedData[$year])) {
                $processedData[$year] = [];
            }
            if (!isset($processedData[$year][$month])) {
                $processedData[$year][$month] = [
                    'id' => $month, // Mês baseado em 0 para JS
                    'name' => $date->translatedFormat('F Y'), // Ex: "Outubro 2025"
                    'year' => $year, // *** AQUI ESTÁ A MUDANÇA: Adicionando o ano ***
                    'days' => [],
                ];
            }
            // Adicionamos o ID da disponibilidade e o tempo.
            // Não há limite_por_horario ou current_bookings aqui,
            // pois cada Disponibilidade representa 1 slot para 1 pessoa.
            $processedData[$year][$month]['days'][$day][] = [
                'id' => $disp->id,
                'time' => $time,
            ];
        }

        $finalMonths = [];
        foreach ($processedData as $year => $monthsOfYear) {
            foreach ($monthsOfYear as $monthData) {
                $daysArray = [];
                foreach ($monthData['days'] as $dayNum => $times) {
                    $daysArray[] = ['number' => $dayNum, 'times' => $times];
                }
                usort($daysArray, function($a, $b) {
                    return $a['number'] - $b['number'];
                });

                $monthData['days'] = $daysArray;
                $finalMonths[] = $monthData;
            }
        }

        return response()->json([
            'agenda' => $agenda,
            'disponibilidades' => $finalMonths,
        ]);
    }

    public function storeAgendamento(Request $request, $slug)
        {
            $agenda = Agenda::where('slug', $slug)->firstOrFail();

            $request->validate([
                'disponibilidade_id' => 'required|exists:disponibilidades,id',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'message' => 'nullable|string|max:1000',
            ]);

            $disponibilidade = \App\Models\Disponibilidade::findOrFail($request->disponibilidade_id);

            if ($disponibilidade->ocupado) {
                return response()->json(['message' => 'Este horário já foi agendado.'], 400);
            }

            $disponibilidade->update([
                'ocupado' => true,
                'nome' => $request->name,
                'telefone' => $request->phone,
                'observacoes' => $request->message,
            ]);

            return response()->json([
                'message' => 'Agendamento realizado com sucesso!',
            ], 201);
        }

}