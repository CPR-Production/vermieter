<?php if (!defined('ABSPATH')) exit; ?>
<?php
$vm_pdf_mode = !empty($vm_pdf_mode);
$vm_pdf_tenant_index = $vm_pdf_tenant_index ?? 'all';
?>

<div class="vm-wrap">
    <h2>Nebenkostenabrechnung</h2>

    <?php if (!$vm_pdf_mode) : ?>
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
    <?php endif; ?>

    <?php if (empty($statement) || empty($statement['property'])) : ?>
        <p>Keine Abrechnungsdaten vorhanden.</p>
        <?php return; ?>
    <?php endif; ?>

    <?php if (!$vm_pdf_mode) : ?>
        <div class="vm-export-controls" style="margin:0 0 20px; padding:12px; border:1px solid #ddd; background:#fafafa;">
            <button type="button"
                    data-vm-pdf-export="1"
                    data-property-id="<?php echo esc_attr($selected_property_id); ?>"
                    data-year="<?php echo esc_attr($selected_year); ?>"
                    data-tenant-index="all">
                <i class="fa-solid fa-file-pdf"></i> Alle Abrechnungen als PDF drucken
            </button>
            <p style="margin:8px 0 0; font-size:12px; color:#666;">
                Bei Mieterwechsel kannst du unten zusätzlich jeden Mieter einzeln als eigenes PDF öffnen.
            </p>
        </div>
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
                    $allocation_display = 'Lt. Abrechnung / Einzelabrechnung';
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
                    $allocation_display = $key_label ;//. ' / ' . number_format($allocation_total, 2, ',', '.');
                    break;
            }
        }

        return $allocation_display;
    };

    $vm_render_object_cost_table = function ($title, $costs, $empty_text, $sum_label) use ($vm_get_allocation_display) {
        ?>
        <div class="vm-pdf-section vm-pdf-section-costs">
            <h3><?php echo esc_html($title); ?></h3>
            <?php if (!empty($costs)) : ?>
                <table class="vm-object-cost-table">
                    <thead>
                        <tr>
                            <th>Rechnung / Position</th>
                            <th>Kategorie</th>
                            <th>Gesamtkosten</th>
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
                                <td style="text-align:right"><?php echo esc_html(vm_format_money($cost->betrag)); ?></td>
                                <td style="text-align:right"><?php echo esc_html($vm_get_allocation_display($cost)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <th colspan="2"><?php echo esc_html($sum_label); ?></th>
                            <th style="text-align:right"><?php echo esc_html(vm_format_money($sum)); ?></th>
                            <th></th> 
                        </tr>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php echo esc_html($empty_text); ?></p>
            <?php endif; ?>
        </div>
        <?php
    };

    $vm_render_tenant_cost_table = function ($items, $title, $empty_text) {
        ?>
        <div class="vm-pdf-section vm-pdf-section-tenant-costs">
            <h5><?php echo esc_html($title); ?></h5>
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

                    $apartment_index = 0;

                    foreach ($cost_items_by_apartment as $apartment_group) :
                        $apartment_index++;
                        $unit_page_class = $apartment_index > 1 ? ' vm-unit-page-break' : '';

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
           

                        <div class="vm-unit-cost-block<?php echo esc_attr($unit_page_class); ?>">
                        <table class="vm-tenant-cost-table <?php echo $show_time_columns ? 'vm-tenant-cost-table--with-time' : 'vm-tenant-cost-table--simple'; ?>">
                            <thead>
                                <tr>
                                    <th>Kostenposition</th>
                                    <th>Gesamtkosten</th>
                                    <th>Verteiler / Schlüssel</th>
                                    <?php if ($show_time_columns): ?>
                                        <th>Anteil vor Zeitfaktor</th>
                                        <th>Tageanteil</th>
                                    <?php endif; ?>
                                    <th>Anteil</th>
                                </tr>
                            </thead>
                            <tbody>
                        <tr style="background:#f5f5f5;">
                            <th colspan="<?php echo esc_attr($show_time_columns ? 6 : 4); ?>" style="text-align:left;">
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
                                <!-- <td><?php echo esc_html($item['apartment_name']); ?></td> -->
                                <td style="text-align:right"><?php echo esc_html(vm_format_money($item['total_cost'] ?? 0)); ?></td>
                                <td style="text-align:center">
                                    <?php if (!empty($item['allocation_display'])) : ?>
                                        <?php echo esc_html($item['allocation_display']); ?>
                                    <?php else : ?>
                                        <?php echo esc_html(number_format((float)($item['tenant_value'] ?? 0), 2, ',', '.')); ?> / <?php echo esc_html(number_format((float)($item['total_value'] ?? 0), 2, ',', '.')); ?>
                                    <?php endif; ?>
                                </td>
                                <?php if ($show_time_columns): ?>
                                    <td style="text-align:right"><?php echo esc_html(vm_format_money($item['share_before_factor'] ?? 0)); ?></td>
                                    <td style="text-align:center">
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

                                <td style="text-align:right"><?php echo esc_html(vm_format_money($item['tenant_share'] ?? 0)); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr style="background:#fafafa;">
                            <th colspan="1" style="text-align:right;">Zwischensumme<br> <?php echo esc_html($apartment_group['apartment_name']); ?></th>
                            <th style="text-align:right"><?php echo esc_html(vm_format_money($apartment_group['sum_total_cost'])); ?></th>
                            <th></th>
                            <?php if ($show_time_columns): ?>
                                <th style="text-align:right"><?php echo esc_html(vm_format_money($apartment_group['sum_share_before_factor'])); ?></th>
                                <th></th>
                            <?php endif; ?>
                            <th style="text-align:right"><?php echo esc_html(vm_format_money($apartment_group['sum_tenant_share'])); ?></th>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th colspan="1">Summe</th>
                        <th style="text-align:right"><?php echo esc_html(vm_format_money($sum_total_cost)); ?></th>
                        <th></th> 
                        <?php if ($show_time_columns): ?>
                            <th style="text-align:right"><?php echo esc_html(vm_format_money($sum_share_before_factor)); ?></th>
                            <th></th>
                        <?php endif; ?>
                        <th style="text-align:right"><?php echo esc_html(vm_format_money($sum_tenant_share)); ?></th>
                    </tr>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php echo esc_html($empty_text); ?></p>
        <?php endif; ?>
        </div>
        <?php
    };


    $vm_render_tax_deductible_table = function ($items) {
        $tax_rows = [];

        $groups = [
            'haushaltsnah' => 'Haushaltsnahe Dienstleistungen',
            'handwerker'   => 'Handwerkerleistungen',
        ];

        foreach (($items ?? []) as $item) {
            $type = $item['tax_deductible_type'] ?? 'none';
            $tax_share = (float) ($item['tax_deductible_share'] ?? 0);

            if (!in_array($type, ['haushaltsnah', 'handwerker'], true) || round($tax_share, 2) <= 0) {
                continue;
            }

            $tax_rows[] = $item;
        }

        if (empty($tax_rows)) {
            return;
        }

        $show_time_columns = false;
        foreach ($tax_rows as $row) {
            if (isset($row['occupied_days'], $row['year_days']) && (int) $row['occupied_days'] < (int) $row['year_days']) {
                $show_time_columns = true;
                break;
            }
        }

        $colspan = $show_time_columns ? 6 : 4;
        ?>
        <br>
        <div class="vm-pdf-section vm-pdf-section-tax-deductible">
            <h3>Steuerlicher Ausweis nach § 35a EStG</h3>

            <?php foreach ($groups as $group_type => $group_title) : ?>
                <?php
                $group_rows = array_values(array_filter($tax_rows, function ($row) use ($group_type) {
                    return ($row['tax_deductible_type'] ?? '') === $group_type;
                }));

                if (empty($group_rows)) {
                    continue;
                }

                $rows_by_apartment = [];
                $group_sum_tax_deductible_amount = 0.0;
                $group_sum_share_before_factor = 0.0;
                $group_sum_tax_deductible_share = 0.0;
                $group_seen_tax_deductible_amount_keys = [];

                foreach ($group_rows as $row) {
                    $apartment_id = (int) ($row['apartment_id'] ?? 0);
                    $apartment_name = trim((string) ($row['apartment_name'] ?? ''));
                    $apartment_key = $apartment_id > 0 ? 'apartment_' . $apartment_id : 'apartment_' . md5($apartment_name);

                    if (!isset($rows_by_apartment[$apartment_key])) {
                        $rows_by_apartment[$apartment_key] = [
                            'apartment_name' => $apartment_name !== '' ? $apartment_name : 'Ohne Einheit',
                            'items' => [],
                            'sum_tax_deductible_amount' => 0.0,
                            'sum_share_before_factor' => 0.0,
                            'sum_tax_deductible_share' => 0.0,
                        ];
                    }

                    $rows_by_apartment[$apartment_key]['items'][] = $row;
                    $rows_by_apartment[$apartment_key]['sum_tax_deductible_amount'] += (float) ($row['tax_deductible_amount'] ?? 0);
                    $rows_by_apartment[$apartment_key]['sum_share_before_factor'] += (float) ($row['share_before_factor'] ?? 0);
                    $rows_by_apartment[$apartment_key]['sum_tax_deductible_share'] += (float) ($row['tax_deductible_share'] ?? 0);

                    // Wichtig bei mehreren Einheiten, z. B. Wohnung + Stellplatz:
                    // Die gleiche Kostenposition kann in der Anzeige pro Einheit auftauchen.
                    // Die Lohn-/Arbeitskosten GESAMT gehören aber zur Rechnung/Kostenposition
                    // und dürfen in der Endsumme nicht mehrfach gezählt werden.
                    $tax_amount_unique_key = '';
                    if (!empty($row['cost_id'])) {
                        $tax_amount_unique_key = 'cost_' . (int) $row['cost_id'];
                    } else {
                        $tax_amount_unique_key = md5(
                            (string) ($row['cost_name'] ?? '') . '|' .
                            (string) ($row['category_name'] ?? '') . '|' .
                            (string) ($row['tax_deductible_type'] ?? '') . '|' .
                            number_format((float) ($row['tax_deductible_amount'] ?? 0), 2, '.', '')
                        );
                    }

                    if (!isset($group_seen_tax_deductible_amount_keys[$tax_amount_unique_key])) {
                        $group_seen_tax_deductible_amount_keys[$tax_amount_unique_key] = true;
                        $group_sum_tax_deductible_amount += (float) ($row['tax_deductible_amount'] ?? 0);
                    }

                    // Diese beiden Werte sind Mieter-/Einheitenanteile und bleiben additiv.
                    $group_sum_share_before_factor += (float) ($row['share_before_factor'] ?? 0);
                    $group_sum_tax_deductible_share += (float) ($row['tax_deductible_share'] ?? 0);
                }

                uasort($rows_by_apartment, function ($a, $b) {
                    return mb_strtolower((string) $a['apartment_name']) <=> mb_strtolower((string) $b['apartment_name']);
                });
                ?>

                <h4><?php echo esc_html($group_title); ?></h4>

                <table class="vm-pdf-table vm-pdf-table-compact">
                    <thead>
                        <tr>
                            <th>Kostenart</th>
                            <th style="text-align:right;">Lohn-/Arbeitskosten gesamt</th>
                            <th>Verteiler / Schlüssel</th>
                            <?php if ($show_time_columns): ?>
                                <th style="text-align:right;">Anteil vor Zeitfaktor</th>
                                <th style="text-align:right;">Tageanteil</th>
                            <?php endif; ?>
                            <th style="text-align:right;">davon §35a</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows_by_apartment as $apartment_group) : ?>
                            <?php
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
                                <th colspan="<?php echo esc_attr($colspan); ?>" style="text-align:left;">
                                    Einheit: <?php echo esc_html($apartment_group['apartment_name']); ?>
                                </th>
                            </tr>

                            <?php foreach ($apartment_group['items'] as $row): ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($row['cost_name'] ?? ''); ?>
                                        <?php if (!empty($row['category_name'])) : ?>
                                            <br><small><?php echo esc_html($row['category_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;"><?php echo esc_html(vm_format_money($row['tax_deductible_amount'] ?? 0)); ?></td>
                                    <td style="text-align:center;">
                                        <?php
                                        if (!empty($row['allocation_display'])) {
                                            echo esc_html($row['allocation_display']);
                                        } else {
                                            echo esc_html(
                                                number_format((float) ($row['tenant_value'] ?? 0), 2, ',', '.')
                                                . ' / ' .
                                                number_format((float) ($row['total_value'] ?? 0), 2, ',', '.')
                                            );
                                        }
                                        ?>
                                    </td>

                                    <?php if ($show_time_columns): ?>
                                        <td style="text-align:right;"><?php echo esc_html(vm_format_money($row['share_before_factor'] ?? 0)); ?></td>
                                        <td style="text-align:center;">
                                            <?php
                                            $occupied_days = (int) ($row['occupied_days'] ?? 0);
                                            $year_days = (int) ($row['year_days'] ?? 0);
                                            $tenant_factor = (float) ($row['tenant_factor'] ?? 0);

                                            echo esc_html(
                                                $occupied_days . ' / ' . $year_days . ' (' . number_format($tenant_factor, 4, ',', '.') . ')'
                                            );
                                            ?>
                                        </td>
                                    <?php endif; ?>

                                    <td style="text-align:right;"><?php echo esc_html(vm_format_money($row['tax_deductible_share'] ?? 0)); ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr style="background:#fafafa;">
                                <th style="text-align:right;">Zwischensumme<br><?php echo esc_html($apartment_group['apartment_name']); ?></th>
                                <th style="text-align:right;"><?php echo esc_html(vm_format_money($apartment_group['sum_tax_deductible_amount'])); ?></th>
                                <th></th>
                                <?php if ($show_time_columns): ?>
                                    <th style="text-align:right;"><?php echo esc_html(vm_format_money($apartment_group['sum_share_before_factor'])); ?></th>
                                    <th></th>
                                <?php endif; ?>
                                <th style="text-align:right;"><?php echo esc_html(vm_format_money($apartment_group['sum_tax_deductible_share'])); ?></th>
                            </tr>
                        <?php endforeach; ?>

                        <tr>
                            <th>Summe <?php echo esc_html($group_title); ?></th>
                            <th style="text-align:right;"><?php echo esc_html(vm_format_money($group_sum_tax_deductible_amount)); ?></th>
                            <th></th>
                            <?php if ($show_time_columns): ?>
                                <th style="text-align:right;"><?php echo esc_html(vm_format_money($group_sum_share_before_factor)); ?></th>
                                <th></th>
                            <?php endif; ?>
                            <th style="text-align:right;"><?php echo esc_html(vm_format_money($group_sum_tax_deductible_share)); ?></th>
                        </tr>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
        <?php
    };

    $vm_pdf_logo_url = '';
    if (defined('VERMIETER_PATH') && file_exists(VERMIETER_PATH . 'assets/img/logo.png')) {
        $vm_pdf_logo_url = VERMIETER_URL . 'assets/img/logo.png';
    }

    $vm_format_pdf_date = function ($date) {
        if (empty($date)) {
            return '—';
        }

        $timestamp = strtotime((string) $date);
        if (!$timestamp) {
            return (string) $date;
        }

        return date_i18n('d.m.Y', $timestamp);
    };

    $vm_settings = class_exists('Vermieter_Admin_Pages')
        ? Vermieter_Admin_Pages::get_vm_settings()
        : [];

    $vm_render_pdf_cover = function ($tenant_statement, $tenant_total_balance, $tenant_operating_sum, $tenant_heating_sum, $tenant_nk_advance_sum, $tenant_hk_advance_sum, $tenant_settlement_payment_sum = 0.0) use ($statement, $vm_pdf_logo_url, $vm_format_pdf_date, $vm_settings) {
        $property = $statement['property'];
        $tenant_name = trim((string) ($tenant_statement['tenant_name'] ?? ''));
        $tenant_salutation = trim((string) ($tenant_statement['tenant_salutation'] ?? ''));
        $tenant_full_name = trim((string) ($tenant_statement['tenant_full_name'] ?? ''));
        if ($tenant_full_name === '') {
            $tenant_full_name = trim(preg_replace('/^(Herr|Frau|Familie|Firma)\s+/i', '', $tenant_name));
        }

        $address_salutation = $tenant_salutation;
        if ($tenant_salutation === 'Herr') {
            $address_salutation = 'Herrn';
        }

        $letter_salutation = 'Sehr geehrte Damen und Herren,';
        if ($tenant_salutation === 'Herr' && $tenant_full_name !== '') {
            $letter_salutation = 'Sehr geehrter Herr ' . $tenant_full_name . ',';
        } elseif ($tenant_salutation === 'Frau' && $tenant_full_name !== '') {
            $letter_salutation = 'Sehr geehrte Frau ' . $tenant_full_name . ',';
        } elseif ($tenant_name !== '') {
            $letter_salutation = 'Sehr geehrte ' . $tenant_name . ',';
        }

        $pdf_sender_parts = array_filter([
            trim((string) ($vm_settings['landlord_name'] ?? '')),
            trim((string) ($vm_settings['landlord_street'] ?? '')),
            trim(trim((string) ($vm_settings['landlord_zip'] ?? '')) . ' ' . trim((string) ($vm_settings['landlord_city'] ?? ''))),
        ]);

        if (empty($pdf_sender_parts)) {
            $pdf_sender_parts = array_filter([
                trim((string) ($property->name ?? '')),
                trim(($property->street ?? '') . ' ' . ($property->house_number ?? '')),
                trim(($property->zip_code ?? '') . ' ' . ($property->city ?? '')),
            ]);
        }

        $pdf_address_name = trim($address_salutation . ' ' . $tenant_full_name);
        if ($pdf_address_name === '') {
            $pdf_address_name = $tenant_name;
        }

        $year = (int) ($statement['year'] ?? 0);
        $period_text = '01.01.' . $year . ' bis 31.12.' . $year;
        $move_in = $vm_format_pdf_date($tenant_statement['move_in_date'] ?? '');
        $move_out = $vm_format_pdf_date($tenant_statement['move_out_date'] ?? '');
        $result_class = $tenant_total_balance < 0 ? 'vm-pdf-result-credit' : ($tenant_total_balance > 0 ? 'vm-pdf-result-debit' : '');
        $result_label = $tenant_total_balance > 0 ? 'Nachzahlung' : ($tenant_total_balance < 0 ? 'Guthaben' : 'Ausgeglichen');
        $tenant_address_lines = Vermieter_Tenants::format_mailing_address_lines($tenant_statement['mailing_address'] ?? '');

        if (empty($tenant_address_lines)) {
            $tenant_address_lines = [
                trim(($property->street ?? '') . ' ' . ($property->house_number ?? '')),
                trim(($property->zip_code ?? '') . ' ' . ($property->city ?? '')),
            ];
        }

        $tenant_address_lines = array_values(array_filter($tenant_address_lines, function ($line) {
            return trim((string) $line) !== '';
        }));
        ?>
        <section class="vm-pdf-cover">
            <div class="vm-pdf-header">
                <div class="vm-pdf-header-left">

                    <div class="vm-pdf-sender">
                        <?php echo esc_html(implode(' · ', $pdf_sender_parts)); ?>
                    </div>

                    <div class="vm-pdf-address">
                        <?php echo esc_html($pdf_address_name); ?><br>

                        <?php foreach ($tenant_address_lines as $address_line) : ?>
                            <?php echo esc_html($address_line); ?><br>
                        <?php endforeach; ?>
                    </div>

                </div>

                <div class="vm-pdf-header-right">

                    <?php if (!empty($vm_pdf_logo_url)) : ?>
                        <div class="vm-pdf-logo-wrap">
                            <img
                                class="vm-pdf-logo"
                                src="<?php echo esc_url($vm_pdf_logo_url); ?>"
                                alt="Logo"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="vm-pdf-date">
                        <?php echo esc_html(date_i18n('d.m.Y')); ?>
                    </div>
                    <!--
                    OPTIONAL SPÄTER:

                    <div class="vm-pdf-extra">
                        Vertragsnummer ...
                        Ansprechpartner ...
                        Telefon ...
                    </div>
                    -->
                </div>
            </div>

            <div class="vm-pdf-letter">
                <h1>Nebenkostenabrechnung <?php echo esc_html($year); ?></h1>

                <p><?php echo esc_html($letter_salutation); ?></p>

                <p>anbei erhalten Sie die Nebenkostenabrechnung für das Abrechnungsjahr <?php echo esc_html($year); ?>.</p>

                <p>
                    <strong>Objekt:</strong>
                    <?php echo esc_html(($property->name ?? '') . ', ' . trim(($property->street ?? '') . ' ' . ($property->house_number ?? '')) . ', ' . trim(($property->zip_code ?? '') . ' ' . ($property->city ?? ''))); ?><br>
                    <strong>Abrechnungszeitraum:</strong> <?php echo esc_html($period_text); ?><br>
                    <strong>Nutzungszeitraum:</strong> <?php echo esc_html($move_in); ?> bis <?php echo esc_html($move_out); ?>
                </p>

                <div class="vm-pdf-result-box <?php echo esc_attr($result_class); ?>">
                    <span class="vm-pdf-result-label"><?php echo esc_html($result_label); ?> aus der Nebenkostenabrechnung</span>
                    <span class="vm-pdf-result-amount"><?php echo esc_html(vm_format_money(abs($tenant_total_balance))); ?></span>
                </div>

                <p>Die Zusammensetzung der Kosten, die angesetzten Vorauszahlungen sowie bereits erfasste Ausgleichszahlungen finden Sie auf den folgenden Seiten.</p>

                <?php if ($tenant_total_balance > 0) : ?>
                    <p>
                        Bitte überweisen Sie die ausstehende Summe von
                        <strong><?php echo esc_html(vm_format_money($tenant_total_balance)); ?></strong>
                        auf folgendes Konto:
                    </p>
                    <p>
                        <?php if (!empty($vm_settings['bank_account_holder'])) : ?>
                            Kontoinhaber: <?php echo esc_html($vm_settings['bank_account_holder']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($vm_settings['bank_iban'])) : ?>
                            IBAN: <?php echo esc_html($vm_settings['bank_iban']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($vm_settings['bank_bic'])) : ?>
                            BIC: <?php echo esc_html($vm_settings['bank_bic']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($vm_settings['bank_name'])) : ?>
                            Bank: <?php echo esc_html($vm_settings['bank_name']); ?>
                        <?php endif; ?>
                    </p>
                <?php elseif ($tenant_total_balance < 0) : ?>
                    <p>Es ergibt sich ein Guthaben zu Ihren Gunsten.</p>
                <?php endif; ?>

                <p>Bitte prüfen Sie die Abrechnung in Ruhe. Bei Rückfragen melden Sie sich gerne.</p>

                <p>Mit freundlichen Grüßen</p>
                <p><?php echo trim((string) ($vm_settings['landlord_name'] ?? '')) ?></p>
            </div>
            <div class="vm-pdf-letter-footer">
                Nebenkosten: <?php echo esc_html(vm_format_money($tenant_operating_sum)); ?> ·
                Heizkosten: <?php echo esc_html(vm_format_money($tenant_heating_sum)); ?> ·
                Vorauszahlungen NK: <?php echo esc_html(vm_format_money($tenant_nk_advance_sum)); ?> ·
                Vorauszahlungen HK: <?php echo esc_html(vm_format_money($tenant_hk_advance_sum)); ?>
            </div>
        </section>
        <?php
    };

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
    ?>

    <?php if (!$vm_pdf_mode) : ?>
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
    <?php endif; ?>

    <div>
        <?php if (!$vm_pdf_mode) : ?>
        <h3>Abrechnung je Mieter</h3>
        <?php endif; ?>
        <?php if (!empty($statement['grouped_tenant_statements'])) : ?>
            <?php foreach ($statement['grouped_tenant_statements'] as $tenant_index => $tenant_statement) : ?>
                <?php if ($vm_pdf_mode && $vm_pdf_tenant_index !== 'all' && (int) $vm_pdf_tenant_index !== (int) $tenant_index) { continue; } ?>
                <?php
                $vm_visible_tenant_indexes = array_keys($statement['grouped_tenant_statements']);
                $vm_last_visible_tenant_index = end($vm_visible_tenant_indexes);

                if ($vm_pdf_mode) {
                    $vm_cover_operating_sum = 0.0;
                    $vm_cover_heating_sum = 0.0;

                    foreach (($tenant_statement['cost_items'] ?? []) as $vm_cover_item) {
                        if ($vm_is_heating_item($vm_cover_item)) {
                            $vm_cover_heating_sum += (float) ($vm_cover_item['tenant_share'] ?? 0);
                        } else {
                            $vm_cover_operating_sum += (float) ($vm_cover_item['tenant_share'] ?? 0);
                        }
                    }

                    $vm_cover_nk_advance_sum = (float) ($tenant_statement['nk_advance_sum'] ?? 0);
                    $vm_cover_hk_advance_sum = (float) ($tenant_statement['hk_advance_sum'] ?? 0);
                    $vm_cover_settlement_payment_sum = (float) ($tenant_statement['settlement_payment_sum'] ?? 0);
                    $vm_cover_total_balance = round(
                        (($vm_cover_operating_sum - $vm_cover_nk_advance_sum) +
                        ($vm_cover_heating_sum - $vm_cover_hk_advance_sum)) - $vm_cover_settlement_payment_sum,
                        2
                    );

                    $vm_render_pdf_cover(
                        $tenant_statement,
                        $vm_cover_total_balance,
                        $vm_cover_operating_sum,
                        $vm_cover_heating_sum,
                        $vm_cover_nk_advance_sum,
                        $vm_cover_hk_advance_sum,
                        $vm_cover_settlement_payment_sum
                    );
                }
                ?>
                <?php if ($vm_pdf_mode) : ?>
                    <div class="vm-pdf-running-footer">
                        <span>Nebenkostenabrechnung <?php echo esc_html($statement['year']); ?></span>
                        <span><?php
                            echo esc_html(
                                $statement['property']->street . ' ' .
                                $statement['property']->house_number . ', ' .
                                $statement['property']->zip_code . ' ' .
                                $statement['property']->city
                            ); 
                            ?>
                        </span>

                    </div>
                <?php endif; ?>
                <div class="vm-tenant-statement vm-pdf-detail-page <?php echo $vm_pdf_mode ? 'vm-pdf-tenant-card' : ''; ?> <?php echo ($vm_pdf_mode && $vm_pdf_tenant_index === 'all' && (int) $tenant_index !== (int) $vm_last_visible_tenant_index) ? 'vm-pdf-page-break' : ''; ?>" style="border:1px solid #ddd; padding:5mm; margin-bottom:25px;">
                    <h4><?php echo esc_html($tenant_statement['tenant_name']); ?></h4>

                    <?php if (!$vm_pdf_mode) : ?>
                        <p class="vm-export-controls" style="margin:0 0 12px;">
                            <button type="button"
                                    data-vm-pdf-export="1"
                                    data-property-id="<?php echo esc_attr($selected_property_id); ?>"
                                    data-year="<?php echo esc_attr($selected_year); ?>"
                                    data-tenant-index="<?php echo esc_attr($tenant_index); ?>">
                                <i class="fa-solid fa-file-pdf"></i> PDF für diesen Mieter
                            </button>
                        </p>
                    <?php endif; ?>

                    <p>
                        <strong>Eingezogen am:</strong> <?php echo esc_html(vm_format_date($tenant_statement['move_in_date']) ?: '—'); ?><br>
                        <strong>Ausgezogen am:</strong> <?php echo esc_html(vm_format_date($tenant_statement['move_out_date']) ?: '—'); ?>
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

                    // Gesamt vor/ nach Zusatz-Zahlungen
                    $tenant_balance_before_special_payments = round($tenant_operating_balance + $tenant_heating_balance, 2);
                    $tenant_settlement_payment_sum = (float) ($tenant_statement['settlement_payment_sum'] ?? 0);
                    $tenant_total_balance = round($tenant_balance_before_special_payments - $tenant_settlement_payment_sum, 2);

                    $is_credit = $tenant_total_balance < 0;
                    $is_debit  = $tenant_total_balance > 0;
                    $prefix = ''; // Kosten minus Vorauszahlungen: positiv = Nachzahlung, negativ = Guthaben

                    ?>

                    <?php if ($vm_pdf_mode) : ?>
                        <div class="vm-pdf-object-overview">
                            <h3>Objekt und Kostenübersicht</h3>
                            <p>
                                <strong><?php echo esc_html($statement['property']->name); ?></strong><br>
                                <?php echo esc_html($statement['property']->street . ' ' . $statement['property']->house_number . ', ' . $statement['property']->zip_code . ' ' . $statement['property']->city); ?><br>
                                <strong>Abrechnungsjahr:</strong> <?php echo esc_html($statement['year']); ?>
                            </p>
                        </div>

                        <?php
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
                    <?php endif; ?>

                    <div class="vm-pdf-section vm-pdf-section-summary">
                        <h5>Ergebnis / Vorauszahlungen</h5>

                        <table class="vm-summary-table">
                            <tbody>
                                <!-- Nebenkosten -->
                                <tr>
                                    <th style="text-align:left;">Nebenkosten (Übertrag)</th>
                                    <td style="text-align:right;"><?php echo esc_html(vm_format_money($tenant_operating_sum)); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        abzgl. Vorauszahlungen NK
                                        <?php if (!empty($tenant_statement['nk_advance_lines'])) : ?>
                                            <br>
                                            <small>
                                                <?php foreach ($tenant_statement['nk_advance_lines'] as $line) : ?>
                                                    <?php
                                                    echo esc_html(
                                                        (int) $line['months'] . ' × ' .
                                                        vm_format_money((float) $line['amount']) . ' = ' .
                                                        vm_format_money((float) $line['sum'])
                                                    );
                                                    ?><br>
                                                <?php endforeach; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
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
                                    <td>
                                        abzgl. Vorauszahlungen HK
                                        <?php if (!empty($tenant_statement['hk_advance_lines'])) : ?>
                                            <br>
                                            <small>
                                                <?php foreach ($tenant_statement['hk_advance_lines'] as $line) : ?>
                                                    <?php
                                                    echo esc_html(
                                                        (int) $line['months'] . ' × ' .
                                                        vm_format_money((float) $line['amount']) . ' = ' .
                                                        vm_format_money((float) $line['sum'])
                                                    );
                                                    ?><br>
                                                <?php endforeach; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;"><?php echo esc_html(vm_format_money(-$tenant_hk_advance_sum)); ?></td>
                                </tr>
                                <tr class="vm-subtotal">
                                    <th style="text-align:left;">Zwischenergebnis Heizkosten</th>
                                    <th style="text-align:right;"><?php echo esc_html(vm_format_money($tenant_heating_balance)); ?></th>
                                </tr>
                                <!-- Gesamtergebnis -->
                                <tr class="vm-subtotal">
                                    <th style="text-align:left;">Ergebnis vor Zusatz-Zahlungen</th>
                                    <th style="text-align:right;">
                                        <?php echo esc_html(vm_format_money($tenant_balance_before_special_payments)); ?>
                                    </th>
                                </tr>
                                <?php if (round($tenant_settlement_payment_sum, 2) != 0.0) : ?>
                                    <tr>
                                        <td>abzgl. erfasste Zusatz-Zahlungen / Ausgleich</td>
                                        <td style="text-align:right;"><?php echo esc_html(vm_format_money(-$tenant_settlement_payment_sum)); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="vm-total <?php echo $tenant_total_balance < 0 ? 'vm-credit' : ($tenant_total_balance > 0 ? 'vm-debit' : ''); ?>">
                                    <th style="text-align:left;">Offenes Ergebnis gesamt</th>
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
                            </tbody>
                        </table>
                    </div>
                    <?php
                    $vm_render_tax_deductible_table(array_merge($tenant_operating_items, $tenant_heating_items));
                    ?>

                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Keine Mieterabrechnungen vorhanden.</p>
        <?php endif; ?>
    </div>
</div>
