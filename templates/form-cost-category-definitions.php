<?php if (!empty($message)) : ?>
    <p><strong><?php echo esc_html($message); ?></strong></p>
<?php endif; ?>

<h3>Kategoriedefinition anlegen</h3>

<form method="post">
    <?php wp_nonce_field('vm_save_cost_category_definition'); ?>
    <input type="hidden" name="vm_action" value="save_cost_category_definition">

    <p>
        <label>Bezeichnung</label><br>
        <input type="text" name="vm_name" required>
    </p>

    <p>
        <label>Beschreibung</label><br>
        <textarea name="vm_description" rows="3"></textarea>
    </p>

    <p>
        <label>Standard-Verteilungsart</label><br>
        <select name="vm_default_allocation_type">
            <?php foreach ($apportionment_types as $type_key => $type_label) : ?>
                <option value="<?php echo esc_attr($type_key); ?>">
                    <?php echo esc_html($type_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label>
            <input type="checkbox" name="vm_default_is_recurring" value="1">
            Standardmäßig wiederkehrend
        </label>
    </p>

    <p>
        <button type="submit" class="vm-btn-primary">
            <i class="fa-solid fa-save"></i> Kategoriedefinition speichern
        </button>
    </p>
</form>

<?php if (!empty($definitions)) : ?>
    <h3>Vorhandene Kategoriedefinitionen</h3>

    <table>
        <thead>
            <tr>
                <th>Bezeichnung</th>
                <th>Beschreibung</th>
                <th>Standard-Verteilungsart</th>
                <th>Wiederkehrend</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($definitions as $definition) : ?>
                <tr>
                    <td><?php echo esc_html($definition->name); ?></td>
                    <td><?php echo esc_html($definition->description ?: '—'); ?></td>
                    <td><?php echo esc_html($apportionment_types[$definition->default_allocation_type] ?? $definition->default_allocation_type); ?></td>
                    <td><?php echo !empty($definition->default_is_recurring) ? 'Ja' : 'Nein'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>