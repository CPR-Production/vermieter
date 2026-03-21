<?php if (!defined('ABSPATH')) exit; ?>

<div class="vm-wrap">
    <h2>Nebenkostenabrechnung</h2>

    <form method="get" style="margin-bottom:20px;">
        <p>
            <label for="vm_property_id">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id">
                <?php foreach ($properties as $property) : ?>
                    <option value="<?php echo esc_attr($property->id); ?>" <?php selected((int) $selected_property_id, (int) $property->id); ?>>
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
            <label for="vm_year">Jahr</label><br>
            <input type="number" name="vm_year" id="vm_year" value="<?php echo esc_attr($selected_year); ?>" required>
        </p>

        <p>
            <button type="submit">Abrechnung anzeigen</button>
        </p>
    </form>

    <?php if (empty($statement) || empty($statement['property'])) : ?>
        <p>Keine Abrechnungsdaten vorhanden.</p>
        <?php return; ?>
    <?php endif; ?>

    <div style="margin-bottom:20px;">
        <h3>Objekt</h3>
        <p>
            <strong><?php echo esc_html($statement['property']->name); ?></strong><br>
            <?php
            echo esc_html(
                $statement['property']->street . ' ' .
                $statement['property']->house_number . ', ' .
                $statement['property']->zip_code . ' ' .
                $statement['property']->city
            );
            ?><br>
            <strong>Abrechnungsjahr:</strong> <?php echo esc_html($statement['year']); ?>
        </p>
    </div>

    <div style="margin-bottom:25px;">
        <h3>Gesamtkosten des Objekts</h3>

        <?php if (!empty($statement['costs'])) : ?>
            <table>
                <thead>
                    <tr>
                        <th>Rechnung / Position</th>
                        <th>Kategorie</th>
                        <th>Gesamtkosten (€)</th>
                        <th>Aufteilung gesamt</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($statement['costs'] as $cost) : ?>
                <?php
                $category = Vermieter_Property_Cost_Categories::get($cost->property_cost_category_id);

                $allocation_total = null;
                $allocation_display = '—';

                if ($category) {

                    $applies_to_type_key = $category->applies_to_type_key ?? 'alle';

                    switch ($category->allocation_type) {

                        case 'wohnflaeche':
                            $allocation_total = Vermieter_Nebenkosten_Billing::get_total_living_space(
                                $statement['property']->id,
                                $applies_to_type_key
                            );

                            $allocation_display = 'Wohnfläche / ' . number_format((float) $allocation_total, 2, ',', '.');
                            break;

                        case 'personen':
                            $allocation_total = Vermieter_Nebenkosten_Billing::get_total_persons(
                                $statement['property']->id,
                                $applies_to_type_key
                            );

                            $allocation_display = 'Personen / ' . number_format((float) $allocation_total, 2, ',', '.');
                            break;
                        case 'distribution_key':
                            $property_distribution_key_id = (int) ($category->property_distribution_key_id ?? 0);
                            $distribution_key = Vermieter_Property_Distribution_Keys::get($property_distribution_key_id);

                            $key_label = $distribution_key->label ?? '—';
                            $allocation_total = isset($distribution_key->total_value)
                                ? (float) $distribution_key->total_value
                                : 0.0;

                            $allocation_display = $key_label . ' / ' . number_format($allocation_total, 2, ',', '.');
                            break;
                    }
                }
                ?>

                <tr>
                    <td><?php echo esc_html($cost->name); ?></td>
                    <td><?php echo esc_html($category->name ?? '—'); ?></td>
                    <td><?php echo esc_html(number_format((float) $cost->betrag, 2, ',', '.')); ?></td>
                    <td><?php echo esc_html($allocation_display); ?></td>
                </tr>

            <?php endforeach; ?>
            </tbody>
            </table>
            <p style="margin-top:10px;">
                <strong>Summe Gesamtkosten:</strong>
                <?php echo esc_html(number_format((float) $statement['cost_total'], 2, ',', '.')); ?> €
            </p>
        <?php else : ?>
            <p>Keine Kosten für dieses Jahr vorhanden.</p>
        <?php endif; ?>
    </div>

    <div>
        <h3>Abrechnung je Mieter</h3>

        <?php if (!empty($statement['grouped_tenant_statements'])) : ?>
            <?php foreach ($statement['grouped_tenant_statements'] as $tenant_statement) : ?>
                <div style="border:1px solid #ddd; padding:15px; margin-bottom:25px;">
                    <h4><?php echo esc_html($tenant_statement['tenant_name']); ?></h4>

                    <p>
                        <strong>Eingezogen am:</strong> <?php echo esc_html($tenant_statement['move_in_date'] ?: '—'); ?><br>
                        <strong>Ausgezogen am:</strong> <?php echo esc_html($tenant_statement['move_out_date'] ?: '—'); ?>
                    </p>
                    <?php if (!empty($tenant_statement['cost_items'])) : ?>
                    <?php 
                        $show_time_columns = false;
                        foreach ($tenant_statement['cost_items'] as $item) {
                            if (
                                isset($item['occupied_days'], $item['year_days']) &&
                                (int)$item['occupied_days'] < (int)$item['year_days']
                            ) {
                                $show_time_columns = true;
                                break;
                            }
                        } ?>

                    <?php if ($show_time_columns): ?>
                        <p style="font-size: 12px; color: #666;">
                            Hinweis: Die Anteile wurden anteilig nach Mietdauer im Abrechnungsjahr berechnet.
                        </p>
                    <?php endif; ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Kostenposition</th>
                                    <th>Einheit</th>
                                    <th>Gesamtkosten (€)</th>
                                    <th>Schlüssel</th>
                                    <th>Aufteilung gesamt</th>
                                    <?php if ($show_time_columns): ?>
                                        <th>Tageanteil</th>
                                        <th>Anteil vor Zeitfaktor (€)</th>
                                    <?php endif; ?>
                                    <th>Anteil (€)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sum_total_cost = 0.0;
                                $sum_tenant_share = 0.0;
                                ?>
                                <?php foreach ($tenant_statement['cost_items'] as $item) : ?>
                                    <?php
                                    $sum_total_cost += (float) ($item['total_cost'] ?? 0);
                                    $sum_tenant_share += (float) ($item['tenant_share'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($item['cost_name']); ?></td>
                                        <td><?php echo esc_html($item['apartment_name']); ?></td>
                                        <td><?php echo esc_html(vm_format_money($item['total_cost'] ?? 0)); ?></td>
                                        <td><?php echo esc_html(number_format((float)($item['tenant_value'] ?? 0), 2, ',', '.')); ?></td>
                                        <td><?php echo esc_html(number_format((float)($item['total_value'] ?? 0), 2, ',', '.')); ?></td>

                                        <?php if ($show_time_columns): ?>
                                            <td>
                                                <?php
                                                $occupied_days = (int) ($item['occupied_days'] ?? 0);
                                                $year_days = (int) ($item['year_days'] ?? 0);
                                                $tenant_factor = (float) ($item['tenant_factor'] ?? 0);

                                                echo esc_html(
                                                    $occupied_days . ' / ' . $year_days . ' (' . number_format($tenant_factor, 4, ',', '.') . ')'
                                                );
                                                ?>
                                            </td>

                                            <td><?php echo esc_html(vm_format_money($item['share_before_factor'] ?? 0)); ?></td>
                                        <?php endif; ?>

                                        <td><?php echo esc_html(vm_format_money($item['tenant_share'] ?? 0)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Summe</th>
                                    <th><?php echo esc_html(vm_format_money($sum_total_cost)); ?></th>
                                    <th></th>
                                    <th></th>
                                    <?php if ($show_time_columns): ?>
                                        <th></th>
                                        <th></th>
                                    <?php endif; ?>
                                    <th><?php echo esc_html(vm_format_money($sum_tenant_share)); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php else : ?>
                        <p>Keine Kostenanteile vorhanden.</p>
                    <?php endif; ?>

                    <p style="margin-top:12px;">
                        <strong>Vorauszahlungen NK:</strong>
                        <?php echo esc_html(vm_format_money($tenant_statement['nk_advance_sum'])); ?><br>

                        <strong>Vorauszahlungen HK:</strong>
                        <?php echo esc_html(vm_format_money($tenant_statement['hk_advance_sum'])); ?><br>

                        <strong>Summe Vorauszahlungen:</strong>
                        <?php echo esc_html(vm_format_money($tenant_statement['advance_sum'])); ?><br>

                        <strong>Ergebnis:</strong>
                        <?php echo esc_html(vm_format_money($tenant_statement['balance'])); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Keine Mieterabrechnungen vorhanden.</p>
        <?php endif; ?>
    </div>
</div>