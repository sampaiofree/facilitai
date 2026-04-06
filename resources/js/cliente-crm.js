import Sortable from 'sortablejs';

const jsonHeaders = (csrfToken) => ({
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': csrfToken,
    'X-Requested-With': 'XMLHttpRequest',
});

const htmlToElement = (html) => {
    const template = document.createElement('template');
    template.innerHTML = html.trim();
    return template.content.firstElementChild;
};

const decodeLeadPayload = (payload) => {
    if (!payload) {
        return {};
    }

    const binary = window.atob(payload);
    const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
    const json = new TextDecoder('utf-8').decode(bytes);

    return JSON.parse(json);
};

document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const pipelineList = document.querySelector('[data-crm-pipelines-list]');
    const board = document.querySelector('[data-crm-board]');

    const pipelineModal = document.getElementById('crmPipelineModal');
    const pipelineForm = pipelineModal?.querySelector('[data-pipeline-form]') || null;
    const pipelineMethodField = pipelineModal?.querySelector('[data-pipeline-method]') || null;
    const pipelineNameField = document.getElementById('crmPipelineName');
    const pipelineTitle = pipelineModal?.querySelector('[data-pipeline-modal-title]') || null;
    const pipelineDescription = pipelineModal?.querySelector('[data-pipeline-modal-description]') || null;
    const pipelineSubmitLabel = pipelineModal?.querySelector('[data-pipeline-submit-label]') || null;
    const pipelineDeleteForm = pipelineModal?.querySelector('[data-pipeline-delete-form]') || null;

    const showModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const hideModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const openPipelineCreateModal = () => {
        if (!pipelineModal || !pipelineForm || !pipelineMethodField || !pipelineNameField) {
            return;
        }

        pipelineForm.action = pipelineForm.dataset.storeAction || pipelineForm.action;
        pipelineMethodField.value = 'POST';
        pipelineNameField.value = '';
        if (pipelineTitle) {
            pipelineTitle.textContent = 'Nova pipeline';
        }
        if (pipelineDescription) {
            pipelineDescription.textContent = 'Defina o nome da pipeline que será usada no CRM.';
        }
        if (pipelineSubmitLabel) {
            pipelineSubmitLabel.textContent = 'Criar pipeline';
        }
        if (pipelineDeleteForm) {
            pipelineDeleteForm.classList.add('hidden');
            pipelineDeleteForm.action = '#';
        }

        showModal(pipelineModal);
    };

    const openPipelineEditModal = (button) => {
        if (!pipelineModal || !pipelineForm || !pipelineMethodField || !pipelineNameField) {
            return;
        }

        pipelineForm.action = button.dataset.updateUrl || pipelineForm.action;
        pipelineMethodField.value = 'PATCH';
        pipelineNameField.value = button.dataset.pipelineName || '';
        if (pipelineTitle) {
            pipelineTitle.textContent = 'Editar pipeline';
        }
        if (pipelineDescription) {
            pipelineDescription.textContent = 'Atualize o nome da pipeline ou exclua se não quiser mais usá-la.';
        }
        if (pipelineSubmitLabel) {
            pipelineSubmitLabel.textContent = 'Salvar pipeline';
        }
        if (pipelineDeleteForm) {
            pipelineDeleteForm.classList.remove('hidden');
            pipelineDeleteForm.action = button.dataset.deleteUrl || '#';
        }

        showModal(pipelineModal);
    };

    document.querySelectorAll('[data-open-pipeline-create-modal]').forEach((button) => {
        button.addEventListener('click', openPipelineCreateModal);
    });

    document.querySelectorAll('[data-open-pipeline-edit-modal]').forEach((button) => {
        button.addEventListener('click', () => openPipelineEditModal(button));
    });

    document.querySelectorAll('[data-close-pipeline-modal]').forEach((button) => {
        button.addEventListener('click', () => hideModal(pipelineModal));
    });

    pipelineModal?.addEventListener('click', (event) => {
        if (event.target === pipelineModal) {
            hideModal(pipelineModal);
        }
    });

    if (pipelineForm) {
        pipelineForm.dataset.storeAction = pipelineForm.action;
    }

    if (pipelineList) {
        const pipelinesTrack = pipelineList.querySelector('[data-pipelines-track]');
        const reorderUrl = pipelineList.dataset.reorderUrl || '';

        if (pipelinesTrack && reorderUrl) {
            Sortable.create(pipelinesTrack, {
                animation: 180,
                handle: '[data-pipeline-handle]',
                draggable: '[data-pipeline-id]',
                forceFallback: true,
                fallbackOnBody: true,
                fallbackTolerance: 4,
                async onEnd() {
                    const pipelineIds = Array.from(pipelinesTrack.querySelectorAll('[data-pipeline-id]'))
                        .map((pipeline) => Number(pipeline.dataset.pipelineId))
                        .filter((value) => Number.isInteger(value) && value > 0);

                    try {
                        const response = await fetch(reorderUrl, {
                            method: 'PATCH',
                            headers: jsonHeaders(csrfToken),
                            body: JSON.stringify({ pipeline_ids: pipelineIds }),
                        });

                        if (!response.ok) {
                            throw new Error('pipeline-reorder-failed');
                        }
                    } catch (error) {
                        window.location.reload();
                    }
                },
            });
        }
    }

    if (!board) {
        return;
    }

    const columnModal = document.getElementById('crmColumnModal');
    const addLeadModal = document.getElementById('crmAddLeadModal');
    const leadModal = document.getElementById('crmLeadModal');
    let dragGuardUntil = 0;

    const openColumnModal = () => showModal(columnModal);
    const closeColumnModal = () => hideModal(columnModal);
    const closeAddLeadModal = () => hideModal(addLeadModal);
    const closeLeadModal = () => hideModal(leadModal);

    const addLeadModalFields = {
        columnName: document.querySelector('[data-add-lead-column-name]'),
        form: document.querySelector('[data-add-lead-search-form]'),
        nameInput: document.getElementById('crmAddLeadSearchName'),
        phoneInput: document.getElementById('crmAddLeadSearchPhone'),
        feedback: document.querySelector('[data-add-lead-search-feedback]'),
        results: document.querySelector('[data-add-lead-search-results]'),
        loadMore: document.querySelector('[data-add-lead-load-more]'),
        submit: document.querySelector('[data-add-lead-search-submit]'),
    };

    const leadModalFields = {
        name: document.getElementById('crmLeadModalName'),
        phone: document.getElementById('crmLeadModalPhone'),
        createdAt: document.getElementById('crmLeadModalCreatedAt'),
        bot: document.getElementById('crmLeadModalBot'),
        info: document.getElementById('crmLeadModalInfo'),
        tags: document.getElementById('crmLeadModalTags'),
    };

    const columnsTrack = board.querySelector('[data-columns-track]');
    const reorderUrl = board.dataset.reorderUrl || '';
    const moveUrlTemplate = board.dataset.moveUrlTemplate || '';

    const getColumnElementById = (columnId) => board.querySelector(`[data-column-id="${String(columnId)}"]`);

    const updateColumnEmptyState = (columnElement) => {
        if (!columnElement) {
            return;
        }

        const emptyState = columnElement.querySelector('[data-column-empty]');
        if (!emptyState) {
            return;
        }

        const hasCards = columnElement.querySelectorAll('[data-lead-card]').length > 0;
        emptyState.classList.toggle('hidden', hasCards);
    };

    const replaceColumnCards = (columnElement, html) => {
        if (!columnElement) {
            return;
        }

        const cardsContainer = columnElement.querySelector('[data-column-cards]');
        const emptyState = columnElement.querySelector('[data-column-empty]');
        if (!cardsContainer) {
            return;
        }

        cardsContainer.querySelectorAll('[data-lead-card]').forEach((card) => card.remove());

        const template = document.createElement('template');
        template.innerHTML = html || '';
        const elements = Array.from(template.content.children);

        elements.forEach((element) => {
            if (emptyState) {
                cardsContainer.insertBefore(element, emptyState);
                return;
            }

            cardsContainer.appendChild(element);
        });
    };

    const applyColumnPayload = (columnElement, payload) => {
        if (!columnElement) {
            return;
        }

        replaceColumnCards(columnElement, payload.html || '');

        const counter = columnElement.querySelector('[data-column-count]');
        if (counter) {
            counter.textContent = String(payload.count || 0);
        }

        const loadMoreButton = columnElement.querySelector('[data-load-more]');
        if (loadMoreButton) {
            loadMoreButton.dataset.offset = String(payload.next_offset || 0);
            loadMoreButton.classList.toggle('hidden', !payload.has_more);
            loadMoreButton.disabled = false;
            loadMoreButton.textContent = 'Carregar mais';
            loadMoreButton.dataset.loading = '0';
        }

        bindCardInteractions(columnElement);
        updateColumnEmptyState(columnElement);
    };

    const refreshColumn = async (columnId) => {
        if (!columnId) {
            return;
        }

        const columnElement = getColumnElementById(columnId);
        const loadMoreButton = columnElement?.querySelector('[data-load-more]');
        const url = loadMoreButton?.dataset.url;
        const query = loadMoreButton?.dataset.query || '';

        if (!columnElement || !url) {
            return;
        }

        const requestUrl = new URL(url, window.location.origin);
        requestUrl.searchParams.set('offset', '0');
        if (query !== '') {
            requestUrl.searchParams.set('q', query);
        }

        const response = await fetch(requestUrl.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('column-refresh-failed');
        }

        const payload = await response.json();
        applyColumnPayload(columnElement, payload);
    };

    const resetAddLeadSearchState = () => {
        if (addLeadModalFields.results) {
            addLeadModalFields.results.innerHTML = '';
        }

        if (addLeadModalFields.feedback) {
            addLeadModalFields.feedback.textContent = 'Informe ao menos 3 caracteres em nome ou 3 dígitos no telefone para buscar leads.';
        }

        if (addLeadModalFields.loadMore) {
            addLeadModalFields.loadMore.classList.add('hidden');
            addLeadModalFields.loadMore.dataset.offset = '0';
            addLeadModalFields.loadMore.dataset.loading = '0';
        }
    };

    const openAddLeadModal = (button) => {
        if (!addLeadModal) {
            return;
        }

        addLeadModal.dataset.columnId = button.dataset.columnId || '';
        addLeadModal.dataset.searchUrl = button.dataset.searchUrl || '';
        addLeadModal.dataset.addUrl = button.dataset.addUrl || '';

        if (addLeadModalFields.columnName) {
            addLeadModalFields.columnName.textContent = button.dataset.columnName || '-';
        }

        if (addLeadModalFields.nameInput) {
            addLeadModalFields.nameInput.value = '';
        }

        if (addLeadModalFields.phoneInput) {
            addLeadModalFields.phoneInput.value = '';
        }

        resetAddLeadSearchState();
        showModal(addLeadModal);
    };

    const openLeadModal = (lead) => {
        if (!leadModal) {
            return;
        }

        leadModalFields.name.textContent = lead.name || 'Sem nome';
        leadModalFields.phone.textContent = lead.phone || '-';
        leadModalFields.createdAt.textContent = lead.created_at || '-';
        leadModalFields.bot.textContent = lead.bot_enabled ? 'Ativado' : 'Desativado';
        leadModalFields.info.textContent = lead.info || 'Sem informações adicionais.';
        leadModalFields.tags.innerHTML = '';

        if (Array.isArray(lead.tags) && lead.tags.length > 0) {
            lead.tags.forEach((tag) => {
                const chip = document.createElement('span');
                chip.className = 'inline-flex rounded-full border border-slate-200 px-2.5 py-1 text-[10px] font-semibold text-slate-600';
                chip.textContent = tag.name;

                if (tag.color) {
                    chip.style.borderColor = `${tag.color}40`;
                    chip.style.backgroundColor = `${tag.color}18`;
                    chip.style.color = tag.color;
                }

                leadModalFields.tags.appendChild(chip);
            });
        } else {
            const empty = document.createElement('span');
            empty.className = 'text-xs text-slate-400';
            empty.textContent = 'Sem tags';
            leadModalFields.tags.appendChild(empty);
        }

        showModal(leadModal);
    };

    const bindCardInteractions = (scope = document) => {
        scope.querySelectorAll('[data-lead-card]').forEach((card) => {
            if (card.dataset.bound === '1') {
                return;
            }

            card.dataset.bound = '1';

            card.querySelectorAll('[data-open-lead-modal]').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (Date.now() < dragGuardUntil) {
                        return;
                    }

                    openLeadModal(decodeLeadPayload(card.dataset.lead || ''));
                });
            });

            card.querySelectorAll('[data-remove-lead-from-pipeline]').forEach((button) => {
                button.addEventListener('click', async (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (Date.now() < dragGuardUntil) {
                        return;
                    }

                    if (!window.confirm('Deseja remover este lead desta pipeline?')) {
                        return;
                    }

                    const removeUrl = button.dataset.removeUrl || '';
                    if (!removeUrl) {
                        return;
                    }

                    const currentColumnId = Number(card.closest('[data-column-id]')?.dataset.columnId || '0');
                    button.disabled = true;
                    const previousLabel = button.textContent;
                    button.textContent = 'Excluindo...';

                    try {
                        const response = await fetch(removeUrl, {
                            method: 'DELETE',
                            headers: jsonHeaders(csrfToken),
                        });

                        if (!response.ok) {
                            throw new Error('remove-lead-failed');
                        }

                        const payload = await response.json();
                        await refreshColumn(payload.previous_column_id || currentColumnId);
                    } catch (error) {
                        button.disabled = false;
                        button.textContent = previousLabel;
                        window.location.reload();
                    }
                });
            });
        });
    };

    const bindSearchResultButtons = (scope = document) => {
        scope.querySelectorAll('[data-add-lead-to-column]').forEach((button) => {
            if (button.dataset.bound === '1') {
                return;
            }

            button.dataset.bound = '1';
            button.addEventListener('click', async (event) => {
                event.preventDefault();

                if (button.disabled || button.dataset.loading === '1') {
                    return;
                }

                const addUrl = addLeadModal?.dataset.addUrl || '';
                const leadId = Number(button.dataset.leadId);
                if (!addUrl || !leadId) {
                    return;
                }

                button.dataset.loading = '1';
                button.disabled = true;
                const previousLabel = button.textContent;
                button.textContent = 'Adicionando...';

                try {
                    const response = await fetch(addUrl, {
                        method: 'POST',
                        headers: jsonHeaders(csrfToken),
                        body: JSON.stringify({ lead_id: leadId }),
                    });

                    if (!response.ok) {
                        throw new Error('add-lead-failed');
                    }

                    const payload = await response.json();
                    await Promise.all([
                        payload.previous_column_id ? refreshColumn(payload.previous_column_id) : Promise.resolve(),
                        payload.column_id ? refreshColumn(payload.column_id) : Promise.resolve(),
                    ]);
                    closeAddLeadModal();
                } catch (error) {
                    button.disabled = false;
                    button.textContent = previousLabel;
                    button.dataset.loading = '0';
                }
            });
        });
    };

    const performLeadSearch = async ({ reset = true } = {}) => {
        const searchUrl = addLeadModal?.dataset.searchUrl || '';
        const name = addLeadModalFields.nameInput?.value?.trim() || '';
        const phone = addLeadModalFields.phoneInput?.value?.trim() || '';
        const digits = phone.replace(/\D/g, '');
        const searchReady = name.length >= 3 || digits.length >= 3;
        const loadMoreButton = addLeadModalFields.loadMore;

        if (!searchReady) {
            resetAddLeadSearchState();
            return;
        }

        if (!searchUrl) {
            return;
        }

        const offset = reset ? 0 : Number(loadMoreButton?.dataset.offset || '0');
        const requestUrl = new URL(searchUrl, window.location.origin);
        requestUrl.searchParams.set('name', name);
        requestUrl.searchParams.set('phone', phone);
        requestUrl.searchParams.set('offset', String(offset));

        if (addLeadModalFields.submit) {
            addLeadModalFields.submit.disabled = true;
            addLeadModalFields.submit.textContent = 'Buscando...';
        }

        if (loadMoreButton && !reset) {
            loadMoreButton.disabled = true;
            loadMoreButton.textContent = 'Carregando...';
            loadMoreButton.dataset.loading = '1';
        }

        if (addLeadModalFields.feedback) {
            addLeadModalFields.feedback.textContent = 'Buscando leads...';
        }

        try {
            const response = await fetch(requestUrl.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('search-leads-failed');
            }

            const payload = await response.json();
            if (addLeadModalFields.results) {
                if (reset) {
                    addLeadModalFields.results.innerHTML = payload.html || '';
                } else {
                    const template = document.createElement('template');
                    template.innerHTML = payload.html || '';
                    addLeadModalFields.results.appendChild(template.content);
                }
                bindSearchResultButtons(addLeadModalFields.results);
            }

            if (addLeadModalFields.feedback) {
                addLeadModalFields.feedback.textContent = payload.count > 0
                    ? `${payload.count} lead(s) encontrado(s).`
                    : 'Nenhum lead encontrado para os filtros informados.';
            }

            if (loadMoreButton) {
                loadMoreButton.dataset.offset = String(payload.next_offset || 0);
                loadMoreButton.classList.toggle('hidden', !payload.has_more);
                loadMoreButton.disabled = false;
                loadMoreButton.textContent = 'Carregar mais';
                loadMoreButton.dataset.loading = '0';
            }
        } catch (error) {
            if (addLeadModalFields.feedback) {
                addLeadModalFields.feedback.textContent = 'Não foi possível buscar os leads agora.';
            }

            if (loadMoreButton) {
                loadMoreButton.disabled = false;
                loadMoreButton.textContent = 'Carregar mais';
                loadMoreButton.dataset.loading = '0';
            }
        } finally {
            if (addLeadModalFields.submit) {
                addLeadModalFields.submit.disabled = false;
                addLeadModalFields.submit.textContent = 'Buscar';
            }
        }
    };

    const bindLoadMoreButtons = (scope = document) => {
        scope.querySelectorAll('[data-load-more]').forEach((button) => {
            if (button.dataset.bound === '1') {
                return;
            }

            button.dataset.bound = '1';
            button.addEventListener('click', async () => {
                if (button.dataset.loading === '1') {
                    return;
                }

                const columnElement = button.closest('[data-column-id]');
                const cardsContainer = columnElement?.querySelector('[data-column-cards]');
                const url = button.dataset.url;
                const offset = button.dataset.offset || '0';
                const query = button.dataset.query || '';

                if (!columnElement || !cardsContainer || !url) {
                    return;
                }

                button.dataset.loading = '1';
                button.disabled = true;
                button.textContent = 'Carregando...';

                try {
                    const requestUrl = new URL(url, window.location.origin);
                    requestUrl.searchParams.set('offset', offset);
                    if (query !== '') {
                        requestUrl.searchParams.set('q', query);
                    }

                    const response = await fetch(requestUrl.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('load-more-failed');
                    }

                    const payload = await response.json();
                    const template = document.createElement('template');
                    template.innerHTML = payload.html || '';
                    cardsContainer.appendChild(template.content);
                    bindCardInteractions(cardsContainer);

                    button.dataset.offset = String(payload.next_offset || 0);
                    button.classList.toggle('hidden', !payload.has_more);
                    button.disabled = false;
                    button.textContent = 'Carregar mais';
                    button.dataset.loading = '0';
                    updateColumnEmptyState(columnElement);
                } catch (error) {
                    button.disabled = false;
                    button.textContent = 'Carregar mais';
                    button.dataset.loading = '0';
                    window.location.reload();
                }
            });
        });
    };

    document.querySelectorAll('[data-open-column-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.disabled) {
                return;
            }

            openColumnModal();
        });
    });

    document.querySelectorAll('[data-close-column-modal]').forEach((button) => {
        button.addEventListener('click', closeColumnModal);
    });

    document.querySelectorAll('[data-open-add-lead-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.disabled) {
                return;
            }

            openAddLeadModal(button);
        });
    });

    document.querySelectorAll('[data-close-add-lead-modal]').forEach((button) => {
        button.addEventListener('click', closeAddLeadModal);
    });

    document.querySelectorAll('[data-close-lead-modal]').forEach((button) => {
        button.addEventListener('click', closeLeadModal);
    });

    columnModal?.addEventListener('click', (event) => {
        if (event.target === columnModal) {
            closeColumnModal();
        }
    });

    addLeadModal?.addEventListener('click', (event) => {
        if (event.target === addLeadModal) {
            closeAddLeadModal();
        }
    });

    leadModal?.addEventListener('click', (event) => {
        if (event.target === leadModal) {
            closeLeadModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeColumnModal();
            closeAddLeadModal();
            closeLeadModal();
        }
    });

    addLeadModalFields.form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        await performLeadSearch({ reset: true });
    });

    addLeadModalFields.loadMore?.addEventListener('click', async () => {
        if (addLeadModalFields.loadMore?.dataset.loading === '1') {
            return;
        }

        await performLeadSearch({ reset: false });
    });

    bindCardInteractions();
    bindLoadMoreButtons();

    if (columnsTrack && reorderUrl) {
        Sortable.create(columnsTrack, {
            animation: 180,
            handle: '[data-column-handle]',
            draggable: '[data-column-id]',
            forceFallback: true,
            fallbackOnBody: true,
            fallbackTolerance: 4,
            async onEnd() {
                const columnIds = Array.from(columnsTrack.querySelectorAll('[data-column-id]'))
                    .map((column) => Number(column.dataset.columnId))
                    .filter((value) => Number.isInteger(value) && value > 0);

                try {
                    const response = await fetch(reorderUrl, {
                        method: 'PATCH',
                        headers: jsonHeaders(csrfToken),
                        body: JSON.stringify({ column_ids: columnIds }),
                    });

                    if (!response.ok) {
                        throw new Error('column-reorder-failed');
                    }

                    window.location.reload();
                } catch (error) {
                    window.location.reload();
                }
            },
        });
    }

    board.querySelectorAll('[data-column-cards]').forEach((cardsContainer) => {
        Sortable.create(cardsContainer, {
            group: {
                name: 'cliente-crm-leads',
                pull: true,
                put: true,
            },
            animation: 180,
            draggable: '[data-lead-card]',
            filter: '[data-open-lead-modal], [data-remove-lead-from-pipeline]',
            preventOnFilter: false,
            fallbackTolerance: 4,
            emptyInsertThreshold: 16,
            onStart() {
                dragGuardUntil = Date.now() + 400;
            },
            async onEnd(event) {
                dragGuardUntil = Date.now() + 400;

                const item = event.item;
                const sourceColumn = event.from.closest('[data-column-id]');
                const targetColumn = event.to.closest('[data-column-id]');

                if (!item || !sourceColumn || !targetColumn) {
                    window.location.reload();
                    return;
                }

                if (sourceColumn.dataset.columnId === targetColumn.dataset.columnId) {
                    return;
                }

                const leadId = Number(item.dataset.leadId);
                const targetColumnId = Number(targetColumn.dataset.columnId);

                if (!leadId || !targetColumnId || !moveUrlTemplate) {
                    window.location.reload();
                    return;
                }

                try {
                    const response = await fetch(moveUrlTemplate.replace('__LEAD_ID__', String(leadId)), {
                        method: 'PATCH',
                        headers: jsonHeaders(csrfToken),
                        body: JSON.stringify({ target_column_id: targetColumnId }),
                    });

                    if (!response.ok) {
                        throw new Error('move-lead-failed');
                    }

                    const payload = await response.json();
                    await Promise.all([
                        payload.previous_column_id ? refreshColumn(payload.previous_column_id) : Promise.resolve(),
                        payload.column_id ? refreshColumn(payload.column_id) : Promise.resolve(),
                    ]);
                } catch (error) {
                    window.location.reload();
                }
            },
        });
    });
});
