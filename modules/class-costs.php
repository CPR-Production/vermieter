<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Costs {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_costs';

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'                   => get_current_user_id(),
                'property_id'               => (int) ($data['property_id'] ?? 0),
                'property_cost_category_id' => (int) ($data['property_cost_category_id'] ?? 0),
                'name'                      => sanitize_text_field($data['name'] ?? ''),
                'betrag'                    => (float) ($data['betrag'] ?? 0),
                'invoice_date'              => sanitize_text_field($data['invoice_date'] ?? ''),
                'period_start'              => sanitize_text_field($data['period_start'] ?? ''),
                'period_end'                => sanitize_text_field($data['period_end'] ?? ''),
                'period_year'               => (int) ($data['period_year'] ?? 0),
            ],
            ['%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%d']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function add_multiple($base_data, $rows) {
        $count = 0;

        if (empty($rows) || !is_array($rows)) {
            return 0;
        }

        foreach ($rows as $row) {
            $property_cost_category_id = (int) ($row['property_cost_category_id'] ?? 0);
            $name = sanitize_text_field($row['name'] ?? '');
            $betrag = isset($row['betrag']) ? (float) str_replace(',', '.', (string) $row['betrag']) : 0;

            if ($property_cost_category_id <= 0 || $name === '' || $betrag <= 0) {
                continue;
            }

            $id = self::add([
                'property_id'               => (int) ($base_data['property_id'] ?? 0),
                'property_cost_category_id' => $property_cost_category_id,
                'name'                      => $name,
                'betrag'                    => $betrag,
                'invoice_date'              => $base_data['invoice_date'] ?? '',
                'period_start'              => $base_data['period_start'] ?? '',
                'period_end'                => $base_data['period_end'] ?? '',
                'period_year'               => (int) ($base_data['period_year'] ?? 0),
            ]);

            if ($id) {
                $count++;
            }
        }

        return $count;
    }

    public static function get_all_by_user($user_id = 0) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table_costs = $wpdb->prefix . 'vm_costs';
        $table_properties = $wpdb->prefix . 'vm_properties';
        $table_property_categories = $wpdb->prefix . 'vm_property_cost_categories';
        $table_definitions = $wpdb->prefix . 'vm_cost_category_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    c.*,
                    p.name AS property_name,
                    d.name AS category_name
                 FROM $table_costs c
                 LEFT JOIN $table_properties p
                    ON c.property_id = p.id
                 LEFT JOIN $table_property_categories pc
                    ON c.property_cost_category_id = pc.id
                 LEFT JOIN $table_definitions d
                    ON pc.cost_category_definition_id = d.id
                 WHERE c.user_id = %d
                 ORDER BY c.invoice_date DESC, c.id DESC",
                $user_id
            )
        );
    }

    public static function get_by_property_and_year($property_id, $year) {
        global $wpdb;

        $table_costs = $wpdb->prefix . 'vm_costs';
        $table_property_categories = $wpdb->prefix . 'vm_property_cost_categories';
        $table_definitions = $wpdb->prefix . 'vm_cost_category_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    c.*,
                    pc.allocation_type,
                    pc.property_distribution_key_id,
                    pc.is_recurring,
                    d.name AS category_name
                 FROM $table_costs c
                 LEFT JOIN $table_property_categories pc
                    ON c.property_cost_category_id = pc.id
                 LEFT JOIN $table_definitions d
                    ON pc.cost_category_definition_id = d.id
                 WHERE c.property_id = %d
                   AND c.period_year = %d
                   AND c.user_id = %d
                 ORDER BY d.name ASC, c.name ASC, c.invoice_date ASC, c.id ASC",
                (int) $property_id,
                (int) $year,
                get_current_user_id()
            )
        );
    }

    public static function get_recorded_years_by_property($property_id) {
        global $wpdb;

        $table_costs = $wpdb->prefix . 'vm_costs';

        $years = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT period_year
                FROM $table_costs
                WHERE property_id = %d
                AND user_id = %d
                AND period_year > 0
                ORDER BY period_year ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );

        return array_map('intval', $years);
    }
}