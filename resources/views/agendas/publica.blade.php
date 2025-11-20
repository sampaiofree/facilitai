<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $agenda->titulo ?? 'Agendamento' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gradient-to-br from-purple-50 to-blue-50 font-sans antialiased">

<div x-data="scheduler()" class="min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-2xl p-8">

       <!-- CABEÇALHO -->
<div class="text-center mb-10">
    {{-- Título da agenda --}}
    <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-gray-900 leading-tight mb-3">
        <span class="bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
            {{ $agenda->titulo }}
        </span>
    </h1>

    {{-- Descrição (se existir) --}}
    @if(!empty($agenda->descricao))
        <p class="text-gray-700 text-lg sm:text-xl max-w-2xl mx-auto mb-4">
            {{ $agenda->descricao }}
        </p>
    @endif

    {{-- Subtítulo simpático --}}
    <div class="inline-flex items-center gap-2 bg-purple-100 text-purple-800 px-5 py-2 rounded-full font-semibold text-sm mb-3 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m2 8H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2z" />
        </svg>
        Agende seu horário agora mesmo
    </div>

    {{-- Frase orientativa --}}
    <p class="text-gray-600 text-base sm:text-lg">
        É rápido e fácil — siga os passos abaixo 👇
    </p>
</div>


        <!-- ETAPAS -->
       <div class="flex justify-center mb-8">
            <div class="flex items-center gap-2 sm:gap-4 flex-wrap justify-center">
                <template x-for="s in [1, 2, 3, 4]" :key="s">
                    <div class="flex items-center">
                        <div :class="{'bg-purple-600 text-white': step >= s, 'bg-gray-200 text-gray-500': step < s}"
                            class="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center font-bold text-base sm:text-lg transition-colors">
                            <span x-text="s"></span>
                        </div>
                        <template x-if="s < 4">
                            <div :class="{'bg-purple-600': step > s, 'bg-gray-200': step <= s}"
                                class="w-8 sm:w-12 md:w-16 h-0.5 sm:h-1 transition-colors"></div>
                        </template>
                    </div>
                </template>
            </div>
        </div>


        <!-- CONTEÚDO -->
        <div>

            <!-- CONTEÚDO PRINCIPAL (visível apenas enquanto não enviado) -->
            <template x-if="!submitted">
                <div>
                    <!-- STEP 1 -->
                    <template x-if="step===1">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10m2 8H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2z"/></svg>
                                1. Escolha o Mês
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <template x-for="(month,idx) in months" :key="idx">
                                    <button
                                        @click="selectMonth(idx)"
                                        class="bg-gradient-to-br from-purple-100 to-blue-100 hover:from-purple-200 hover:to-blue-200 
                                            p-8 rounded-2xl border-2 border-transparent hover:border-purple-400 transition-all transform hover:scale-105">
                                        <div class="text-4xl mb-3">📅</div>
                                        <div class="text-xl font-semibold text-gray-800" x-text="month.name"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- STEP 2 -->
                    <template x-if="step===2">
                        <div>
                            <button @click="step=1" class="flex items-center gap-2 text-purple-600 hover:text-purple-700 mb-6 font-semibold">
                                ← Voltar
                            </button>
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                                2. Escolha o Dia em <span x-text="months[selectedMonth]?.name"></span>
                            </h2>

                            <div class="grid grid-cols-7 gap-2">
                                <template x-for="day in weekDays" :key="day">
                                    <div class="text-center font-semibold text-gray-600 py-2" x-text="day"></div>
                                </template>

                                <template x-for="dayObj in monthGrid" :key="dayObj.key">
                                    <button
                                        @click="!dayObj.disabled && selectDay(dayObj.number)"
                                        :disabled="dayObj.disabled"
                                        :class="dayObj.disabled 
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                                            : 'bg-gradient-to-br from-purple-100 to-blue-100 hover:from-purple-200 hover:to-blue-200 text-gray-800 hover:scale-110'"
                                        class="aspect-square rounded-xl text-lg font-semibold transition-all flex items-center justify-center">
                                        <span x-text="dayObj.number || ''"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- STEP 3 -->
                    <template x-if="step===3">
                        <div>
                            <button @click="step=2" class="flex items-center gap-2 text-purple-600 hover:text-purple-700 mb-6 font-semibold">
                                ← Voltar
                            </button>
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                                3. Escolha o Horário
                            </h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <template x-if="horarios.length > 0">
                                    <template x-for="slot in horarios" :key="slot.id">
                                        <button 
                                            @click="selectTime(slot)"
                                            class="p-6 rounded-2xl bg-gradient-to-br from-purple-100 to-blue-100 hover:from-purple-200 hover:to-blue-200
                                                text-gray-800 font-bold transition-all transform hover:scale-105 border-2 border-transparent hover:border-purple-400">
                                            <div class="text-3xl mb-2">🕐</div>
                                            <div class="text-xl" x-text="slot.time"></div>
                                        </button>
                                    </template>
                                </template>

                                <template x-if="horarios.length === 0">
                                    <div class="col-span-3 text-center p-8 text-gray-600">
                                        <p class="text-lg">Nenhum horário disponível neste dia.</p>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </template>

                    <!-- STEP 4 -->
                    <template x-if="step===4">
                        <div>
                            <button @click="step=3" class="flex items-center gap-2 text-purple-600 hover:text-purple-700 mb-6 font-semibold">
                                ← Voltar
                            </button>
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                                4. Suas Informações
                            </h2>

                            <div class="bg-purple-50 rounded-2xl p-6 mb-8">
                                <p class="text-gray-700 text-lg"><strong>Data:</strong> <span x-text="selectedDayLabel"></span></p>
                                <p class="text-gray-700 text-lg"><strong>Horário:</strong> <span x-text="selectedTime?.time"></span></p>

                            </div>

                            <div class="space-y-6">
                                <input x-model="form.name" type="text" placeholder="Seu nome completo"
                                    class="w-full p-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 text-lg" />
                                <input x-model="form.phone" type="tel" placeholder="(00) 00000-0000"
                                    class="w-full p-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 text-lg" />
                                <textarea x-model="form.message" placeholder="Mensagem (opcional)"
                                        class="w-full p-4 border-2 border-gray-200 rounded-xl focus:border-purple-500 text-lg h-32 resize-none"></textarea>

                                <button
                                    @click="submitBooking"
                                    :disabled="!form.name || !form.phone || submitting"
                                    class="w-full py-5 rounded-xl font-bold text-xl transition-all transform shadow-lg flex items-center justify-center gap-3"
                                    :class="{
                                        'bg-gray-300 text-gray-500 cursor-not-allowed': !form.name || !form.phone || submitting,
                                        'bg-gradient-to-r from-purple-600 to-blue-600 text-white hover:scale-105': form.name && form.phone && !submitting
                                    }"
                                >
                                    <template x-if="submitting">
                                        <span class="flex items-center gap-2">
                                            <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                            </svg>
                                            Agendando...
                                        </span>
                                    </template>

                                    <template x-if="!submitting">
                                        <span>Confirmar Agendamento 🎉</span>
                                    </template>
                                </button>

                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- SUCESSO -->
            <template x-if="submitted">
                <div x-transition.opacity.duration.500ms class="text-center p-8">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        ✅
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Agendamento Confirmado!</h2>
                    <p class="text-gray-600 mb-6">Aguardamos sua visita! 😊</p>
                    <button @click="resetForm"
                            class="w-full bg-purple-600 text-white py-4 rounded-xl font-semibold hover:bg-purple-700 transition-colors text-lg">
                        Fazer Novo Agendamento
                    </button>
                </div>
            </template>

        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
