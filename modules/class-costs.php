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
                'target_apartment_id'       => !empty($data['target_apartment_id']) ? (int) $data['target_apartment_id'] : null,
                'apartment_tenant_id'       => !empty($data['apartment_tenant_id']) ? (int) $data['apartment_tenant_id'] : null,
                'calculation_mode'          => !empty($data['calculation_mode']) ? sanitize_text_field($data['calculation_mode']) : 'allocation',
                'source_type'               => !empty($data['source_type']) ? sanitize_text_field($data['source_type']) : null,
                'source_id'                 => !empty($data['source_id']) ? (int) $data['source_id'] : null,
                'no_time_factor'            => !empty($data['no_time_factor']) ? 1 : 0,
            ],
            ['%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%d']
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

    public static function get($id) {
        global $wpdb;

        $table_costs = $wpdb->prefix . 'vm_costs';
        $table_properties = $wpdb->prefix . 'vm_properties';
        $table_property_categories = $wpdb->prefix . 'vm_property_cost_categories';
        $table_definitions = $wpdb->prefix . 'vm_cost_category_definitions';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    c.*,
                    p.name AS property_name,
                    pc.allocation_type,
                    pc.is_recurring,
                    pc.property_distribution_key_id,
                    d.name AS category_name
                 FROM $table_costs c
                 LEFT JOIN $table_properties p
                    ON c.property_id = p.id
                 LEFT JOIN $table_property_categories pc
                    ON c.property_cost_category_id = pc.id
                 LEFT JOIN $table_definitions d
                    ON pc.cost_category_definition_id = d.id
                 WHERE c.id = %d
                   AND c.user_id = %d
                 LIMIT 1",
                (int) $id,
                get_current_user_id()
            )
        );
    }

    public static function update($id, $data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_costs';

        $updated = $wpdb->update(
            $table,
            [
                'property_id'               => (int) ($data['property_id'] ?? 0),
                'property_cost_category_id' => (int) ($data['property_cost_category_id'] ?? 0),
                'name'                      => sanitize_text_field($data['name'] ?? ''),
                'betrag'                    => (float) ($data['betrag'] ?? 0),
                'invoice_date'              => sanitize_text_field($data['invoice_date'] ?? ''),
                'period_start'              => sanitize_text_field($data['period_start'] ?? ''),
                'period_end'                => sanitize_text_field($data['period_end'] ?? ''),
                'period_year'               => (int) ($data['period_year'] ?? 0),
                'target_apartment_id'       => !empty($data['target_apartment_id']) ? (int) $data['target_apartment_id'] : null,
                'apartment_tenant_id'       => !empty($data['apartment_tenant_id']) ? (int) $data['apartment_tenant_id'] : null,
                'calculation_mode'          => !empty($data['calculation_mode']) ? sanitize_text_field($data['calculation_mode']) : 'allocation',
                'no_time_factor'            => !empty($data['no_time_factor']) ? 1 : 0,
            ],
            [
                'id'      => (int) $id,
                'user_id' => get_current_user_id(),
            ],
            ['%d', '%d', '%s', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d'],
            ['%d', '%d']
        );

        return $updated !== false;
    }

    public static function delete($id) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_costs';

        $deleted = $wpdb->delete(
            $table,
            [
                'id'      => (int) $id,
                'user_id' => get_current_user_id(),
            ],
            ['%d', '%d']
        );

        return $deleted !== false && $deleted > 0;
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
                 ORDER BY d.name ASC, c.name ASC, c.invoice_date ASC, c.id ASC",
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
                   AND c.user_id = %d
                   AND (
                        c.period_year = %d
                        OR (
                            c.period_start IS NOT NULL
                            AND c.period_start <> ''
                            AND c.period_end IS NOT NULL
                            AND c.period_end <> ''
                            AND c.period_start <= %s
                            AND c.period_end >= %s
                        )
                        OR (
                            (c.period_year IS NULL OR c.period_year = 0)
                            AND c.invoice_date IS NOT NULL
                            AND c.invoice_date <> ''
                            AND YEAR(c.invoice_date) = %d
                        )
                   )
                 ORDER BY d.name ASC, c.name ASC, c.invoice_date ASC, c.id ASC",
                (int) $property_id,
                get_current_user_id(),
                (int) $year,
                $year . '-12-31',
                $year . '-01-01',
                (int) $year
            )
        );
    }

    public static function delete_by_source($source_type, $source_id) {
        if (!is_user_logged_in()) { return false; }
        global $wpdb;
        $table = $wpdb->prefix . 'vm_costs';
        return $wpdb->delete($table, [
            'user_id' => get_current_user_id(),
            'source_type' => sanitize_text_field($source_type),
            'source_id' => (int) $source_id,
        ], ['%d','%s','%d']) !== false;
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

    public static function add_brunata_split_rows($base_data, $rows) {
        $count = 0;

        if (empty($rows) || !is_array($rows)) {
            return 0;
        }

        foreach ($rows as $row) {
            $property_cost_category_id = (int) ($row['property_cost_category_id'] ?? 0);
            $apartment_id = (int) ($row['apartment_id'] ?? 0);
            $category_name = sanitize_text_field($row['category_name'] ?? 'Heizkosten');
            $usage_rows = $row['usage'] ?? [];

            if ($property_cost_category_id <= 0 || $apartment_id <= 0 || empty($usage_rows) || !is_array($usage_rows)) {
                continue;
            }

            foreach ($usage_rows as $usage_row) {
                $apartment_tenant_id = (int) ($usage_row['apartment_tenant_id'] ?? 0);
                $amount = isset($usage_row['amount']) ? (float) str_replace(',', '.', (string) $usage_row['amount']) : 0.0;
                $label = sanitize_text_field($usage_row['label'] ?? '');
                $usage_start = sanitize_text_field($usage_row['start_date'] ?? ($base_data['period_start'] ?? ''));
                $usage_end = sanitize_text_field($usage_row['end_date'] ?? ($base_data['period_end'] ?? ''));

                if ($apartment_tenant_id <= 0 || $amount <= 0) {
                    continue;
                }

                $name_parts = [];
                $name_parts[] = 'Jahresabrechnung ' . (int) ($base_data['period_year'] ?? 0);
                if ($category_name !== '') {
                    $name_parts[] = $category_name;
                }
                if ($label !== '') {
                    $name_parts[] = $label;
                }

                $id = self::add([
                    'property_id'               => (int) ($base_data['property_id'] ?? 0),
                    'property_cost_category_id' => $property_cost_category_id,
                    'name'                      => implode(' - ', array_filter($name_parts)),
                    'betrag'                    => $amount,
                    'invoice_date'              => $base_data['invoice_date'] ?? '',
                    'period_start'              => $usage_start ?: ($base_data['period_start'] ?? ''),
                    'period_end'                => $usage_end ?: ($base_data['period_end'] ?? ''),
                    'period_year'               => (int) ($base_data['period_year'] ?? 0),
                    'target_apartment_id'       => $apartment_id,
                    'apartment_tenant_id'       => $apartment_tenant_id,
                    'calculation_mode'          => 'manual_tenant_amount',
                    'source_type'               => 'brunata_statement',
                    'no_time_factor'            => 1,
                ]);

                if ($id) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function get_brunata_entry_rows($property_id, $year, $property_categories = []) {
        $property_id = (int) $property_id;
        $year = (int) $year;

        if ($property_id <= 0 || $year <= 0) {
            return [];
        }

        if (empty($property_categories)) {
            $property_categories = Vermieter_Property_Cost_Categories::get_by_property($property_id);
        }

        $brunata_categories = array_values(array_filter($property_categories, function ($category) {
            return (($category->allocation_type ?? '') === 'brunata_statement');
        }));

        if (empty($brunata_categories)) {
            return [];
        }

        $apartments = Vermieter_Apartments::get_by_property($property_id);
        $apartments = array_values(array_filter($apartments, function ($apartment) {
            return (($apartment->type_key ?? 'wohnung') === 'wohnung');
        }));

        if (empty($apartments)) {
            return [];
        }

        $rows = [];

        foreach ($brunata_categories as $category) {
            foreach ($apartments as $apartment) {
                $timeline = Vermieter_Apartment_Tenants::get_timeline_with_vacancies_by_apartment_and_year((int) $apartment->id, $year);
                $usage = [];

                foreach ($timeline as $item) {
                    if (($item['type'] ?? '') !== 'tenant' || empty($item['row'])) {
                        continue;
                    }

                    $tenant_row = $item['row'];
                    $usage[] = [
                        'apartment_tenant_id' => (int) $tenant_row->id,
                        'label' => trim((string) ($item['label'] ?? 'Mieter')),
                        'start_date' => (string) ($item['start_date'] ?? ''),
                        'end_date' => (string) ($item['end_date'] ?? ''),
                        'days' => (int) ($item['days'] ?? 0),
                    ];
                }

                if (empty($usage)) {
                    continue;
                }

                $rows[] = [
                    'property_cost_category_id' => (int) $category->id,
                    'category_name' => (string) ($category->name ?? $category->category_name ?? 'Heizkosten'),
                    'apartment_id' => (int) $apartment->id,
                    'apartment_name' => (string) ($apartment->name ?? ''),
                    'usage' => $usage,
                ];
            }
        }

        return $rows;
    }

}