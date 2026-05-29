<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Verteilerschlüssel anlegen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_distribution_key'); ?>
        <input type="hidden" name="vm_action" value="save_distribution_key">

        <p>
            <label for="vm_property_id">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>">
                        <?php echo esc_html($property->name . ' - ' . $property->street . ' ' . $property->house_number); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_label">Bezeichnung</label><br>
            <input type="text" name="vm_label" id="vm_label" required placeholder="z. B. Stellplätze">
        </p>

        <p>
            <label for="vm_unit_code">Einheit</label><br>
            <input type="text" name="vm_unit_code" id="vm_unit_code" required placeholder="z. B. STEL">
        </p>

        <p>
            <label for="vm_total_value">Von insgesamt</label><br>
            <input type="text" name="vm_total_value" id="vm_total_value" required placeholder="z. B. 1000,00">
        </p>

        <p>
            <button type="submit" class="vm-btn-primary">
                <i class="fa-solid fa-save"></i> Verteilerschlüssel speichern
            </button>
        </p>
    </form>

    <?php if (!empty($distribution_keys)) : ?>
        <h3>Vorhandene Verteilerschlüssel</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Objekt</th>
                    <th>Bezeichnung</th>
                    <th>Einheit</th>
                    <th>Von insgesamt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($distribution_keys as $key) : ?>
                    <tr>
                        <td><?php echo esc_html($key->property_name ?: '—'); ?></td>
                        <td>
                            <?php
                            echo esc_html(
                                trim(
                                    ($key->property_street ?? '') . ' ' .
                                    ($key->property_house_number ?? '') // . ', ' .
                                    //($key->property_zip_code ?? '') . ' ' .
                                    //($key->property_city ?? '')
                                )
                            );
                            ?>
                        </td>
                        <td><?php echo esc_html($key->label); ?></td>
                        <td><?php echo esc_html($key->unit_code); ?></td>
                        <td><?php echo esc_html(number_format((float) $key->total_value, 2, ',', '.')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>