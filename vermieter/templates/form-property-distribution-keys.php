<?php if (!defined('ABSPATH')) exit; ?>

<?php
$edit_item = isset($edit_item) ? $edit_item : null;
?>

<div class="vm-wrap">
    <h2><?php echo $edit_item ? 'Schlüssel-Zuordnung bearbeiten' : 'Schlüssel einem Objekt zuordnen'; ?></h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_property_distribution_key'); ?>
        <input type="hidden" name="vm_action" value="save_property_distribution_key">
        <input type="hidden" name="vm_record_id" value="<?php echo esc_attr($edit_item->id ?? 0); ?>">

        <p>
            <label for="vm_property_id">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>" <?php selected((int) ($edit_item->property_id ?? 0), (int) $property->id); ?>>
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
                <option value="alle" <?php selected($edit_item->applies_to_type_key ?? '', 'alle'); ?>>Alle Typen</option>
            </select>
        </p>

        <p>
            <label for="vm_distribution_key_definition_id">Schlüsseldefinition</label><br>
            <select name="vm_distribution_key_definition_id" id="vm_distribution_key_definition_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($definitions as $definition) : ?>
                    <option value="<?php echo esc_attr($definition->id); ?>" <?php selected((int) ($edit_item->distribution_key_definition_id ?? 0), (int) $definition->id); ?>>
                        <?php
                        echo esc_html(
                            $definition->label . ' (' .
                            $definition->unit_code . ' / ' .
                            number_format((float) $definition->total_value, 2, ',', '.') . ')'
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <button type="submit"><?php echo $edit_item ? 'Zuordnung aktualisieren' : 'Zuordnung speichern'; ?></button>

            <?php if ($edit_item) : ?>
                <a href="<?php echo esc_url(remove_query_arg('edit_id')); ?>" style="margin-left:10px;">Abbrechen</a>
            <?php endif; ?>
        </p>
    </form>

    <?php if (!empty($assigned_keys)) : ?>
    <?php
        $property_options = [];
        foreach ($properties as $property) {
            $property_options[] = [
                'value' => (int) $property->id,
                'label' => $property->name . ' - ' . $property->street . ' ' . $property->house_number . ', ' . $property->zip_code . ' ' . $property->city,
            ];
        }

        $definition_options = [];
        foreach ($definitions as $definition) {
            $definition_options[] = [
                'value' => (int) $definition->id,
                'label' => $definition->label . ' (' . $definition->unit_code . ' / ' . number_format((float) $definition->total_value, 2, ',', '.') . ')',
            ];
        }

        $type_options = [
            ['value' => 'wohnung', 'label' => 'Wohnung'],
            ['value' => 'garage', 'label' => 'Garage'],
            ['value' => 'stellplatz', 'label' => 'Stellplatz'],
            ['value' => 'keller', 'label' => 'Keller'],
            ['value' => 'alle', 'label' => 'Alle Typen'],
        ];
        ?>

        <h3>Vorhandene Zuordnungen</h3>

        <table id="vm-property-distribution-keys-table">
            <thead>
                <tr>
                    <th>Objekt</th>
                    <th>Adresse</th>
                    <th>Typ</th>
                    <th>Bezeichnung</th>
                    <th>Einheit</th>
                    <th>Von insgesamt</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_keys as $key) : ?>
                    <?php $address = trim(($key->property_street ?? '') . ' ' . ($key->property_house_number ?? '')); ?>
                    <tr
                        data-record-id="<?php echo esc_attr((int) $key->id); ?>"
                        data-property-id="<?php echo esc_attr((int) $key->property_id); ?>"
                        data-definition-id="<?php echo esc_attr((int) $key->distribution_key_definition_id); ?>"
                        data-type-key="<?php echo esc_attr($key->applies_to_type_key); ?>"
                    >
                        <td><?php echo esc_html($key->property_name ?: '—'); ?></td>
                        <td><?php echo esc_html($address); ?></td>
                        <td><?php echo esc_html(vm_format_type($key->applies_to_type_key)); ?></td>
                        <td><?php echo esc_html($key->label); ?></td>
                        <td><?php echo esc_html($key->unit_code); ?></td>
                        <td><?php echo esc_html(number_format((float) $key->total_value, 2, ',', '.')); ?></td>
                        <td>
                            <button type="button" class="button vm-inline-edit-key"><i class="fa-solid fa-ellipsis"></i></button>
                        
                            <form method="post" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('Diese Zuordnung wirklich löschen?');">
                                <?php wp_nonce_field('vm_delete_property_distribution_key'); ?>
                                <input type="hidden" name="vm_action" value="delete_property_distribution_key">
                                <input type="hidden" name="vm_record_id" value="<?php echo esc_attr((int) $key->id); ?>">
                                <button type="submit" class="button button-link-delete"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.getElementById('vm-property-distribution-keys-table');

                if (!table) {
                    return;
                }

                const propertyOptions = <?php echo wp_json_encode($property_options); ?>;
                const definitionOptions = <?php echo wp_json_encode($definition_options); ?>;
                const typeOptions = <?php echo wp_json_encode($type_options); ?>;
                const colspan = 7;
                let openEditorRow = null;

                const createSelect = function (name, options, selectedValue) {
                    const select = document.createElement('select');
                    select.name = name;
                    select.required = true;
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

                const closeEditor = function () {
                    if (openEditorRow) {
                        openEditorRow.remove();
                        openEditorRow = null;
                    }
                };

                table.addEventListener('click', function (event) {
                    const button = event.target.closest('.vm-inline-edit-key');

                    if (!button) {
                        return;
                    }

                    const row = button.closest('tr');
                    const recordId = row.dataset.recordId;
                    const propertyId = row.dataset.propertyId;
                    const definitionId = row.dataset.definitionId;
                    const typeKey = row.dataset.typeKey;

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
                    nonceField.value = '<?php echo esc_js(wp_create_nonce('vm_update_property_distribution_key_inline')); ?>';
                    form.appendChild(nonceField);

                    const refererField = document.createElement('input');
                    refererField.type = 'hidden';
                    refererField.name = '_wp_http_referer';
                    refererField.value = '<?php echo esc_js(wp_unslash($_SERVER['REQUEST_URI'] ?? '')); ?>';
                    form.appendChild(refererField);

                    const actionField = document.createElement('input');
                    actionField.type = 'hidden';
                    actionField.name = 'vm_action';
                    actionField.value = 'update_property_distribution_key_inline';
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
                    propertyWrap.appendChild(propertyLabel);
                    propertyWrap.appendChild(createSelect('vm_property_id', propertyOptions, propertyId));
                    fieldsWrap.appendChild(propertyWrap);

                    const typeWrap = document.createElement('div');
                    const typeLabel = document.createElement('label');
                    typeLabel.textContent = 'Gilt für Typ';
                    typeWrap.appendChild(typeLabel);
                    typeWrap.appendChild(createSelect('vm_applies_to_type_key', typeOptions, typeKey));
                    fieldsWrap.appendChild(typeWrap);

                    const definitionWrap = document.createElement('div');
                    const definitionLabel = document.createElement('label');
                    definitionLabel.textContent = 'Schlüsseldefinition';
                    definitionWrap.appendChild(definitionLabel);
                    definitionWrap.appendChild(createSelect('vm_distribution_key_definition_id', definitionOptions, definitionId));
                    fieldsWrap.appendChild(definitionWrap);

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

    <?php if (!empty($assigned_keys)) : ?>
        <?php
        $grouped_keys = [];

        foreach ($assigned_keys as $key) {
            $property_id = (int) ($key->property_id ?? 0);
            $property_name = $key->property_name ?: '—';
            $property_address = trim(
                ($key->property_street ?? '') . ' ' .
                ($key->property_house_number ?? '')
            );
            $type_key = $key->applies_to_type_key ?: 'alle';

            if (!isset($grouped_keys[$property_id])) {
                $grouped_keys[$property_id] = [
                    'property_name'    => $property_name,
                    'property_address' => $property_address,
                    'types'            => [],
                ];
            }

            if (!isset($grouped_keys[$property_id]['types'][$type_key])) {
                $grouped_keys[$property_id]['types'][$type_key] = [];
            }

            $grouped_keys[$property_id]['types'][$type_key][] = $key;
        }

/*         $type_labels = [
            'wohnung'   => 'Wohnung',
            'garage'    => 'Garage',
            'stellplatz'=> 'Stellplatz',
            'keller'    => 'Keller',
            'alle'      => 'Alle Typen',
        ]; */
        ?>
        <br>
        <h3>Verteilwerte je Apartment</h3>

        <form method="post">
            <?php wp_nonce_field('vm_save_inline_distribution_values'); ?>
            <input type="hidden" name="vm_action" value="save_inline_distribution_values">

            <?php foreach ($grouped_keys as $property_id => $property_group) : ?>
                <div style="border:1px solid #ccd0d4; padding:16px; margin-bottom:24px; background:#fff;">
                    <h4 style="margin:0 0 6px 0;">
                        Objekt: <?php echo esc_html($property_group['property_name']); ?>
                    </h4>

                    <?php if (!empty($property_group['property_address'])) : ?>
                        <p style="margin:0 0 14px 0; color:#666;">
                            <?php echo esc_html($property_group['property_address']); ?>
                        </p>
                    <?php endif; ?>

                    <div style="margin-left:20px;">
                        <p style="margin:0 0 12px 0; font-weight:600;">
                            Apartments / Einheiten
                        </p>

                        <?php foreach ($property_group['types'] as $type_key => $keys_for_type) : ?>
                            <div style="margin:0 0 20px 20px; padding-left:14px; border-left:3px solid #dcdcde;">
                                <p style="margin:0 0 12px 0; font-weight:600;">
                                    Typ:
                                    <?php echo esc_html(vm_format_type($type_key)); ?>
                                </p>

                                <?php foreach ($keys_for_type as $key) : ?>
                                    <?php
                                    $property_distribution_key_id = (int) $key->id;
                                    $apartments = $apartments_by_property_distribution_key[$property_distribution_key_id] ?? [];
                                    $values = $distribution_values_map[$property_distribution_key_id] ?? [];
                                    ?>

                                    <div style="margin:0 0 24px 20px; padding-left:14px; border-left:3px solid #e5e5e5;">
                                        <p style="margin:0 0 6px 0; font-weight:600;">
                                            Schlüssel: <?php echo esc_html($key->label); ?>
                                        </p>

                                        <p style="margin:0 0 12px 0; color:#666;">
                                            Einheit: <?php echo esc_html($key->unit_code); ?>
                                            |
                                            Von insgesamt: <?php echo esc_html(number_format((float) $key->total_value, 2, ',', '.')); ?>
                                        </p>

                                        <div style="margin-left:20px;">
                                            <?php if (!empty($apartments)) : ?>
                                                <table style="margin-top:0;">
                                                    <thead>
                                                        <tr>
                                                            <th>Apartment</th>
                                                            <th>Typ</th>
                                                            <th>Wert</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($apartments as $apartment) : ?>
                                                            <tr>
                                                                <td><?php echo esc_html($apartment->name); ?></td>
                                                                <td><?php echo esc_html(vm_format_type($apartment->type_key)); ?></td>
                                                                <td>
                                                                    <input
                                                                        type="text"
                                                                        name="vm_distribution_values[<?php echo esc_attr($property_distribution_key_id); ?>][<?php echo esc_attr($apartment->id); ?>]"
                                                                        value="<?php echo esc_attr(isset($values[$apartment->id]) ? str_replace('.', ',', (string) $values[$apartment->id]) : '0,00'); ?>"
                                                                        style="min-width:120px;"
                                                                    >
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else : ?>
                                                <p style="margin:0;">Keine passenden Apartments für diesen Typ vorhanden.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <p>
                <button type="submit" class="vm-btn-primary">
                    <i class="fa-solid fa-save"></i> Verteilwerte speichern
                </button>
            </p>
        </form>
    <?php endif; ?>
</div>