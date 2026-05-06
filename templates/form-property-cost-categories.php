<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Kategorie einem Objekt zuordnen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_property_cost_category'); ?>
        <input type="hidden" name="vm_action" value="save_property_cost_category">

        <p>
            <label for="vm_cost_category_definition_id">Kategorie</label><br>
            <select name="vm_cost_category_definition_id" id="vm_cost_category_definition_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($definitions as $definition) : ?>
                    <option value="<?php echo esc_attr($definition->id); ?>" <?php selected((int) ($edit_item->cost_category_definition_id ?? 0), (int) $definition->id); ?>>
                        <?php echo esc_html($definition->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_property_id">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>" <?php selected($edit_item->property_id ?? 0, $property->id); ?>>
                        <?php
                        echo esc_html(
                            $property->name . ' - ' .
                            $property->street . ' ' .
                            $property->house_number . ', ' .
                            $property->zip_code . ' ' .
                            $property->city
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_applies_to_type_key">Gilt für Typ</label><br>
            <select name="vm_applies_to_type_key" id="vm_applies_to_type_key" required>
                <option value="wohnung" <?php selected($edit_item->applies_to_type_key ?? '', 'wohnung'); ?>>Wohnung</option>
                <option value="garage" <?php selected($edit_item->applies_to_type_key ?? '', 'garage'); ?>>Garage</option>
                <option value="stellplatz" <?php selected($edit_item->applies_to_type_key ?? '', 'stellplatz'); ?>>Stellplatz</option>
                <option value="keller" <?php selected($edit_item->applies_to_type_key ?? '', 'keller'); ?>>Keller</option>
                <option value="alle" <?php selected($edit_item->applies_to_type_key ?? 'alle', 'alle'); ?>>Alle Typen</option>
            </select>
        </p>

        <p>
            <label for="vm_allocation_type">Verteilungsart</label><br>
            <select name="vm_allocation_type" id="vm_allocation_type" required>
                <option value="wohnflaeche" <?php selected($edit_item->allocation_type ?? 'wohnflaeche', 'wohnflaeche'); ?>>Wohnfläche</option>
                <option value="personen" <?php selected($edit_item->allocation_type ?? '', 'personen'); ?>>Personen</option>
                <option value="distribution_key" <?php selected($edit_item->allocation_type ?? '', 'distribution_key'); ?>>Verteilerschlüssel</option>
                <option value="brunata_statement" <?php selected($edit_item->allocation_type ?? '', 'brunata_statement'); ?>>Lt. Abrechnung Brunata</option>
            </select>
        </p>

        <p>
            <label for="vm_property_distribution_key_id">Objekt-Schlüssel</label><br>
            <select name="vm_property_distribution_key_id" id="vm_property_distribution_key_id">
                <option value="">Bitte wählen</option>
                <?php foreach ($assigned_keys as $key) : ?>
                    <option value="<?php echo esc_attr($key->id); ?>" <?php selected((int) ($edit_item->property_distribution_key_id ?? 0), (int) $key->id); ?>>
                        <?php
                        echo esc_html(
                            $key->property_name . ' | ' .
                            vm_format_type($key->applies_to_type_key) . ' | ' .
                            $key->label . ' (' . $key->unit_code . ' / ' .
                            number_format((float) $key->total_value, 2, ',', '.') . ')'
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><small>Nur nötig, wenn Verteilungsart = Verteilerschlüssel.</small>
        </p>

        <p>
            <label>
                <input type="checkbox" name="vm_is_recurring" value="1" <?php checked((int) ($edit_item->is_recurring ?? 0), 1); ?>>
                Wiederkehrende Kategorie
            </label>
        </p>

        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Zuordnung speichern
            </button>
        </p>
    </form>

    <?php if (!empty($assigned_categories)) : ?>
        <?php
        $property_options = [];
        foreach ($properties as $property) {
            $property_options[] = [
                'value' => (int) $property->id,
                'label' => $property->name . ' - ' . $property->street . ' ' . $property->house_number . ', ' . $property->zip_code . ' ' . $property->city,
            ];
        }

        $category_options = [];
        foreach ($definitions as $definition) {
            $category_options[] = [
                'value' => (int) $definition->id,
                'label' => $definition->name,
            ];
        }

        $type_options = [
            ['value' => 'wohnung', 'label' => 'Wohnung'],
            ['value' => 'garage', 'label' => 'Garage'],
            ['value' => 'stellplatz', 'label' => 'Stellplatz'],
            ['value' => 'keller', 'label' => 'Keller'],
            ['value' => 'alle', 'label' => 'Alle Typen'],
        ];

        $distribution_key_options = [];
        foreach ($distribution_keys as $key) {
            $distribution_key_options[] = [
                'value' => (int) $key->id,
                'label' => ($key->property_name ?: 'Objekt') . ' | ' . vm_format_type($key->applies_to_type_key) . ' | ' . $key->label . ' (' . $key->unit_code . ')',
                'property_id' => (int) $key->property_id,
            ];
        }
        ?>

        <h3>
            <?php echo !empty($active_property)
                ? 'Vorhandene Zuordnungen für dieses Objekt <strong>' . esc_html($active_property->name) . '</strong>'
                : 'Vorhandene Zuordnungen'; ?>
        </h3>

        <?php if (!empty($active_property)) : ?>
            <p>
                <em>
                    Es werden nur Zuordnungen für das Objekt
                    <strong><?php echo esc_html($active_property->name); ?></strong>
                    angezeigt.
                </em>
                <a href="<?php echo esc_url(vm_get_page_url('vermieter-objekt-kostenkategorien')); ?>" class="button button-secondary" style="margin-left:10px;">
                    Filter aufheben
                </a>
            </p>
        <?php endif; ?>

        <?php $show_property_column = empty($active_property); ?>
                    
        <table id="vm-property-cost-categories-table">
            <thead>
                <tr>
                    <?php if ($show_property_column) : ?>
                        <th>Objekt</th>
                    <?php endif; ?>
                    <th>Typ</th>
                    <th>Kategorie</th>
                    <th>Verteilungsart</th>
                    <th>Schlüssel</th>
                    <th>Wiederkehrend</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_categories as $item) : ?>
                    <tr
                        data-record-id="<?php echo esc_attr((int) $item->id); ?>"
                        data-property-id="<?php echo esc_attr((int) $item->property_id); ?>"
                        data-definition-id="<?php echo esc_attr((int) $item->cost_category_definition_id); ?>"
                        data-type-key="<?php echo esc_attr($item->applies_to_type_key); ?>"
                        data-allocation-type="<?php echo esc_attr($item->allocation_type); ?>"
                        data-distribution-key-id="<?php echo esc_attr((int) ($item->property_distribution_key_id ?? 0)); ?>"
                        data-is-recurring="<?php echo esc_attr((int) ($item->is_recurring ?? 0)); ?>"
                    >
                        <?php if ($show_property_column) : ?>
                            <td><?php echo esc_html($item->property_name); ?></td>
                        <?php endif; ?>
                        <td><?php echo esc_html(vm_format_type($item->applies_to_type_key)); ?></td>
                        <td><?php echo esc_html($item->category_name); ?></td>
                        <td><?php echo esc_html($apportionment_types[$item->allocation_type] ?? $item->allocation_type); ?></td>
                        <td>
                            <?php
                            if (!empty($item->distribution_label)) {
                                echo esc_html($item->distribution_label . ' (' . $item->distribution_unit_code . ')');
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td><?php echo (int) $item->is_recurring === 1 ? 'Ja' : 'Nein'; ?></td>
                        <td>
                            <button type="button" class="button vm-inline-edit-cost-category"><i class="fa-solid fa-ellipsis"></i></button>

                            <form method="post" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('Diese Zuordnung wirklich löschen?');">
                                <?php wp_nonce_field('vm_delete_property_cost_category'); ?>
                                <input type="hidden" name="vm_action" value="delete_property_cost_category">
                                <input type="hidden" name="vm_record_id" value="<?php echo esc_attr((int) $item->id); ?>">
                                <button type="submit" class="button button-link-delete"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.getElementById('vm-property-cost-categories-table');

                if (!table) {
                    return;
                }

                const propertyOptions = <?php echo wp_json_encode($property_options); ?>;
                const categoryOptions = <?php echo wp_json_encode($category_options); ?>;
                const typeOptions = <?php echo wp_json_encode($type_options); ?>;
                const distributionKeyOptions = <?php echo wp_json_encode($distribution_key_options); ?>;
                const colspan = 7;
                let openEditorRow = null;

                const createSelect = function (name, options, selectedValue) {
                    const select = document.createElement('select');
                    select.name = name;
                    select.required = false;
                    select.style.width = '100%';

                    options.forEach(function (optionData) {
                        const option = document.createElement('option');
                        option.value = String(optionData.value);
                        option.textContent = optionData.label;

                        if (String(optionData.value) === String(selectedValue)) {
                            option.selected = true;
                        }

                        select.appendChild(option);
                    });

                    return select;
                };

                const createDistributionSelect = function (selectedPropertyId, selectedDistributionKeyId) {
                    const select = document.createElement('select');
                    select.name = 'vm_property_distribution_key_id';
                    select.style.width = '100%';

                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = 'Bitte wählen';
                    select.appendChild(emptyOption);

                    distributionKeyOptions.forEach(function (optionData) {
                        if (String(optionData.property_id) !== String(selectedPropertyId)) {
                            return;
                        }

                        const option = document.createElement('option');
                        option.value = String(optionData.value);
                        option.textContent = optionData.label;

                        if (String(optionData.value) === String(selectedDistributionKeyId)) {
                            option.selected = true;
                        }

                        select.appendChild(option);
                    });

                    return select;
                };

                const closeEditor = function () {
                    if (openEditorRow) {
                        openEditorRow.remove();
                        openEditorRow = null;
                    }
                };

                table.addEventListener('click', function (event) {
                    const button = event.target.closest('.vm-inline-edit-cost-category');

                    if (!button) {
                        return;
                    }

                    const row = button.closest('tr');
                    const recordId = row.dataset.recordId;
                    const propertyId = row.dataset.propertyId;
                    const definitionId = row.dataset.definitionId;
                    const typeKey = row.dataset.typeKey;
                    const allocationType = row.dataset.allocationType;
                    const distributionKeyId = row.dataset.distributionKeyId;
                    const isRecurring = row.dataset.isRecurring === '1';

                    closeEditor();

                    const editorRow = document.createElement('tr');
                    const editorCell = document.createElement('td');
                    editorCell.colSpan = colspan;
                    editorCell.style.background = '#f6f7f7';
                    editorCell.style.padding = '12px';

                    const form = document.createElement('form');
                    form.method = 'post';

                    const nonceField = document.createElement('input');
                    nonceField.type = 'hidden';
                    nonceField.name = '_wpnonce';
                    nonceField.value = '<?php echo esc_js(wp_create_nonce('vm_update_property_cost_category_inline')); ?>';
                    form.appendChild(nonceField);

                    const refererField = document.createElement('input');
                    refererField.type = 'hidden';
                    refererField.name = '_wp_http_referer';
                    refererField.value = '<?php echo esc_js(wp_unslash($_SERVER['REQUEST_URI'] ?? '')); ?>';
                    form.appendChild(refererField);

                    const actionField = document.createElement('input');
                    actionField.type = 'hidden';
                    actionField.name = 'vm_action';
                    actionField.value = 'update_property_cost_category_inline';
                    form.appendChild(actionField);

                    const recordField = document.createElement('input');
                    recordField.type = 'hidden';
                    recordField.name = 'vm_record_id';
                    recordField.value = recordId;
                    form.appendChild(recordField);

                    const fieldsWrap = document.createElement('div');
                    fieldsWrap.style.display = 'grid';
                    fieldsWrap.style.gridTemplateColumns = 'repeat(auto-fit, minmax(220px, 1fr))';
                    fieldsWrap.style.gap = '12px';
                    fieldsWrap.style.marginBottom = '12px';

                    const propertyWrap = document.createElement('div');
                    const propertyLabel = document.createElement('label');
                    propertyLabel.textContent = 'Objekt';
                    const propertySelect = createSelect('vm_property_id', propertyOptions, propertyId);
                    propertySelect.required = true;
                    propertyWrap.appendChild(propertyLabel);
                    propertyWrap.appendChild(propertySelect);
                    fieldsWrap.appendChild(propertyWrap);

                    const typeWrap = document.createElement('div');
                    const typeLabel = document.createElement('label');
                    typeLabel.textContent = 'Gilt für Typ';
                    const typeSelect = createSelect('vm_applies_to_type_key', typeOptions, typeKey);
                    typeSelect.required = true;
                    typeWrap.appendChild(typeLabel);
                    typeWrap.appendChild(typeSelect);
                    fieldsWrap.appendChild(typeWrap);

                    const categoryWrap = document.createElement('div');
                    const categoryLabel = document.createElement('label');
                    categoryLabel.textContent = 'Kategorie';
                    const categorySelect = createSelect('vm_cost_category_definition_id', categoryOptions, definitionId);
                    categorySelect.required = true;
                    categoryWrap.appendChild(categoryLabel);
                    categoryWrap.appendChild(categorySelect);
                    fieldsWrap.appendChild(categoryWrap);

                    const allocationWrap = document.createElement('div');
                    const allocationLabel = document.createElement('label');
                    allocationLabel.textContent = 'Verteilungsart';
                    const allocationOptions = [
                        { value: 'wohnflaeche', label: 'Wohnfläche' },
                        { value: 'personen', label: 'Personen' },
                        { value: 'distribution_key', label: 'Verteilerschlüssel' },
                        { value: 'brunata_statement', label: 'Lt. Abrechnung Brunata' }
                    ];
                    const allocationSelect = createSelect('vm_allocation_type', allocationOptions, allocationType);
                    allocationSelect.required = true;
                    allocationWrap.appendChild(allocationLabel);
                    allocationWrap.appendChild(allocationSelect);
                    fieldsWrap.appendChild(allocationWrap);

                    const distributionWrap = document.createElement('div');
                    const distributionLabel = document.createElement('label');
                    distributionLabel.textContent = 'Verteilerschlüssel';
                    let distributionSelect = createDistributionSelect(propertyId, distributionKeyId);
                    distributionWrap.appendChild(distributionLabel);
                    distributionWrap.appendChild(distributionSelect);
                    fieldsWrap.appendChild(distributionWrap);

                    const recurringWrap = document.createElement('div');
                    const recurringLabel = document.createElement('label');
                    const recurringCheckbox = document.createElement('input');
                    recurringCheckbox.type = 'checkbox';
                    recurringCheckbox.name = 'vm_is_recurring';
                    recurringCheckbox.value = '1';
                    recurringCheckbox.checked = isRecurring;
                    recurringLabel.appendChild(recurringCheckbox);
                    recurringLabel.appendChild(document.createTextNode(' Wiederkehrende Kategorie'));
                    recurringWrap.appendChild(recurringLabel);
                    fieldsWrap.appendChild(recurringWrap);

                    propertySelect.addEventListener('change', function () {
                        const newSelect = createDistributionSelect(propertySelect.value, '');
                        distributionWrap.replaceChild(newSelect, distributionSelect);
                        distributionSelect = newSelect;
                    });

                    form.appendChild(fieldsWrap);

                    const actionsWrap = document.createElement('div');

                    const saveButton = document.createElement('button');
                    saveButton.type = 'submit';
                    saveButton.className = 'button button-primary';
                    saveButton.textContent = 'Speichern';
                    actionsWrap.appendChild(saveButton);

                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'button';
                    cancelButton.style.marginLeft = '8px';
                    cancelButton.textContent = 'Abbrechen';
                    cancelButton.addEventListener('click', closeEditor);
                    actionsWrap.appendChild(cancelButton);

                    form.appendChild(actionsWrap);
                    editorCell.appendChild(form);
                    editorRow.appendChild(editorCell);

                    row.insertAdjacentElement('afterend', editorRow);
                    openEditorRow = editorRow;
                });
            });
        </script>
    <?php endif; ?>
</div>