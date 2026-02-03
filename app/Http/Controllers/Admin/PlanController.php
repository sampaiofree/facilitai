<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::orderByDesc('created_at')->get();
        return view('admin.plano.index', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePlan($request);

        Plan::create($data);

        return redirect()->route('adm.plano.index')->with('success', 'Plano criado com sucesso.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validatePlan($request);

        $plan->update($data);

        return redirect()->route('adm.plano.index')->with('success', 'Plano atualizado com sucesso.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('adm.plano.index')->with('success', 'Plano removido com sucesso.');
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_cents' => ['required', 'numeric', 'min:0'],
            'max_conexoes' => ['required', 'integer', 'min:0'],
            'storage_limit_mb' => ['required', 'integer', 'min:0'],
        ]);
    }
}
