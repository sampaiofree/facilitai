<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteCrmPipeline;
use App\Models\ClienteCrmPipelineColumn;
use App\Models\ClienteLead;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClienteCrmController extends Controller
{
    private const LEADS_PER_COLUMN = 10;

    public function index(): View
    {
        $cliente = $this->currentCliente();
        $pipelines = $this->pipelines($cliente);

        return view('cliente.crm.index', [
            'cliente' => $cliente,
            'pipelines' => $pipelines,
        ]);
    }

    public function show(Request $request, ClienteCrmPipeline $crmPipeline): View
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);

        $searchValue = trim((string) $request->input('q', ''));
        $searchTerm = $this->resolveSearchTerm($searchValue);
        $columns = $this->pipelineColumns($crmPipeline);
        $availableTags = $this->availableTags($cliente);
        $board = $columns
            ->values()
            ->map(function (ClienteCrmPipelineColumn $column, int $index) use ($cliente, $columns, $searchTerm): array {
                $viewData = $this->buildColumnViewData($cliente, $columns, $column, $searchTerm);
                $viewData['can_move_left'] = $index > 0;
                $viewData['can_move_right'] = $index < ($columns->count() - 1);

                return $viewData;
            })
            ->all();

        return view('cliente.crm.show', [
            'cliente' => $cliente,
            'crmPipeline' => $crmPipeline,
            'board' => $board,
            'columns' => $columns,
            'availableTags' => $availableTags,
            'searchValue' => $searchValue,
            'searchTerm' => $searchTerm,
            'leadsPerColumn' => self::LEADS_PER_COLUMN,
        ]);
    }

    public function storePipeline(Request $request): RedirectResponse|JsonResponse
    {
        $cliente = $this->currentCliente();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        $position = (int) ClienteCrmPipeline::query()
            ->where('cliente_id', $cliente->id)
            ->max('position') + 1;

        $pipeline = ClienteCrmPipeline::query()->create([
            'cliente_id' => $cliente->id,
            'name' => trim((string) $data['name']),
            'position' => max(1, $position),
        ]);

        if ($this->expectsJson($request)) {
            return response()->json([
                'message' => 'Pipeline criada com sucesso.',
                'pipeline_id' => (int) $pipeline->id,
            ], 201);
        }

        return redirect()
            ->route('cliente.crm.index')
            ->with('success', 'Pipeline criada com sucesso.');
    }

    public function updatePipeline(Request $request, ClienteCrmPipeline $crmPipeline): RedirectResponse|JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        $crmPipeline->update([
            'name' => trim((string) $data['name']),
        ]);

        if ($this->expectsJson($request)) {
            return response()->json([
                'message' => 'Pipeline atualizada com sucesso.',
            ]);
        }

        return redirect()
            ->route('cliente.crm.index')
            ->with('success', 'Pipeline atualizada com sucesso.');
    }

    public function destroyPipeline(Request $request, ClienteCrmPipeline $crmPipeline): RedirectResponse|JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);

        DB::transaction(function () use ($crmPipeline, $cliente): void {
            $crmPipeline->delete();
            $this->normalizePipelinePositions($cliente->id);
        });

        if ($this->expectsJson($request)) {
            return response()->json([
                'message' => 'Pipeline removida com sucesso.',
            ]);
        }

        return redirect()
            ->route('cliente.crm.index')
            ->with('success', 'Pipeline removida com sucesso.');
    }

    public function reorderPipelines(Request $request): JsonResponse
    {
        $cliente = $this->currentCliente();
        $data = $request->validate([
            'pipeline_ids' => ['required', 'array', 'min:1'],
            'pipeline_ids.*' => ['integer'],
        ]);

        $pipelineIds = array_values(array_map('intval', (array) $data['pipeline_ids']));
        $ownedIds = $this->pipelines($cliente)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sortedOwnedIds = $ownedIds;
        sort($pipelineIds);
        sort($sortedOwnedIds);

        if ($pipelineIds !== $sortedOwnedIds) {
            throw ValidationException::withMessages([
                'pipeline_ids' => ['A ordem enviada é inválida para este CRM.'],
            ]);
        }

        $orderedIds = array_values(array_map('intval', (array) $data['pipeline_ids']));

        DB::transaction(function () use ($orderedIds, $cliente): void {
            foreach ($orderedIds as $index => $pipelineId) {
                ClienteCrmPipeline::query()
                    ->where('cliente_id', $cliente->id)
                    ->whereKey($pipelineId)
                    ->update([
                        'position' => $index + 1,
                    ]);
            }
        });

        return response()->json([
            'message' => 'Ordem das pipelines atualizada com sucesso.',
        ]);
    }

    public function storeColumn(Request $request, ClienteCrmPipeline $crmPipeline): RedirectResponse|JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);

        $data = $request->validate([
            'tag_id' => ['required', 'integer'],
        ]);

        $tag = Tag::query()
            ->where('id', (int) $data['tag_id'])
            ->where('cliente_id', $cliente->id)
            ->where('user_id', $cliente->user_id)
            ->first();

        if (!$tag) {
            throw ValidationException::withMessages([
                'tag_id' => ['Selecione uma tag válida do cliente.'],
            ]);
        }

        $alreadyExists = ClienteCrmPipelineColumn::query()
            ->where('tag_id', $tag->id)
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'tag_id' => ['Esta tag já está em uso em outra pipeline do CRM.'],
            ]);
        }

        $position = (int) ClienteCrmPipelineColumn::query()
            ->where('pipeline_id', $crmPipeline->id)
            ->max('position') + 1;

        $column = ClienteCrmPipelineColumn::query()->create([
            'pipeline_id' => $crmPipeline->id,
            'tag_id' => $tag->id,
            'position' => max(1, $position),
        ]);

        if ($this->expectsJson($request)) {
            return response()->json([
                'message' => 'Coluna criada com sucesso.',
                'column_id' => (int) $column->id,
            ], 201);
        }

        return redirect()
            ->route('cliente.crm.show', $crmPipeline)
            ->with('success', 'Coluna criada com sucesso.');
    }

    public function destroyColumn(Request $request, ClienteCrmPipeline $crmPipeline, ClienteCrmPipelineColumn $crmColumn): RedirectResponse|JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);
        $this->ensureColumnBelongsToPipeline($crmColumn, $crmPipeline);

        DB::transaction(function () use ($crmColumn, $crmPipeline): void {
            $crmColumn->delete();
            $this->normalizeColumnPositions($crmPipeline->id);
        });

        if ($this->expectsJson($request)) {
            return response()->json([
                'message' => 'Coluna removida com sucesso.',
            ]);
        }

        return redirect()
            ->route('cliente.crm.show', $crmPipeline)
            ->with('success', 'Coluna removida com sucesso.');
    }

    public function reorderColumns(Request $request, ClienteCrmPipeline $crmPipeline): JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);

        $data = $request->validate([
            'column_ids' => ['required', 'array', 'min:1'],
            'column_ids.*' => ['integer'],
        ]);

        $columnIds = array_values(array_map('intval', (array) $data['column_ids']));
        $ownedIds = $this->pipelineColumns($crmPipeline)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sortedOwnedIds = $ownedIds;
        sort($columnIds);
        sort($sortedOwnedIds);

        if ($columnIds !== $sortedOwnedIds) {
            throw ValidationException::withMessages([
                'column_ids' => ['A ordem enviada é inválida para esta pipeline.'],
            ]);
        }

        $orderedIds = array_values(array_map('intval', (array) $data['column_ids']));

        $this->persistColumnOrder($crmPipeline, $columnIds, $orderedIds);

        return response()->json([
            'message' => 'Ordem atualizada com sucesso.',
        ]);
    }

    public function moveColumn(Request $request, ClienteCrmPipeline $crmPipeline, ClienteCrmPipelineColumn $crmColumn): RedirectResponse|JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);
        $this->ensureColumnBelongsToPipeline($crmColumn, $crmPipeline);

        $data = $request->validate([
            'direction' => ['required', 'string', Rule::in(['left', 'right'])],
            'q' => ['nullable', 'string', 'max:191'],
        ]);

        $columns = $this->pipelineColumns($crmPipeline)->values();
        $orderedIds = $columns->pluck('id')->map(fn ($id) => (int) $id)->all();
        $currentIndex = $columns->search(fn (ClienteCrmPipelineColumn $column) => (int) $column->id === (int) $crmColumn->id);

        abort_if($currentIndex === false, 404);

        $targetIndex = $data['direction'] === 'left'
            ? max(0, $currentIndex - 1)
            : min($columns->count() - 1, $currentIndex + 1);

        if ($targetIndex === $currentIndex) {
            $message = 'A coluna já está nessa extremidade.';

            if ($this->expectsJson($request)) {
                return response()->json([
                    'message' => $message,
                    'column_id' => (int) $crmColumn->id,
                    'position' => $currentIndex + 1,
                ]);
            }

            return $this->redirectToPipelineBoard($crmPipeline, $request, $message);
        }

        [$orderedIds[$currentIndex], $orderedIds[$targetIndex]] = [$orderedIds[$targetIndex], $orderedIds[$currentIndex]];

        $this->persistColumnOrder($crmPipeline, $columns->pluck('id')->map(fn ($id) => (int) $id)->all(), $orderedIds);

        $message = $data['direction'] === 'left'
            ? 'Coluna movida para a esquerda.'
            : 'Coluna movida para a direita.';

        if ($this->expectsJson($request)) {
            return response()->json([
                'message' => $message,
                'column_id' => (int) $crmColumn->id,
                'position' => $targetIndex + 1,
                'column_ids' => $orderedIds,
            ]);
        }

        return $this->redirectToPipelineBoard($crmPipeline, $request, $message);
    }

    public function columnLeads(Request $request, ClienteCrmPipeline $crmPipeline, ClienteCrmPipelineColumn $crmColumn): JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);
        $this->ensureColumnBelongsToPipeline($crmColumn, $crmPipeline);

        $data = $request->validate([
            'offset' => ['nullable', 'integer', 'min:0'],
            'q' => ['nullable', 'string', 'max:191'],
        ]);

        $offset = max(0, (int) ($data['offset'] ?? 0));
        $searchTerm = $this->resolveSearchTerm((string) ($data['q'] ?? ''));
        $columns = $this->pipelineColumns($crmPipeline);

        $query = $this->visibleLeadsQuery($cliente, $columns, $crmColumn, $searchTerm);
        $total = (clone $query)->count();
        $items = (clone $query)
            ->offset($offset)
            ->limit(self::LEADS_PER_COLUMN)
            ->get();

        $leads = $this->mapLeadCards($items);
        $nextOffset = $offset + count($leads);

        return response()->json([
            'html' => view('cliente.crm._lead_cards', [
                'leads' => $leads,
                'crmPipeline' => $crmPipeline,
            ])->render(),
            'has_more' => $nextOffset < $total,
            'next_offset' => $nextOffset,
            'count' => $total,
            'lead_ids' => array_values(array_map(fn (array $lead) => (int) $lead['id'], $leads)),
        ]);
    }

    public function searchLeads(Request $request, ClienteCrmPipeline $crmPipeline, ClienteCrmPipelineColumn $crmColumn): JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);
        $this->ensureColumnBelongsToPipeline($crmColumn, $crmPipeline);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:40'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $offset = max(0, (int) ($data['offset'] ?? 0));
        $searchReady = $this->leadSearchIsReady($name, $phone);

        if (!$searchReady) {
            return response()->json([
                'html' => '',
                'has_more' => false,
                'next_offset' => 0,
                'count' => 0,
                'lead_ids' => [],
                'search_ready' => false,
            ]);
        }

        $columns = $this->pipelineColumns($crmPipeline);
        $query = ClienteLead::query()
            ->with('tags')
            ->where('cliente_id', $cliente->id);

        $this->applyLeadSearchFilters($query, $name, $phone);

        $total = (clone $query)->count();
        $items = (clone $query)
            ->orderByRaw('LOWER(COALESCE(name, \'\'))')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit(self::LEADS_PER_COLUMN)
            ->get();

        $results = collect($items)
            ->map(fn (ClienteLead $lead) => $this->mapLeadSearchResult($lead, $columns, $crmColumn))
            ->values()
            ->all();

        $nextOffset = $offset + count($results);

        return response()->json([
            'html' => view('cliente.crm._search_results', ['results' => $results])->render(),
            'has_more' => $nextOffset < $total,
            'next_offset' => $nextOffset,
            'count' => $total,
            'lead_ids' => array_values(array_map(fn (array $lead) => (int) $lead['id'], $results)),
            'search_ready' => true,
        ]);
    }

    public function addLeadToColumn(Request $request, ClienteCrmPipeline $crmPipeline, ClienteCrmPipelineColumn $crmColumn): JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);
        $this->ensureColumnBelongsToPipeline($crmColumn, $crmPipeline);

        $data = $request->validate([
            'lead_id' => ['required', 'integer'],
        ]);

        /** @var ClienteLead|null $clienteLead */
        $clienteLead = ClienteLead::query()
            ->with('tags')
            ->whereKey((int) $data['lead_id'])
            ->first();

        if (!$clienteLead) {
            throw ValidationException::withMessages([
                'lead_id' => ['Selecione um lead válido.'],
            ]);
        }

        $this->ensureLeadBelongsToCliente($clienteLead, $cliente);

        $columns = $this->pipelineColumns($crmPipeline);
        $previousColumn = $this->resolveVisibleColumnForLead($clienteLead, $columns);
        $newTagIds = $this->assignLeadToColumn($clienteLead, $columns, $crmColumn);
        $clienteLead->load('tags');

        return response()->json([
            'message' => 'Lead adicionado à pipeline com sucesso.',
            'lead_id' => (int) $clienteLead->id,
            'column_id' => (int) $crmColumn->id,
            'previous_column_id' => $previousColumn?->id ? (int) $previousColumn->id : null,
            'tag_ids' => $newTagIds,
            'lead' => $this->mapLeadCard($clienteLead),
        ]);
    }

    public function moveLead(Request $request, ClienteCrmPipeline $crmPipeline, ClienteLead $clienteLead): JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);
        $this->ensureLeadBelongsToCliente($clienteLead, $cliente);

        $data = $request->validate([
            'target_column_id' => ['required', 'integer'],
        ]);

        $columns = $this->pipelineColumns($crmPipeline);
        /** @var ClienteCrmPipelineColumn|null $targetColumn */
        $targetColumn = $columns->firstWhere('id', (int) $data['target_column_id']);

        if (!$targetColumn) {
            throw ValidationException::withMessages([
                'target_column_id' => ['Selecione uma coluna válida da pipeline.'],
            ]);
        }

        $clienteLead->loadMissing('tags');
        $previousColumn = $this->resolveVisibleColumnForLead($clienteLead, $columns);
        $newTagIds = $this->assignLeadToColumn($clienteLead, $columns, $targetColumn);
        $clienteLead->load('tags');

        return response()->json([
            'message' => 'Lead movido com sucesso.',
            'column_id' => (int) $targetColumn->id,
            'previous_column_id' => $previousColumn?->id ? (int) $previousColumn->id : null,
            'tag_ids' => $newTagIds,
            'lead' => $this->mapLeadCard($clienteLead),
        ]);
    }

    public function removeLeadFromPipeline(ClienteCrmPipeline $crmPipeline, ClienteLead $clienteLead): JsonResponse
    {
        $cliente = $this->currentCliente();
        $this->ensurePipelineBelongsToCliente($crmPipeline, $cliente);
        $this->ensureLeadBelongsToCliente($clienteLead, $cliente);

        $columns = $this->pipelineColumns($crmPipeline);
        $clienteLead->loadMissing('tags');
        $previousColumn = $this->resolveVisibleColumnForLead($clienteLead, $columns);
        $newTagIds = $this->removeLeadFromPipelineTags($clienteLead, $columns);
        $clienteLead->load('tags');

        return response()->json([
            'message' => 'Lead removido da pipeline com sucesso.',
            'lead_id' => (int) $clienteLead->id,
            'previous_column_id' => $previousColumn?->id ? (int) $previousColumn->id : null,
            'tag_ids' => $newTagIds,
        ]);
    }

    private function currentCliente(): Cliente
    {
        /** @var Cliente $cliente */
        $cliente = auth('client')->user();

        return $cliente;
    }

    private function pipelines(Cliente $cliente): Collection
    {
        return ClienteCrmPipeline::query()
            ->withCount('columns')
            ->where('cliente_id', $cliente->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    private function pipelineColumns(ClienteCrmPipeline $crmPipeline): Collection
    {
        return ClienteCrmPipelineColumn::query()
            ->with('tag')
            ->where('pipeline_id', $crmPipeline->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    private function availableTags(Cliente $cliente): Collection
    {
        return Tag::query()
            ->where('cliente_id', $cliente->id)
            ->where('user_id', $cliente->user_id)
            ->whereDoesntHave('crmPipelineColumn')
            ->orderBy('name')
            ->get();
    }

    private function buildColumnViewData(Cliente $cliente, Collection $columns, ClienteCrmPipelineColumn $column, string $searchTerm): array
    {
        $query = $this->visibleLeadsQuery($cliente, $columns, $column, $searchTerm);
        $count = (clone $query)->count();
        $items = (clone $query)
            ->limit(self::LEADS_PER_COLUMN)
            ->get();
        $leads = $this->mapLeadCards($items);

        return [
            'id' => (int) $column->id,
            'position' => (int) $column->position,
            'tag_id' => (int) $column->tag_id,
            'tag_name' => trim((string) ($column->tag?->name ?? 'Sem tag')),
            'tag_color' => trim((string) ($column->tag?->color ?? '')),
            'count' => $count,
            'has_more' => $count > count($leads),
            'next_offset' => count($leads),
            'leads' => $leads,
        ];
    }

    private function visibleLeadsQuery(Cliente $cliente, Collection $columns, ClienteCrmPipelineColumn $column, string $searchTerm): Builder
    {
        $rightTagIds = $columns
            ->filter(fn (ClienteCrmPipelineColumn $candidate) => (int) $candidate->position > (int) $column->position)
            ->pluck('tag_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = ClienteLead::query()
            ->with('tags')
            ->where('cliente_id', $cliente->id)
            ->whereHas('tags', fn (Builder $builder) => $builder->where('tags.id', $column->tag_id));

        if ($rightTagIds !== []) {
            $query->whereDoesntHave('tags', fn (Builder $builder) => $builder->whereIn('tags.id', $rightTagIds));
        }

        $this->applySearchTerm($query, $searchTerm);

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function applySearchTerm(Builder $query, string $searchTerm): void
    {
        if ($searchTerm === '' || mb_strlen($searchTerm) < 3) {
            return;
        }

        $normalizedTerm = mb_strtolower(Str::ascii($searchTerm));
        $termLower = mb_strtolower($searchTerm);
        $digits = preg_replace('/\D/', '', $searchTerm) ?? '';
        $normalizedNameExpr = $this->normalizedColumnSql('name');

        $query->where(function (Builder $subQuery) use ($termLower, $normalizedTerm, $digits, $normalizedNameExpr): void {
            $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$termLower}%"])
                ->orWhereRaw("{$normalizedNameExpr} LIKE ?", ["%{$normalizedTerm}%"]);

            if ($digits !== '') {
                $subQuery->orWhere('phone', 'like', "%{$digits}%");
            }
        });
    }

    private function applyLeadSearchFilters(Builder $query, string $name, string $phone): void
    {
        $normalizedName = trim($name);
        $normalizedPhone = preg_replace('/\D/', '', $phone) ?? '';

        if ($normalizedName !== '' && mb_strlen($normalizedName) >= 3) {
            $termLower = mb_strtolower($normalizedName);
            $asciiTerm = mb_strtolower(Str::ascii($normalizedName));
            $normalizedNameExpr = $this->normalizedColumnSql('name');

            $query->where(function (Builder $subQuery) use ($termLower, $asciiTerm, $normalizedNameExpr): void {
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$termLower}%"])
                    ->orWhereRaw("{$normalizedNameExpr} LIKE ?", ["%{$asciiTerm}%"]);
            });
        }

        if ($normalizedPhone !== '' && strlen($normalizedPhone) >= 3) {
            $query->where('phone', 'like', "%{$normalizedPhone}%");
        }
    }

    private function normalizedColumnSql(string $column): string
    {
        $expression = "LOWER({$column})";
        $replacements = [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ];

        foreach ($replacements as $from => $to) {
            $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
    }

    private function resolveSearchTerm(string $value): string
    {
        $term = trim($value);

        return mb_strlen($term) >= 3 ? $term : '';
    }

    private function leadSearchIsReady(string $name, string $phone): bool
    {
        if (mb_strlen(trim($name)) >= 3) {
            return true;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return strlen($digits) >= 3;
    }

    private function mapLeadCards(iterable $leads): array
    {
        return collect($leads)
            ->map(fn (ClienteLead $lead) => $this->mapLeadCard($lead))
            ->values()
            ->all();
    }

    private function mapLeadCard(ClienteLead $lead): array
    {
        return [
            'id' => (int) $lead->id,
            'phone' => $lead->phone ?: '-',
            'phone_raw' => $lead->phone,
            'name' => $lead->name ?: 'Sem nome',
            'name_raw' => $lead->name,
            'info' => $lead->info ?: 'Sem informações adicionais.',
            'info_raw' => $lead->info,
            'created_at' => $lead->created_at?->format('d/m/Y H:i') ?? '-',
            'bot_enabled' => (bool) $lead->bot_enabled,
            'tags' => $lead->tags
                ->map(fn (Tag $tag) => [
                    'id' => (int) $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])
                ->values()
                ->all(),
        ];
    }

    private function mapLeadSearchResult(ClienteLead $lead, Collection $columns, ClienteCrmPipelineColumn $targetColumn): array
    {
        $card = $this->mapLeadCard($lead);
        $visibleColumn = $this->resolveVisibleColumnForLead($lead, $columns);

        return array_merge($card, [
            'current_column_id' => $visibleColumn?->id ? (int) $visibleColumn->id : null,
            'current_column_name' => $visibleColumn?->tag?->name ?: null,
            'same_column' => (int) ($visibleColumn?->id ?? 0) === (int) $targetColumn->id,
        ]);
    }

    private function resolveVisibleColumnForLead(ClienteLead $lead, Collection $columns): ?ClienteCrmPipelineColumn
    {
        $tagIds = $lead->tags->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($tagIds === []) {
            return null;
        }

        /** @var ClienteCrmPipelineColumn|null $column */
        $column = $columns
            ->filter(fn (ClienteCrmPipelineColumn $candidate) => in_array((int) $candidate->tag_id, $tagIds, true))
            ->sortByDesc('position')
            ->first();

        return $column;
    }

    private function assignLeadToColumn(ClienteLead $clienteLead, Collection $columns, ClienteCrmPipelineColumn $targetColumn): array
    {
        $pipelineTagIds = $columns->pluck('tag_id')->map(fn ($id) => (int) $id)->all();
        $currentTagIds = $clienteLead->tags->pluck('id')->map(fn ($id) => (int) $id)->all();
        $preservedNonPipelineTags = array_values(array_diff($currentTagIds, $pipelineTagIds));
        $newTagIds = array_values(array_unique(array_merge(
            $preservedNonPipelineTags,
            [(int) $targetColumn->tag_id],
        )));

        $clienteLead->tags()->sync($newTagIds);

        return $newTagIds;
    }

    private function removeLeadFromPipelineTags(ClienteLead $clienteLead, Collection $columns): array
    {
        $pipelineTagIds = $columns->pluck('tag_id')->map(fn ($id) => (int) $id)->all();
        $currentTagIds = $clienteLead->tags->pluck('id')->map(fn ($id) => (int) $id)->all();
        $newTagIds = array_values(array_diff($currentTagIds, $pipelineTagIds));

        $clienteLead->tags()->sync($newTagIds);

        return $newTagIds;
    }

    private function normalizePipelinePositions(int $clienteId): void
    {
        ClienteCrmPipeline::query()
            ->where('cliente_id', $clienteId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (ClienteCrmPipeline $pipeline, int $index): void {
                $expectedPosition = $index + 1;
                if ((int) $pipeline->position !== $expectedPosition) {
                    $pipeline->update([
                        'position' => $expectedPosition,
                    ]);
                }
            });
    }

    private function normalizeColumnPositions(int $pipelineId): void
    {
        ClienteCrmPipelineColumn::query()
            ->where('pipeline_id', $pipelineId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (ClienteCrmPipelineColumn $column, int $index): void {
                $expectedPosition = $index + 1;
                if ((int) $column->position !== $expectedPosition) {
                    $column->update([
                        'position' => $expectedPosition,
                    ]);
                }
            });
    }

    private function ensurePipelineBelongsToCliente(ClienteCrmPipeline $crmPipeline, Cliente $cliente): void
    {
        abort_unless((int) $crmPipeline->cliente_id === (int) $cliente->id, 403);
    }

    private function ensureColumnBelongsToPipeline(ClienteCrmPipelineColumn $crmColumn, ClienteCrmPipeline $crmPipeline): void
    {
        abort_unless((int) $crmColumn->pipeline_id === (int) $crmPipeline->id, 403);
    }

    private function ensureLeadBelongsToCliente(ClienteLead $clienteLead, Cliente $cliente): void
    {
        abort_unless((int) $clienteLead->cliente_id === (int) $cliente->id, 403);
    }

    private function persistColumnOrder(ClienteCrmPipeline $crmPipeline, array $currentIds, array $orderedIds): void
    {
        DB::transaction(function () use ($crmPipeline, $currentIds, $orderedIds): void {
            $temporaryOffset = count($currentIds) + 1000;

            foreach ($currentIds as $index => $columnId) {
                ClienteCrmPipelineColumn::query()
                    ->where('pipeline_id', $crmPipeline->id)
                    ->whereKey($columnId)
                    ->update([
                        'position' => $temporaryOffset + $index,
                    ]);
            }

            foreach ($orderedIds as $index => $columnId) {
                ClienteCrmPipelineColumn::query()
                    ->where('pipeline_id', $crmPipeline->id)
                    ->whereKey($columnId)
                    ->update([
                        'position' => $index + 1,
                    ]);
            }
        });
    }

    private function redirectToPipelineBoard(ClienteCrmPipeline $crmPipeline, Request $request, string $message): RedirectResponse
    {
        $params = ['crmPipeline' => $crmPipeline];
        $search = trim((string) $request->input('q', ''));

        if ($search !== '') {
            $params['q'] = $search;
        }

        return redirect()
            ->route('cliente.crm.show', $params)
            ->with('success', $message);
    }

    private function expectsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
