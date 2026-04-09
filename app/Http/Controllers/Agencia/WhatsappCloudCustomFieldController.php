<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\WhatsappCloudCustomField;
use App\Support\CustomFieldScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WhatsappCloudCustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;
        $filterData = $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists('clientes', 'id')
                    ->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
        ]);
        $clienteFilter = isset($filterData['cliente_id']) ? (int) $filterData['cliente_id'] : null;

        $fieldsQuery = WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->with('cliente:id,nome')
            ->orderByRaw('CASE WHEN cliente_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name');

        if ($clienteFilter) {
            $fieldsQuery->where(function ($query) use ($clienteFilter) {
                $query->where('cliente_id', $clienteFilter)
                    ->orWhereNull('cliente_id');
            });
        }

        $fields = $fieldsQuery->get();

        $clientes = Cliente::query()
            ->where('user_id', $userId)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('agencia.campos-personalizados.index', [
            'fields' => $fields,
            'clientes' => $clientes,
            'clienteFilter' => $clienteFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')
                    ->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
            'label' => ['nullable', 'string', 'max:120'],
            'sample_value' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $clienteId = (int) $data['cliente_id'];
        $normalizedName = CustomFieldScope::normalizeFieldName((string) $data['name']);
        if (CustomFieldScope::hasLegacyConflict($userId, $normalizedName)) {
            return redirect()
                ->route('agencia.campos-personalizados.index')
                ->withErrors(['name' => CustomFieldScope::legacyConflictMessage()])
                ->withInput();
        }

        $uniqueName = CustomFieldScope::resolveUniqueFieldName($userId, $clienteId, $normalizedName, null);

        WhatsappCloudCustomField::create([
            'user_id' => $userId,
            'cliente_id' => $clienteId,
            'name' => $uniqueName,
            'label' => $this->nullableTrim($data['label'] ?? null),
            'sample_value' => $this->nullableTrim($data['sample_value'] ?? null),
            'description' => $this->nullableTrim($data['description'] ?? null),
        ]);

        return redirect()
            ->route('agencia.campos-personalizados.index')
            ->with('success', "Campo personalizado salvo como {$uniqueName}.");
    }

    public function update(Request $request, WhatsappCloudCustomField $campoPersonalizado): RedirectResponse
    {
        $this->ensureOwnership($campoPersonalizado, $request->user()->id);
        $userId = (int) $request->user()->id;

        if ($campoPersonalizado->cliente_id === null) {
            return redirect()
                ->route('agencia.campos-personalizados.index')
                ->with('error', 'Campos globais legados são somente leitura.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')
                    ->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
            'label' => ['nullable', 'string', 'max:120'],
            'sample_value' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $clienteId = (int) $data['cliente_id'];
        $normalizedName = CustomFieldScope::normalizeFieldName((string) $data['name']);
        if (CustomFieldScope::hasLegacyConflict($userId, $normalizedName, $campoPersonalizado->id)) {
            return redirect()
                ->route('agencia.campos-personalizados.index')
                ->withErrors(['name' => CustomFieldScope::legacyConflictMessage()])
                ->withInput();
        }

        $uniqueName = CustomFieldScope::resolveUniqueFieldName($userId, $clienteId, $normalizedName, $campoPersonalizado->id);

        $campoPersonalizado->update([
            'name' => $uniqueName,
            'cliente_id' => $clienteId,
            'label' => $this->nullableTrim($data['label'] ?? null),
            'sample_value' => $this->nullableTrim($data['sample_value'] ?? null),
            'description' => $this->nullableTrim($data['description'] ?? null),
        ]);

        return redirect()
            ->route('agencia.campos-personalizados.index')
            ->with('success', "Campo personalizado atualizado para {$uniqueName}.");
    }

    public function destroy(Request $request, WhatsappCloudCustomField $campoPersonalizado): RedirectResponse
    {
        $this->ensureOwnership($campoPersonalizado, $request->user()->id);

        if ($campoPersonalizado->cliente_id === null) {
            return redirect()
                ->route('agencia.campos-personalizados.index')
                ->with('error', 'Campos globais legados são somente leitura.');
        }

        $campoPersonalizado->delete();

        return redirect()
            ->route('agencia.campos-personalizados.index')
            ->with('success', 'Campo personalizado removido com sucesso.');
    }

    private function ensureOwnership(WhatsappCloudCustomField $field, int $userId): void
    {
        if ((int) $field->user_id !== $userId) {
            abort(403);
        }
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
