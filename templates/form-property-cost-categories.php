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
                    <option value="<?php echo esc_attr($definition->id); ?>">
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
                    <option value="<?php echo esc_attr($property->id); ?>">
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
                <option value="wohnung">Wohnung</option>
                <option value="garage">Garage</option>
                <option value="stellplatz">Stellplatz</option>
                <option value="keller">Keller</option>
                <option value="alle">Alle Typen</option>
            </select>
        </p>

        <p>
            <label for="vm_allocation_type">Verteilungsart</label><br>
            <select name="vm_allocation_type" id="vm_allocation_type" required>
                <option value="wohnflaeche">Wohnfläche</option>
                <option value="personen">Personen</option>
                <option value="distribution_key">Verteilerschlüssel</option>
            </select>
        </p>

        <p>
            <label for="vm_property_distribution_key_id">Objekt-Schlüssel</label><br>
            <select name="vm_property_distribution_key_id" id="vm_property_distribution_key_id">
                <option value="">Bitte wählen</option>
                <?php foreach ($assigned_keys as $key) : ?>
                    <option value="<?php echo esc_attr($key->id); ?>">
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
                <input type="checkbox" name="vm_is_recurring" value="1">
                Wiederkehrende Kategorie
            </label>
        </p>

        <p>
            <button type="submit">Zuordnung speichern</button>
        </p>
    </form>

    <?php if (!empty($categories)) : ?>
        <h3>Vorhandene Objekt-Kategorien</h3>

        <table>
            <thead>
                <tr>
                    <th>Objekt</th>
                    <th>Kategorie</th>
                    <th>Typ</th>
                    <th>Verteilungsart</th>
                    <th>Objekt-Schlüssel</th>
                    <th>Wiederkehrend</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category) : ?>
                    <tr>
                        <td><?php echo esc_html($category->property_name ?? '—'); ?></td>
                        <td><?php echo esc_html($category->name); ?></td>
                        <td><?php echo esc_html(vm_format_type($category->applies_to_type_key)); ?></td>
                        <td><?php echo esc_html($category->allocation_type); ?></td>
                        <td>
                            <?php
                            echo !empty($category->distribution_key_label)
                                ? esc_html($category->distribution_key_label . ' (' . $category->distribution_key_unit_code . ')')
                                : '—';
                            ?>
                        </td>
                        <td><?php echo (int) $category->is_recurring === 1 ? 'Ja' : 'Nein'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>