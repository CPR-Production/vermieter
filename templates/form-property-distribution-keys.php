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
        <h3>Vorhandene Zuordnungen</h3>

        <table>
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
                        <td><?php echo esc_html($key->applies_to_type_key); ?></td>
                        <td><?php echo esc_html($key->label); ?></td>
                        <td><?php echo esc_html($key->unit_code); ?></td>
                        <td><?php echo esc_html(number_format((float) $key->total_value, 2, ',', '.')); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('edit_id', (int) $key->id)); ?>">Bearbeiten</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($assigned_keys)) : ?>
        <h3>Verteilwerte je Apartment</h3>

        <form method="post">
            <?php wp_nonce_field('vm_save_inline_distribution_values'); ?>
            <input type="hidden" name="vm_action" value="save_inline_distribution_values">

            <?php foreach ($assigned_keys as $key) : ?>
                <?php
                $property_distribution_key_id = (int) $key->id;
                $apartments = $apartments_by_property_distribution_key[$property_distribution_key_id] ?? [];
                $values = $distribution_values_map[$property_distribution_key_id] ?? [];
                ?>

                <h4>
                    <?php
                    echo esc_html(
                        ($key->property_name ?: '—') . ' | ' .
                        $key->applies_to_type_key . ' | ' .
                        $key->label . ' (' . $key->unit_code . ' / ' .
                        number_format((float) $key->total_value, 2, ',', '.') . ')'
                    );
                    ?>
                </h4>

                <?php if (!empty($apartments)) : ?>
                    <table>
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
                                    <td><?php echo esc_html($apartment->type_key); ?></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="vm_distribution_values[<?php echo esc_attr($property_distribution_key_id); ?>][<?php echo esc_attr($apartment->id); ?>]"
                                            value="<?php echo esc_attr(isset($values[$apartment->id]) ? str_replace('.', ',', (string) $values[$apartment->id]) : '0,00'); ?>"
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>Keine passenden Apartments für diesen Typ vorhanden.</p>
                <?php endif; ?>
            <?php endforeach; ?>

            <p>
                <button type="submit">Verteilwerte speichern</button>
            </p>
        </form>
    <?php endif; ?>
</div>