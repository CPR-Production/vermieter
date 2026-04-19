<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Jahreskosten als Tabelle erfassen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="get" style="margin-bottom:20px;">
        <input type="hidden" name="vm_year" id="vm_year_filter" value="<?php echo esc_attr($period['year']); ?>">

        <p>
            <label for="vm_property_id_filter">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id_filter" onchange="this.form.submit()" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>" <?php selected($selected_property_id, $property->id); ?>>
                        <?php echo esc_html($property->name . ' - ' . $property->street . ' ' . $property->house_number); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
    </form>

    <?php if ($selected_property_id > 0) : ?>
        <form method="post" id="vm-costs-table-form">
            <?php wp_nonce_field('vm_save_costs_table'); ?>
            <input type="hidden" name="vm_action" value="save_costs_table">
            <input type="hidden" name="vm_property_id" value="<?php echo esc_attr($selected_property_id); ?>">

            <p>
                <label for="vm_period_year">Jahr</label><br>
                <input type="number" name="vm_period_year" id="vm_period_year" value="<?php echo esc_attr($period['year']); ?>" required>
            </p>

            <p>
                <label for="vm_invoice_date">Rechnungsdatum</label><br>
                <input type="date" name="vm_invoice_date" id="vm_invoice_date">
            </p>

            <p>
                <label for="vm_period_start">Abrechnungsbeginn</label><br>
                <input type="date" name="vm_period_start" id="vm_period_start" value="<?php echo esc_attr($period['start']); ?>" required>
            </p>

            <p>
                <label for="vm_period_end">Abrechnungsende</label><br>
                <input type="date" name="vm_period_end" id="vm_period_end" value="<?php echo esc_attr($period['end']); ?>" required>
            </p>

            <h3>Positionen</h3>

            <table id="vm-costs-table">
                <thead>
                    <tr>
                        <th><i class="fa-solid fa-layer-group"></i> Kategorie</th>
                        <th>Bezeichnung</th>
                        <th><i class="fa-solid fa-euro-sign"></i> Betrag</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($property_categories)) : ?>
                        <?php foreach ($property_categories as $index => $category) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($category->name); ?>
                                    <input type="hidden" name="vm_rows[<?php echo esc_attr($index); ?>][property_cost_category_id]" value="<?php echo esc_attr($category->id); ?>">
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="vm_rows[<?php echo esc_attr($index); ?>][name]"
                                        value="<?php echo esc_attr('Jahresabrechnung ' . $period['year']); ?>"
                                        data-auto-name="<?php echo esc_attr('Jahresabrechnung ' . $period['year']); ?>"
                                        style="width:100%;"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="vm_rows[<?php echo esc_attr($index); ?>][betrag]"
                                        placeholder="0,00"
                                    >
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3">Für dieses Objekt sind noch keine Kategorien zugeordnet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <button type="button" id="vm-add-row">+ Zeile hinzufügen</button>
            </p>

            <p>
                <button type="submit" class="vm-btn-primary">
                    <i class="fa-solid fa-save"></i> Tabelle Speichern
                </button>
            </p>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tableBody = document.querySelector('#vm-costs-table tbody');
                const addRowButton = document.getElementById('vm-add-row');
                const yearField = document.getElementById('vm_period_year');
                const periodStartField = document.getElementById('vm_period_start');
                const periodEndField = document.getElementById('vm_period_end');
                const yearFilterField = document.getElementById('vm_year_filter');

                const getInvoiceName = function () {
                    const year = parseInt(yearField.value, 10);

                    if (!year || year < 1000 || year > 9999) {
                        return 'Jahresabrechnung';
                    }

                    return 'Jahresabrechnung ' + year;
                };

                const updateAutoNames = function () {
                    const newAutoName = getInvoiceName();
                    const nameFields = document.querySelectorAll('#vm-costs-table input[name$="[name]"]');

                    nameFields.forEach(function (field) {
                        const previousAutoName = field.dataset.autoName || '';

                        if (field.value === '' || field.value === previousAutoName) {
                            field.value = newAutoName;
                            field.dataset.autoName = newAutoName;
                        }
                    });
                };

                if (yearField && periodStartField && periodEndField) {
                    const updatePeriodDatesFromYear = function () {
                        const year = parseInt(yearField.value, 10);

                        if (!year || year < 1000 || year > 9999) {
                            return;
                        }

                        periodStartField.value = year + '-01-01';
                        periodEndField.value = year + '-12-31';
                        
                        if (yearFilterField) {
                            yearFilterField.value = year;
                        }

                        updateAutoNames();
                    };

                    yearField.addEventListener('change', updatePeriodDatesFromYear);
                    yearField.addEventListener('input', updatePeriodDatesFromYear);
                }

                if (!tableBody || !addRowButton) {
                    return;
                }

                let rowIndex = <?php echo !empty($property_categories) ? count($property_categories) : 0; ?>;

                const categoryOptions = `<?php
                    $options = '<option value="">Bitte wählen</option>';
                    foreach ($property_categories as $category) {
                        $options .= '<option value="' . esc_attr($category->id) . '">' . esc_html($category->name) . '</option>';
                    }
                    echo $options;
                ?>`;

                addRowButton.addEventListener('click', function () {
                    const autoName = getInvoiceName();
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <select name="vm_rows[${rowIndex}][property_cost_category_id]">
                                ${categoryOptions}
                            </select>
                        </td>
                        <td>
                            <input
                                type="text"
                                name="vm_rows[${rowIndex}][name]"
                                value="${autoName}"
                                data-auto-name="${autoName}"
                                style="width:100%;"
                            >
                        </td>
                        <td>
                            <input type="text" name="vm_rows[${rowIndex}][betrag]" placeholder="0,00">
                        </td>
                    `;
                    tableBody.appendChild(tr);
                    rowIndex++;
                });
            });
        </script>
    <?php endif; ?>
</div>