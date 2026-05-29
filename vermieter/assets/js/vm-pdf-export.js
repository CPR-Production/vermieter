(function () {
    'use strict';

    function setButtonState(button, isLoading) {
        if (!button) return;
        if (isLoading) {
            button.dataset.vmOriginalText = button.textContent;
            button.textContent = (window.vmPdfExport && vmPdfExport.labels && vmPdfExport.labels.loading) || 'PDF-Ansicht wird vorbereitet …';
            button.disabled = true;
        } else {
            button.textContent = button.dataset.vmOriginalText || button.textContent;
            button.disabled = false;
        }
    }

    function openPrintWindow(html, filename) {
        var printWindow = window.open('', '_blank');

        if (!printWindow) {
            alert('Bitte Pop-ups für diese Seite erlauben, damit die PDF-Ansicht geöffnet werden kann.');
            return;
        }

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();

        try {
            printWindow.document.title = filename || 'nebenkostenabrechnung.pdf';
        } catch (e) {}
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-vm-pdf-export]');
        if (!button) return;

        event.preventDefault();

        if (!window.vmPdfExport || !vmPdfExport.ajaxUrl || !vmPdfExport.nonce) {
            alert('PDF-Export ist nicht korrekt initialisiert.');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'vm_render_statement_pdf_html');
        formData.append('nonce', vmPdfExport.nonce);
        formData.append('property_id', button.getAttribute('data-property-id') || '0');
        formData.append('year', button.getAttribute('data-year') || '0');
        formData.append('tenant_index', button.getAttribute('data-tenant-index') || 'all');

        setButtonState(button, true);

        fetch(vmPdfExport.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.success || !payload.data || !payload.data.html) {
                    var message = payload && payload.data && payload.data.message
                        ? payload.data.message
                        : ((vmPdfExport.labels && vmPdfExport.labels.error) || 'PDF-Ansicht konnte nicht erstellt werden.');
                    throw new Error(message);
                }

                openPrintWindow(payload.data.html, payload.data.filename);
            })
            .catch(function (error) {
                alert(error.message || ((vmPdfExport.labels && vmPdfExport.labels.error) || 'PDF-Ansicht konnte nicht erstellt werden.'));
            })
            .finally(function () {
                setButtonState(button, false);
            });
    });
})();
