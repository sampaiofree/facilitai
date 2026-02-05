@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Usuários</h2>
            <p class="text-sm text-slate-500">Gerencie os usuários com acesso à plataforma.</p>
        </div>
        <button id="openUserModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Novo usuário</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">E-mail</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Telefone</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Asaas</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Asaas Sub</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Plano</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Conexões</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Admin</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Data de cadastro</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $user->mobile_phone ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $user->customer_asaas_id ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $user->asaas_sub ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $user->plan?->name ?? "-" }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $user->conexoes_count ?? 0 }}</td>
                        <td class="px-5 py-4">
                            @if($user->is_admin)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Sim</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Não</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                @php
                                    $userPayload = [
                                        'id' => $user->id,
                                        'name' => $user->name,
                                        'email' => $user->email,
                                        'cpf_cnpj' => $user->cpf_cnpj,
                                        'mobile_phone' => $user->mobile_phone,
                                        'customer_asaas_id' => $user->customer_asaas_id,
                                        'asaas_sub' => $user->asaas_sub,
                                        'is_admin' => $user->is_admin,
                                        'plan_id' => $user->plan_id,
                                        'plan_name' => $user->plan?->name,
                                        'plan_price_cents' => $user->plan?->price_cents,
                                        'created_at' => $user->created_at?->format('d/m/Y H:i'),
                                        'updated_at' => $user->updated_at?->format('d/m/Y H:i'),
                                        'conexoes_count' => $user->conexoes_count ?? 0,
                                        'asaas_webhooks' => $user->asaasWebhooks->map(function ($hook) {
                                            return [
                                                'id' => $hook->id,
                                                'event_type' => $hook->event_type,
                                                'status' => $hook->status,
                                                'value' => $hook->value,
                                                'billing_type' => $hook->billing_type,
                                                'payment_id' => $hook->payment_id,
                                                'customer_id' => $hook->customer_id,
                                                'external_reference' => $hook->external_reference,
                                                'payment_at' => $hook->payment_at?->format('d/m/Y'),
                                                'confirmed_at' => $hook->confirmed_at?->format('d/m/Y'),
                                                'created_at' => $hook->created_at?->format('d/m/Y H:i'),
                                            ];
                                        })->values()->all(),
                                    ];
                                @endphp
                                <button type="button"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900"
                                    data-open-view
                                    data-user='@json($userPayload, JSON_UNESCAPED_UNICODE)'
                                >Ver</button>
                                <button type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-mobile_phone="{{ $user->mobile_phone }}"
                                    data-cpf_cnpj="{{ $user->cpf_cnpj }}"
                                    data-customer_asaas_id="{{ $user->customer_asaas_id }}"
                                    data-plan_id="{{ $user->plan_id }}"
                                    data-is_admin="{{ $user->is_admin ? '1' : '0' }}"
                                >Editar</button>
                                <form method="POST" action="{{ route('adm.users.destroy', $user) }}" onsubmit="return confirm('Deseja excluir este usuário?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-5 py-6 text-center text-slate-500">Nenhum usuário encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="userViewModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[720px] max-w-[95vw] max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Detalhes do usuário</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-view>x</button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-600 md:grid-cols-2">
                <div><span class="text-xs uppercase tracking-wide text-slate-400">ID</span><div id="viewUserId" class="font-semibold text-slate-900"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Nome</span><div id="viewUserName" class="font-semibold text-slate-900"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Email</span><div id="viewUserEmail"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">CPF/CNPJ</span><div id="viewUserCpf"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Telefone</span><div id="viewUserPhone"></div></div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-slate-400">Customer Asaas</span>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <span id="viewUserCustomer"></span>
                        <button type="button"
                            id="createAsaasCustomerBtn"
                            class="hidden rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900"
                        >Criar Customer</button>
                    </div>
                    <p id="createAsaasCustomerHint" class="mt-1 text-[11px] text-rose-600 hidden">CPF/CNPJ obrigatório para criar customer.</p>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-slate-400">Asaas Sub</span>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <a id="viewUserAsaasSubLink" href="#" class="text-blue-600 hover:underline"></a>
                        <button type="button"
                            id="createAsaasSubscriptionBtn"
                            class="hidden rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900"
                        >Criar assinatura</button>
                    </div>
                    <p id="createAsaasSubscriptionHint" class="mt-1 text-[11px] text-rose-600 hidden"></p>
                </div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Admin</span><div id="viewUserAdmin"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Plano</span><div id="viewUserPlan"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Conexões</span><div id="viewUserConnections"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Criado em</span><div id="viewUserCreated"></div></div>
                <div><span class="text-xs uppercase tracking-wide text-slate-400">Atualizado em</span><div id="viewUserUpdated"></div></div>
            </div>

            <div class="mt-6">
                <h4 class="text-sm font-semibold text-slate-700">Asaas Webhooks</h4>
                <div class="mt-2 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-400 uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2 text-left">ID</th>
                                <th class="px-3 py-2 text-left">Evento</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Valor</th>
                                <th class="px-3 py-2 text-left">Pagamento</th>
                                <th class="px-3 py-2 text-left">Criado em</th>
                            </tr>
                        </thead>
                        <tbody id="viewUserWebhooks" class="border-t border-slate-100 text-slate-700">
                            <tr>
                                <td colspan="6" class="px-3 py-2 text-center text-slate-400">Sem registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="subscriptionModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] max-w-[95vw] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Criar assinatura</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-subscription>x</button>
            </div>
            <form id="subscriptionForm" class="mt-5 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="subscriptionNextDueDate">Próximo vencimento</label>
                    <input id="subscriptionNextDueDate" name="next_due_date" type="date" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="subscriptionBillingType">Forma de pagamento</label>
                    <select id="subscriptionBillingType" name="billing_type" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="UNDEFINED">UNDEFINED</option>
                        <option value="BOLETO">BOLETO</option>
                        <option value="CREDIT_CARD">CREDIT_CARD</option>
                        <option value="PIX">PIX</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="subscriptionCycle">Ciclo</label>
                    <select id="subscriptionCycle" name="cycle" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="MONTHLY">MONTHLY</option>
                        <option value="YEARLY">YEARLY</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="subscriptionCustomer">Customer Asaas</label>
                    <input id="subscriptionCustomer" type="text" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 text-slate-600 shadow-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="subscriptionValue">Valor (Plan.price_cents)</label>
                    <input id="subscriptionValue" type="text" readonly class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 text-slate-600 shadow-sm">
                </div>
                <p id="subscriptionFormHint" class="text-[11px] text-rose-600 hidden"></p>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-subscription>Cancelar</button>
                    <button type="submit" id="subscriptionSubmit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="userModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="userModalTitle" class="text-lg font-semibold text-slate-900">Novo usuário</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>
            <form id="userForm" method="POST" action="{{ route('adm.users.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="userFormMethod" value="POST">
                <input type="hidden" name="user_id" id="userId" value="{{ old('user_id', '') }}">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="userName">Nome</label>
                    <input id="userName" name="name" type="text" maxlength="255" required value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="userEmail">E-mail</label>
                    <input id="userEmail" name="email" type="email" maxlength="255" required value="{{ old('email') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="userCpf">CPF/CNPJ</label>
                    <input id="userCpf" name="cpf_cnpj" type="text" maxlength="50" value="{{ old('cpf_cnpj') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="userPhone">Telefone</label>
                    <input id="userPhone" name="mobile_phone" type="text" maxlength="20" value="{{ old('mobile_phone') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="userCustomerAsaas">Customer Asaas ID</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input id="userCustomerAsaas" name="customer_asaas_id" type="text" maxlength="255" value="{{ old('customer_asaas_id') }}" class="flex-1 rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button"
                            id="registerAsaasCustomerBtn"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900"
                        >Registrar</button>
                    </div>
                    <p id="registerAsaasCustomerHint" class="mt-1 text-[11px] text-rose-600 hidden">CPF/CNPJ obrigatório para criar customer.</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="userPlan">Plano</label>
                    <select id="userPlan" name="plan_id" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Sem plano</option>
                        @foreach(($plans ?? collect()) as $plan)
                            <option value="{{ $plan->id }}" {{ (string) old('plan_id') === (string) $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="userPassword">Senha</label>
                    <input id="userPassword" name="password" type="password" minlength="8" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-center gap-2">
                    <input id="userIsAdmin" name="is_admin" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="userIsAdmin" class="text-sm text-slate-600">Administrador</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const viewModal = document.getElementById('userViewModal');
            const closeViewButtons = document.querySelectorAll('[data-close-view]');
            const modal = document.getElementById('userModal');
            const openBtn = document.getElementById('openUserModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('userForm');
            const methodInput = document.getElementById('userFormMethod');
            const title = document.getElementById('userModalTitle');
            const userIdInput = document.getElementById('userId');
            const name = document.getElementById('userName');
            const email = document.getElementById('userEmail');
            const cpf = document.getElementById('userCpf');
            const phone = document.getElementById('userPhone');
            const customer = document.getElementById('userCustomerAsaas');
            const registerAsaasCustomerBtn = document.getElementById('registerAsaasCustomerBtn');
            const registerAsaasCustomerHint = document.getElementById('registerAsaasCustomerHint');
            const planSelect = document.getElementById('userPlan');
            const password = document.getElementById('userPassword');
            const isAdmin = document.getElementById('userIsAdmin');
            const viewUserId = document.getElementById('viewUserId');
            const viewUserName = document.getElementById('viewUserName');
            const viewUserEmail = document.getElementById('viewUserEmail');
            const viewUserCpf = document.getElementById('viewUserCpf');
            const viewUserPhone = document.getElementById('viewUserPhone');
            const viewUserCustomer = document.getElementById('viewUserCustomer');
            const viewUserAsaasSubLink = document.getElementById('viewUserAsaasSubLink');
            const viewUserAdmin = document.getElementById('viewUserAdmin');
            const viewUserPlan = document.getElementById('viewUserPlan');
            const viewUserConnections = document.getElementById('viewUserConnections');
            const viewUserCreated = document.getElementById('viewUserCreated');
            const viewUserUpdated = document.getElementById('viewUserUpdated');
            const viewUserWebhooks = document.getElementById('viewUserWebhooks');
            const createAsaasCustomerBtn = document.getElementById('createAsaasCustomerBtn');
            const createAsaasCustomerHint = document.getElementById('createAsaasCustomerHint');
            const createAsaasSubscriptionBtn = document.getElementById('createAsaasSubscriptionBtn');
            const createAsaasSubscriptionHint = document.getElementById('createAsaasSubscriptionHint');
            const subscriptionModal = document.getElementById('subscriptionModal');
            const subscriptionForm = document.getElementById('subscriptionForm');
            const subscriptionNextDueDate = document.getElementById('subscriptionNextDueDate');
            const subscriptionBillingType = document.getElementById('subscriptionBillingType');
            const subscriptionCycle = document.getElementById('subscriptionCycle');
            const subscriptionCustomer = document.getElementById('subscriptionCustomer');
            const subscriptionValue = document.getElementById('subscriptionValue');
            const subscriptionFormHint = document.getElementById('subscriptionFormHint');
            const subscriptionSubmit = document.getElementById('subscriptionSubmit');
            const closeSubscriptionButtons = document.querySelectorAll('[data-close-subscription]');
            const createCustomerUrlTemplate = "{{ route('adm.users.asaas-customer', ['user' => '__ID__']) }}";
            const createSubscriptionUrlTemplate = "{{ route('adm.users.asaas-subscription', ['user' => '__ID__']) }}";
            const subscriptionLinkUrlTemplate = "{{ route('adm.users.asaas-subscription-link', ['user' => '__ID__']) }}";
            let currentViewUser = null;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const toggleHint = (element, message = '') => {
                if (!element) return;
                if (message) {
                    element.textContent = message;
                    element.classList.remove('hidden');
                } else {
                    element.classList.add('hidden');
                }
            };

            const setRegisterButtonState = (state) => {
                if (!registerAsaasCustomerBtn) return;
                if (state.hidden) {
                    registerAsaasCustomerBtn.classList.add('hidden');
                    return;
                }
                registerAsaasCustomerBtn.classList.remove('hidden');
                registerAsaasCustomerBtn.disabled = !!state.disabled;
                if (state.label) {
                    registerAsaasCustomerBtn.textContent = state.label;
                }
            };

            const setCreateButtonState = (state) => {
                if (!createAsaasCustomerBtn) return;
                if (state.hidden) {
                    createAsaasCustomerBtn.classList.add('hidden');
                    return;
                }
                createAsaasCustomerBtn.classList.remove('hidden');
                createAsaasCustomerBtn.disabled = !!state.disabled;
                if (state.label) {
                    createAsaasCustomerBtn.textContent = state.label;
                }
            };

            const setSubscriptionButtonState = (state) => {
                if (!createAsaasSubscriptionBtn) return;
                if (state.hidden) {
                    createAsaasSubscriptionBtn.classList.add('hidden');
                    return;
                }
                createAsaasSubscriptionBtn.classList.remove('hidden');
                createAsaasSubscriptionBtn.disabled = !!state.disabled;
                if (state.label) {
                    createAsaasSubscriptionBtn.textContent = state.label;
                }
            };

            const requestAsaasCustomer = async (userId) => {
                const url = createCustomerUrlTemplate.replace('__ID__', userId);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json',
                    },
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!response.ok || (data && data.error)) {
                    const message = data?.message || 'Falha ao criar customer no Asaas.';
                    return { ok: false, message };
                }

                return { ok: true, customerId: data?.customer_id };
            };

            const openViewModal = () => {
                viewModal?.classList.remove('hidden');
                viewModal?.classList.add('flex');
            };
            const closeViewModal = () => {
                viewModal?.classList.add('hidden');
                viewModal?.classList.remove('flex');
            };
            const openSubscriptionModal = () => {
                subscriptionModal?.classList.remove('hidden');
                subscriptionModal?.classList.add('flex');
            };
            const closeSubscriptionModal = () => {
                subscriptionModal?.classList.add('hidden');
                subscriptionModal?.classList.remove('flex');
            };
            const openModal = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
            const closeModal = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
            const resetForm = () => {
                form.action = "{{ route('adm.users.store') }}";
                methodInput.value = 'POST';
                title.textContent = 'Novo usuário';
                userIdInput.value = '';
                name.value = '';
                email.value = '';
                cpf.value = '';
                phone.value = '';
                customer.value = '';
                if (planSelect) {
                    planSelect.value = '';
                }
                password.value = '';
                password.required = true;
                isAdmin.checked = false;
                setRegisterButtonState({ hidden: true });
                toggleHint(registerAsaasCustomerHint);
            };
            const openCreateModal = () => {
                resetForm();
                openModal();
            };
            const openEditModal = (payload) => {
                form.action = `{{ url('/adm/users') }}/${payload.id}`;
                methodInput.value = 'PATCH';
                title.textContent = 'Editar usuário';
                userIdInput.value = payload.id;
                name.value = payload.name || '';
                email.value = payload.email || '';
                cpf.value = payload.cpf_cnpj || '';
                phone.value = payload.mobile_phone || '';
                customer.value = payload.customer_asaas_id || '';
                if (planSelect) {
                    planSelect.value = payload.plan_id ?? '';
                }
                isAdmin.checked = payload.is_admin;
                password.value = '';
                password.required = false;
                setRegisterButtonState({
                    hidden: false,
                    disabled: !!payload.customer_asaas_id,
                    label: payload.customer_asaas_id ? 'Registrado' : 'Registrar',
                });
                toggleHint(registerAsaasCustomerHint);
                openModal();
            };
            openBtn.addEventListener('click', openCreateModal);
            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
            closeViewButtons.forEach(btn => btn.addEventListener('click', closeViewModal));
            viewModal?.addEventListener('click', (event) => { if (event.target === viewModal) closeViewModal(); });
            closeSubscriptionButtons.forEach(btn => btn.addEventListener('click', closeSubscriptionModal));
            subscriptionModal?.addEventListener('click', (event) => {
                if (event.target === subscriptionModal) closeSubscriptionModal();
            });
            document.querySelectorAll('[data-open-edit]').forEach(button => {
                button.addEventListener('click', () => {
                    openEditModal({
                        id: button.dataset.id,
                        name: button.dataset.name,
                        email: button.dataset.email,
                        cpf_cnpj: button.dataset.cpf_cnpj,
                        mobile_phone: button.dataset.mobile_phone,
                        customer_asaas_id: button.dataset.customer_asaas_id,
                        plan_id: button.dataset.plan_id || '',
                        is_admin: button.dataset.is_admin === '1',
                    });
                });
            });

            document.querySelectorAll('[data-open-view]').forEach(button => {
                button.addEventListener('click', () => {
                    const raw = button.dataset.user;
                    if (!raw) return;
                    let payload = null;
                    try {
                        payload = JSON.parse(raw);
                    } catch (error) {
                        payload = null;
                    }
                    if (!payload) return;

                    currentViewUser = payload;
                    viewUserId.textContent = payload.id ?? '-';
                    viewUserName.textContent = payload.name ?? '-';
                    viewUserEmail.textContent = payload.email ?? '-';
                    viewUserCpf.textContent = payload.cpf_cnpj ?? '-';
                    viewUserPhone.textContent = payload.mobile_phone ?? '-';
                    viewUserCustomer.textContent = payload.customer_asaas_id ?? '-';
                    if (viewUserAsaasSubLink) {
                        if (payload.asaas_sub) {
                            viewUserAsaasSubLink.textContent = payload.asaas_sub;
                            viewUserAsaasSubLink.classList.remove('pointer-events-none', 'text-slate-400');
                        } else {
                            viewUserAsaasSubLink.textContent = '-';
                            viewUserAsaasSubLink.classList.add('pointer-events-none', 'text-slate-400');
                        }
                    }
                    viewUserAdmin.textContent = payload.is_admin ? 'Sim' : 'Não';
                    viewUserPlan.textContent = payload.plan_name ?? payload.plan_id ?? '-';
                    viewUserConnections.textContent = payload.conexoes_count ?? 0;
                    viewUserCreated.textContent = payload.created_at ?? '-';
                    viewUserUpdated.textContent = payload.updated_at ?? '-';
                    setCreateButtonState({
                        hidden: !!payload.customer_asaas_id,
                        disabled: false,
                        label: 'Criar Customer',
                    });
                    toggleHint(createAsaasCustomerHint);
                    const hasPlanPrice = payload.plan_price_cents !== null && payload.plan_price_cents !== undefined;
                    const subscriptionHint = !payload.customer_asaas_id
                        ? 'Customer Asaas obrigatório para criar assinatura.'
                        : (!hasPlanPrice ? 'Plano sem valor definido para criar assinatura.' : '');
                    setSubscriptionButtonState({
                        hidden: !!payload.asaas_sub,
                        disabled: !!subscriptionHint,
                        label: 'Criar assinatura',
                    });
                    toggleHint(createAsaasSubscriptionHint, subscriptionHint);

                    if (Array.isArray(payload.asaas_webhooks) && payload.asaas_webhooks.length) {
                        viewUserWebhooks.innerHTML = payload.asaas_webhooks.map(item => `
                            <tr>
                                <td class="px-3 py-2">${item.id ?? '-'}</td>
                                <td class="px-3 py-2">${item.event_type ?? '-'}</td>
                                <td class="px-3 py-2">${item.status ?? '-'}</td>
                                <td class="px-3 py-2">${item.value ?? '-'}</td>
                                <td class="px-3 py-2">${item.payment_id ?? '-'}</td>
                                <td class="px-3 py-2">${item.created_at ?? '-'}</td>
                            </tr>
                        `).join('');
                    } else {
                        viewUserWebhooks.innerHTML = '<tr><td colspan="6" class="px-3 py-2 text-center text-slate-400">Sem registros.</td></tr>';
                    }

                    openViewModal();
                });
            });

            createAsaasSubscriptionBtn?.addEventListener('click', () => {
                if (!currentViewUser) return;
                if (!currentViewUser.customer_asaas_id) {
                    toggleHint(createAsaasSubscriptionHint, 'Customer Asaas obrigatório para criar assinatura.');
                    return;
                }
                if (currentViewUser.plan_price_cents === null || currentViewUser.plan_price_cents === undefined) {
                    toggleHint(createAsaasSubscriptionHint, 'Plano sem valor definido para criar assinatura.');
                    return;
                }

                subscriptionForm?.reset();
                if (subscriptionCustomer) {
                    subscriptionCustomer.value = currentViewUser.customer_asaas_id || '';
                }
                if (subscriptionValue) {
                    subscriptionValue.value = currentViewUser.plan_price_cents ?? '';
                }
                toggleHint(subscriptionFormHint);
                openSubscriptionModal();
            });

            viewUserAsaasSubLink?.addEventListener('click', async (event) => {
                event.preventDefault();
                if (!currentViewUser?.asaas_sub) return;

                const url = subscriptionLinkUrlTemplate.replace('__ID__', currentViewUser.id);
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!response.ok || (data && data.error)) {
                    const message = data?.message || 'Falha ao obter link da cobrança.';
                    toggleHint(createAsaasSubscriptionHint, message);
                    return;
                }

                if (data?.url) {
                    window.open(data.url, '_blank');
                }
            });

            registerAsaasCustomerBtn?.addEventListener('click', async () => {
                const userId = userIdInput.value;
                if (!userId) return;
                if (!cpf.value) {
                    toggleHint(registerAsaasCustomerHint, 'CPF/CNPJ obrigatório para criar customer.');
                    return;
                }

                setRegisterButtonState({ hidden: false, disabled: true, label: 'Registrando...' });
                toggleHint(registerAsaasCustomerHint);

                const result = await requestAsaasCustomer(userId);
                if (!result.ok) {
                    toggleHint(registerAsaasCustomerHint, result.message);
                    setRegisterButtonState({ hidden: false, disabled: false, label: 'Registrar' });
                    return;
                }

                customer.value = result.customerId || '';
                setRegisterButtonState({ hidden: false, disabled: true, label: 'Registrado' });
            });

            createAsaasCustomerBtn?.addEventListener('click', async () => {
                if (!currentViewUser?.id) return;
                if (!currentViewUser.cpf_cnpj) {
                    toggleHint(createAsaasCustomerHint, 'CPF/CNPJ obrigatório para criar customer.');
                    return;
                }

                setCreateButtonState({ hidden: false, disabled: true, label: 'Criando...' });
                toggleHint(createAsaasCustomerHint);

                const result = await requestAsaasCustomer(currentViewUser.id);
                if (!result.ok) {
                    toggleHint(createAsaasCustomerHint, result.message);
                    setCreateButtonState({ hidden: false, disabled: false, label: 'Criar Customer' });
                    return;
                }

                viewUserCustomer.textContent = result.customerId || '-';
                currentViewUser.customer_asaas_id = result.customerId || null;
                setCreateButtonState({ hidden: true });
                const hasPlanPrice = currentViewUser.plan_price_cents !== null && currentViewUser.plan_price_cents !== undefined;
                const subscriptionHint = !currentViewUser.customer_asaas_id
                    ? 'Customer Asaas obrigatório para criar assinatura.'
                    : (!hasPlanPrice ? 'Plano sem valor definido para criar assinatura.' : '');
                setSubscriptionButtonState({
                    hidden: !!currentViewUser.asaas_sub,
                    disabled: !!subscriptionHint,
                    label: 'Criar assinatura',
                });
                toggleHint(createAsaasSubscriptionHint, subscriptionHint);
            });

            subscriptionForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!currentViewUser?.id) return;

                if (!subscriptionCustomer?.value) {
                    toggleHint(subscriptionFormHint, 'Customer Asaas obrigatório para criar assinatura.');
                    return;
                }
                if (!subscriptionNextDueDate?.value) {
                    toggleHint(subscriptionFormHint, 'Informe a data do próximo pagamento.');
                    return;
                }

                if (subscriptionSubmit) {
                    subscriptionSubmit.disabled = true;
                    subscriptionSubmit.textContent = 'Criando...';
                }
                toggleHint(subscriptionFormHint);

                const url = createSubscriptionUrlTemplate.replace('__ID__', currentViewUser.id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        next_due_date: subscriptionNextDueDate.value,
                        billing_type: subscriptionBillingType?.value || 'UNDEFINED',
                        cycle: subscriptionCycle?.value || 'MONTHLY',
                    }),
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!response.ok || (data && data.error)) {
                    const message = data?.message || 'Falha ao criar assinatura no Asaas.';
                    toggleHint(subscriptionFormHint, message);
                    if (subscriptionSubmit) {
                        subscriptionSubmit.disabled = false;
                        subscriptionSubmit.textContent = 'Criar';
                    }
                    return;
                }

                const subscriptionId = data?.subscription_id || '';
                currentViewUser.asaas_sub = subscriptionId;
                if (viewUserAsaasSubLink) {
                    if (subscriptionId) {
                        viewUserAsaasSubLink.textContent = subscriptionId;
                        viewUserAsaasSubLink.classList.remove('pointer-events-none', 'text-slate-400');
                    } else {
                        viewUserAsaasSubLink.textContent = '-';
                        viewUserAsaasSubLink.classList.add('pointer-events-none', 'text-slate-400');
                    }
                }
                setSubscriptionButtonState({ hidden: true });
                toggleHint(createAsaasSubscriptionHint);
                closeSubscriptionModal();
                if (subscriptionSubmit) {
                    subscriptionSubmit.disabled = false;
                    subscriptionSubmit.textContent = 'Criar';
                }
            });

            const previousMethod = @json(old('_method', 'POST'));
            const previousUserId = @json(old('user_id', ''));
            if (previousMethod === 'PATCH' && previousUserId) {
                openEditModal({
                    id: previousUserId,
                    name: @json(old('name', '')),
                    email: @json(old('email', '')),
                    cpf_cnpj: @json(old('cpf_cnpj', '')),
                    mobile_phone: @json(old('mobile_phone', '')),
                    customer_asaas_id: @json(old('customer_asaas_id', '')),
                    plan_id: @json(old('plan_id', '')),
                    is_admin: @json((bool) old('is_admin', false)),
                });
            } else if (@json($errors->any())) {
                openModal();
            }
        })();
    </script>
@endsection




