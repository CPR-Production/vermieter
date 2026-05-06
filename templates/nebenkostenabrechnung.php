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

    <?php
    $vm_is_heating_cost = function ($cost) use ($statement) {
        $category = Vermieter_Property_Cost_Categories::get($cost->property_cost_category_id ?? 0);
        return $category && (($category->allocation_type ?? '') === 'brunata_statement');
    };

    $vm_is_heating_item = function ($item) {
        return !empty($item['is_brunata_statement']) || (($item['allocation_type'] ?? '') === 'brunata_statement');
    };

    $vm_get_allocation_display = function ($cost) use ($statement) {
        $category = Vermieter_Property_Cost_Categories::get($cost->property_cost_category_id);
        $allocation_display = '—';

        if ($category) {
            $applies_to_type_key = $category->applies_to_type_key ?? 'alle';

            switch ($category->allocation_type) {
                case 'brunata_statement':
                    $allocation_display = 'Lt. Abrechnung Brunata';
                    break;

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
                    $allocation_total = isset($distribution_key->total_value) ? (float) $distribution_key->total_value : 0.0;
                    $allocation_display = $key_label . ' / ' . number_format($allocation_total, 2, ',', '.');
                    break;
            }
        }

        return $allocation_display;
    };

    $vm_render_object_cost_table = function ($title, $costs, $empty_text, $sum_label) use ($vm_get_allocation_display) {
        ?>
        <div style="margin-bottom:25px;">
            <h3><?php echo esc_html($title); ?></h3>

            <?php if (!empty($costs)) : ?>
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
                        <?php
                        $sum = 0.0;
                        foreach ($costs as $cost) :
                            $category = Vermieter_Property_Cost_Categories::get($cost->property_cost_category_id);
                            $sum += (float) ($cost->betrag ?? 0);
                            ?>
                            <tr>
                                <td><?php echo esc_html($cost->name); ?></td>
                                <td><?php echo esc_html($category->name ?? '—'); ?></td>
                                <td><?php echo esc_html(number_format((float) $cost->betrag, 2, ',', '.')); ?></td>
                                <td><?php echo esc_html($vm_get_allocation_display($cost)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top:10px;">
                    <strong><?php echo esc_html($sum_label); ?>:</strong>
                    <?php echo esc_html(number_format((float) $sum, 2, ',', '.')); ?> €
                </p>
            <?php else : ?>
                <p><?php echo esc_html($empty_text); ?></p>
            <?php endif; ?>
        </div>
        <?php
    };

    $vm_render_tenant_cost_table = function ($items, $title, $empty_text) {
        ?>
        <h5 style="margin:16px 0 8px;"><?php echo esc_html($title); ?></h5>

        <?php if (!empty($items)) : ?>
            <?php
            $show_time_columns = false;
            foreach ($items as $item) {
                if (isset($item['occupied_days'], $item['year_days']) && (int) $item['occupied_days'] < (int) $item['year_days']) {
                    $show_time_columns = true;
                    break;
                }
            }
            ?>

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
                        <th>Verteiler / Schlüssel</th>
                        <?php if ($show_time_columns): ?>
                            <th>Anteil vor Zeitfaktor (€)</th>
                            <th>Tageanteil</th>
                        <?php endif; ?>
                        <th>Anteil (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sum_total_cost = 0.0;
                    $sum_share_before_factor = 0.0;
                    $sum_tenant_share = 0.0;
                    $cost_items_by_apartment = [];

                    foreach ($items as $item) {
                        $apartment_id = (int) ($item['apartment_id'] ?? 0);
                        $apartment_name = trim((string) ($item['apartment_name'] ?? ''));
                        $apartment_key = $apartment_id > 0 ? 'apartment_' . $apartment_id : 'apartment_' . md5($apartment_name);

                        if (!isset($cost_items_by_apartment[$apartment_key])) {
                            $cost_items_by_apartment[$apartment_key] = [
                                'apartment_name' => $apartment_name !== '' ? $apartment_name : 'Ohne Einheit',
                                'items' => [],
                                'sum_total_cost' => 0.0,
                                'sum_share_before_factor' => 0.0,
                                'sum_tenant_share' => 0.0,
                            ];
                        }

                        $cost_items_by_apartment[$apartment_key]['items'][] = $item;
                        $cost_items_by_apartment[$apartment_key]['sum_share_before_factor'] += (float) ($item['share_before_factor'] ?? 0);
                        $cost_items_by_apartment[$apartment_key]['sum_tenant_share'] += (float) ($item['tenant_share'] ?? 0);

                        $sum_share_before_factor += (float) ($item['share_before_factor'] ?? 0);
                        $sum_tenant_share += (float) ($item['tenant_share'] ?? 0);
                    }

                    foreach ($cost_items_by_apartment as $apartment_key => $apartment_group) {
                        $unique_total_costs = [];
                        foreach (($apartment_group['items'] ?? []) as $item) {
                            $cost_id = (int) ($item['cost_id'] ?? 0);
                            $total_key = $cost_id > 0
                                ? 'cost_' . $cost_id
                                : 'row_' . md5(($item['cost_name'] ?? '') . '|' . ($item['category_name'] ?? '') . '|' . ($item['total_cost'] ?? 0));

                            if (!isset($unique_total_costs[$total_key])) {
                                $unique_total_costs[$total_key] = (float) ($item['total_cost'] ?? 0);
                            }
                        }

                        $cost_items_by_apartment[$apartment_key]['sum_total_cost'] = round(array_sum($unique_total_costs), 2);
                    }

                    $unique_total_costs = [];
                    foreach ($items as $item) {
                        $cost_id = (int) ($item['cost_id'] ?? 0);
                        $total_key = $cost_id > 0
                            ? 'cost_' . $cost_id
                            : 'row_' . md5(($item['cost_name'] ?? '') . '|' . ($item['category_name'] ?? '') . '|' . ($item['total_cost'] ?? 0));

                        if (!isset($unique_total_costs[$total_key])) {
                            $unique_total_costs[$total_key] = (float) ($item['total_cost'] ?? 0);
                        }
                    }
                    $sum_total_cost = round(array_sum($unique_total_costs), 2);

                    uasort($cost_items_by_apartment, function ($a, $b) {
                        return mb_strtolower((string) $a['apartment_name']) <=> mb_strtolower((string) $b['apartment_name']);
                    });

                    foreach ($cost_items_by_apartment as $apartment_group) :
                        usort($apartment_group['items'], function ($a, $b) {
                            $category_a = mb_strtolower(trim((string) ($a['category_name'] ?? '')));
                            $category_b = mb_strtolower(trim((string) ($b['category_name'] ?? '')));

                            if ($category_a !== $category_b) {
                                return $category_a <=> $category_b;
                            }

                            return mb_strtolower(trim((string) ($a['cost_name'] ?? '')))
                                <=> mb_strtolower(trim((string) ($b['cost_name'] ?? '')));
                        });
                        ?>
                        <tr style="background:#f5f5f5;">
                            <th colspan="<?php echo esc_attr($show_time_columns ? 7 : 5); ?>" style="text-align:left;">
                                Einheit: <?php echo esc_html($apartment_group['apartment_name']); ?>
                            </th>
                        </tr>

                        <?php foreach ($apartment_group['items'] as $item) : ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($item['cost_name']); ?>
                                    <?php if (!empty($item['category_name'])) : ?>
                                        <br><small><?php echo esc_html($item['category_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($item['apartment_name']); ?></td>
                                <td><?php echo esc_html(vm_format_money($item['total_cost'] ?? 0)); ?></td>
                                <td>
                                    <?php if (!empty($item['allocation_display'])) : ?>
                                        <?php echo esc_html($item['allocation_display']); ?>
                                    <?php else : ?>
                                        <?php echo esc_html(number_format((float)($item['tenant_value'] ?? 0), 2, ',', '.')); ?> / <?php echo esc_html(number_format((float)($item['total_value'] ?? 0), 2, ',', '.')); ?>
                                    <?php endif; ?>
                                </td>

                                <?php if ($show_time_columns): ?>
                                    <td><?php echo esc_html(vm_format_money($item['share_before_factor'] ?? 0)); ?></td>
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
                                <?php endif; ?>

                                <td><?php echo esc_html(vm_format_money($item['tenant_share'] ?? 0)); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr style="background:#fafafa;">
                            <th colspan="2" style="text-align:right;">Zwischensumme <?php echo esc_html($apartment_group['apartment_name']); ?></th>
                            <th><?php echo esc_html(vm_format_money($apartment_group['sum_total_cost'])); ?></th>
                            <th></th>
                            <?php if ($show_time_columns): ?>
                                <th><?php echo esc_html(vm_format_money($apartment_group['sum_share_before_factor'])); ?></th>
                                <th></th>
                            <?php endif; ?>
                            <th><?php echo esc_html(vm_format_money($apartment_group['sum_tenant_share'])); ?></th>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Summe</th>
                        <th><?php echo esc_html(vm_format_money($sum_total_cost)); ?></th>
                        <th></th>
                        <?php if ($show_time_columns): ?>
                            <th><?php echo esc_html(vm_format_money($sum_share_before_factor)); ?></th>
                            <th></th>
                        <?php endif; ?>
                        <th><?php echo esc_html(vm_format_money($sum_tenant_share)); ?></th>
                    </tr>
                </tfoot>
            </table>
        <?php else : ?>
            <p><?php echo esc_html($empty_text); ?></p>
        <?php endif; ?>
        <?php
    };
    ?>

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

    <?php
    $operating_costs = [];
    $heating_costs = [];

    if (!empty($statement['costs'])) {
        foreach ($statement['costs'] as $cost) {
            if ($vm_is_heating_cost($cost)) {
                $category = Vermieter_Property_Cost_Categories::get($cost->property_cost_category_id);
                $key = 'brunata_' . (int) $cost->property_cost_category_id . '_' . (int) ($cost->period_year ?? $statement['year']);

                if (!isset($heating_costs[$key])) {
                    $category_name = trim((string) ($category->name ?? 'Heizkosten'));
                    $heating_costs[$key] = (object) [
                        'property_cost_category_id' => (int) $cost->property_cost_category_id,
                        'name' => 'Jahresabrechnung ' . (int) ($cost->period_year ?? $statement['year']) . ($category_name !== '' ? ' - ' . $category_name : ''),
                        'betrag' => 0.0,
                    ];
                }

                $heating_costs[$key]->betrag += (float) ($cost->betrag ?? 0);
                continue;
            }

            $operating_costs['cost_' . (int) $cost->id] = $cost;
        }
    }

    $vm_render_object_cost_table(
        'Gesamt Nebenkosten des Objekts',
        $operating_costs,
        'Keine Nebenkosten für dieses Jahr vorhanden.',
        'Summe Nebenkosten'
    );

    $vm_render_object_cost_table(
        'Gesamt Heizkosten des Objekts',
        $heating_costs,
        'Keine Heizkosten für dieses Jahr vorhanden.',
        'Summe Heizkosten'
    );
    ?>

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

                    <?php
                    $tenant_operating_items = [];
                    $tenant_heating_items = [];

                    foreach (($tenant_statement['cost_items'] ?? []) as $item) {
                        if ($vm_is_heating_item($item)) {
                            $tenant_heating_items[] = $item;
                        } else {
                            $tenant_operating_items[] = $item;
                        }
                    }

                    $vm_render_tenant_cost_table(
                        $tenant_operating_items,
                        'Nebenkosten',
                        'Keine Nebenkostenanteile vorhanden.'
                    );

                    $vm_render_tenant_cost_table(
                        $tenant_heating_items,
                        'Heizkosten',
                        'Keine Heizkostenanteile vorhanden.'
                    );

                    $tenant_operating_sum = 0.0;
                    foreach ($tenant_operating_items as $item) {
                        $tenant_operating_sum += (float) ($item['tenant_share'] ?? 0);
                    }

                    $tenant_heating_sum = 0.0;
                    foreach ($tenant_heating_items as $item) {
                        $tenant_heating_sum += (float) ($item['tenant_share'] ?? 0);
                    }

                    $tenant_nk_advance_sum = (float) ($tenant_statement['nk_advance_sum'] ?? 0);
                    $tenant_hk_advance_sum = (float) ($tenant_statement['hk_advance_sum'] ?? 0);

                    // Zwischenergebnisse korrekt getrennt
                    $tenant_operating_balance = round($tenant_operating_sum - $tenant_nk_advance_sum, 2);
                    $tenant_heating_balance   = round($tenant_heating_sum - $tenant_hk_advance_sum, 2);

                    // Gesamt
                    $tenant_total_balance = round($tenant_operating_balance + $tenant_heating_balance, 2);

                    $is_credit = $tenant_total_balance < 0;
                    $is_debit  = $tenant_total_balance > 0;
                    $prefix = $tenant_total_balance > 0 ? '-' : '';
                    ?>

                    <h5 style="margin:18px 0 8px;">Ergebnis / Vorauszahlungen</h5>

                    <table class="vm-summary-table">
                        <tbody>
                            <!-- Nebenkosten -->
                            <tr>
                                <th style="text-align:left;">Nebenkosten (Übertrag)</th>
                                <td style="text-align:right;"><?php echo esc_html(vm_format_money($tenant_operating_sum)); ?></td>
                            </tr>
                            <tr>
                                <td>abzgl. Vorauszahlungen NK</td>
                                <td style="text-align:right;"><?php echo esc_html(vm_format_money(-$tenant_nk_advance_sum)); ?></td>
                            </tr>
                            <tr class="vm-subtotal">
                                <th style="text-align:left;">Zwischenergebnis Nebenkosten</th>
                                <th style="text-align:right;"><?php echo esc_html(vm_format_money($tenant_operating_balance)); ?></th>
                            </tr>
                            <tr><td></td><td></td></tr>
                            <!-- Heizkosten -->
                            <tr>
                                <th style="text-align:left;">Heizkosten (Übertrag)</th>
                                <td style="text-align:right;"><?php echo esc_html(vm_format_money($tenant_heating_sum)); ?></td>
                            </tr>
                            <tr>
                                <td>abzgl. Vorauszahlungen HK</td>
                                <td style="text-align:right;"><?php echo esc_html(vm_format_money(-$tenant_hk_advance_sum)); ?></td>
                            </tr>
                            <tr class="vm-subtotal">
                                <th style="text-align:left;">Zwischenergebnis Heizkosten</th>
                                <th style="text-align:right;"><?php echo esc_html(vm_format_money($tenant_heating_balance)); ?></th>
                            </tr>
                        </tbody>

                        <tfoot>
                            <!-- Gesamtergebnis -->
                            <tr class="vm-total <?php echo $tenant_total_balance < 0 ? 'vm-credit' : ($tenant_total_balance > 0 ? 'vm-debit' : ''); ?>">
                                <th style="text-align:left;">Ergebnis gesamt</th>
                                <th style="text-align:right;">
                                    <?php echo esc_html($prefix . vm_format_money($tenant_total_balance)); ?>
                                </th>
                            </tr>

                            <!-- Klartext -->
                            <tr>
                                <td colspan="2" style="text-align:center; font-weight:bold;">
                                    <?php
                                        if ($tenant_total_balance > 0) {
                                            echo 'Nachzahlung durch den Mieter';
                                        } elseif ($tenant_total_balance < 0) {
                                            echo 'Guthaben für den Mieter';
                                        } else {
                                            echo 'Ausgeglichen – keine Nachzahlung und kein Guthaben';
                                        }
                                        ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Keine Mieterabrechnungen vorhanden.</p>
        <?php endif; ?>
    </div>
</div>
