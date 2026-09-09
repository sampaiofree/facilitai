@extends($layout)

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900">Conjuntos de grupos</h2>
        <p class="text-sm text-slate-500">Organize grupos do WhatsApp por conexão Uazapi.</p>
    </div>

    @if($conjuntos->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <p class="text-sm font-semibold text-slate-700">Nenhum conjunto de grupos cadastrado.</p>
            <p class="mt-1 text-xs text-slate-500">Crie o primeiro conjunto para começar a organizar seus grupos.</p>
            <button
                type="button"
                id="openGrupoModal"
                class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >Novo conjunto</button>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <aside class="lg:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-900">Conjuntos cadastrados</p>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $conjuntos->count() }}</span>
                            <button
                                type="button"
                                id="openGrupoModal"
                                class="rounded-lg bg-blue-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-blue-700"
                            >Novo conjunto</button>
                        </div>
                    </div>

                    <div class="mt-3 max-h-[calc(100vh-16rem)] space-y-2 overflow-y-auto pr-1">
                        @foreach($conjuntos as $conjunto)
                            @php
                                $isSelected = $selectedConjunto && (int) $selectedConjunto->id === (int) $conjunto->id;
                            @endphp
                            <article class="rounded-xl border {{ $isSelected ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white' }}">
                                <a
                                    href="{{ route($routePrefix . '.index', ['conjunto_id' => $conjunto->id, 'tab' => $activeTab]) }}"
                                    class="block rounded-xl px-3 py-3"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold {{ $isSelected ? 'text-blue-800' : 'text-slate-900' }}">{{ $conjunto->name }}</p>
                                            <p class="mt-1 text-[11px] text-slate-500">{{ $conjunto->conexao?->name ?? 'Sem conexão' }}</p>
                                            @if($showClienteInfo)
                                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $conjunto->conexao?->cliente?->nome ?? 'Sem cliente' }}</p>
                                            @endif
                                        </div>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $conjunto->items_count }}</span>
                                    </div>
                                    <p class="mt-2 text-[11px] text-slate-500">Atualizado em {{ $conjunto->updated_at?->format('d/m/Y H:i') }}</p>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>
            </aside>

            <section class="lg:col-span-2">
                @if($selectedConjunto)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        @php
                            $selectedConjuntoEditPayload = json_encode([
                                'id' => $selectedConjunto->id,
                                'name' => $selectedConjunto->name,
                                'conexao_id' => $selectedConjunto->conexao_id,
                                'groups' => $selectedConjunto->items
                                    ->map(fn ($item) => [
                                        'jid' => $item->group_jid,
                                        'name' => $item->group_name,
                                    ])
                                    ->values(),
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        @endphp
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conjunto selecionado</p>
                                <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ $selectedConjunto->name }}</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">{{ $selectedConjunto->items_count }} grupo(s)</span>
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-600"
                                    data-action="edit-conjunto"
                                    data-payload="{{ $selectedConjuntoEditPayload }}"
                                >Editar</button>
                                <form method="POST" action="{{ route($routePrefix . '.destroy', $selectedConjunto) }}" onsubmit="return confirm('Deseja excluir este conjunto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-rose-500 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-rose-600"
                                    >Excluir</button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                            <div><span class="font-semibold text-slate-800">Conexão:</span> {{ $selectedConjunto->conexao?->name ?? '—' }}</div>
                            @if($showClienteInfo)
                                <div><span class="font-semibold text-slate-800">Cliente:</span> {{ $selectedConjunto->conexao?->cliente?->nome ?? '—' }}</div>
                            @endif
                            <div><span class="font-semibold text-slate-800">Atualizado em:</span> {{ $selectedConjunto->updated_at?->format('d/m/Y H:i') ?? '—' }}</div>
                            <div><span class="font-semibold text-slate-800">Timezone:</span> {{ $timezone }}</div>
                        </div>

                        <div class="mt-5 border-b border-slate-200">
                            <div class="-mb-px flex items-center gap-2">
                                <a
                                    href="{{ route($routePrefix . '.index', ['conjunto_id' => $selectedConjunto->id, 'tab' => 'groups']) }}"
                                    class="rounded-t-lg border border-b-0 px-4 py-2 text-xs font-semibold transition {{ $activeTab === 'groups' ? 'border-slate-200 bg-white text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                                >Grupos</a>
                                <a
                                    href="{{ route($routePrefix . '.index', ['conjunto_id' => $selectedConjunto->id, 'tab' => 'messages']) }}"
                                    class="rounded-t-lg border border-b-0 px-4 py-2 text-xs font-semibold transition {{ $activeTab === 'messages' ? 'border-slate-200 bg-white text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                                >Mensagens</a>
                            </div>
                        </div>

                        @if($activeTab === 'groups')
                            <div class="mt-5 rounded-xl border border-slate-200">
                                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-800">Grupos do conjunto</p>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-semibold">JID</th>
                                                <th class="px-4 py-3 text-left font-semibold">Nome</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse($selectedConjunto->items as $item)
                                                <tr>
                                                    <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $item->group_jid }}</td>
                                                    <td class="px-4 py-3 text-slate-700">{{ $item->group_name ?: '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="px-4 py-6 text-center text-slate-500">Nenhum grupo vinculado a este conjunto.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="mt-5 rounded-xl border border-slate-200">
                                <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">Ações do conjunto</p>
                                        <p class="text-xs text-slate-500">Enviadas e programadas (timezone: {{ $timezone }})</p>
                                    </div>
                                    <button
                                        type="button"
                                        id="openGroupMessageModal"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                                    >Criar ação</button>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 text-left font-semibold">Ação</th>
                                                <th class="px-4 py-3 text-left font-semibold">Resumo</th>
                                                <th class="px-4 py-3 text-left font-semibold">Tipo envio</th>
                                                <th class="px-4 py-3 text-left font-semibold">Agendada</th>
                                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                                <th class="px-4 py-3 text-left font-semibold">Criada</th>
                                                <th class="px-4 py-3 text-left font-semibold">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse($mensagens as $mensagem)
                                                @php
                                                    $status = (string) $mensagem->status;
                                                    $statusLabel = match ($status) {
                                                        'pending' => 'Programada',
                                                        'queued' => 'Na fila',
                                                        'sent' => 'Enviada',
                                                        'failed' => 'Falhou',
                                                        'canceled' => 'Cancelada',
                                                        default => ucfirst($status),
                                                    };
                                                    $statusClass = match ($status) {
                                                        'pending' => 'bg-amber-100 text-amber-700',
                                                        'queued' => 'bg-blue-100 text-blue-700',
                                                        'sent' => 'bg-emerald-100 text-emerald-700',
                                                        'failed' => 'bg-rose-100 text-rose-700',
                                                        'canceled' => 'bg-slate-200 text-slate-700',
                                                        default => 'bg-slate-100 text-slate-700',
                                                    };
                                                @endphp
                                                <tr @if($showFailureDetails) data-group-message-row="{{ $mensagem->id }}" @endif>
                                                    <td class="px-4 py-3 text-slate-700">{{ $mensagem->actionTypeLabel() }}</td>
                                                    <td class="px-4 py-3">
                                                        <p class="max-w-xs truncate text-slate-800">{{ \Illuminate\Support\Str::limit($mensagem->actionSummary(), 110) }}</p>
                                                        <p class="mt-1 text-[11px] text-slate-500" @if($showFailureDetails) data-group-message-counts @endif>OK: {{ $mensagem->sent_count }} · Falhas: {{ $mensagem->failed_count }}</p>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700">{{ $mensagem->dispatch_type === 'now' ? 'Imediata' : 'Programada' }}</td>
                                                    <td class="px-4 py-3 text-slate-700">{{ $mensagem->scheduled_for_label ?? '—' }}</td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}" @if($showFailureDetails) data-group-message-status @endif>{{ $statusLabel }}</span>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700">{{ $mensagem->created_at_label ?? '—' }}</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center gap-2">
                                                            @if($showFailureDetails)
                                                                @php
                                                                    $showFailureButton = $status === 'failed' || (int) $mensagem->failed_count > 0;
                                                                    $failureButtonLabel = (int) $mensagem->failed_count > 1 ? 'Ver falhas' : 'Ver falha';
                                                                @endphp
                                                                <button
                                                                    type="button"
                                                                    class="{{ $showFailureButton ? '' : 'hidden' }} rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100"
                                                                    data-view-group-failure
                                                                    data-message-id="{{ $mensagem->id }}"
                                                                >{{ $failureButtonLabel }}</button>
                                                            @endif

                                                            <button
                                                                type="button"
                                                                class="rounded-lg bg-indigo-500 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-600"
                                                                data-action="edit-group-message"
                                                                data-payload="{{ json_encode([
                                                                    ...$mensagem->toEditorPayload(),
                                                                    'scheduled_for_input' => $mensagem->scheduled_for_input,
                                                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                                            >Editar</button>

                                                            <form method="POST" action="{{ route($routePrefix . '.mensagens.destroy', ['grupoConjunto' => $selectedConjunto, 'grupoConjuntoMensagem' => $mensagem]) }}" onsubmit="return confirm('Deseja excluir este registro de mensagem?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button
                                                                    type="submit"
                                                                    class="rounded-lg bg-rose-500 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-rose-600"
                                                                >Excluir</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhum registro de ação para este conjunto.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
                        <p class="text-sm font-semibold text-slate-700">Nenhum conjunto selecionado.</p>
                        <p class="mt-1 text-xs text-slate-500">Escolha um conjunto na coluna da esquerda para visualizar os grupos.</p>
                    </div>
                @endif
            </section>
        </div>
    @endif

    @if($selectedConjunto)
        @if($showFailureDetails)
            <div
                id="groupFailureModal"
                class="fixed inset-0 z-50 hidden items-center justify-center overflow-auto bg-black/50 px-4 py-6 backdrop-blur"
                data-status-url="{{ route('cliente.grupos.mensagens.status', $selectedConjunto) }}"
            >
                <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Detalhes da falha</h3>
                            <p id="groupFailureMeta" class="mt-1 text-xs text-slate-500"></p>
                        </div>
                        <button type="button" data-close-group-failure-modal class="text-slate-500 hover:text-slate-700">x</button>
                    </div>

                    <div id="groupFailureLoading" class="mt-5 hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">Carregando detalhes...</div>
                    <div id="groupFailureFetchError" class="mt-5 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                    <div id="groupFailureContent" class="mt-5 hidden space-y-4">
                        <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Status</p>
                                <p id="groupFailureStatus" class="mt-1 text-sm font-semibold text-slate-800">—</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Enviadas</p>
                                <p id="groupFailureSentCount" class="mt-1 text-sm font-semibold text-emerald-700">0</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Falhas</p>
                                <p id="groupFailureFailedCount" class="mt-1 text-sm font-semibold text-rose-700">0</p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-600">Motivo</p>
                            <p id="groupFailureMessage" class="mt-1 text-sm text-rose-800">—</p>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-slate-800">Grupos que falharam</p>
                            <div class="mt-2 overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Grupo</th>
                                            <th class="px-4 py-3 text-left font-semibold">Código</th>
                                            <th class="px-4 py-3 text-left font-semibold">Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="groupFailureItems" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                            <p id="groupFailureItemsEmpty" class="mt-2 hidden text-xs text-slate-500">Não há detalhes separados por grupo para esta falha.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div id="groupMessageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6 backdrop-blur">
            <div class="max-h-[95vh] w-full max-w-5xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 id="groupMessageModalTitle" class="text-lg font-semibold text-slate-900">Criar ação</h3>
                    <button type="button" data-close-group-message-modal class="text-slate-500 hover:text-slate-700">x</button>
                </div>

                <form
                    id="groupMessageForm"
                    method="POST"
                    action="{{ route($routePrefix . '.mensagens.store', $selectedConjunto) }}"
                    data-create-route-template="{{ route($routePrefix . '.mensagens.store', ['grupoConjunto' => '__CONJUNTO__']) }}"
                    data-update-route-template="{{ route($routePrefix . '.mensagens.update', ['grupoConjunto' => '__CONJUNTO__', 'grupoConjuntoMensagem' => '__MENSAGEM__']) }}"
                    data-selected-conjunto-id="{{ $selectedConjunto->id }}"
                    data-recipient-count="{{ $selectedConjunto->items_count }}"
                    class="mt-5 space-y-4"
                >
                    @csrf
                    <input type="hidden" name="_method" id="groupMessageFormMethod" value="POST">
                    <input type="hidden" name="message_form" id="groupMessageFormContext" value="create">
                    <input type="hidden" name="message_id" id="groupMessageId" value="">

                    <p class="text-xs text-slate-500">A ação será aplicada para todos os grupos vinculados ao conjunto no momento do salvamento.</p>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 md:col-span-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Escolha a ação</p>
                            <div class="mt-2 space-y-2">
                                <label data-action-card data-action-value="send_text" class="block cursor-pointer rounded-lg border border-slate-200 bg-white p-3 transition hover:border-blue-300">
                                    <input class="sr-only" type="radio" name="action_type" value="send_text" checked>
                                    <span class="text-sm font-semibold text-slate-800">Enviar texto</span>
                                </label>
                                <label data-action-card data-action-value="send_media" class="block cursor-pointer rounded-lg border border-slate-200 bg-white p-3 transition hover:border-blue-300">
                                    <input class="sr-only" type="radio" name="action_type" value="send_media">
                                    <span class="text-sm font-semibold text-slate-800">Enviar mídia</span>
                                </label>
                                <label data-action-card data-action-value="update_group_name" class="block cursor-pointer rounded-lg border border-slate-200 bg-white p-3 transition hover:border-blue-300">
                                    <input class="sr-only" type="radio" name="action_type" value="update_group_name">
                                    <span class="text-sm font-semibold text-slate-800">Trocar título</span>
                                </label>
                                <label data-action-card data-action-value="update_group_description" class="block cursor-pointer rounded-lg border border-slate-200 bg-white p-3 transition hover:border-blue-300">
                                    <input class="sr-only" type="radio" name="action_type" value="update_group_description">
                                    <span class="text-sm font-semibold text-slate-800">Trocar descrição</span>
                                </label>
                                <label data-action-card data-action-value="update_group_image" class="block cursor-pointer rounded-lg border border-slate-200 bg-white p-3 transition hover:border-blue-300">
                                    <input class="sr-only" type="radio" name="action_type" value="update_group_image">
                                    <span class="text-sm font-semibold text-slate-800">Trocar foto</span>
                                </label>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                            <div data-action-panel="send_text">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageText">Texto</label>
                                <textarea
                                    id="groupMessageText"
                                    name="text"
                                    rows="5"
                                    maxlength="2000"
                                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                ></textarea>
                            </div>

                            <div data-action-panel="send_media" class="hidden space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageMediaType">Tipo de mídia</label>
                                        <select id="groupMessageMediaType" name="media_type" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                            <option value="">Selecione</option>
                                            <option value="image">Imagem</option>
                                            <option value="video">Vídeo</option>
                                            <option value="document">Documento</option>
                                            <option value="audio">Áudio</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageMediaUrl">Link da mídia</label>
                                        <input id="groupMessageMediaUrl" name="media_url" type="url" placeholder="https://..." class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageCaption">Legenda (opcional)</label>
                                    <textarea
                                        id="groupMessageCaption"
                                        name="caption"
                                        rows="3"
                                        maxlength="1024"
                                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                    ></textarea>
                                </div>
                            </div>

                            <div data-action-panel="update_group_name" class="hidden space-y-3">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageGroupName">Novo título</label>
                                <input id="groupMessageGroupName" name="group_name" type="text" maxlength="25" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">

                                @if($showGroupNameSequence)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700" for="groupMessageGroupNameSequence">
                                            <input
                                                id="groupMessageGroupNameSequence"
                                                name="group_name_sequence"
                                                type="checkbox"
                                                value="1"
                                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            Adicionar sequência automática
                                        </label>

                                        <div id="groupMessageGroupNameSequenceOptions" class="mt-3 hidden space-y-2">
                                            <div class="max-w-xs">
                                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageGroupNameSequenceStart">Número inicial</label>
                                                <input
                                                    id="groupMessageGroupNameSequenceStart"
                                                    name="group_name_sequence_start"
                                                    type="number"
                                                    value="1"
                                                    min="1"
                                                    max="999999999"
                                                    step="1"
                                                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                                >
                                            </div>
                                            <p id="groupMessageGroupNameSequencePreview" class="text-xs font-medium text-slate-700"></p>
                                            <p id="groupMessageGroupNameSequenceLimit" class="text-[11px] text-slate-500"></p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div data-action-panel="update_group_description" class="hidden">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageGroupDescription">Nova descrição</label>
                                <textarea
                                    id="groupMessageGroupDescription"
                                    name="group_description"
                                    rows="5"
                                    maxlength="2048"
                                    class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                ></textarea>
                            </div>

                            <div data-action-panel="update_group_image" class="hidden">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageGroupImageUrl">Link da nova foto</label>
                                <input id="groupMessageGroupImageUrl" name="group_image_url" type="url" placeholder="https://..." class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </div>

                            <div id="groupMessageMentionAllWrap" class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700" for="groupMessageMentionAll">
                                    <input
                                        id="groupMessageMentionAll"
                                        name="mention_all"
                                        type="checkbox"
                                        value="1"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    Marcar todos do grupo (@all)
                                </label>
                                <p class="mt-1 text-[11px] text-slate-500">Disponível para envio de texto e mídia.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageSendType">Tipo de envio</label>
                            <select id="groupMessageSendType" name="send_type" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="now">Enviar agora</option>
                                <option value="scheduled">Programar envio</option>
                            </select>
                        </div>

                        <div id="groupMessageScheduleWrap" class="hidden">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupMessageScheduledFor">Data e hora</label>
                            <input
                                id="groupMessageScheduledFor"
                                name="scheduled_for"
                                type="datetime-local"
                                class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                            >
                            <p class="mt-1 text-[11px] text-slate-500">Timezone: {{ $timezone }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" data-close-group-message-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                        <button id="groupMessageSubmit" type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div id="grupoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6 backdrop-blur">
        <div class="max-h-[95vh] w-full max-w-5xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="grupoModalTitle" class="text-lg font-semibold text-slate-900">Novo conjunto</h3>
                <button type="button" data-close-grupo-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>

            <form id="grupoForm" method="POST" action="{{ route($routePrefix . '.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="grupo_conjunto_id" id="grupoConjuntoId" value="">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="grupoName">Nome</label>
                        <input
                            id="grupoName"
                            name="name"
                            type="text"
                            maxlength="255"
                            required
                            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="grupoConexao">Conexão</label>
                        <select id="grupoConexao" name="conexao_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione uma conexão</option>
                            @foreach($conexoes as $conexao)
                                <option value="{{ $conexao->id }}">{{ $conexao->name }}{{ $conexao->cliente ? ' — ' . $conexao->cliente->nome : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800">Grupos da conexão</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                id="loadConnectionGroups"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >Carregar grupos da conexão</button>
                        </div>
                    </div>

                    <div id="groupsLoadMessage" class="mt-3 hidden rounded-lg px-3 py-2 text-xs"></div>

                    <div class="mt-3">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="groupsSearch">Pesquisar grupos por nome</label>
                        <input
                            id="groupsSearch"
                            type="text"
                            placeholder="Digite ao menos 2 letras para buscar"
                            class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                        >
                        <div id="groupsSearchLoader" class="mt-2 hidden items-center gap-2 text-xs text-slate-500">
                            <svg class="h-4 w-4 animate-spin text-slate-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                            </svg>
                            <span>Pesquisando grupos...</span>
                        </div>
                    </div>

                    <div id="connectionGroupsList" class="mt-3 grid max-h-64 gap-2 overflow-y-auto rounded-lg border border-slate-100 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Selecione uma conexão e digite ao menos 2 letras para pesquisar grupos.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-800">Adicionar por link de convite</p>
                    <div class="mt-3">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="inviteGroupLink">Link de convite</label>
                        <div class="mt-1 grid gap-3 md:grid-cols-4">
                            <div class="md:col-span-3">
                                <input
                                    id="inviteGroupLink"
                                    type="url"
                                    placeholder="https://chat.whatsapp.com/..."
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                >
                            </div>
                            <div class="flex items-center">
                                <button
                                    type="button"
                                    id="addInviteGroup"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >Buscar e adicionar</button>
                            </div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Cole o link completo do convite e clique em buscar.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800">Grupos selecionados</p>
                        <span id="selectedGroupsCount" class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">0</span>
                    </div>

                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">JID</th>
                                    <th class="px-3 py-2 text-left font-semibold">Nome</th>
                                    <th class="px-3 py-2 text-left font-semibold">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="selectedGroupsTableBody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                    <p id="selectedGroupsEmpty" class="mt-3 text-xs text-slate-500">Nenhum grupo selecionado.</p>
                </div>

                <div id="groupsHiddenInputs"></div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" data-close-grupo-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button id="grupoFormSubmit" type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const groupJidRegex = /^[0-9]+@g\.us$/;

            const modal = document.getElementById('grupoModal');
            const openModalButton = document.getElementById('openGrupoModal');
            const closeModalButtons = document.querySelectorAll('[data-close-grupo-modal]');
            const modalTitle = document.getElementById('grupoModalTitle');
            const form = document.getElementById('grupoForm');
            const formSubmitButton = document.getElementById('grupoFormSubmit');
            const conjuntoIdInput = document.getElementById('grupoConjuntoId');
            const nameInput = document.getElementById('grupoName');
            const conexaoSelect = document.getElementById('grupoConexao');
            const loadGroupsButton = document.getElementById('loadConnectionGroups');
            const groupsSearchInput = document.getElementById('groupsSearch');
            const groupsListContainer = document.getElementById('connectionGroupsList');
            const groupsLoadMessage = document.getElementById('groupsLoadMessage');
            const groupsSearchLoader = document.getElementById('groupsSearchLoader');
            const inviteGroupLinkInput = document.getElementById('inviteGroupLink');
            const addInviteGroupButton = document.getElementById('addInviteGroup');
            const selectedGroupsCount = document.getElementById('selectedGroupsCount');
            const selectedGroupsTableBody = document.getElementById('selectedGroupsTableBody');
            const selectedGroupsEmpty = document.getElementById('selectedGroupsEmpty');
            const groupsHiddenInputs = document.getElementById('groupsHiddenInputs');

            const connectionGroupsUrlTemplate = "{{ route($routePrefix . '.conexoes.groups', ['conexao' => '__CONEXAO__']) }}";
            const connectionGroupInviteUrlTemplate = "{{ route($routePrefix . '.conexoes.group-invite', ['conexao' => '__CONEXAO__']) }}";

            const oldState = {
                id: @json(old('grupo_conjunto_id')),
                name: @json(old('name')),
                conexaoId: @json(old('conexao_id')),
                groups: @json(old('groups', [])),
            };

            const selectedGroups = new Map();
            const connectionGroups = new Map();
            const searchMinChars = 2;
            let searchDebounceTimer = null;
            let searchAbortController = null;
            let searchRequestSerial = 0;

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const normalizeGroup = (group) => {
                const jid = String(group?.jid ?? '').trim();
                if (!groupJidRegex.test(jid)) {
                    return null;
                }

                const name = String(group?.name ?? '').trim();

                return {
                    jid,
                    name: name !== '' ? name : jid,
                };
            };

            const setLoadMessage = (type, message) => {
                groupsLoadMessage.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'bg-rose-50', 'text-rose-700', 'bg-slate-100', 'text-slate-700');

                if (!message) {
                    groupsLoadMessage.classList.add('hidden');
                    groupsLoadMessage.textContent = '';
                    return;
                }

                if (type === 'success') {
                    groupsLoadMessage.classList.add('bg-emerald-50', 'text-emerald-700');
                } else if (type === 'error') {
                    groupsLoadMessage.classList.add('bg-rose-50', 'text-rose-700');
                } else {
                    groupsLoadMessage.classList.add('bg-slate-100', 'text-slate-700');
                }

                groupsLoadMessage.textContent = message;
            };

            const setSearchLoading = (isLoading) => {
                if (!groupsSearchLoader) {
                    return;
                }

                groupsSearchLoader.classList.toggle('hidden', !isLoading);
                groupsSearchLoader.classList.toggle('flex', isLoading);
            };

            const renderHiddenInputs = () => {
                groupsHiddenInputs.innerHTML = '';
                const groups = Array.from(selectedGroups.values());

                groups.forEach((group, index) => {
                    const jidInput = document.createElement('input');
                    jidInput.type = 'hidden';
                    jidInput.name = `groups[${index}][jid]`;
                    jidInput.value = group.jid;

                    const hiddenNameInput = document.createElement('input');
                    hiddenNameInput.type = 'hidden';
                    hiddenNameInput.name = `groups[${index}][name]`;
                    hiddenNameInput.value = group.name ?? '';

                    groupsHiddenInputs.appendChild(jidInput);
                    groupsHiddenInputs.appendChild(hiddenNameInput);
                });
            };

            const renderSelectedGroups = () => {
                selectedGroupsTableBody.innerHTML = '';

                const groups = Array.from(selectedGroups.values()).sort((a, b) => a.name.localeCompare(b.name));

                selectedGroupsCount.textContent = String(groups.length);
                selectedGroupsEmpty.classList.toggle('hidden', groups.length > 0);

                groups.forEach((group) => {
                    const row = document.createElement('tr');

                    const jidCell = document.createElement('td');
                    jidCell.className = 'px-3 py-2 text-slate-700';
                    jidCell.textContent = group.jid;

                    const nameCell = document.createElement('td');
                    nameCell.className = 'px-3 py-2 text-slate-700';
                    nameCell.textContent = group.name ?? group.jid;

                    const actionCell = document.createElement('td');
                    actionCell.className = 'px-3 py-2';

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'rounded-md bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-200';
                    removeButton.textContent = 'Remover';
                    removeButton.addEventListener('click', () => {
                        selectedGroups.delete(group.jid);
                        renderSelectedGroups();
                        renderConnectionGroups();
                    });

                    actionCell.appendChild(removeButton);

                    row.appendChild(jidCell);
                    row.appendChild(nameCell);
                    row.appendChild(actionCell);

                    selectedGroupsTableBody.appendChild(row);
                });

                renderHiddenInputs();
            };

            const renderConnectionGroups = (emptyMessage = null) => {
                const groups = Array.from(connectionGroups.values()).sort((a, b) => a.name.localeCompare(b.name));

                groupsListContainer.innerHTML = '';

                if (groups.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'text-xs text-slate-500';
                    empty.textContent = emptyMessage || 'Digite ao menos 2 letras para pesquisar grupos por nome.';
                    groupsListContainer.appendChild(empty);
                    return;
                }

                groups.forEach((group) => {
                    const item = document.createElement('label');
                    item.className = 'flex items-start gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 hover:border-slate-300';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500';
                    checkbox.checked = selectedGroups.has(group.jid);
                    checkbox.addEventListener('change', () => {
                        if (checkbox.checked) {
                            selectedGroups.set(group.jid, group);
                        } else {
                            selectedGroups.delete(group.jid);
                        }

                        renderSelectedGroups();
                    });

                    const textWrap = document.createElement('span');
                    textWrap.className = 'min-w-0';

                    const nameText = document.createElement('span');
                    nameText.className = 'block font-semibold text-slate-800';
                    nameText.textContent = group.name;

                    const jidText = document.createElement('span');
                    jidText.className = 'block text-slate-500';
                    jidText.textContent = group.jid;

                    textWrap.appendChild(nameText);
                    textWrap.appendChild(jidText);

                    item.appendChild(checkbox);
                    item.appendChild(textWrap);

                    groupsListContainer.appendChild(item);
                });
            };

            const addGroupToSelection = (group) => {
                const normalized = normalizeGroup(group);
                if (!normalized) {
                    return false;
                }

                selectedGroups.set(normalized.jid, normalized);
                renderSelectedGroups();
                renderConnectionGroups();
                return true;
            };

            const clearGroupsState = () => {
                selectedGroups.clear();
                connectionGroups.clear();
                groupsSearchInput.value = '';
                renderSelectedGroups();
                renderConnectionGroups('Selecione uma conexão e pesquise grupos por nome.');
            };

            const resetForm = () => {
                form.reset();
                conjuntoIdInput.value = '';
                modalTitle.textContent = 'Novo conjunto';
                formSubmitButton.textContent = 'Salvar';
                clearGroupsState();
                setLoadMessage(null, null);
            };

            const loadConnectionGroups = async () => {
                const conexaoId = String(conexaoSelect.value || '').trim();
                if (conexaoId === '') {
                    setLoadMessage('error', 'Selecione uma conexão para carregar os grupos.');
                    connectionGroups.clear();
                    renderConnectionGroups('Selecione uma conexão para pesquisar.');
                    return;
                }

                const search = String(groupsSearchInput.value || '').trim();
                if (search.length < searchMinChars) {
                    connectionGroups.clear();
                    renderConnectionGroups(`Digite ao menos ${searchMinChars} letras para pesquisar grupos por nome.`);
                    setLoadMessage('info', `Digite ao menos ${searchMinChars} letras para iniciar a pesquisa.`);
                    return;
                }

                const url = new URL(connectionGroupsUrlTemplate.replace('__CONEXAO__', conexaoId), window.location.origin);
                url.searchParams.set('force', '1');
                url.searchParams.set('no_participants', '1');
                url.searchParams.set('search', search);

                if (searchAbortController) {
                    searchAbortController.abort();
                }

                searchAbortController = new AbortController();
                const currentRequest = ++searchRequestSerial;

                loadGroupsButton.disabled = true;
                setSearchLoading(true);
                setLoadMessage('info', `Pesquisando grupos por "${search}"...`);

                try {
                    const response = await fetch(url.toString(), {
                        signal: searchAbortController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const json = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const errorMessage = typeof json?.message === 'string' && json.message.trim() !== ''
                            ? json.message
                            : 'Falha ao carregar grupos da conexão.';
                        throw new Error(errorMessage);
                    }

                    if (currentRequest !== searchRequestSerial) {
                        return;
                    }

                    const groups = Array.isArray(json?.data) ? json.data : [];
                    connectionGroups.clear();

                    groups.forEach((group) => {
                        const normalized = normalizeGroup(group);
                        if (!normalized) {
                            return;
                        }

                        connectionGroups.set(normalized.jid, normalized);

                        const selected = selectedGroups.get(normalized.jid);
                        if (selected && (selected.name === selected.jid || !selected.name)) {
                            selectedGroups.set(normalized.jid, normalized);
                        }
                    });

                    renderSelectedGroups();
                    renderConnectionGroups();

                    if (connectionGroups.size === 0) {
                        setLoadMessage('info', `Nenhum grupo encontrado para "${search}".`);
                        renderConnectionGroups(`Nenhum grupo encontrado para "${search}".`);
                    } else {
                        setLoadMessage('success', `${connectionGroups.size} grupo(s) encontrado(s) para "${search}".`);
                    }
                } catch (error) {
                    if (error?.name === 'AbortError') {
                        return;
                    }

                    setLoadMessage('error', error?.message || 'Falha ao carregar grupos da conexão.');
                    renderConnectionGroups('Não foi possível pesquisar grupos agora.');
                } finally {
                    if (currentRequest === searchRequestSerial) {
                        loadGroupsButton.disabled = false;
                        setSearchLoading(false);
                    }
                }
            };

            const fillFormForEdit = (payload) => {
                resetForm();

                conjuntoIdInput.value = String(payload?.id ?? '');
                nameInput.value = String(payload?.name ?? '');
                conexaoSelect.value = String(payload?.conexao_id ?? '');

                selectedGroups.clear();
                (Array.isArray(payload?.groups) ? payload.groups : []).forEach((group) => {
                    const normalized = normalizeGroup(group);
                    if (!normalized) {
                        return;
                    }
                    selectedGroups.set(normalized.jid, normalized);
                });

                modalTitle.textContent = 'Editar conjunto';
                formSubmitButton.textContent = 'Atualizar';
                renderSelectedGroups();
                renderConnectionGroups('Pesquise por nome para listar grupos desta conexão.');
            };

            openModalButton?.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeModalButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal();
                });
            });

            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            loadGroupsButton?.addEventListener('click', loadConnectionGroups);

            groupsSearchInput?.addEventListener('input', () => {
                if (searchDebounceTimer) {
                    clearTimeout(searchDebounceTimer);
                }

                const term = String(groupsSearchInput.value || '').trim();
                if (term.length < searchMinChars) {
                    if (searchAbortController) {
                        searchAbortController.abort();
                    }
                    setSearchLoading(false);
                    loadGroupsButton.disabled = false;
                    connectionGroups.clear();
                    renderConnectionGroups(`Digite ao menos ${searchMinChars} letras para pesquisar grupos por nome.`);
                    setLoadMessage('info', `Digite ao menos ${searchMinChars} letras para iniciar a pesquisa.`);
                    return;
                }

                searchDebounceTimer = setTimeout(() => {
                    loadConnectionGroups();
                }, 400);
            });

            conexaoSelect?.addEventListener('change', () => {
                connectionGroups.clear();
                selectedGroups.clear();
                if (searchAbortController) {
                    searchAbortController.abort();
                }
                setSearchLoading(false);
                loadGroupsButton.disabled = false;
                setLoadMessage(null, null);
                renderSelectedGroups();
                renderConnectionGroups('Selecione uma conexão e pesquise grupos por nome.');
            });

            addInviteGroupButton?.addEventListener('click', async () => {
                const conexaoId = String(conexaoSelect.value || '').trim();
                if (conexaoId === '') {
                    setLoadMessage('error', 'Selecione uma conexão antes de buscar por link de convite.');
                    return;
                }

                const inviteLink = String(inviteGroupLinkInput?.value || '').trim();
                if (inviteLink === '') {
                    setLoadMessage('error', 'Cole um link de convite para buscar o grupo.');
                    inviteGroupLinkInput?.focus();
                    return;
                }

                const url = new URL(connectionGroupInviteUrlTemplate.replace('__CONEXAO__', conexaoId), window.location.origin);
                url.searchParams.set('invite_link', inviteLink);

                addInviteGroupButton.disabled = true;
                setLoadMessage('info', 'Consultando convite e buscando dados do grupo...');

                try {
                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const errorMessage = typeof json?.message === 'string' && json.message.trim() !== ''
                            ? json.message
                            : 'Não foi possível resolver o convite informado.';
                        throw new Error(errorMessage);
                    }

                    const normalized = normalizeGroup(json?.data);
                    if (!normalized) {
                        throw new Error('Convite válido, mas não foi possível identificar um group JID válido.');
                    }

                    addGroupToSelection(normalized);
                    if (inviteGroupLinkInput) {
                        inviteGroupLinkInput.value = '';
                    }

                    setLoadMessage('success', `Grupo ${normalized.name} adicionado ao conjunto.`);
                } catch (error) {
                    setLoadMessage('error', error?.message || 'Falha ao buscar grupo pelo convite.');
                } finally {
                    addInviteGroupButton.disabled = false;
                }
            });

            form?.addEventListener('submit', (event) => {
                if (selectedGroups.size === 0) {
                    event.preventDefault();
                    setLoadMessage('error', 'Selecione ao menos um grupo para salvar o conjunto.');
                    return;
                }

                renderHiddenInputs();
            });

            document.querySelectorAll('[data-action="edit-conjunto"]').forEach((button) => {
                button.addEventListener('click', () => {
                    const payload = JSON.parse(button.dataset.payload || '{}');
                    fillFormForEdit(payload);
                    openModal();
                });
            });

            const shouldOpenFromOldInput = Boolean(oldState.name || oldState.conexaoId || (Array.isArray(oldState.groups) && oldState.groups.length > 0));
            if (shouldOpenFromOldInput) {
                fillFormForEdit({
                    id: oldState.id,
                    name: oldState.name,
                    conexao_id: oldState.conexaoId,
                    groups: oldState.groups,
                });
                openModal();
            } else {
                renderSelectedGroups();
                renderConnectionGroups();
            }
        })();
    </script>

    <script>
        (() => {
            const modal = document.getElementById('groupMessageModal');
            const openBtn = document.getElementById('openGroupMessageModal');
            const closeBtns = document.querySelectorAll('[data-close-group-message-modal]');
            const form = document.getElementById('groupMessageForm');
            const title = document.getElementById('groupMessageModalTitle');
            const submitBtn = document.getElementById('groupMessageSubmit');
            const methodInput = document.getElementById('groupMessageFormMethod');
            const contextInput = document.getElementById('groupMessageFormContext');
            const messageIdInput = document.getElementById('groupMessageId');
            const actionCards = Array.from(document.querySelectorAll('[data-action-card]'));
            const actionRadioInputs = Array.from(form?.querySelectorAll('input[name="action_type"]') || []);
            const actionPanels = Array.from(form?.querySelectorAll('[data-action-panel]') || []);

            const messageTextInput = document.getElementById('groupMessageText');
            const mediaTypeInput = document.getElementById('groupMessageMediaType');
            const mediaUrlInput = document.getElementById('groupMessageMediaUrl');
            const captionInput = document.getElementById('groupMessageCaption');
            const groupNameInput = document.getElementById('groupMessageGroupName');
            const groupNameSequenceInput = document.getElementById('groupMessageGroupNameSequence');
            const groupNameSequenceStartInput = document.getElementById('groupMessageGroupNameSequenceStart');
            const groupNameSequenceOptions = document.getElementById('groupMessageGroupNameSequenceOptions');
            const groupNameSequencePreview = document.getElementById('groupMessageGroupNameSequencePreview');
            const groupNameSequenceLimit = document.getElementById('groupMessageGroupNameSequenceLimit');
            const groupDescriptionInput = document.getElementById('groupMessageGroupDescription');
            const groupImageUrlInput = document.getElementById('groupMessageGroupImageUrl');
            const mentionAllWrap = document.getElementById('groupMessageMentionAllWrap');
            const mentionAllInput = document.getElementById('groupMessageMentionAll');
            const sendTypeInput = document.getElementById('groupMessageSendType');
            const scheduleWrap = document.getElementById('groupMessageScheduleWrap');
            const scheduledForInput = document.getElementById('groupMessageScheduledFor');

            if (!modal || !form || !sendTypeInput) {
                return;
            }

            const ACTION_TYPES = [
                'send_text',
                'send_media',
                'update_group_name',
                'update_group_description',
                'update_group_image',
            ];

            const createRouteTemplate = form.dataset.createRouteTemplate;
            const updateRouteTemplate = form.dataset.updateRouteTemplate;
            const selectedConjuntoId = String(form.dataset.selectedConjuntoId || '');

            const oldMessageState = {
                form: @json(old('message_form')),
                id: @json(old('message_id')),
                actionType: @json(old('action_type')),
                sendType: @json(old('send_type')),
                scheduledFor: @json(old('scheduled_for')),
                text: @json(old('text', old('mensagem'))),
                mediaType: @json(old('media_type')),
                mediaUrl: @json(old('media_url')),
                caption: @json(old('caption')),
                groupName: @json(old('group_name')),
                groupNameSequence: @json(old('group_name_sequence')),
                groupNameSequenceStart: @json(old('group_name_sequence_start', 1)),
                groupDescription: @json(old('group_description')),
                groupImageUrl: @json(old('group_image_url')),
                mentionAll: @json(old('mention_all')),
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const buildCreateAction = () => createRouteTemplate.replace('__CONJUNTO__', selectedConjuntoId);
            const buildUpdateAction = (messageId) => updateRouteTemplate
                .replace('__CONJUNTO__', selectedConjuntoId)
                .replace('__MENSAGEM__', String(messageId));

            const normalizeActionType = (value) => {
                const normalized = String(value || '').trim();
                return ACTION_TYPES.includes(normalized) ? normalized : 'send_text';
            };

            const getCurrentActionType = () => {
                const checked = actionRadioInputs.find((input) => input.checked);
                return normalizeActionType(checked?.value);
            };

            const isTruthy = (value) => ['1', 'true', 'on', 'yes'].includes(String(value || '').toLowerCase());

            const syncGroupNameSequence = () => {
                if (!groupNameSequenceInput || !groupNameSequenceStartInput || !groupNameSequenceOptions) {
                    return;
                }

                const isGroupNameAction = getCurrentActionType() === 'update_group_name';
                const isEnabled = isGroupNameAction && groupNameSequenceInput.checked;
                groupNameSequenceInput.disabled = !isGroupNameAction;
                groupNameSequenceStartInput.disabled = !isEnabled;
                groupNameSequenceStartInput.required = isEnabled;
                groupNameSequenceOptions.classList.toggle('hidden', !isEnabled);

                if (!isEnabled) {
                    groupNameInput?.setCustomValidity('');
                    return;
                }

                const baseName = String(groupNameInput?.value || '').trim();
                const parsedStart = Number.parseInt(groupNameSequenceStartInput.value, 10);
                const sequenceStart = Number.isInteger(parsedStart) && parsedStart >= 1 ? parsedStart : 1;
                const recipientCount = Math.max(1, Number.parseInt(form.dataset.recipientCount || '1', 10) || 1);
                const sequenceEnd = sequenceStart + recipientCount - 1;
                const firstTitle = `${baseName} #${sequenceStart}`.trim();
                const lastTitle = `${baseName} #${sequenceEnd}`.trim();
                const maxBaseLength = Math.max(0, 25 - Array.from(` #${sequenceEnd}`).length);
                const exceedsLimit = Array.from(lastTitle).length > 25;

                if (groupNameSequencePreview) {
                    groupNameSequencePreview.textContent = recipientCount > 1
                        ? `Prévia: ${firstTitle} … ${lastTitle}`
                        : `Prévia: ${firstTitle}`;
                }
                if (groupNameSequenceLimit) {
                    groupNameSequenceLimit.textContent = `Com esta sequência, o título-base pode ter até ${maxBaseLength} caracteres.`;
                    groupNameSequenceLimit.classList.toggle('text-rose-600', exceedsLimit);
                    groupNameSequenceLimit.classList.toggle('text-slate-500', !exceedsLimit);
                }

                groupNameInput?.setCustomValidity(
                    exceedsLimit
                        ? `O título-base pode ter no máximo ${maxBaseLength} caracteres para esta sequência.`
                        : ''
                );
            };

            const syncActionRequiredFields = (actionType) => {
                if (messageTextInput) {
                    messageTextInput.required = actionType === 'send_text';
                }
                if (mediaTypeInput) {
                    mediaTypeInput.required = actionType === 'send_media';
                }
                if (mediaUrlInput) {
                    mediaUrlInput.required = actionType === 'send_media';
                }
                if (groupNameInput) {
                    groupNameInput.required = actionType === 'update_group_name';
                }
                if (groupDescriptionInput) {
                    groupDescriptionInput.required = actionType === 'update_group_description';
                }
                if (groupImageUrlInput) {
                    groupImageUrlInput.required = actionType === 'update_group_image';
                }
                if (mentionAllWrap && mentionAllInput) {
                    const shouldShowMention = actionType === 'send_text' || actionType === 'send_media';
                    mentionAllWrap.classList.toggle('hidden', !shouldShowMention);
                    mentionAllInput.disabled = !shouldShowMention;

                    if (!shouldShowMention) {
                        mentionAllInput.checked = false;
                    }
                }

                syncGroupNameSequence();
            };

            const setActionType = (nextActionType) => {
                const actionType = normalizeActionType(nextActionType);

                actionRadioInputs.forEach((input) => {
                    input.checked = input.value === actionType;
                });

                actionPanels.forEach((panel) => {
                    const isActive = panel.dataset.actionPanel === actionType;
                    panel.classList.toggle('hidden', !isActive);
                });

                actionCards.forEach((card) => {
                    const isActive = card.dataset.actionValue === actionType;
                    card.classList.toggle('border-blue-300', isActive);
                    card.classList.toggle('bg-blue-50', isActive);
                    card.classList.toggle('border-slate-200', !isActive);
                    card.classList.toggle('bg-white', !isActive);
                });

                syncActionRequiredFields(actionType);
            };

            const syncScheduleVisibility = () => {
                const isScheduled = sendTypeInput.value === 'scheduled';
                scheduleWrap.classList.toggle('hidden', !isScheduled);
                scheduledForInput.required = isScheduled;
            };

            const clearActionFields = () => {
                if (messageTextInput) {
                    messageTextInput.value = '';
                }
                if (mediaTypeInput) {
                    mediaTypeInput.value = '';
                }
                if (mediaUrlInput) {
                    mediaUrlInput.value = '';
                }
                if (captionInput) {
                    captionInput.value = '';
                }
                if (groupNameInput) {
                    groupNameInput.value = '';
                }
                if (groupNameSequenceInput) {
                    groupNameSequenceInput.checked = false;
                }
                if (groupNameSequenceStartInput) {
                    groupNameSequenceStartInput.value = '1';
                }
                if (groupDescriptionInput) {
                    groupDescriptionInput.value = '';
                }
                if (groupImageUrlInput) {
                    groupImageUrlInput.value = '';
                }
                if (mentionAllInput) {
                    mentionAllInput.checked = false;
                }
            };

            const resetForm = () => {
                form.reset();
                form.action = buildCreateAction();
                methodInput.value = 'POST';
                contextInput.value = 'create';
                messageIdInput.value = '';
                title.textContent = 'Criar ação';
                submitBtn.textContent = 'Salvar';
                sendTypeInput.value = 'now';
                clearActionFields();
                setActionType('send_text');
                syncScheduleVisibility();
            };

            const fillForEdit = (payload) => {
                if (!payload?.id) {
                    return;
                }

                if (!['pending', 'failed'].includes(String(payload.status || ''))) {
                    window.alert('Somente mensagens pendentes ou com falha podem ser editadas.');
                    return;
                }

                resetForm();
                form.action = buildUpdateAction(payload.id);
                methodInput.value = 'PATCH';
                contextInput.value = 'edit';
                messageIdInput.value = String(payload.id);
                title.textContent = 'Editar ação';
                submitBtn.textContent = 'Atualizar';

                setActionType(payload.action_type);
                if (messageTextInput) {
                    messageTextInput.value = String(payload.text || payload.mensagem || '');
                }
                if (mediaTypeInput) {
                    mediaTypeInput.value = String(payload.media_type || '');
                }
                if (mediaUrlInput) {
                    mediaUrlInput.value = String(payload.media_url || '');
                }
                if (captionInput) {
                    captionInput.value = String(payload.caption || '');
                }
                if (groupNameInput) {
                    groupNameInput.value = String(payload.group_name || '');
                }
                if (groupNameSequenceInput) {
                    groupNameSequenceInput.checked = Boolean(payload.group_name_sequence);
                }
                if (groupNameSequenceStartInput) {
                    groupNameSequenceStartInput.value = String(payload.group_name_sequence_start || 1);
                }
                if (groupDescriptionInput) {
                    groupDescriptionInput.value = String(payload.group_description || '');
                }
                if (groupImageUrlInput) {
                    groupImageUrlInput.value = String(payload.group_image_url || '');
                }
                if (mentionAllInput) {
                    mentionAllInput.checked = Boolean(payload.mention_all);
                }

                syncGroupNameSequence();

                sendTypeInput.value = payload.dispatch_type === 'scheduled' ? 'scheduled' : 'now';
                scheduledForInput.value = String(payload.scheduled_for_input || '');
                syncScheduleVisibility();
                openModal();
            };

            openBtn?.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            sendTypeInput.addEventListener('change', syncScheduleVisibility);
            groupNameInput?.addEventListener('input', syncGroupNameSequence);
            groupNameSequenceInput?.addEventListener('change', syncGroupNameSequence);
            groupNameSequenceStartInput?.addEventListener('input', syncGroupNameSequence);
            actionRadioInputs.forEach((input) => {
                input.addEventListener('change', () => setActionType(input.value));
            });

            document.querySelectorAll('[data-action="edit-group-message"]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const payload = JSON.parse(btn.dataset.payload || '{}');
                    fillForEdit(payload);
                });
            });

            const shouldOpenFromOld = Boolean(
                oldMessageState.form
                || oldMessageState.text
                || oldMessageState.mediaUrl
                || oldMessageState.groupName
                || oldMessageState.groupDescription
                || oldMessageState.groupImageUrl
                || oldMessageState.mentionAll
            );
            if (shouldOpenFromOld) {
                resetForm();

                if (oldMessageState.form === 'edit' && oldMessageState.id) {
                    form.action = buildUpdateAction(oldMessageState.id);
                    methodInput.value = 'PATCH';
                    contextInput.value = 'edit';
                    messageIdInput.value = String(oldMessageState.id);
                    title.textContent = 'Editar ação';
                    submitBtn.textContent = 'Atualizar';
                }

                setActionType(oldMessageState.actionType);
                if (messageTextInput) {
                    messageTextInput.value = String(oldMessageState.text || '');
                }
                if (mediaTypeInput) {
                    mediaTypeInput.value = String(oldMessageState.mediaType || '');
                }
                if (mediaUrlInput) {
                    mediaUrlInput.value = String(oldMessageState.mediaUrl || '');
                }
                if (captionInput) {
                    captionInput.value = String(oldMessageState.caption || '');
                }
                if (groupNameInput) {
                    groupNameInput.value = String(oldMessageState.groupName || '');
                }
                if (groupNameSequenceInput) {
                    groupNameSequenceInput.checked = isTruthy(oldMessageState.groupNameSequence);
                }
                if (groupNameSequenceStartInput) {
                    groupNameSequenceStartInput.value = String(oldMessageState.groupNameSequenceStart || 1);
                }
                if (groupDescriptionInput) {
                    groupDescriptionInput.value = String(oldMessageState.groupDescription || '');
                }
                if (groupImageUrlInput) {
                    groupImageUrlInput.value = String(oldMessageState.groupImageUrl || '');
                }
                if (mentionAllInput) {
                    mentionAllInput.checked = isTruthy(oldMessageState.mentionAll);
                }

                syncGroupNameSequence();

                sendTypeInput.value = oldMessageState.sendType === 'scheduled' ? 'scheduled' : 'now';
                scheduledForInput.value = String(oldMessageState.scheduledFor || '');
                syncScheduleVisibility();
                openModal();
            } else {
                setActionType('send_text');
            }
        })();
    </script>

    @if($showFailureDetails && $selectedConjunto)
        <script>
            (() => {
                const modal = document.getElementById('groupFailureModal');
                const loading = document.getElementById('groupFailureLoading');
                const fetchError = document.getElementById('groupFailureFetchError');
                const content = document.getElementById('groupFailureContent');
                const meta = document.getElementById('groupFailureMeta');
                const statusText = document.getElementById('groupFailureStatus');
                const sentCount = document.getElementById('groupFailureSentCount');
                const failedCount = document.getElementById('groupFailureFailedCount');
                const message = document.getElementById('groupFailureMessage');
                const itemsBody = document.getElementById('groupFailureItems');
                const itemsEmpty = document.getElementById('groupFailureItemsEmpty');

                if (!modal || !modal.dataset.statusUrl) {
                    return;
                }

                const statusUrl = modal.dataset.statusUrl;
                const cache = new Map();
                const statusClasses = {
                    pending: ['bg-amber-100', 'text-amber-700'],
                    queued: ['bg-blue-100', 'text-blue-700'],
                    sent: ['bg-emerald-100', 'text-emerald-700'],
                    failed: ['bg-rose-100', 'text-rose-700'],
                    canceled: ['bg-slate-200', 'text-slate-700'],
                };
                const allStatusClasses = Object.values(statusClasses).flat();

                let pollTimer = null;
                let requestPromise = null;
                let shouldPoll = true;
                let networkFailures = 0;
                let openedMessageId = null;

                const setModalState = (state, errorMessage = '') => {
                    loading?.classList.toggle('hidden', state !== 'loading');
                    fetchError?.classList.toggle('hidden', state !== 'error');
                    content?.classList.toggle('hidden', state !== 'content');
                    if (fetchError) {
                        fetchError.textContent = errorMessage;
                    }
                };

                const openModal = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                };

                const closeModal = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    openedMessageId = null;
                };

                const appendCell = (row, text, className = '') => {
                    const cell = document.createElement('td');
                    cell.className = `px-4 py-3 align-top ${className}`.trim();
                    cell.textContent = text;
                    row.appendChild(cell);
                };

                const renderFailure = (record) => {
                    const failure = record?.failure || {};
                    const items = Array.isArray(failure.items) ? failure.items : [];

                    meta.textContent = `Ação #${record.id}${record.failed_at_label ? ` · Falhou em ${record.failed_at_label}` : ''}`;
                    statusText.textContent = record.status_label || record.status || '—';
                    sentCount.textContent = String(record.sent_count ?? 0);
                    failedCount.textContent = String(record.failed_count ?? 0);
                    message.textContent = failure.message || 'Não foi possível identificar o motivo da falha.';
                    itemsBody.innerHTML = '';

                    items.forEach((item) => {
                        const row = document.createElement('tr');
                        const groupCell = document.createElement('td');
                        groupCell.className = 'px-4 py-3 align-top';

                        const name = document.createElement('p');
                        name.className = 'font-medium text-slate-800';
                        name.textContent = item.name || item.jid || 'Grupo não identificado';
                        groupCell.appendChild(name);

                        if (item.jid && item.jid !== item.name) {
                            const jid = document.createElement('p');
                            jid.className = 'mt-0.5 font-mono text-[11px] text-slate-500';
                            jid.textContent = item.jid;
                            groupCell.appendChild(jid);
                        }

                        row.appendChild(groupCell);
                        appendCell(row, item.http_status ? String(item.http_status) : '—', 'font-mono text-xs text-slate-600');
                        appendCell(row, item.message || 'Não foi possível identificar o motivo da falha.', 'text-slate-700');
                        itemsBody.appendChild(row);
                    });

                    itemsEmpty.classList.toggle('hidden', items.length > 0);
                    setModalState('content');
                };

                const updateRow = (record) => {
                    const row = document.querySelector(`[data-group-message-row="${record.id}"]`);
                    if (!row) {
                        return;
                    }

                    const counts = row.querySelector('[data-group-message-counts]');
                    if (counts) {
                        counts.textContent = `OK: ${record.sent_count ?? 0} · Falhas: ${record.failed_count ?? 0}`;
                    }

                    const status = row.querySelector('[data-group-message-status]');
                    if (status) {
                        status.textContent = record.status_label || record.status || '—';
                        status.classList.remove(...allStatusClasses);
                        status.classList.add(...(statusClasses[record.status] || ['bg-slate-100', 'text-slate-700']));
                    }

                    const failureButton = row.querySelector('[data-view-group-failure]');
                    if (failureButton) {
                        const failures = Number(record.failed_count || 0);
                        const hasFailure = record.status === 'failed' || failures > 0;
                        failureButton.classList.toggle('hidden', !hasFailure);
                        failureButton.textContent = failures > 1 ? 'Ver falhas' : 'Ver falha';
                    }

                    const editButton = row.querySelector('[data-action="edit-group-message"]');
                    if (editButton) {
                        try {
                            const editorPayload = JSON.parse(editButton.dataset.payload || '{}');
                            editorPayload.status = record.status;
                            editButton.dataset.payload = JSON.stringify(editorPayload);
                        } catch (error) {
                            editButton.dataset.payload = JSON.stringify({
                                id: record.id,
                                status: record.status,
                            });
                        }
                    }

                    if (openedMessageId === String(record.id) && record.failure) {
                        renderFailure(record);
                    }
                };

                const schedulePoll = (delay) => {
                    window.clearTimeout(pollTimer);
                    if (!shouldPoll || document.hidden) {
                        return;
                    }
                    pollTimer = window.setTimeout(refreshStatuses, delay);
                };

                const nextPollDelay = (records) => {
                    if (records.some((record) => record.status === 'queued')) {
                        return 5000;
                    }
                    if (records.some((record) => record.status === 'pending')) {
                        return 60000;
                    }
                    return null;
                };

                const refreshStatuses = async () => {
                    if (requestPromise) {
                        return requestPromise;
                    }

                    requestPromise = (async () => {
                        const response = await fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(payload.message || 'Não foi possível atualizar o status das ações.');
                        }

                        const records = Array.isArray(payload.data) ? payload.data : [];
                        records.forEach((record) => {
                            cache.set(String(record.id), record);
                            updateRow(record);
                        });

                        networkFailures = 0;
                        shouldPoll = Boolean(payload.has_processing);
                        const delay = nextPollDelay(records);
                        if (shouldPoll && delay !== null) {
                            schedulePoll(delay);
                        }

                        return records;
                    })();

                    try {
                        return await requestPromise;
                    } catch (error) {
                        networkFailures += 1;
                        shouldPoll = true;
                        schedulePoll(Math.min(5000 * (2 ** (networkFailures - 1)), 30000));
                        throw error;
                    } finally {
                        requestPromise = null;
                    }
                };

                document.querySelectorAll('[data-view-group-failure]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        const messageId = String(button.dataset.messageId || '');
                        openedMessageId = messageId;
                        openModal();

                        const cached = cache.get(messageId);
                        if (cached?.failure) {
                            renderFailure(cached);
                            return;
                        }

                        setModalState('loading');
                        try {
                            await refreshStatuses();
                            const record = cache.get(messageId);
                            if (!record?.failure) {
                                throw new Error('Os detalhes desta falha ainda não estão disponíveis.');
                            }
                            renderFailure(record);
                        } catch (error) {
                            setModalState('error', error?.message || 'Não foi possível carregar os detalhes da falha.');
                        }
                    });
                });

                document.querySelectorAll('[data-close-group-failure-modal]').forEach((button) => {
                    button.addEventListener('click', closeModal);
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });

                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        window.clearTimeout(pollTimer);
                        return;
                    }
                    if (shouldPoll) {
                        refreshStatuses().catch(() => {});
                    }
                });

                refreshStatuses().catch(() => {});
            })();
        </script>
    @endif
@endpush
