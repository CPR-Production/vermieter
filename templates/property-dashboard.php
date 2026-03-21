<?php if (!defined('ABSPATH')) exit; ?>

<?php
$vm_get_page_url = function ($slug, $query = []) {
    $page = get_page_by_path($slug, OBJECT, 'page');

    if (!$page) {
        return '#';
    }

    $url = get_permalink($page->ID);

    if (!empty($query)) {
        $url = add_query_arg($query, $url);
    }

    return $url;
};
?>

<div class="vm-wrap">
    <h2>Objekt-Übersicht</h2>

    <form method="get" style="margin-bottom:20px;">
        <p>
            <label for="vm_property_id">Objekt wählen</label><br>
            <select name="vm_property_id" id="vm_property_id" onchange="this.form.submit()">
                <?php foreach ($properties as $item) : ?>
                    <option value="<?php echo esc_attr($item->id); ?>" <?php selected((int) $selected_property_id, (int) $item->id); ?>>
                        <?php
                        echo esc_html(
                            $item->name . ' - ' .
                            $item->street . ' ' .
                            $item->house_number . ', ' .
                            $item->zip_code . ' ' .
                            $item->city
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
    </form>

    <?php if (!$property) : ?>
        <p>Kein Objekt gefunden.</p>
    <?php return; endif; ?>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3>Objekt</h3>
        <p>
            <strong><?php echo esc_html($property->name); ?></strong><br>
            <?php
            echo esc_html(
                $property->street . ' ' .
                $property->house_number . ', ' .
                $property->zip_code . ' ' .
                $property->city
            );
            ?>
        </p>
        <p>
            <a href="<?php echo esc_url($vm_get_page_url('vermieter-objekte', ['edit_id' => (int) $property->id])); ?>">Bearbeiten</a>
        </p>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3>Apartments / Einheiten</h3>

        <?php if (!empty($apartments)) : ?>
            <table>
                <thead>
                    <tr>
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
                            <td><?php echo esc_html($apartment->name); ?></td>
                            <td><?php echo esc_html($apartment->type_key); ?></td>
                            <td><?php echo esc_html(number_format((float) $apartment->wohnflaeche, 2, ',', '.')); ?></td>
                            <td><?php echo esc_html($apartment->personen); ?></td>
                            <td>
                                <a href="<?php echo esc_url($vm_get_page_url('vermieter-wohnungen', ['edit_id' => (int) $apartment->id])); ?>">Bearbeiten</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Keine Einheiten vorhanden.</p>
        <?php endif; ?>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3>Mieter</h3>

        <?php
        $has_tenants = false;
        foreach ($apartment_tenants as $rows) {
            if (!empty($rows)) {
                $has_tenants = true;
                break;
            }
        }
        ?>

        <?php if ($has_tenants) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Einheit</th>
                        <th>Mieter</th>
                        <th>Einzug</th>
                        <th>Auszug</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apartments as $apartment) : ?>
                        <?php foreach (($apartment_tenants[$apartment->id] ?? []) as $tenant_row) : ?>
                            <tr>
                                <td><?php echo esc_html($apartment->name); ?></td>
                                <td><?php echo esc_html(trim($tenant_row->salutation . ' ' . $tenant_row->first_name . ' ' . $tenant_row->last_name)); ?></td>
                                <td><?php echo esc_html($tenant_row->move_in_date); ?></td>
                                <td><?php echo esc_html($tenant_row->move_out_date ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Keine Mieter-Zuordnungen vorhanden.</p>
        <?php endif; ?>

        <p>
            <a href="<?php echo esc_url($vm_get_page_url('vermieter-wohnung-mieter')); ?>">Bearbeiten</a>
        </p>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3>Kostenarten- / Umlageschlüsselzuordnungen</h3>

        <?php if (!empty($cost_categories)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Kategorie</th>
                        <th>Typ</th>
                        <th>Verteilungsart</th>
                        <th>Schlüssel</th>
                        <th>Wiederkehrend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cost_categories as $category) : ?>
                        <tr>
                            <td><?php echo esc_html($category->name); ?></td>
                            <td><?php echo esc_html($category->applies_to_type_key); ?></td>
                            <td>
                                <?php
                                echo esc_html(
                                    $apportionment_types[$category->allocation_type] ?? $category->allocation_type
                                );
                                ?>
                            </td>
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
        <?php else : ?>
            <p>Keine Kostenarten-Zuordnungen vorhanden.</p>
        <?php endif; ?>

        <p>
            <a href="<?php echo esc_url($vm_get_page_url('vermieter-objekt-kostenkategorien')); ?>">Bearbeiten</a>
        </p>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3>Verteilerschlüssel</h3>

        <?php if (!empty($distribution_keys)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Typ</th>
                        <th>Bezeichnung</th>
                        <th>Einheit</th>
                        <th>Von insgesamt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribution_keys as $key) : ?>
                        <tr>
                            <td><?php echo esc_html($key->applies_to_type_key); ?></td>
                            <td><?php echo esc_html($key->label); ?></td>
                            <td><?php echo esc_html($key->unit_code); ?></td>
                            <td><?php echo esc_html(number_format((float) $key->total_value, 2, ',', '.')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Keine Verteilerschlüssel vorhanden.</p>
        <?php endif; ?>

        <p>
            <a href="<?php echo esc_url($vm_get_page_url('vermieter-objekt-schluessel')); ?>">Bearbeiten</a>
        </p>
    </div>
</div>