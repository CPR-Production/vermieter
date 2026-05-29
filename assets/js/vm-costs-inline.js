document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('#vm-costs-list-table');
    if (!table) return;

    let activeRow = null;

    const moneyToGerman = (value) => {
        const num = Number(value || 0);
        return num.toFixed(2).replace('.', ',') + ' €';
    };

    const escapeHtml = (str) => {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '—';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}.${parts[1]}.${parts[0]}`;
    };

    const closeEditor = () => {
        if (!activeRow) return;
        const editRow = activeRow.nextElementSibling;
        if (editRow && editRow.classList.contains('vm-cost-edit-row')) {
            editRow.style.display = 'none';
            editRow.querySelector('.vm-cost-inline-editor').innerHTML = '';
        }
        activeRow.classList.remove('vm-row-editing');
        activeRow = null;
    };

    const buildEditorHtml = (row, categories) => {
        const categoryId = row.dataset.categoryId || '';
        const options = categories.map((cat) => {
            const selected = String(cat.id) === String(categoryId) ? 'selected' : '';
            return `<option value="${cat.id}" ${selected}>${escapeHtml(cat.name)}</option>`;
        }).join('');

        return `
            <form class="vm-inline-cost-form">
                <div style="display:grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap:12px; align-items:end;">
                    <p>
                        <label>Kategorie</label><br>
                        <select name="property_cost_category_id" required style="width:100%;">
                            <option value="">Bitte wählen</option>
                            ${options}
                        </select>
                    </p>
                    <p>
                        <label>Rechnung / Bezeichnung</label><br>
                        <input type="text" name="name" value="${escapeHtml(row.dataset.name || '')}" required style="width:100%;">
                    </p>
                    <p>
                        <label>Betrag</label><br>
                        <input type="text" name="betrag" value="${escapeHtml((row.dataset.betrag || '0').replace('.', ','))}" required style="width:100%;">
                    </p>
                    <p>
                        <label>Steuerlicher Ausweis</label><br>
                        <select name="tax_deductible_type" style="width:100%;">
                            <option value="none" ${row.dataset.taxDeductibleType === 'none' || !row.dataset.taxDeductibleType ? 'selected' : ''}>nicht ausweisen</option>
                            <option value="haushaltsnah" ${row.dataset.taxDeductibleType === 'haushaltsnah' ? 'selected' : ''}>haushaltsnah</option>
                            <option value="handwerker" ${row.dataset.taxDeductibleType === 'handwerker' ? 'selected' : ''}>Handwerker</option>
                        </select>
                    </p>
                    <p>
                        <label>davon Lohn/Arbeit</label><br>
                        <input type="text" name="tax_deductible_amount" value="${escapeHtml((row.dataset.taxDeductibleAmount || '0').replace('.', ','))}" style="width:100%;">
                    </p>
                    <p>
                        <label>Rechnungsdatum</label><br>
                        <input type="date" name="invoice_date" value="${escapeHtml(row.dataset.invoiceDate || '')}" required style="width:100%;">
                    </p>
                    <p>
                        <label>Zeitraum von</label><br>
                        <input type="date" name="period_start" value="${escapeHtml(row.dataset.periodStart || '')}" required style="width:100%;">
                    </p>
                    <p>
                        <label>Zeitraum bis</label><br>
                        <input type="date" name="period_end" value="${escapeHtml(row.dataset.periodEnd || '')}" required style="width:100%;">
                    </p>
                    <p>
                        <label>Jahr</label><br>
                        <input type="number" name="period_year" value="${escapeHtml(row.dataset.periodYear || '')}" required style="width:100%;">
                    </p>
                </div>
                <p style="margin-top:12px;">
                    <button type="submit" class="button button-primary vm-inline-save-cost">
                        <i class="fa-solid fa-save"></i> ${vmCostsInline.labels.save}
                    </button>
                    <button type="button" class="button vm-inline-cancel-cost">
                        ${vmCostsInline.labels.cancel}
                    </button>
                </p>
                <div class="vm-inline-cost-message" style="margin-top:8px;"></div>
            </form>
        `;
    };

    const updateDisplayRow = (row, item) => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 7) return;

        row.dataset.categoryId = item.property_cost_category_id;
        row.dataset.categoryName = item.category_name || '';
        row.dataset.name = item.name || '';
        row.dataset.betrag = String(item.betrag || 0);
        row.dataset.invoiceDate = item.invoice_date || '';
        row.dataset.periodStart = item.period_start || '';
        row.dataset.periodEnd = item.period_end || '';
        row.dataset.periodYear = String(item.period_year || '');
        row.dataset.isRecurring = item.is_recurring ? '1' : '0';
        row.dataset.taxDeductibleType = item.tax_deductible_type || 'none';
        row.dataset.taxDeductibleAmount = String(item.tax_deductible_amount || 0);

        cells[0].textContent = item.category_name || '—';
        cells[1].textContent = item.name || '';
        cells[2].textContent = item.betrag_formatted || moneyToGerman(item.betrag);
        let taxLabel = '—';
        if (item.tax_deductible_type === 'haushaltsnah') taxLabel = 'haushaltsnah';
        if (item.tax_deductible_type === 'handwerker') taxLabel = 'Handwerker';
        cells[3].innerHTML = escapeHtml(taxLabel) + (Number(item.tax_deductible_amount || 0) > 0 ? '<br><small>' + escapeHtml(moneyToGerman(item.tax_deductible_amount)) + '</small>' : '');
        cells[4].textContent = item.invoice_date_formatted || formatDate(item.invoice_date);
        cells[5].innerHTML = `${escapeHtml(item.period_start_formatted || formatDate(item.period_start))}<br>bis ${escapeHtml(item.period_end_formatted || formatDate(item.period_end))}`;
        cells[6].textContent = item.is_recurring_label || (item.is_recurring ? 'Ja' : 'Nein');
    };

    table.addEventListener('click', function (event) {
        const editButton = event.target.closest('.vm-inline-edit-cost');
        const deleteButton = event.target.closest('.vm-inline-delete-cost');
        const cancelButton = event.target.closest('.vm-inline-cancel-cost');

        if (cancelButton) {
            closeEditor();
            return;
        }

        if (editButton) {
            const row = editButton.closest('.vm-cost-row');
            if (!row) return;

            if (activeRow && activeRow !== row) {
                closeEditor();
            }

            const editRow = row.nextElementSibling;
            if (!editRow || !editRow.classList.contains('vm-cost-edit-row')) return;

            const editor = editRow.querySelector('.vm-cost-inline-editor');
            const categories = JSON.parse(editor.dataset.categories || '[]');

            if (activeRow === row) {
                closeEditor();
                return;
            }

            editor.innerHTML = buildEditorHtml(row, categories);
            editRow.style.display = '';
            row.classList.add('vm-row-editing');
            activeRow = row;

            const firstInput = editor.querySelector('select, input');
            if (firstInput) firstInput.focus();
            return;
        }

        if (deleteButton) {
            const row = deleteButton.closest('.vm-cost-row');
            if (!row) return;

            if (!window.confirm(vmCostsInline.labels.confirm)) {
                return;
            }

            const body = new URLSearchParams();
            body.append('action', 'vm_delete_cost_inline');
            body.append('nonce', vmCostsInline.nonce);
            body.append('id', row.dataset.id);

            fetch(vmCostsInline.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        alert(data?.data?.message || vmCostsInline.labels.error);
                        return;
                    }

                    const editRow = row.nextElementSibling;
                    if (editRow && editRow.classList.contains('vm-cost-edit-row')) {
                        editRow.remove();
                    }
                    row.remove();
                    activeRow = null;
                })
                .catch(() => {
                    alert(vmCostsInline.labels.error);
                });

            return;
        }
    });

    table.addEventListener('submit', function (event) {
        const form = event.target.closest('.vm-inline-cost-form');
        if (!form) return;

        event.preventDefault();

        const row = form.closest('.vm-cost-edit-row')?.previousElementSibling;
        if (!row || !row.classList.contains('vm-cost-row')) return;

        const messageBox = form.querySelector('.vm-inline-cost-message');
        const submitButton = form.querySelector('.vm-inline-save-cost');

        submitButton.disabled = true;
        messageBox.textContent = vmCostsInline.labels.saving;

        const formData = new URLSearchParams();
        formData.append('action', 'vm_update_cost_inline');
        formData.append('nonce', vmCostsInline.nonce);
        formData.append('id', row.dataset.id);
        formData.append('property_id', row.dataset.propertyId);
        formData.append('property_cost_category_id', form.querySelector('[name="property_cost_category_id"]').value);
        formData.append('name', form.querySelector('[name="name"]').value);
        formData.append('betrag', form.querySelector('[name="betrag"]').value);
        formData.append('invoice_date', form.querySelector('[name="invoice_date"]').value);
        formData.append('period_start', form.querySelector('[name="period_start"]').value);
        formData.append('period_end', form.querySelector('[name="period_end"]').value);
        formData.append('period_year', form.querySelector('[name="period_year"]').value);
        formData.append('tax_deductible_type', form.querySelector('[name="tax_deductible_type"]').value);
        formData.append('tax_deductible_amount', form.querySelector('[name="tax_deductible_amount"]').value);

        fetch(vmCostsInline.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: formData.toString()
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    messageBox.textContent = data?.data?.message || vmCostsInline.labels.error;
                    submitButton.disabled = false;
                    return;
                }

                updateDisplayRow(row, data.data.item);
                messageBox.textContent = '';
                closeEditor();
                row.style.transition = 'background-color 0.6s ease';
                row.style.backgroundColor = '#eef9f0';
                window.setTimeout(() => {
                    row.style.backgroundColor = '';
                }, 1200);
            })
            .catch(() => {
                messageBox.textContent = vmCostsInline.labels.error;
                submitButton.disabled = false;
            });
    });
});