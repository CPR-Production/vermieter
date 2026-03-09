<?php if (!defined('ABSPATH')) exit; ?>

<?php
$edit_item = isset($edit_item) ? $edit_item : null;
?>

<div class="vm-wrap">
    <h2><?php echo $edit_item ? 'Wohnung bearbeiten' : 'Wohnung anlegen'; ?></h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_apartment'); ?>
        <input type="hidden" name="vm_action" value="save_apartment">
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
            <label for="vm_apartment_name">Name</label><br>
            <input
                type="text"
                name="vm_apartment_name"
                id="vm_apartment_name"
                required
                value="<?php echo esc_attr($edit_item->name ?? ''); ?>"
            >
        </p>

        <p>
            <label for="vm_type_key">Typ</label><br>
            <select name="vm_type_key" id="vm_type_key" required>
                <option value="wohnung" <?php selected($edit_item->type_key ?? '', 'wohnung'); ?>>Wohnung</option>
                <option value="garage" <?php selected($edit_item->type_key ?? '', 'garage'); ?>>Garage</option>
                <option value="stellplatz" <?php selected($edit_item->type_key ?? '', 'stellplatz'); ?>>Stellplatz</option>
                <option value="keller" <?php selected($edit_item->type_key ?? '', 'keller'); ?>>Keller</option>
            </select>
        </p>

        <p>
            <label for="vm_wohnflaeche">Wohnfläche</label><br>
            <input
                type="text"
                name="vm_wohnflaeche"
                id="vm_wohnflaeche"
                value="<?php echo esc_attr(isset($edit_item->wohnflaeche) ? str_replace('.', ',', (string) $edit_item->wohnflaeche) : '0,00'); ?>"
            >
        </p>

        <p>
            <label for="vm_personen">Personen</label><br>
            <input
                type="number"
                name="vm_personen"
                id="vm_personen"
                min="0"
                value="<?php echo esc_attr($edit_item->personen ?? 0); ?>"
            >
        </p>

        <p>
            <button type="submit"><?php echo $edit_item ? 'Wohnung aktualisieren' : 'Wohnung speichern'; ?></button>
            <?php if ($edit_item) : ?>
                <a href="<?php echo esc_url(remove_query_arg('edit_id')); ?>" style="margin-left:10px;">Abbrechen</a>
            <?php endif; ?>
        </p>
    </form>

    <?php if (!empty($apartments)) : ?>
        <h3>Vorhandene Wohnungen</h3>
        <table>
            <thead>
                <tr>
                    <th>Objekt</th>
                    <th>Name</th>
                    <th>Typ</th>
                    <th>Wohnfläche</th>
                    <th>Personen</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apartments as $apartment) : ?>
                    <tr>
                        <td><?php echo esc_html($apartment->property_name ?? '—'); ?></td>
                        <td><?php echo esc_html($apartment->name); ?></td>
                        <td><?php echo esc_html($apartment->type_key); ?></td>
                        <td><?php echo esc_html(number_format((float) $apartment->wohnflaeche, 2, ',', '.')); ?></td>
                        <td><?php echo esc_html($apartment->personen); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('edit_id', (int) $apartment->id)); ?>">Bearbeiten</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>