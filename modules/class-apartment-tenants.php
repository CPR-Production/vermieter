<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Apartment_Tenants {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartment_tenants';

        $apartment_id   = (int) ($data['apartment_id'] ?? 0);
        $tenant_id      = (int) ($data['tenant_id'] ?? 0);
        $move_in_date   = sanitize_text_field($data['move_in_date'] ?? '');
        $move_out_date  = !empty($data['move_out_date']) ? sanitize_text_field($data['move_out_date']) : null;

        if ($apartment_id <= 0 || $tenant_id <= 0 || $move_in_date === '') {
            return false;
        }

        if (!empty($move_out_date) && $move_out_date < $move_in_date) {
            return false;
        }

        if (self::has_overlap($apartment_id, $move_in_date, $move_out_date)) {
            return false;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'       => get_current_user_id(),
                'apartment_id'  => $apartment_id,
                'tenant_id'     => $tenant_id,
                'move_in_date'  => $move_in_date,
                'move_out_date' => $move_out_date,
            ],
            ['%d', '%d', '%d', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function has_overlap($apartment_id, $move_in_date, $move_out_date = null, $exclude_id = 0) {
        global $wpdb;

        $table = $wpdb->prefix . 'vm_apartment_tenants';
        $user_id = get_current_user_id();

        $new_start = $move_in_date;
        $new_end = $move_out_date ?: '9999-12-31';

        $sql = "
            SELECT COUNT(*)
            FROM $table
            WHERE user_id = %d
              AND apartment_id = %d
              AND move_in_date <= %s
              AND COALESCE(move_out_date, '9999-12-31') >= %s
        ";

        $params = [
            $user_id,
            (int) $apartment_id,
            $new_end,
            $new_start,
        ];

        if ($exclude_id > 0) {
            $sql .= " AND id != %d";
            $params[] = (int) $exclude_id;
        }

        $count = $wpdb->get_var($wpdb->prepare($sql, ...$params));

        return (int) $count > 0;
    }

    public static function get_all_by_user($user_id = 0) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table_links      = $wpdb->prefix . 'vm_apartment_tenants';
        $table_apartments = $wpdb->prefix . 'vm_apartments';
        $table_properties = $wpdb->prefix . 'vm_properties';
        $table_tenants    = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    at.*,
                    a.name AS apartment_name,
                    a.property_id,
                    p.name AS property_name,
                    t.salutation,
                    t.first_name,
                    t.last_name
                 FROM $table_links at
                 LEFT JOIN $table_apartments a ON at.apartment_id = a.id
                 LEFT JOIN $table_properties p ON a.property_id = p.id
                 LEFT JOIN $table_tenants t ON at.tenant_id = t.id
                 WHERE at.user_id = %d
                 ORDER BY p.name ASC, a.name ASC, at.move_in_date ASC",
                $user_id
            )
        );
    }

    public static function get_by_apartment($apartment_id) {
        global $wpdb;

        $table_links   = $wpdb->prefix . 'vm_apartment_tenants';
        $table_tenants = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    at.*,
                    t.salutation,
                    t.first_name,
                    t.last_name
                 FROM $table_links at
                 LEFT JOIN $table_tenants t ON at.tenant_id = t.id
                 WHERE at.apartment_id = %d
                   AND at.user_id = %d
                 ORDER BY at.move_in_date ASC, at.id ASC",
                (int) $apartment_id,
                get_current_user_id()
            )
        );
    }

    public static function get_by_apartment_and_year($apartment_id, $year) {
        global $wpdb;

        $year_start = $year . '-01-01';
        $year_end   = $year . '-12-31';

        $table_links   = $wpdb->prefix . 'vm_apartment_tenants';
        $table_tenants = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    at.*,
                    t.salutation,
                    t.first_name,
                    t.last_name
                 FROM $table_links at
                 LEFT JOIN $table_tenants t ON at.tenant_id = t.id
                 WHERE at.apartment_id = %d
                   AND at.user_id = %d
                   AND at.move_in_date <= %s
                   AND COALESCE(at.move_out_date, '9999-12-31') >= %s
                 ORDER BY at.move_in_date ASC",
                (int) $apartment_id,
                get_current_user_id(),
                $year_end,
                $year_start
            )
        );
    }

    public static function get_timeline_with_vacancies_by_apartment_and_year($apartment_id, $year) {
        $year = (int) $year;
        $apartment = Vermieter_Apartments::get($apartment_id);

        if (!$apartment) {
            return [];
        }

        $year_start = $year . '-01-01';
        $year_end   = $year . '-12-31';
        $range_start = !empty($apartment->acquisition_date) ? max($year_start, $apartment->acquisition_date) : $year_start;
        $range_end   = !empty($apartment->disposal_date) ? min($year_end, $apartment->disposal_date) : $year_end;

        if ($range_end < $range_start) {
            return [];
        }

        return self::build_usage_timeline_for_range($apartment_id, $range_start, $range_end);
    }

    public static function get_full_timeline_with_vacancies_by_apartment($apartment_id) {
        $apartment = Vermieter_Apartments::get($apartment_id);

        if (!$apartment) {
            return [];
        }

        $range_start = !empty($apartment->acquisition_date) ? $apartment->acquisition_date : null;

        if (empty($range_start)) {
            $rows = self::get_by_apartment($apartment_id);
            if (empty($rows)) {
                return [];
            }
            $range_start = $rows[0]->move_in_date;
        }

        $range_end = !empty($apartment->disposal_date) ? $apartment->disposal_date : current_time('Y-m-d');

        if ($range_end < $range_start) {
            return [];
        }

        return self::build_usage_timeline_for_range($apartment_id, $range_start, $range_end);
    }

    public static function get_usage_summary_by_apartment_and_year($apartment_id, $year) {
        $apartment = Vermieter_Apartments::get($apartment_id);
        $timeline = self::get_timeline_with_vacancies_by_apartment_and_year($apartment_id, $year);
        $summary = [
            'year' => (int) $year,
            'in_inventory' => false,
            'inventory_start' => null,
            'inventory_end' => null,
            'inventory_days' => 0,
            'tenant_days' => 0,
            'vacancy_days' => 0,
            'status' => 'Nicht im Bestand',
        ];

        if (!$apartment) {
            return $summary;
        }

        $year_start = $year . '-01-01';
        $year_end   = $year . '-12-31';
        $inventory_start = !empty($apartment->acquisition_date) ? max($year_start, $apartment->acquisition_date) : $year_start;
        $inventory_end   = !empty($apartment->disposal_date) ? min($year_end, $apartment->disposal_date) : $year_end;

        if ($inventory_end < $inventory_start) {
            return $summary;
        }

        $summary['in_inventory'] = true;
        $summary['inventory_start'] = $inventory_start;
        $summary['inventory_end'] = $inventory_end;
        $summary['inventory_days'] = self::count_days_inclusive($inventory_start, $inventory_end);

        foreach ($timeline as $item) {
            if (($item['type'] ?? '') === 'tenant') {
                $summary['tenant_days'] += (int) ($item['days'] ?? 0);
            }

            if (($item['type'] ?? '') === 'vacancy') {
                $summary['vacancy_days'] += (int) ($item['days'] ?? 0);
            }
        }

        if (!empty($apartment->disposal_date) && $apartment->disposal_date < current_time('Y-m-d')) {
            $summary['status'] = 'Verkauft / aus Bestand';
        } elseif (!empty($timeline)) {
            $last_item = end($timeline);
            $summary['status'] = (($last_item['type'] ?? '') === 'tenant') ? 'Vermietet' : 'Leerstand';
            reset($timeline);
        } else {
            $summary['status'] = 'Leerstand';
        }

        return $summary;
    }
    
    public static function get_usage_summary_by_apartment($apartment_id) {
        $apartment = Vermieter_Apartments::get($apartment_id);
        $summary = [
            'in_inventory'   => false,
            'inventory_start'=> null,
            'inventory_end'  => null,
            'inventory_days' => 0,
            'tenant_days'    => 0,
            'vacancy_days'   => 0,
            'status'         => 'Nicht im Bestand',
        ];

        if (!$apartment) {
            return $summary;
        }

        $range_start = !empty($apartment->acquisition_date) ? $apartment->acquisition_date : null;

        if (empty($range_start)) {
            $rows = self::get_by_apartment($apartment_id);

            if (empty($rows)) {
                return $summary;
            }

            $range_start = $rows[0]->move_in_date;
        }

        $today = current_time('Y-m-d');
        $range_end = !empty($apartment->disposal_date) ? $apartment->disposal_date : $today;

        if ($range_end < $range_start) {
            return $summary;
        }

        $timeline = self::build_usage_timeline_for_range($apartment_id, $range_start, $range_end);

        $summary['in_inventory'] = true;
        $summary['inventory_start'] = $range_start;
        $summary['inventory_end'] = $range_end;
        $summary['inventory_days'] = self::count_days_inclusive($range_start, $range_end);

        foreach ($timeline as $item) {
            if (($item['type'] ?? '') === 'tenant') {
                $summary['tenant_days'] += (int) ($item['days'] ?? 0);
            }

            if (($item['type'] ?? '') === 'vacancy') {
                $summary['vacancy_days'] += (int) ($item['days'] ?? 0);
            }
        }

        if (!empty($apartment->disposal_date) && $apartment->disposal_date < $today) {
            $summary['status'] = 'Verkauft / aus Bestand';
        } elseif (!empty($timeline)) {
            $last_item = end($timeline);
            $summary['status'] = (($last_item['type'] ?? '') === 'tenant') ? 'Vermietet' : 'Leerstand';
            reset($timeline);
        } else {
            $summary['status'] = 'Leerstand';
        }

        return $summary;
    }

    public static function get_vacancy_days_by_apartment_and_year($apartment_id, $year) {
        $timeline = self::get_timeline_with_vacancies_by_apartment_and_year($apartment_id, $year);
        $days = 0;

        foreach ($timeline as $item) {
            if (($item['type'] ?? '') === 'vacancy') {
                $days += (int) $item['days'];
            }
        }

        return $days;
    }

    private static function build_usage_timeline_for_range($apartment_id, $range_start, $range_end) {
        $rows = self::get_by_apartment($apartment_id);
        $timeline = [];
        $cursor = $range_start;

        foreach ($rows as $row) {
            $start = max($row->move_in_date, $range_start);
            $end = !empty($row->move_out_date) ? min($row->move_out_date, $range_end) : $range_end;

            if ($end < $range_start || $start > $range_end || $end < $start) {
                continue;
            }

            if ($start > $cursor) {
                $vacancy_end = date('Y-m-d', strtotime($start . ' -1 day'));

                if ($vacancy_end >= $cursor) {
                    $timeline[] = [
                        'type'       => 'vacancy',
                        'label'      => 'Leerstand',
                        'start_date' => $cursor,
                        'end_date'   => $vacancy_end,
                        'days'       => self::count_days_inclusive($cursor, $vacancy_end),
                    ];
                }
            }

            $timeline[] = [
                'type'       => 'tenant',
                'row'        => $row,
                'label'      => trim(($row->salutation ?? '') . ' ' . ($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
                'start_date' => $start,
                'end_date'   => $end,
                'days'       => self::count_days_inclusive($start, $end),
            ];

            $cursor = date('Y-m-d', strtotime($end . ' +1 day'));

            if ($cursor > $range_end) {
                break;
            }
        }

        if ($cursor <= $range_end) {
            $timeline[] = [
                'type'       => 'vacancy',
                'label'      => 'Leerstand',
                'start_date' => $cursor,
                'end_date'   => $range_end,
                'days'       => self::count_days_inclusive($cursor, $range_end),
            ];
        }

        return $timeline;
    }

    private static function count_days_inclusive($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end   = new DateTime($end_date);

        return (int) $start->diff($end)->days + 1;
    }
}