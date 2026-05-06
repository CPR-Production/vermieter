<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Jahreskosten als Tabelle erfassen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="get" id="vm-costs-filter-form" style="margin-bottom:20px;">
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

            <table id="vm-costs-entry-table">
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
                            <?php if (($category->allocation_type ?? '') === 'brunata_statement') { continue; } ?>
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

            <?php if (!empty($brunata_entry_rows)) : ?>
                <h3>Heizkosten laut Brunata</h3>
                <p><small>Diese Positionen werden als Nebenkosten mit dem Schlüssel „Lt. Abrechnung Brunata“ gespeichert. Es werden nur Wohnungen angezeigt; Stellplätze, Garagen und Keller bleiben außen vor. Bei Mieterwechsel trägst du Gesamtbetrag und Teilbeträge ein – sobald genau ein Feld leer ist, berechnet JavaScript den fehlenden Wert automatisch.</small></p>

                <table class="widefat striped vm-table" id="vm-brunata-entry-table">
                    <thead>
                        <tr>
                            <th>Kategorie</th>
                            <th>Wohnung</th>
                            <th>Gesamt</th>
                            <th>Nutzung / Mieter</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brunata_entry_rows as $b_index => $b_row) : ?>
                            <tr class="vm-brunata-group">
                                <td>
                                    <?php echo esc_html($b_row['category_name']); ?>
                                    <input type="hidden" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][property_cost_category_id]" value="<?php echo esc_attr($b_row['property_cost_category_id']); ?>">
                                    <input type="hidden" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][category_name]" value="<?php echo esc_attr($b_row['category_name']); ?>">
                                </td>
                                <td>
                                    <?php echo esc_html($b_row['apartment_name']); ?>
                                    <input type="hidden" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][apartment_id]" value="<?php echo esc_attr($b_row['apartment_id']); ?>">
                                </td>
                                <td>
                                    <input type="text" class="vm-brunata-total" placeholder="0,00" style="width:110px;">
                                </td>
                                <td>
                                    <?php foreach ($b_row['usage'] as $u_index => $usage) : ?>
                                        <div style="margin-bottom:8px;">
                                            <strong><?php echo esc_html($usage['label']); ?></strong><br>
                                            <small><?php echo esc_html(vm_format_date($usage['start_date'])); ?> bis <?php echo esc_html(vm_format_date($usage['end_date'])); ?> · <?php echo (int) $usage['days']; ?> Tage</small><br>
                                            <input type="hidden" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][usage][<?php echo esc_attr($u_index); ?>][apartment_tenant_id]" value="<?php echo esc_attr($usage['apartment_tenant_id']); ?>">
                                            <input type="hidden" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][usage][<?php echo esc_attr($u_index); ?>][label]" value="<?php echo esc_attr($usage['label']); ?>">
                                            <input type="hidden" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][usage][<?php echo esc_attr($u_index); ?>][start_date]" value="<?php echo esc_attr($usage['start_date']); ?>">
                                            <input type="hidden" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][usage][<?php echo esc_attr($u_index); ?>][end_date]" value="<?php echo esc_attr($usage['end_date']); ?>">
                                            <input type="text" class="vm-brunata-part" name="vm_brunata_rows[<?php echo esc_attr($b_index); ?>][usage][<?php echo esc_attr($u_index); ?>][amount]" placeholder="0,00" style="width:110px;">
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p>
                <button type="submit" class="vm-btn-primary">
                    <i class="fa-solid fa-save"></i> Tabelle Speichern
                </button>
            </p>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const yearField = document.getElementById('vm_period_GET_year');

                if (!yearField || !yearField.form) {
                    return;
                }

                yearField.addEventListener('change', function () {
                    yearField.form.submit();
                });
            });

            document.addEventListener('DOMContentLoaded', function () {
                const tableBody = document.querySelector('#vm-costs-entry-table tbody');
                const addRowButton = document.getElementById('vm-add-row');
                const yearField = document.getElementById('vm_period_year');
                const periodStartField = document.getElementById('vm_period_start');
                const periodEndField = document.getElementById('vm_period_end');
                const yearFilterField = document.getElementById('vm_year_filter');
                const filterForm = document.getElementById('vm-costs-filter-form');

                const getInvoiceName = function () {
                    const year = parseInt(yearField.value, 10);

                    if (!year || year < 1000 || year > 9999) {
                        return 'Jahresabrechnung';
                    }

                    return 'Jahresabrechnung ' + year;
                };

                const updateAutoNames = function () {
                    const newAutoName = getInvoiceName();
                    const nameFields = document.querySelectorAll('#vm-costs-entry-table input[name$="[name]"]');

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

                    const submitYearFilter = function () {
                        const year = parseInt(yearField.value, 10);

                        if (!year || year < 1000 || year > 9999) {
                            return;
                        }

                        if (yearFilterField) {
                            yearFilterField.value = year;
                        }

                        if (filterForm) {
                            filterForm.submit();
                        }
                    };

                    yearField.addEventListener('input', updatePeriodDatesFromYear);

                    yearField.addEventListener('change', function () {
                        updatePeriodDatesFromYear();
                        submitYearFilter();
                    });
                }


                const toNumber = function (value) {
                    if (typeof value !== 'string') {
                        value = String(value || '');
                    }
                    value = value.trim().replace(/\./g, '').replace(',', '.');
                    const number = parseFloat(value);
                    return isNaN(number) ? 0 : number;
                };

                const formatMoneyInput = function (number) {
                    return (Math.round(number * 100) / 100).toFixed(2).replace('.', ',');
                };

                document.querySelectorAll('.vm-brunata-group').forEach(function (group) {
                    const totalField = group.querySelector('.vm-brunata-total');
                    const partFields = Array.from(group.querySelectorAll('.vm-brunata-part'));
                    const allFields = [totalField].concat(partFields).filter(Boolean);

                    const recalc = function (changedField) {
                        const emptyFields = allFields.filter(function (field) {
                            return field.value.trim() === '';
                        });

                        if (emptyFields.length !== 1) {
                            return;
                        }

                        const emptyField = emptyFields[0];

                        if (emptyField === totalField) {
                            let sum = 0;
                            partFields.forEach(function (field) {
                                sum += toNumber(field.value);
                            });
                            emptyField.value = formatMoneyInput(sum);
                            return;
                        }

                        const total = toNumber(totalField.value);
                        if (total <= 0) {
                            return;
                        }

                        let used = 0;
                        partFields.forEach(function (field) {
                            if (field !== emptyField) {
                                used += toNumber(field.value);
                            }
                        });

                        const rest = total - used;
                        if (rest >= 0) {
                            emptyField.value = formatMoneyInput(rest);
                        }
                    };

                    allFields.forEach(function (field) {
                        field.addEventListener('input', function () {
                            recalc(field);
                        });

                        field.addEventListener('blur', function () {
                            if (field.value.trim() !== '') {
                                field.value = formatMoneyInput(toNumber(field.value));
                            }
                        });
                    });
                });

                if (!tableBody || !addRowButton) {
                    return;
                }

                let rowIndex = <?php echo !empty($property_categories) ? count($property_categories) : 0; ?>;

                const categoryOptions = `<?php
                    $options = '<option value="">Bitte wählen</option>';
                    foreach ($property_categories as $category) {
                        if (($category->allocation_type ?? '') === 'brunata_statement') { continue; }
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

    <?php
    $category_options = [];
    foreach (($property_categories ?? []) as $category) {
        $category_options[] = [
            'id'   => (int) $category->id,
            'name' => (string) $category->name,
        ];
    }
    ?>

    <?php if ($selected_property_id > 0 && $selected_year > 0) : ?>
        <h3>
            Erfasste Kosten für dieses Objekt und Jahr
        </h3>
        <p>
            <small style="font-weight:normal; color:#666;">
                <?php echo (int) count($costs ?? []); ?> Positionen
            </small>
        </p>
        <form>
            <input type="hidden" name="vm_property_id" value="<?php echo esc_attr($selected_property_id); ?>">

            <p>
                <label for="vm_period_GET_year">Jahr</label><br>
                <input type="number" name="vm_year" id="vm_period_GET_year" value="<?php echo esc_attr($period['year']); ?>" required>
            </p>
        </form>

        <?php if (!empty($costs)) : ?>
            <table id="vm-costs-list-table">
                <thead>
                    <tr>
                        <th>Kategorie</th>
                        <th>Rechnung / Bezeichnung</th>
                        <th>Betrag</th>
                        <th>Rechnungsdatum</th>
                        <th>Zeitraum</th>
                        <th>Wiederkehrend</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($costs as $cost) : ?>
                        <?php $is_editing = !empty($edit_item) && (int) $edit_item->id === (int) $cost->id; ?>

                        <?php if ($is_editing) : ?>
                            <tr>
                                <form method="post">
                                    <?php wp_nonce_field('vm_update_cost'); ?>
                                    <input type="hidden" name="vm_action" value="update_cost">
                                    <input type="hidden" name="vm_record_id" value="<?php echo (int) $cost->id; ?>">
                                    <input type="hidden" name="vm_property_id" value="<?php echo (int) $selected_property_id; ?>">

                                    <td>
                                        <select name="vm_property_cost_category_id" required style="width:100%;">
                                            <option value="">Bitte wählen</option>
                                            <?php foreach ($property_categories as $category) : ?>
                                                <option value="<?php echo esc_attr($category->id); ?>" <?php selected((int) $cost->property_cost_category_id, (int) $category->id); ?>>
                                                    <?php echo esc_html($category->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="vm_name" value="<?php echo esc_attr($cost->name); ?>" required style="width:100%;">
                                    </td>
                                    <td>
                                        <input type="text" name="vm_betrag" value="<?php echo esc_attr(number_format((float) $cost->betrag, 2, ',', '')); ?>" required style="width:120px;">
                                    </td>
                                    <td>
                                        <input type="date" name="vm_invoice_date" value="<?php echo esc_attr($cost->invoice_date); ?>" required>
                                    </td>
                                    <td>
                                        <input type="date" name="vm_period_start" value="<?php echo esc_attr($cost->period_start); ?>" required style="margin-bottom:4px;"><br>
                                        <input type="date" name="vm_period_end" value="<?php echo esc_attr($cost->period_end); ?>" required><br>
                                        <input type="number" name="vm_period_year" value="<?php echo esc_attr((int) $cost->period_year); ?>" required style="width:100px; margin-top:4px;">
                                    </td>
                                    <td>
                                        <?php echo !empty($cost->is_recurring) ? 'Ja' : 'Nein'; ?>
                                    </td>
                                    <td>
                                        <button type="submit" class="button button-primary" aria-label="Speichern">
                                            <i class="fa-solid fa-save"></i>
                                        </button>
                                        <a href="<?php echo esc_url(remove_query_arg('edit_id')); ?>" class="button" aria-label="Abbrechen">
                                            Abbrechen
                                        </a>
                                    </td>
                                </form>
                            </tr>
                        <?php else : ?>
                            <tr
                                class="vm-cost-row"
                                data-id="<?php echo (int) $cost->id; ?>"
                                data-property-id="<?php echo (int) $selected_property_id; ?>"
                                data-category-id="<?php echo (int) $cost->property_cost_category_id; ?>"
                                data-category-name="<?php echo esc_attr($cost->category_name ?: ''); ?>"
                                data-name="<?php echo esc_attr($cost->name); ?>"
                                data-betrag="<?php echo esc_attr(number_format((float) $cost->betrag, 2, '.', '')); ?>"
                                data-invoice-date="<?php echo esc_attr($cost->invoice_date); ?>"
                                data-period-start="<?php echo esc_attr($cost->period_start); ?>"
                                data-period-end="<?php echo esc_attr($cost->period_end); ?>"
                                data-period-year="<?php echo esc_attr((int) $cost->period_year); ?>"
                                data-is-recurring="<?php echo !empty($cost->is_recurring) ? '1' : '0'; ?>"
                            >
                                <td><?php echo esc_html($cost->category_name ?: '—'); ?></td>
                                <td><?php echo esc_html($cost->name); ?></td>
                                <td><?php echo esc_html(number_format((float) $cost->betrag, 2, ',', '.')); ?> €</td>
                                <td><?php echo esc_html(vm_format_date($cost->invoice_date)); ?></td>
                                <td>
                                    <?php echo esc_html(vm_format_date($cost->period_start)); ?><br>
                                    bis <?php echo esc_html(vm_format_date($cost->period_end)); ?>
                                </td>
                                <td><?php echo !empty($cost->is_recurring) ? 'Ja' : 'Nein'; ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="button vm-inline-edit-cost"
                                        aria-label="Bearbeiten"
                                    >
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="button button-link-delete vm-inline-delete-cost"
                                        aria-label="Löschen"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                            <tr class="vm-cost-edit-row" style="display:none;">
                                <td colspan="7">
                                    <div
                                        class="vm-cost-inline-editor"
                                        data-categories="<?php echo esc_attr(wp_json_encode($category_options)); ?>"
                                    ></div>
                                </td>
                            </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Für dieses Objekt und Jahr sind noch keine Kostenpositionen erfasst.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>