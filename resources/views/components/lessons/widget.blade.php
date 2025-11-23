<div id="lessons-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300" style="z-index: 998;"></div>
<div id="lesson-modal-overlay" class="fixed inset-0 bg-black/70 opacity-0 pointer-events-none transition-opacity duration-300" style="z-index: 1001;"></div>
<div id="lesson-modal" class="fixed inset-0 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300" style="z-index: 1002;">
    <div class="relative w-full h-full sm:h-[90vh] sm:max-w-5xl bg-white rounded-none sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <button id="lesson-modal-close" class="absolute top-3 right-3 text-white bg-black/60 hover:bg-black/80 rounded-full w-9 h-9 flex items-center justify-center z-10">
            ✕
        </button>
        <div class="flex-1 bg-black">
            <iframe id="lesson-modal-iframe" src="" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
        </div>
        <div class="p-6 overflow-y-auto max-h-[40vh] sm:max-h-[35vh]">
            <h3 id="lesson-modal-title" class="text-xl font-semibold text-gray-900 mb-3"></h3>
            <div id="lesson-modal-body" class="text-sm text-gray-700 leading-relaxed"></div>
        </div>
    </div>
</div>

<div id="lessons-drawer" class="fixed top-0 right-0 w-full sm:w-[360px] h-full bg-white shadow-2xl translate-x-full transition-transform duration-300 flex flex-col" style="z-index: 999;">
    <div class="p-4 border-b flex items-center justify-between">
        <div>
            <p class="text-xs uppercase tracking-widest text-indigo-500 font-semibold">Ajuda</p>
            <h3 class="text-lg font-semibold text-gray-900">Aulas recomendadas</h3>
        </div>
        <button id="lessons-close" class="text-gray-500 hover:text-gray-700">
            ✕
        </button>
    </div>
    <div class="flex-1 overflow-y-auto p-4 space-y-3">
        <div id="lessons-loading" class="flex items-center gap-2 text-sm text-gray-600">
            <span class="h-2 w-2 rounded-full bg-indigo-500 animate-ping"></span>
            Carregando aulas...
        </div>
        <div id="lessons-error" class="hidden text-sm text-red-600 bg-red-50 border border-red-100 p-3 rounded-lg"></div>
        <div id="lessons-empty" class="hidden text-sm text-gray-600 bg-gray-50 border border-gray-100 p-3 rounded-lg">
            Nenhuma aula disponível para esta página.
        </div>
        <div id="lessons-list" class="space-y-6"></div>
    </div>
</div>



<button class="lessons-help-trigger fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 px-5 py-3 text-white shadow-lg shadow-indigo-500/40 transition-all duration-300 hover:scale-105 hover:shadow-indigo-500/60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
    <!-- Ícone de Play Circular (Mais amigável) -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm14.024-.983a1.125 1.125 0 010 1.966l-5.603 3.113A1.125 1.125 0 019 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113z" clip-rule="evenodd" />
    </svg>
    <span class="font-bold tracking-wide">Vídeo Aulas</span>
</button>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const overlay = document.getElementById('lessons-overlay');
        const drawer = document.getElementById('lessons-drawer');
        const triggers = document.querySelectorAll('.lessons-help-trigger');
        const loadingEl = document.getElementById('lessons-loading');
        const listEl = document.getElementById('lessons-list');
        const emptyEl = document.getElementById('lessons-empty');
        const errorEl = document.getElementById('lessons-error');
        const closeEl = document.getElementById('lessons-close');
        const modalOverlay = document.getElementById('lesson-modal-overlay');
        const modal = document.getElementById('lesson-modal');
        const modalClose = document.getElementById('lesson-modal-close');
        const modalTitle = document.getElementById('lesson-modal-title');
        const modalBody = document.getElementById('lesson-modal-body');
        const modalIframe = document.getElementById('lesson-modal-iframe');

        let loaded = false;
        let isOpen = false;
        let lessonsCache = [];

        const closeDrawer = () => {
            isOpen = false;
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100');
            drawer.classList.add('translate-x-full');
        };

        const openDrawer = async () => {
            isOpen = true;
            overlay.classList.remove('pointer-events-none');
            overlay.classList.remove('opacity-0');
            overlay.classList.add('opacity-100');
            drawer.classList.remove('translate-x-full');

            if (!loaded) {
                await fetchLessons();
                loaded = true;
            }
        };

        const openModal = (lesson) => {
            modalTitle.textContent = lesson.title;
            modalBody.innerHTML = lesson.support_html || '';
            modalIframe.src = lesson.embed_url;

            modalOverlay.classList.remove('pointer-events-none', 'opacity-0');
            modalOverlay.classList.add('opacity-100');
            modal.classList.remove('pointer-events-none', 'opacity-0');
            modal.classList.add('opacity-100');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = () => {
            modalIframe.src = '';
            modalOverlay.classList.add('pointer-events-none', 'opacity-0');
            modalOverlay.classList.remove('opacity-100');
            modal.classList.add('pointer-events-none', 'opacity-0');
            modal.classList.remove('opacity-100');
            document.body.classList.remove('overflow-hidden');
        };

        const renderLessons = (lessons) => {
            listEl.innerHTML = '';
            lessonsCache = lessons;

            lessons.forEach((lesson) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left px-4 py-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition flex items-center justify-between';
                button.innerHTML = `
                    <span class="text-sm font-semibold text-gray-900">${lesson.title}</span>
                    <span class="text-xs text-indigo-600 font-semibold">Ver aula</span>
                `;

                button.addEventListener('click', () => openModal(lesson));
                listEl.appendChild(button);
            });
        };

        const fetchLessons = async () => {
            loadingEl.classList.remove('hidden');
            emptyEl.classList.add('hidden');
            errorEl.classList.add('hidden');
            listEl.innerHTML = '';

            const path = window.location.pathname || '/';
            const locale = document.documentElement.lang || 'pt-BR';

            try {
                const response = await fetch(`/lessons/for-page?path=${encodeURIComponent(path)}&locale=${encodeURIComponent(locale)}`);
                if (!response.ok) {
                    throw new Error('Erro ao carregar aulas.');
                }

                const data = await response.json();
                const lessons = data.lessons || [];

                if (!lessons.length) {
                    emptyEl.classList.remove('hidden');
                    return;
                }

                renderLessons(lessons);
            } catch (error) {
                errorEl.textContent = error.message || 'Erro ao carregar aulas.';
                errorEl.classList.remove('hidden');
            } finally {
                loadingEl.classList.add('hidden');
            }
        };

        triggers.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                if (isOpen) {
                    closeDrawer();
                } else {
                    openDrawer();
                }
            });
        });

        overlay?.addEventListener('click', closeDrawer);
        closeEl?.addEventListener('click', closeDrawer);
        modalOverlay?.addEventListener('click', closeModal);
        modalClose?.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (isOpen && event.key === 'Escape') {
                closeDrawer();
            }
            if (!modal.classList.contains('pointer-events-none') && event.key === 'Escape') {
                closeModal();
            }
        });
    });
</script>
