<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\WhatsappCloudCustomField;
use App\Support\CustomFieldScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClienteCustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $cliente = $request->user('client');

        $fields = WhatsappCloudCustomField::query()
            ->where('user_id', $cliente->user_id)
            ->where('cliente_id', $cliente->id)
            ->orderBy('name')
            ->get();

        return view('cliente.campos-personalizados.index', [
            'fields' => $fields,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = $request->user('client');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:120'],
            'sample_value' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $normalizedName = CustomFieldScope::normalizeFieldName((string) $data['name']);
        if (CustomFieldScope::hasLegacyConflict($cliente->user_id, $normalizedName)) {
            return redirect()
                ->route('cliente.campos-personalizados.index')
                ->withErrors(['name' => CustomFieldScope::legacyConflictMessage()])
                ->withInput();
        }

        $uniqueName = CustomFieldScope::resolveUniqueFieldName(
            $cliente->user_id,
            $cliente->id,
            $normalizedName,
            null
        );

        WhatsappCloudCustomField::create([
            'user_id' => $cliente->user_id,
            'cliente_id' => $cliente->id,
            'name' => $uniqueName,
            'label' => $this->nullableTrim($data['label'] ?? null),
            'sample_value' => $this->nullableTrim($data['sample_value'] ?? null),
            'description' => $this->nullableTrim($data['description'] ?? null),
        ]);

        return redirect()
            ->route('cliente.campos-personalizados.index')
            ->with('success', "Campo personalizado salvo como {$uniqueName}.");
    }

    public function update(Request $request, WhatsappCloudCustomField $campoPersonalizado): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureOwnership($campoPersonalizado, (int) $cliente->user_id, (int) $cliente->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:120'],
            'sample_value' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $normalizedName = CustomFieldScope::normalizeFieldName((string) $data['name']);
        if (CustomFieldScope::hasLegacyConflict($cliente->user_id, $normalizedName, $campoPersonalizado->id)) {
            return redirect()
                ->route('cliente.campos-personalizados.index')
                ->withErrors(['name' => CustomFieldScope::legacyConflictMessage()])
                ->withInput();
        }

        $uniqueName = CustomFieldScope::resolveUniqueFieldName(
            $cliente->user_id,
            $cliente->id,
            $normalizedName,
            $campoPersonalizado->id
        );

        $campoPersonalizado->update([
            'name' => $uniqueName,
            'label' => $this->nullableTrim($data['label'] ?? null),
            'sample_value' => $this->nullableTrim($data['sample_value'] ?? null),
            'description' => $this->nullableTrim($data['description'] ?? null),
        ]);

        return redirect()
            ->route('cliente.campos-personalizados.index')
            ->with('success', "Campo personalizado atualizado para {$uniqueName}.");
    }

    public function destroy(Request $request, WhatsappCloudCustomField $campoPersonalizado): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureOwnership($campoPersonalizado, (int) $cliente->user_id, (int) $cliente->id);

        $campoPersonalizado->delete();

        return redirect()
            ->route('cliente.campos-personalizados.index')
            ->with('success', 'Campo personalizado removido com sucesso.');
    }

    private function ensureOwnership(WhatsappCloudCustomField $field, int $userId, int $clienteId): void
    {
        if ((int) $field->user_id !== $userId || (int) $field->cliente_id !== $clienteId) {
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