Alpine.data('scheduler', () => ({
    step: 1,
    months: @json($months),
    weekDays: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
    selectedMonth: null,
    selectedDay: null,
    selectedTime: null,
    submitted: false,
    form: { name: '', phone: '', message: '' },
    horarios: [],
    submitting: false,

    get monthGrid() {
        if (this.selectedMonth === null) return [];
        const m = this.months[this.selectedMonth];
        const days = [];
        const first = new Date(m.year, m.id - 1, 1);
        const offset = first.getDay(); // domingo=0
        const last = new Date(m.year, m.id, 0).getDate();
        const available = new Set(m.days);

        for (let i=0; i<offset; i++) days.push({key:'empty-'+i, number:'', disabled:true});
        for (let d=1; d<=last; d++) {
            const disabled = !available.has(d);
            days.push({key:`d-${d}`, number:d, disabled});
        }
        return days;
    },
    get selectedDayLabel() {
        const m = this.months[this.selectedMonth];
        return `${this.selectedDay} de ${m.name}`;
    },

    selectMonth(i){ this.selectedMonth=i; this.step=2; },
    async selectDay(d) {
        this.selectedDay = d;
        this.selectedTime = null;
        this.horarios = [];
        this.step = 3;

        const m = this.months[this.selectedMonth];
        const date = `${m.year}-${String(m.id).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

        try {
            const response = await fetch(`/api/agendamento/{{ $agenda->slug }}/horarios?date=${date}`);
            const data = await response.json();
            this.horarios = data.horarios || [];
        } catch (e) {
            console.error('Erro ao buscar horários:', e);
            this.horarios = [];
        }
    },

    selectTime(slot){ this.selectedTime=slot; this.step=4; },
    async submitBooking() {
        if (!this.form.name || !this.form.phone || !this.selectedTime?.id) {
            alert('Preencha todas as informações antes de confirmar.');
            return;
        }

        if (this.submitting) return; // evita duplo clique

        this.submitting = true;

        try {
            const response = await fetch(`/api/agendamento/{{ $agenda->slug }}/agendar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    disponibilidade_id: this.selectedTime.id,
                    name: this.form.name,
                    phone: this.form.phone,
                    message: this.form.message
                })
            });

            const data = await response.json();

            if (!response.ok) throw new Error(data.message || 'Erro ao realizar agendamento.');

            // sucesso ✅
            this.submitted = true;
            this.horarios = [];
        } catch (error) {
            console.error('Erro no agendamento:', error);
            alert(error.message || 'Erro inesperado. Tente novamente.');
        } finally {
            this.submitting = false; // libera o botão novamente
        }
    },


    resetForm() {
        this.step = 1;
        this.selectedMonth = null;
        this.selectedDay = null;
        this.selectedTime = null;
        this.form = { name: '', phone: '', message: '' };
        this.submitted = false;
        this.horarios = [];
    }

}));
});
</script>

</body>
</html>
