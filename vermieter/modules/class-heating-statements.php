<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Heating_Statements
{
    public static function get_default_rows()
    {
        return [
            ['cost_type' => 'heating', 'split_type' => 'base', 'label' => 'Heizung Grundkosten', 'category_hint' => 'Heizung'],
            ['cost_type' => 'heating', 'split_type' => 'consumption', 'label' => 'Heizung Verbrauchskosten', 'category_hint' => 'Heizung'],
            ['cost_type' => 'heating', 'split_type' => 'special', 'label' => 'Verbrauchsschätzung / Sonderkosten', 'category_hint' => 'Heizung'],
            ['cost_type' => 'hot_water', 'split_type' => 'base', 'label' => 'Warmwasser Grundkosten', 'category_hint' => 'Warmwasser'],
            ['cost_type' => 'hot_water', 'split_type' => 'consumption', 'label' => 'Warmwasser Verbrauchskosten', 'category_hint' => 'Warmwasser'],
        ];
    }

    public static function get_usage_rows_by_property_and_year($property_id, $year)
    {
        global $wpdb;

        $property_id = (int) $property_id;
        $year = (int) $year;
        if ($property_id <= 0 || $year <= 0) {
            return [];
        }

        $year_start = $year . '-01-01';
        $year_end = $year . '-12-31';

        $table_links = $wpdb->prefix . 'vm_apartment_tenants';
        $table_apartments = $wpdb->prefix . 'vm_apartments';
        $table_tenants = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                at.id AS apartment_tenant_id,
                at.apartment_id,
                at.tenant_id,
                at.move_in_date,
                at.move_out_date,
                a.name AS apartment_name,
                t.salutation,
                t.first_name,
                t.last_name,
                GREATEST(at.move_in_date, %s) AS usage_start,
                LEAST(COALESCE(at.move_out_date, %s), %s) AS usage_end
             FROM $table_links at
             INNER JOIN $table_apartments a ON at.apartment_id = a.id
             LEFT JOIN $table_tenants t ON at.tenant_id = t.id
             WHERE at.user_id = %d
               AND a.property_id = %d
               AND at.move_in_date <= %s
               AND COALESCE(at.move_out_date, '9999-12-31') >= %s
             ORDER BY a.name ASC, at.move_in_date ASC, at.id ASC",
            $year_start,
            $year_end,
            $year_end,
            get_current_user_id(),
            $property_id,
            $year_end,
            $year_start
        ));
    }

    public static function save($data, $rows)
    {
        if (!is_user_logged_in()) { return false; }

        global $wpdb;
        $table_statements = $wpdb->prefix . 'vm_heating_statements';
        $table_items = $wpdb->prefix . 'vm_heating_statement_items';
        $user_id = get_current_user_id();

        $property_id = (int) ($data['property_id'] ?? 0);
        $year = (int) ($data['billing_year'] ?? 0);
        $period_start = sanitize_text_field($data['period_start'] ?? '');
        $period_end = sanitize_text_field($data['period_end'] ?? '');

        if ($property_id <= 0 || $year <= 0 || $period_start === '' || $period_end === '') {
            return false;
        }

        $wpdb->insert($table_statements, [
            'user_id' => $user_id,
            'property_id' => $property_id,
            'apartment_id' => 0,
            'provider_name' => sanitize_text_field($data['provider_name'] ?? 'Brunata Metrona'),
            'billing_year' => $year,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'statement_date' => !empty($data['statement_date']) ? sanitize_text_field($data['statement_date']) : null,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        ], ['%d','%d','%d','%s','%d','%s','%s','%s','%s']);

        $statement_id = (int) $wpdb->insert_id;
        if (!$statement_id) { return false; }

        $usage_rows = self::get_usage_rows_by_property_and_year($property_id, $year);
        $usage_by_id = [];
        foreach ($usage_rows as $usage) {
            $usage_by_id[(int) $usage->apartment_tenant_id] = $usage;
        }

        $total_building_amount = 0.0;
        $own_unit_amount = 0.0;
        $created_costs = 0;

        foreach ((array) $rows as $row) {
            $amount_building = self::to_float($row['amount_building_total'] ?? 0);
            $property_cost_category_id = (int) ($row['property_cost_category_id'] ?? 0);
            $is_billable = !empty($row['is_billable']) ? 1 : 0;
            $label = sanitize_text_field($row['label'] ?? 'Heizkosten');
            $split_type = sanitize_text_field($row['split_type'] ?? 'consumption');
            $cost_type = sanitize_text_field($row['cost_type'] ?? 'heating');
            $usage_amounts = (array) ($row['usage_amounts'] ?? []);

            $total_building_amount += $amount_building;

            foreach ($usage_amounts as $apartment_tenant_id => $raw_amount) {
                $apartment_tenant_id = (int) $apartment_tenant_id;
                $amount_own = self::to_float($raw_amount);

                if ($amount_own <= 0 || empty($usage_by_id[$apartment_tenant_id])) {
                    continue;
                }

                $usage = $usage_by_id[$apartment_tenant_id];
                $apartment_id = (int) $usage->apartment_id;
                $cost_id = null;

                if ($is_billable && $property_cost_category_id > 0) {
                    $cost_id = Vermieter_Costs::add([
                        'property_id' => $property_id,
                        'property_cost_category_id' => $property_cost_category_id,
                        'name' => 'Jahresabrechnung ' . $year . ' - ' . $label,
                        'betrag' => $amount_own,
                        'invoice_date' => !empty($data['statement_date']) ? sanitize_text_field($data['statement_date']) : current_time('Y-m-d'),
                        'period_start' => $period_start,
                        'period_end' => $period_end,
                        'period_year' => $year,
                        'target_apartment_id' => $apartment_id,
                        'apartment_tenant_id' => $apartment_tenant_id,
                        'calculation_mode' => 'manual_tenant_amount',
                        'source_type' => 'heating_statement',
                        'source_id' => $statement_id,
                        'no_time_factor' => 1,
                    ]);

                    if ($cost_id) { $created_costs++; }
                }

                $wpdb->insert($table_items, [
                    'user_id' => $user_id,
                    'statement_id' => $statement_id,
                    'cost_id' => $cost_id ? (int) $cost_id : null,
                    'apartment_tenant_id' => $apartment_tenant_id,
                    'cost_type' => $cost_type,
                    'split_type' => $split_type,
                    'label' => $label,
                    'property_cost_category_id' => $property_cost_category_id,
                    'amount_building_total' => $amount_building,
                    'amount_own_unit' => $amount_own,
                    'consumption_building_total' => 0,
                    'consumption_own_unit' => 0,
                    'consumption_unit' => '',
                    'distribution_key' => '',
                    'base_value_building' => 0,
                    'base_value_own_unit' => 0,
                    'price_per_unit' => 0,
                    'already_period_related' => 1,
                    'is_billable' => $is_billable,
                ], ['%d','%d','%d','%d','%s','%s','%s','%d','%f','%f','%f','%f','%s','%s','%f','%f','%f','%d','%d']);

                $own_unit_amount += $amount_own;
            }
        }

        $wpdb->update($table_statements, [
            'total_building_amount' => $total_building_amount > 0 ? $total_building_amount : $own_unit_amount,
            'own_unit_amount' => $own_unit_amount,
            'updated_at' => current_time('mysql'),
        ], ['id' => $statement_id, 'user_id' => $user_id], ['%f','%f','%s'], ['%d','%d']);

        return ['statement_id' => $statement_id, 'created_costs' => $created_costs];
    }

    public static function get_all_by_user($property_id = 0, $year = 0)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_heating_statements';
        $table_properties = $wpdb->prefix . 'vm_properties';
        $where = ['s.user_id = %d'];
        $args = [get_current_user_id()];
        if ($property_id > 0) { $where[] = 's.property_id = %d'; $args[] = (int) $property_id; }
        if ($year > 0) { $where[] = 's.billing_year = %d'; $args[] = (int) $year; }
        $sql = "SELECT s.*, p.name AS property_name
                FROM $table s
                LEFT JOIN $table_properties p ON s.property_id = p.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY s.billing_year DESC, p.name ASC, s.id DESC";
        return $wpdb->get_results($wpdb->prepare($sql, $args));
    }

    public static function get_items($statement_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_heating_statement_items';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE statement_id = %d AND user_id = %d ORDER BY id ASC",
            (int) $statement_id,
            get_current_user_id()
        ));
    }

    public static function delete($statement_id)
    {
        if (!is_user_logged_in()) { return false; }
        global $wpdb;
        $statement_id = (int) $statement_id;
        Vermieter_Costs::delete_by_source('heating_statement', $statement_id);
        $wpdb->delete($wpdb->prefix . 'vm_heating_statement_items', ['statement_id' => $statement_id, 'user_id' => get_current_user_id()], ['%d','%d']);
        return $wpdb->delete($wpdb->prefix . 'vm_heating_statements', ['id' => $statement_id, 'user_id' => get_current_user_id()], ['%d','%d']) !== false;
    }

    private static function to_float($value)
    {
        if (is_string($value)) {
            $value = trim(wp_unslash($value));
            if ($value === '') { return 0.0; }
            if (strpos($value, ',') !== false) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
        }
        return round((float) $value, 6);
    }
}
