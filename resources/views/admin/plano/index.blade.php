@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Planos</h2>
            <p class="text-sm text-slate-500">Gerencie os planos disponíveis para as agências.</p>
        </div>
        <button
            type="button"
            id="openPlanModal"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
        >Criar plano</button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Nome</th>
                        <th class="px-3 py-2 text-left font-semibold">Preço</th>
                        <th class="px-3 py-2 text-left font-semibold">Conexões</th>
                        <th class="px-3 py-2 text-left font-semibold">Armazenamento (MB)</th>
                        <th class="px-3 py-2 text-left font-semibold">Criado em</th>
                        <th class="px-3 py-2 text-left font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($plans as $plan)
                        <tr>
                            <td class="px-3 py-3 font-medium text-slate-900">{{ $plan->name }}</td>
                            <td class="px-3 py-3">{{ 'R$ ' . number_format($plan->price_cents, 2, ',', '.') }}</td>
                            <td class="px-3 py-3">{{ $plan->max_conexoes }}</td>
                            <td class="px-3 py-3">{{ $plan->storage_limit_mb }}</td>
                            <td class="px-3 py-3">{{ $plan->created_at?->format('d/m/Y') }}</td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="plan-edit inline-flex items-center gap-1 rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-slate-900 hover:text-slate-900"
                                        data-plan="{{ json_encode([
                                            'id' => $plan->id,
                                            'name' => $plan->name,
                                            'price_cents' => number_format($plan->price_cents, 2, '.', ''),
                                            'max_conexoes' => $plan->max_conexoes,
                                            'storage_limit_mb' => $plan->storage_limit_mb,
                                        ], JSON_UNESCAPED_UNICODE) }}"
                                    >Editar</button>
                                    <form action="{{ route('adm.plano.destroy', $plan) }}" method="POST" onsubmit="return confirm('Excluir este plano?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-[11px] font-semibold text-rose-600 transition hover:border-rose-300"
                                        >Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-slate-400">Nenhum plano cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="planModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4 py-6">
        <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 id="planModalTitle" class="text-lg font-semibold text-slate-900">Novo plano</h3>
                <button type="button" data-plan-close class="text-slate-500 hover:text-slate-700">×</button>
            </div>
            <form
                id="planForm"
                method="POST"
                action="{{ route('adm.plano.store') }}"
                data-create-route="{{ route('adm.plano.store') }}"
                data-update-route-template="{{ route('adm.plano.update', ['plan' => '__PLAN_ID__']) }}"
                class="space-y-4"
            >
                @csrf
                <input type="hidden" name="_method" value="POST" id="planFormMethod">

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="planName">Nome</label>
                    <input id="planName" name="name" type="text" required maxlength="255" class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="planPrice">Preço (ex: 199.90)</label>
                        <input id="planPrice" name="price_cents" type="text" required inputmode="decimal" class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="planMaxConexoes">Máx. conexões</label>
                        <input id="planMaxConexoes" name="max_conexoes" type="number" min="0" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="planStorageLimit">Limite de armazenamento (MB)</label>
                    <input id="planStorageLimit" name="storage_limit_mb" type="number" min="0" required class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                </div>

                <div class="flex justify-end gap-3 pt-3">
                    <button type="button" data-plan-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                    <button type="submit" id="planFormSubmit" class="rounded-2xl bg-blue-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('planModal');
            const form = document.getElementById('planForm');
            const methodInput = document.getElementById('planFormMethod');
            const title = document.getElementById('planModalTitle');
            const submitLabel = document.getElementById('planFormSubmit');
            const fieldName = document.getElementById('planName');
            const fieldPrice = document.getElementById('planPrice');
            const fieldMax = document.getElementById('planMaxConexoes');
            const fieldStorage = document.getElementById('planStorageLimit');
            const openBtn = document.getElementById('openPlanModal');
            const closeButtons = document.querySelectorAll('[data-plan-close]');
            const updateRouteTemplate = form.dataset.updateRouteTemplate;

            const openModal = () => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };

            const closeModal = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            const resetForm = () => {
                form.reset();
                methodInput.value = 'POST';
                form.action = form.dataset.createRoute;
                title.textContent = 'Novo plano';
                submitLabel.textContent = 'Salvar';
            };

            openBtn?.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeButtons.forEach(btn => {
                btn.addEventListener('click', closeModal);
            });

            document.querySelectorAll('.plan-edit').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.getAttribute('data-plan') || '{}');
                    if (!data.id) return;

                    resetForm();
                    methodInput.value = 'PUT';
                    form.action = updateRouteTemplate.replace('__PLAN_ID__', data.id);
                    title.textContent = 'Editar plano';
                    submitLabel.textContent = 'Atualizar';
                    fieldName.value = data.name || '';
                    fieldPrice.value = data.price_cents ?? '';
                    fieldMax.value = data.max_conexoes ?? '';
                    fieldStorage.value = data.storage_limit_mb ?? '';

                    openModal();
                });
            });

            document.addEventListener('click', event => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();
    </script>
@endpush
