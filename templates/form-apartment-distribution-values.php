<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Verteilwerte den Wohnungen zuordnen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="get" style="margin-bottom:20px;">
        <p>
            <label for="vm_property_id_filter">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id_filter" onchange="this.form.submit()">
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>" <?php selected($selected_property_id, $property->id); ?>>
                        <?php echo esc_html($property->name . ' - ' . $property->street . ' ' . $property->house_number); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="vm_distribution_key_id_filter">Verteilerschlüssel</label><br>
            <select name="vm_distribution_key_id" id="vm_distribution_key_id_filter" onchange="this.form.submit()">
                <?php foreach ($distribution_keys as $key) : ?>
                    <option value="<?php echo esc_attr($key->id); ?>" <?php selected($selected_distribution_key_id, $key->id); ?>>
                        <?php echo esc_html($key->label . ' (' . $key->unit_code . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
    </form>

    <?php if ($distribution_key) : ?>
        <p>
            <strong>Einheit:</strong> <?php echo esc_html($distribution_key->unit_code); ?><br>
            <strong>Von insgesamt:</strong> <?php echo esc_html(number_format((float) $distribution_key->total_value, 2, ',', '.')); ?><br>
            <strong>Aktuell eingetragene Summe:</strong> <?php echo esc_html(number_format((float) $sum_values, 2, ',', '.')); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field('vm_save_distribution_values'); ?>
            <input type="hidden" name="vm_action" value="save_distribution_values">
            <input type="hidden" name="vm_distribution_key_id" value="<?php echo esc_attr($selected_distribution_key_id); ?>">

            <table>
                <thead>
                    <tr>
                        <th>Apartment</th>
                        <th>Wert</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apartments as $apartment) : ?>
                        <tr>
                            <td><?php echo esc_html($apartment->name); ?></td>
                            <td>
                                <input
                                    type="text"
                                    name="vm_distribution_values[<?php echo esc_attr($apartment->id); ?>]"
                                    value="<?php echo esc_attr(isset($values[$apartment->id]) ? str_replace('.', ',', (string) $values[$apartment->id]) : '0,00'); ?>"
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="submit">Verteilwerte speichern</button>
            </p>
        </form>
    <?php else : ?>
        <p>Bitte zuerst ein Objekt und einen Verteilerschlüssel auswählen.</p>
    <?php endif; ?>
</div>