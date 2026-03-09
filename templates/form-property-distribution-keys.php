<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Schlüssel einem Objekt zuordnen</h2>

    <?php if (!empty($message)) : ?>
        <p><strong><?php echo esc_html($message); ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('vm_save_property_distribution_key'); ?>
        <input type="hidden" name="vm_action" value="save_property_distribution_key">

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
            <label for="vm_distribution_key_definition_id">Schlüsseldefinition</label><br>
            <select name="vm_distribution_key_definition_id" id="vm_distribution_key_definition_id" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($definitions as $definition) : ?>
                    <option value="<?php echo esc_attr($definition->id); ?>">
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
            <button type="submit">Zuordnung speichern</button>
        </p>
    </form>

    <?php if (!empty($assigned_keys)) : ?>
        <h3>Vorhandene Zuordnungen</h3>

        <form method="post">
            <?php wp_nonce_field('vm_save_inline_distribution_values'); ?>
            <input type="hidden" name="vm_action" value="save_inline_distribution_values">
            <table>
                <thead>
                    <tr>
                        <th>Objekt</th>
                        <th>Adresse</th>
                        <th>Bezeichnung</th>
                        <th>Verteilwert</th>
                        <th>Einheit</th>
                        <th>Von insgesamt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assigned_keys as $key) : ?>
                        <?php
                        $property_id = (int) $key->property_id;
                        $property_distribution_key_id = (int) $key->id;
                        $apartments = $apartments_by_property[$property_id] ?? [];
                        $distribution_values = $distribution_values_map[$property_distribution_key_id] ?? [];
                        $sum = 0;
                        foreach ($distribution_values as $single_value) {
                            $sum += (float) $single_value;
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html($key->property_name ?: '—'); ?></td>
                            <td>
                                <?php
                                echo esc_html(
                                    trim(
                                        ($key->property_street ?? '') . ' ' .
                                        ($key->property_house_number ?? '') /* . ', ' .
                                        ($key->property_zip_code ?? '') . ' ' .
                                        ($key->property_city ?? '') */
                                    )
                                );
                                ?>
                            </td>
                            <td><?php echo esc_html($key->label); ?></td>
                            <td>
                                <?php if (!empty($apartments)) : ?>
                                    <?php foreach ($apartments as $apartment) : ?>
                                        <?php
                                        $apartment_id = (int) $apartment->id;
                                        $current_value = isset($distribution_values[$apartment_id])
                                            ? (float) $distribution_values[$apartment_id]
                                            : 0;
                                        ?>
                                        <div style="margin-bottom:8px;">
                                            <!-- <label style="display:block; font-weight:600; margin-bottom:2px;">
                                                <?php echo esc_html($apartment->name); ?>
                                            </label> -->
                                            <input
                                                type="text"
                                                name="vm_distribution_values[<?php echo esc_attr($property_distribution_key_id); ?>][<?php echo esc_attr($apartment_id); ?>]"
                                                value="<?php echo esc_attr(number_format($current_value, 2, ',', '')); ?>"
                                                style="width:120px;"
                                            >
                                        </div>
                                    <?php endforeach; ?>

                                    <div style="margin-top:8px; font-size:12px;">
                                        <strong>Aktuell:</strong>
                                        <?php echo esc_html(number_format((float) $sum, 2, ',', '.')); ?>
                                    </div>
                                <?php else : ?>
                                    <em>Keine Wohnungen vorhanden</em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($key->unit_code); ?></td>
                            <td><?php echo esc_html(number_format((float) $key->total_value, 2, ',', '.')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="submit">Verteilwerte speichern</button>
            </p>
        </form>
    <?php endif; ?>
    <?php if (!empty($selected_key)) : ?>
    <hr>

    <h3>Verteilwerte je Wohnung</h3>

    <p>
        <strong>Objekt:</strong>
        <?php echo esc_html($selected_key->property_name ?: '—'); ?><br>

        <strong>Schlüssel:</strong>
        <?php
        echo esc_html(
            $selected_key->label . ' (' .
            $selected_key->unit_code . ' / ' .
            number_format((float) $selected_key->total_value, 2, ',', '.') . ')'
        );
        ?>
    </p>

    <?php if (!empty($apartments)) : ?>
        <form method="post">
            <?php wp_nonce_field('vm_save_apartment_distribution_values'); ?>
            <input type="hidden" name="vm_action" value="save_apartment_distribution_values">
            <input type="hidden" name="vm_property_distribution_key_id" value="<?php echo esc_attr($selected_key_id); ?>">

            <table>
                <thead>
                    <tr>
                        <th>Wohnung</th>
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
                                    value="<?php echo esc_attr(isset($distribution_values[$apartment->id]) ? str_replace('.', ',', (string) $distribution_values[$apartment->id]) : '0,00'); ?>"
                                >
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Aktuelle Summe</th>
                        <th><?php echo esc_html(number_format((float) $distribution_sum, 2, ',', '.')); ?></th>
                    </tr>
                </tfoot>
            </table>

            <p>
                <button type="submit">Verteilwerte speichern</button>
            </p>
        </form>
        <?php else : ?>
            <p>Für dieses Objekt sind noch keine Wohnungen angelegt.</p>
    <?php endif; ?>
    <?php endif; ?>
</div>