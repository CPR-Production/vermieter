<?php if (!empty($message)) : ?>
    <p><strong><?php echo esc_html($message); ?></strong></p>
<?php endif; ?>

<h3>Kategorie einem Objekt zuordnen</h3>

<form method="post">
    <?php wp_nonce_field('vm_save_property_cost_category'); ?>
    <input type="hidden" name="vm_action" value="save_property_cost_category">

    <p>
        <label>Kategorie</label><br>
        <select name="vm_cost_category_definition_id" required>
            <option value="">Bitte wählen</option>
            <?php foreach ($definitions as $definition) : ?>
                <option value="<?php echo esc_attr($definition->id); ?>">
                    <?php echo esc_html($definition->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label>Verteilungsart</label><br>
        <select name="vm_allocation_type" required>
            <?php foreach ($apportionment_types as $type_key => $type_label) : ?>
                <option value="<?php echo esc_attr($type_key); ?>">
                    <?php echo esc_html($type_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label>Objekt-Schlüssel</label><br>
        <select name="vm_property_distribution_key_id" required>
            <option value="">Bitte wählen</option>
            <?php foreach ($assigned_keys as $key) : ?>
                <option value="<?php echo esc_attr($key->id); ?>">
                    <?php
                    echo esc_html(
                        ($key->property_name ?: 'Objekt') . ' – ' .
                        ($key->label ?: 'Schlüssel') . ' / ' .
                        ($key->unit_code ?: '') . ' / ' .
                        number_format((float) ($key->total_value ?? 0), 2, ',', '.')
                    );
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label>
            <input type="checkbox" name="vm_is_recurring" value="1">
            Wiederkehrend
        </label>
    </p>

    <p>
        <button type="submit">Objekt-Kategorie speichern</button>
    </p>
</form>

<?php if (!empty($property_categories)) : ?>
    <h3>Vorhandene Objekt-Kategorien</h3>

    <table>
        <thead>
            <tr>
                <th>Objekt</th>
                <th>Kategorie</th>
                <th>Verteilungsart</th>
                <th>Schlüssel-ID</th>
                <th>Wiederkehrend</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($property_categories as $category) : ?>
                <tr>
                    <td><?php echo esc_html($category->property_name ?: '—'); ?></td>
                    <td><?php echo esc_html($category->category_name); ?></td>
                    <td><?php echo esc_html($apportionment_types[$category->allocation_type] ?? $category->allocation_type); ?></td>
                    <td>
                    <?php
                    if (!empty($category->key_label)) {
                        echo esc_html(
                            $category->key_label /* . ' / ' .
                            $category->key_unit_code . ' / ' .
                            number_format((float) $category->key_total_value, 2, ',', '.')
                         */);
                    } else {
                        echo '—';
                    }
                    ?>
                    </td>
                    <td><?php echo !empty($category->is_recurring) ? 'Ja' : 'Nein'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>