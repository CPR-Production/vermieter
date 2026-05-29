<?php
if (!defined('ABSPATH')) {
    exit;
}

$selected_property_id = (int) ($selected_property_id ?? 0);
$selected_year = (int) ($selected_year ?? current_time('Y'));
$period_start = $selected_year . '-01-01';
$period_end = $selected_year . '-12-31';

$category_by_hint = [];
foreach (($property_categories ?? []) as $category) {
    $name = strtolower((string) ($category->category_name ?? $category->name ?? ''));
    if (strpos($name, 'warmwasser') !== false) { $category_by_hint['Warmwasser'] = (int) $category->id; }
    if (strpos($name, 'heizung') !== false || strpos($name, 'heiz') !== false) { $category_by_hint['Heizung'] = (int) $category->id; }
}
?>

<div class="vm-wrap">
    <h2>Heizkostenabrechnung erfassen</h2>

    <?php if (!empty($message)) : ?>
        <div class="notice notice-info"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <form method="get" id="vm-heating-filter-form" class="vm-card" style="margin-bottom: 1rem;">
        <input type="hidden" name="vm_year" id="vm_year_filter" value="<?php echo esc_attr($selected_year); ?>">
        <p>
            <label for="vm_property_id_filter">Objekt</label><br>
            <select name="vm_property_id" id="vm_property_id_filter" onchange="this.form.submit()">
                <?php foreach (($properties ?? []) as $property) : ?>
                    <option value="<?php echo (int) $property->id; ?>" <?php selected($selected_property_id, (int) $property->id); ?>><?php echo esc_html($property->name); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
    </form>

    <?php if (empty($property_categories)) : ?>
        <div class="notice notice-warning"><p>Für dieses Objekt sind noch keine Kostenkategorien zugeordnet. Lege unter „Objekt-Kostenkategorien“ mindestens „Heizung“ und „Warmwasser“ an.</p></div>
    <?php endif; ?>

    <form method="post" class="vm-card">
        <?php wp_nonce_field('vm_save_heating_statement'); ?>
        <input type="hidden" name="vm_action" value="save_heating_statement">
        <input type="hidden" name="vm_property_id" value="<?php echo esc_attr($selected_property_id); ?>">

        <h3>Brunata/Metrona-Abrechnung als Nutzungsbeträge</h3>
        <p><small>Diese Maske ist für Abrechnungen mit Zwischenablesung gedacht. Du trägst die von Brunata fertig berechneten Teilbeträge pro Nutzung/Mietverhältnis ein. Das System summiert daraus die Heizkosten für Statistik und Nebenkostenabrechnung. Es wird kein zusätzlicher Zeitfaktor angewendet.</small></p>

        <p>
            <label for="vm_billing_year">Jahr</label><br>
            <input type="number" name="vm_billing_year" id="vm_billing_year" value="<?php echo esc_attr($selected_year); ?>" min="2000" max="2100" required>
        </p>
        <p>
            <label for="vm_statement_date">Rechnungsdatum</label><br>
            <input type="date" name="vm_statement_date" id="vm_statement_date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>">
        </p>
        <p>
            <label for="vm_period_start">Abrechnungsbeginn</label><br>
            <input type="date" name="vm_period_start" id="vm_period_start" value="<?php echo esc_attr($period_start); ?>" required>
        </p>
        <p>
            <label for="vm_period_end">Abrechnungsende</label><br>
            <input type="date" name="vm_period_end" id="vm_period_end" value="<?php echo esc_attr($period_end); ?>" required>
        </p>
        <p>
            <label for="vm_provider_name">Dienstleister</label><br>
            <input type="text" name="vm_provider_name" id="vm_provider_name" value="Brunata Metrona">
        </p>

        <?php if (empty($usage_rows)) : ?>
            <div class="notice notice-warning"><p>Für dieses Objekt/Jahr wurden keine Mietverhältnisse gefunden. Bitte zuerst die Nutzung/Mieter der Wohnungen erfassen.</p></div>
        <?php else : ?>
            <h3>Positionen</h3>
            <table class="widefat striped vm-table vm-heating-manual-table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Kategorie</th>
                        <th>Gebäude gesamt €<br><small>optional / Statistik</small></th>
                        <?php foreach (($usage_rows ?? []) as $usage) :
                            $tenant_name = trim(($usage->salutation ?? '') . ' ' . ($usage->first_name ?? '') . ' ' . ($usage->last_name ?? ''));
                        ?>
                            <th>
                                <?php echo esc_html($usage->apartment_name); ?><br>
                                <small><?php echo esc_html($tenant_name); ?></small><br>
                                <small><?php echo esc_html(vm_format_date($usage->usage_start) . ' - ' . vm_format_date($usage->usage_end)); ?></small>
                            </th>
                        <?php endforeach; ?>
                        <th>abrechnen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($default_rows ?? []) as $index => $row) :
                        $hint = $row['category_hint'];
                        $selected_category_id = $category_by_hint[$hint] ?? 0;
                    ?>
                        <tr>
                            <td>
                                <input type="hidden" name="vm_rows[<?php echo (int) $index; ?>][cost_type]" value="<?php echo esc_attr($row['cost_type']); ?>">
                                <input type="hidden" name="vm_rows[<?php echo (int) $index; ?>][split_type]" value="<?php echo esc_attr($row['split_type']); ?>">
                                <input type="text" name="vm_rows[<?php echo (int) $index; ?>][label]" value="<?php echo esc_attr($row['label']); ?>" style="min-width:190px;">
                            </td>
                            <td>
                                <select name="vm_rows[<?php echo (int) $index; ?>][property_cost_category_id]">
                                    <option value="0">Nicht zugeordnet</option>
                                    <?php foreach (($property_categories ?? []) as $category) : ?>
                                        <option value="<?php echo (int) $category->id; ?>" <?php selected($selected_category_id, (int) $category->id); ?>><?php echo esc_html($category->category_name ?? $category->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="vm_rows[<?php echo (int) $index; ?>][amount_building_total]" value="" style="width:110px;"></td>
                            <?php foreach (($usage_rows ?? []) as $usage) : ?>
                                <td><input type="text" name="vm_rows[<?php echo (int) $index; ?>][usage_amounts][<?php echo (int) $usage->apartment_tenant_id; ?>]" value="" style="width:110px;"></td>
                            <?php endforeach; ?>
                            <td><input type="checkbox" name="vm_rows[<?php echo (int) $index; ?>][is_billable]" value="1" checked></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p>
            <label for="vm_notes">Notizen</label><br>
            <textarea name="vm_notes" id="vm_notes" rows="3" style="width:100%;"></textarea>
        </p>

        <p><button type="submit" class="button button-primary">Heizkostenabrechnung speichern</button></p>
    </form>

    <h3>Erfasste Heizkostenabrechnungen für dieses Objekt</h3>
    <?php if (empty($statements)) : ?>
        <p>Noch keine Heizkostenabrechnung für dieses Objekt erfasst.</p>
    <?php else : ?>
        <table class="widefat striped vm-table">
            <thead>
                <tr>
                    <th>Jahr</th>
                    <th>Objekt</th>
                    <th>Dienstleister</th>
                    <th>Zeitraum</th>
                    <th>Gebäude gesamt</th>
                    <th>Summe Nutzungsbeträge</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statements as $statement) : ?>
                    <tr>
                        <td><?php echo (int) $statement->billing_year; ?></td>
                        <td><?php echo esc_html($statement->property_name); ?></td>
                        <td><?php echo esc_html($statement->provider_name); ?></td>
                        <td><?php echo esc_html(vm_format_date($statement->period_start) . ' - ' . vm_format_date($statement->period_end)); ?></td>
                        <td><?php echo esc_html(vm_format_money((float) $statement->total_building_amount)); ?></td>
                        <td><?php echo esc_html(vm_format_money((float) $statement->own_unit_amount)); ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Diese Heizkostenabrechnung inklusive erzeugter Kostenpositionen löschen?');">
                                <?php wp_nonce_field('vm_delete_heating_statement'); ?>
                                <input type="hidden" name="vm_action" value="delete_heating_statement">
                                <input type="hidden" name="vm_statement_id" value="<?php echo (int) $statement->id; ?>">
                                <button type="submit" class="button"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const yearField = document.getElementById('vm_billing_year');
            const periodStartField = document.getElementById('vm_period_start');
            const periodEndField = document.getElementById('vm_period_end');
            const yearFilterField = document.getElementById('vm_year_filter');
            if (!yearField || !periodStartField || !periodEndField) { return; }
            const updatePeriodDatesFromYear = function () {
                const year = parseInt(yearField.value, 10);
                if (!year || year < 1000 || year > 9999) { return; }
                periodStartField.value = year + '-01-01';
                periodEndField.value = year + '-12-31';
                if (yearFilterField) { yearFilterField.value = year; }
            };
            yearField.addEventListener('input', updatePeriodDatesFromYear);
            yearField.addEventListener('change', updatePeriodDatesFromYear);
        });
    </script>
</div>
