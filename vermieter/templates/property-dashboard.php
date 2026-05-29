<?php if (!defined('ABSPATH')) exit; ?>

<?php
$vm_format_date = function ($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date_i18n('d.m.Y', $timestamp) : $date;
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
        <h3><i class="fa-solid fa-building"></i>Objekt</h3>
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
            <a href="<?php echo esc_url(vm_get_page_url('vermieter-objekte', ['edit_id' => (int) $property->id])); ?>">Bearbeiten</a>
        </p>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3><i class="fa-solid fa-door-open"></i> Apartments / Einheiten</h3>

        <?php if (!empty($apartments)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Typ</th>
                        <th>Wohnfläche</th>
                        <th><i class="fa-solid fa-user"></i>Personen</th>
                        <th><i class="fa-solid fa-calendar"></i>Im Bestand seit</th>
                        <th><i class="fa-solid fa-calendar"></i>Aus Bestand bis</th>
                        <th><i class="fa-solid fa-ellipsis"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apartments as $apartment) : ?>
                        <tr>
                            <td><?php echo esc_html($apartment->name); ?></td>
                            <td><?php echo esc_html(vm_format_type($apartment->type_key)); ?></td>
                            <td><?php echo esc_html(number_format((float) $apartment->wohnflaeche, 2, ',', '.')); ?></td>
                            <td><?php echo esc_html($apartment->personen); ?></td>
                            <td><?php echo esc_html($vm_format_date($apartment->acquisition_date ?? '')); ?></td>
                            <td><?php echo esc_html($vm_format_date($apartment->disposal_date ?? '')); ?></td>
                            <td>
                                <a href="<?php echo esc_url(vm_get_page_url('vermieter-wohnungen', ['edit_id' => (int) $apartment->id])); ?>">Bearbeiten</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p style="color:#666;">
                Keine Einheiten vorhanden.
                <a href="<?php echo esc_url(vm_get_page_url('vermieter-wohnungen')); ?>">
                    Jetzt anlegen →
                </a>
            </p>
        <?php endif; ?>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3><i class="fa-solid fa-building-user"></i>Nutzung / Leerstand gesamt</h3>
        
        <?php if (!empty($apartments)) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Einheit</th>
                        <th>Im Bestand seit</th>
                        <th>Im Bestand bis</th>
                        <th>Tage im Bestand</th>
                        <th>Vermietet</th>
                        <th>Leerstand</th>
                        <th>Status aktuell</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apartments as $apartment) : ?>
                        <?php $usage = $apartment_usage_summaries[$apartment->id] ?? []; ?>
                        <tr>
                            <td><?php echo esc_html($apartment->name); ?></td>
                            <td><?php echo esc_html($vm_format_date($usage['inventory_start'] ?? ($apartment->acquisition_date ?? ''))); ?></td>
                            <td><?php echo esc_html($vm_format_date($usage['inventory_end'] ?? ($apartment->disposal_date ?? ''))); ?></td>
                            <td><?php echo esc_html((string) ((int) ($usage['inventory_days'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) ((int) ($usage['tenant_days'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) ((int) ($usage['vacancy_days'] ?? 0))); ?></td>
                            <td><?php echo esc_html($usage['status'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="color:#666; margin-top:8px;">Die Berechnung berücksichtigt den Bestand ab Kaufdatum bzw. bis Verkaufsdatum der Einheit.</p>
        <?php else : ?>
            <p>Keine Einheiten vorhanden.</p>
        <?php endif; ?>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
    <h3><i class="fa-solid fa-file-invoice-dollar"></i>Nebenkosten erfasst</h3>

    <?php if (!empty($apartments) && !empty($all_cost_years)) : ?>
        <table>
            <thead>
                <tr>
                    <th>Einheit</th>
                    <?php foreach ($all_cost_years as $year) : ?>
                        <th><?php echo esc_html((string) $year); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apartments as $apartment) : ?>
                    <tr>
                        <td><?php echo esc_html($apartment->name); ?></td>

                        <?php foreach ($all_cost_years as $year) : ?>
                            <?php
                            $status = $cost_status_matrix[$apartment->id][$year] ?? 'not_relevant';
                            $label = '—';
                            $style = 'background:#f5f5f5; color:#666; text-align:center; font-weight:bold;';

                            if ($status === 'complete') {
                                $label = '✓';
                                $style = 'background:#d4edda; color:#155724; text-align:center; font-weight:bold;';
                            } elseif ($status === 'missing') {
                                $label = '!';
                                $style = 'background:#fff3cd; color:#856404; text-align:center; font-weight:bold;';
                            }
                            ?>
                            <?php
                            $cost_form_url = home_url('/vermieter-kosten-erfassen/');
                            ?>
                            <td style="<?php echo esc_attr($style); ?>">
                                <?php if ($status === 'missing') : ?>
                                    <a href="<?php echo esc_url(add_query_arg([
                                        'vm_property_id' => $selected_property_id,
                                        'vm_year'        => $year,
                                    ], $cost_form_url)); ?>" style="color:inherit; font-weight:bold; text-decoration:none;" title="Kosten für <?php echo esc_attr((string) $year); ?> erfassen">
                                        <?php echo esc_html($label); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html($label); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="color:#666; margin-top:8px;">
            ✓ = Kosten für das Jahr vorhanden, ! = keine Kosten erfasst, — = Einheit nicht im Bestand
        </p>
    <?php elseif (!empty($apartments)) : ?>
        <p>Für die vorhandenen Einheiten konnten keine Bestandsjahre ermittelt werden.</p>
    <?php else : ?>
        <p>Keine Einheiten vorhanden.</p>
    <?php endif; ?>
</div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3><i class="fa-solid fa-user"></i> Mieter</h3>
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
                                <td><?php echo esc_html($vm_format_date($tenant_row->move_in_date)); ?></td>
                                <td><?php echo esc_html($vm_format_date($tenant_row->move_out_date ?: '—')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Keine Mieter-Zuordnungen vorhanden.</p>
        <?php endif; ?>

        <p>
            <a href="<?php echo esc_url(vm_get_page_url('vermieter-wohnung-mieter')); ?>">Bearbeiten</a>
        </p>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3>
            <i class="fa-solid fa-money-bill-wave"></i>
            Kostenarten- / Umlageschlüsselzuordnungen
        </h3>
        <p>
            <?php echo (int) ($cost_category_count ?? 0); ?> Kategorien,
            <?php echo (int) ($distribution_key_count ?? 0); ?> Schlüssel
        </p>

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
                            <td><?php echo esc_html(vm_format_type($category->applies_to_type_key)); ?></td>
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
            <a href="<?php echo esc_url(vm_get_page_url('vermieter-objekt-kostenkategorien', ['property_id' => (int) $selected_property_id])); ?>" class="button" aria-label="Kostenarten-Zuordnungen bearbeiten">Bearbeiten</a>
        </p>
    </div>

    <div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
        <h3><i class="fa-solid fa-cube"></i>Verteilerschlüssel</h3>
        
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
                            <td><?php echo esc_html(vm_format_type($key->applies_to_type_key)); ?></td>
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
            <a href="<?php echo esc_url(vm_get_page_url('vermieter-objekt-schluessel')); ?>">Bearbeiten</a>
        </p>
    </div>
</div>