<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Nebenkosten_Billing
{
    public static function build_tenant_statement($property_id, $year){
        $property_id = (int) $property_id;
        $year = (int) $year;

        if ($property_id <= 0 || $year <= 0) {
            return [];
        }

        $costs = Vermieter_Costs::get_by_property_and_year($property_id, $year);
        $apartments = Vermieter_Apartments::get_by_property($property_id);

        if (empty($apartments)) {
            return [];
        }

        $tenant_statements = [];

        foreach ($costs as $cost) {
            $property_cost_category = Vermieter_Property_Cost_Categories::get($cost->property_cost_category_id);

            if (!$property_cost_category) {
                continue;
            }

            $eligible_apartments = self::get_eligible_apartments($apartments, $property_cost_category);

            if (empty($eligible_apartments)) {
                continue;
            }

            $shares = self::distribute_cost_to_apartments($cost, $property_cost_category, $eligible_apartments);

            foreach ($shares as $apartment_id => $share_amount) {
                if ($share_amount <= 0) {
                    continue;
                }

                $apartment_tenants = Vermieter_Apartment_Tenants::get_by_apartment($apartment_id);

                foreach ($apartment_tenants as $apartment_tenant) {
                    $time_data = self::get_tenant_time_data($apartment_tenant, $year);
                    $tenant_factor = (float) $time_data['factor'];
                    $occupied_days = (int) $time_data['occupied_days'];
                    $year_days = (int) $time_data['year_days'];

                    if ($tenant_factor <= 0) {
                        continue;
                    }

                    $tenant_share = round((float) $share_amount * (float) $tenant_factor, 2);
                    $statement_key = self::get_statement_key($apartment_tenant);
                    
                    if (!isset($tenant_statements[$statement_key])) {
                        $tenant_statements[$statement_key] = self::create_empty_statement($apartment_tenant, $property_id, $year);
                    }

                    $tenant_value = 0.0;
                    $total_value = 0.0;

                    switch ($property_cost_category->allocation_type) {
                        case 'personen':
                            foreach ($eligible_apartments as $apartment) {
                                if ((int) $apartment->id === (int) $apartment_id) {
                                    $tenant_value = (float) ($apartment->personen ?? 0);
                                    break;
                                }
                            }

                            $total_value = self::get_total_persons(
                                $property_id,
                                $property_cost_category->applies_to_type_key ?? 'alle'
                            );
                            break;

                        case 'distribution_key':
                            $property_distribution_key_id = (int) ($property_cost_category->property_distribution_key_id ?? 0);

                            $values = Vermieter_Apartment_Distribution_Values::get_values_by_property_distribution_key(
                                $property_distribution_key_id
                            );

                            $tenant_value = (float) ($values[$apartment_id] ?? 0);

                            $distribution_key = Vermieter_Property_Distribution_Keys::get($property_distribution_key_id);
                            $total_value = isset($distribution_key->total_value)
                                ? (float) $distribution_key->total_value
                                : 0.0;
                            break;

                        case 'wohnflaeche':
                        default:
                            foreach ($eligible_apartments as $apartment) {
                                if ((int) $apartment->id === (int) $apartment_id) {
                                    $tenant_value = (float) ($apartment->wohnflaeche ?? 0);
                                    break;
                                }
                            }

                            $total_value = self::get_total_living_space(
                                $property_id,
                                $property_cost_category->applies_to_type_key ?? 'alle'
                            );
                            break;
                    }

                    error_log(
                        'TIME DEBUG | tenant_id=' . $apartment_tenant->id .
                        ' | move_in=' . $apartment_tenant->move_in_date .
                        ' | move_out=' . $apartment_tenant->move_out_date .
                        ' | occupied_days=' . $occupied_days .
                        ' | year_days=' . $year_days .
                        ' | calculated_factor=' . ($occupied_days / $year_days) .
                        ' | tenant_factor=' . $tenant_factor
                    );

                    $category_name = trim((string) ($property_cost_category->name ?? ''));
                    $display_cost_name = trim((string) ($cost->name ?? ''));

                    if ($display_cost_name !== '' && $category_name !== '' && stripos($display_cost_name, $category_name) === false) {
                        $display_cost_name .= ' - ' . $category_name;
                    }

                    $tenant_statements[$statement_key]['cost_items'][] = [
                        'cost_id'               => (int) $cost->id,
                        'cost_name'             => $display_cost_name,
                        'category_name'         => $category_name,
                        'allocation_type'       => $property_cost_category->allocation_type ?? '',
                        'apartment_id'          => (int) $apartment_id,
                        'apartment_name'        => self::find_apartment_name($eligible_apartments, $apartment_id),
                        'total_cost'            => round((float) $cost->betrag, 2),
                        'tenant_value'          => round((float) $tenant_value, 2),
                        'total_value'           => round((float) $total_value, 2),
                        'share_before_factor'   => round((float) $share_amount, 2),
                        'occupied_days'         => (int) $occupied_days,
                        'year_days'             => (int) $year_days,
                        'tenant_factor'         => round((float) $tenant_factor, 6),
                        'tenant_share'          => round((float) $tenant_share, 2),
                    ];

                    $tenant_statements[$statement_key]['cost_sum'] += round((float) $tenant_share, 2);
                }
            }
        }

        foreach ($tenant_statements as $statement_key => $statement) {
            $advances = self::get_advances_for_year((int) $statement['apartment_tenant_id'], $year);

            $tenant_statements[$statement_key]['cost_sum'] = round((float) $tenant_statements[$statement_key]['cost_sum'], 2);
            $tenant_statements[$statement_key]['nk_advance_sum'] = round((float) $advances['nk'], 2);
            $tenant_statements[$statement_key]['hk_advance_sum'] = round((float) $advances['hk'], 2);
            $tenant_statements[$statement_key]['advance_sum'] = round($advances['nk'] + $advances['hk'], 2);
            $tenant_statements[$statement_key]['balance'] = round(
                $tenant_statements[$statement_key]['advance_sum'] - $tenant_statements[$statement_key]['cost_sum'],
                2
            );
        }

        return array_values($tenant_statements);
    }

    public static function distribute_cost_to_apartments($cost, $property_cost_category, $eligible_apartments){
        $cost_amount = round((float) $cost->betrag, 2);

        switch ($property_cost_category->allocation_type) {
            case 'personen':
                $basis_values = [];
                foreach ($eligible_apartments as $apartment) {
                    $basis_values[$apartment->id] = (float) ($apartment->personen ?? 0);
                }

                return self::distribute_by_basis($basis_values, $cost_amount);

            case 'distribution_key':
                $property_distribution_key_id = (int) ($property_cost_category->property_distribution_key_id ?? 0);
                $values = Vermieter_Apartment_Distribution_Values::get_values_by_property_distribution_key($property_distribution_key_id);
                $distribution_key = Vermieter_Property_Distribution_Keys::get($property_distribution_key_id);

                $basis_values = [];
                foreach ($eligible_apartments as $apartment) {
                    $basis_values[$apartment->id] = (float) ($values[$apartment->id] ?? 0);
                }

                $fixed_total = isset($distribution_key->total_value)
                    ? (float) $distribution_key->total_value
                    : 0.0;
                return self::distribute_by_basis_with_fixed_total($basis_values, $cost_amount, $fixed_total);

            case 'wohnflaeche':
            default:
                $basis_values = [];
                foreach ($eligible_apartments as $apartment) {
                    $basis_values[$apartment->id] = (float) ($apartment->wohnflaeche ?? 0);
                }

                return self::distribute_by_basis($basis_values, $cost_amount);
        }
    }

    public static function get_eligible_apartments($apartments, $property_cost_category){
        $type_key = $property_cost_category->applies_to_type_key ?? 'alle';

        return array_values(array_filter($apartments, function ($apartment) use ($type_key) {
            return $type_key === 'alle' || (($apartment->type_key ?? '') === $type_key);
        }));
    }
    
    public static function get_tenant_year_factor($apartment_tenant, $year){
        $time_data = self::get_tenant_time_data($apartment_tenant, $year);
        return (float) $time_data['factor'];
    }
/* 
    public static function get_tenant_year_factor($apartment_tenant, $year){
        $year_start = $year . '-01-01';
        $year_end = $year . '-12-31';

        $occupancy_start = max($year_start, $apartment_tenant->move_in_date);
        $occupancy_end = !empty($apartment_tenant->move_out_date)
            ? min($year_end, $apartment_tenant->move_out_date)
            : $year_end;

        if ($occupancy_end < $occupancy_start) {
            return 0;
        }

        $start = new DateTime($occupancy_start);
        $end = new DateTime($occupancy_end);
        $occupied_days = (int) $start->diff($end)->days + 1;

        $year_days = self::is_leap_year($year) ? 366 : 365;

        return $occupied_days / $year_days;
    }
 */
    public static function get_advances_for_year($apartment_tenant_id, $year)
    {
        $year_start = $year . '-01-01';
        $year_end = $year . '-12-31';

        $tenancy = self::get_apartment_tenant($apartment_tenant_id);

        if (!$tenancy) {
            return ['nk' => 0.0, 'hk' => 0.0];
        }

        $months = Vermieter_Tenant_Payments::get_months_for_tenancy($tenancy, $year_end);

        $nk_sum = 0.0;
        $hk_sum = 0.0;

        foreach ($months as $month) {
            if ($month < $year_start || $month > $year_end) {
                continue;
            }

            $target = Vermieter_Tenant_Payments::get_monthly_target_prorated($tenancy, $month);
            $nk_sum += (float) ($target['nk_advance'] ?? 0);
            $hk_sum += (float) ($target['hk_advance'] ?? 0);
        }

        return [
            'nk' => round($nk_sum, 2),
            'hk' => round($hk_sum, 2),
        ];
    }

    public static function get_total_living_space($property_id, $applies_to_type_key = 'alle')
    {
        $apartments = Vermieter_Apartments::get_by_property((int) $property_id);

        if (empty($apartments)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($apartments as $apartment) {
            if ($applies_to_type_key !== 'alle' && ($apartment->type_key ?? '') !== $applies_to_type_key) {
                continue;
            }

            $total += (float) ($apartment->wohnflaeche ?? 0);
        }

        return round($total, 2);
    }

    public static function get_total_persons($property_id, $applies_to_type_key = 'alle')
    {
        $apartments = Vermieter_Apartments::get_by_property((int) $property_id);

        if (empty($apartments)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($apartments as $apartment) {
            if ($applies_to_type_key !== 'alle' && ($apartment->type_key ?? '') !== $applies_to_type_key) {
                continue;
            }

            $total += (float) ($apartment->personen ?? 0);
        }

        return round($total, 2);
    }

    public static function get_distribution_key_total($property_distribution_key_id, $eligible_apartment_ids = [])
    {
        $values = Vermieter_Apartment_Distribution_Values::get_values_by_property_distribution_key(
            (int) $property_distribution_key_id
        );

        if (empty($values)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($values as $apartment_id => $value) {
            if (!empty($eligible_apartment_ids) && !in_array((int) $apartment_id, $eligible_apartment_ids, true)) {
                continue;
            }

            $total += (float) $value;
        }

        return round($total, 2);
    }

    protected static function distribute_by_basis_with_fixed_total($basis_values, $cost_amount, $fixed_total){
        $shares = [];

        $fixed_total = (float) $fixed_total;
        if ($fixed_total <= 0) {
            foreach ($basis_values as $apartment_id => $value) {
                $shares[$apartment_id] = 0.0;
            }
            return $shares;
        }

        foreach ($basis_values as $apartment_id => $value) {
            $shares[$apartment_id] = round(
                ((float) $value / $fixed_total) * (float) $cost_amount,
                2
            );
        }
/*         error_log(
            'FIXED TOTAL DEBUG | apartment_id=' . $apartment_id .
            ' | value=' . $value .
            ' | fixed_total=' . $fixed_total .
            ' | cost_amount=' . $cost_amount .
            ' | result=' . $shares[$apartment_id]
        ); */
        return $shares;
    }
    protected static function distribute_by_basis($basis_values, $cost_amount)
    {
        $shares = [];
        $sum_basis = array_sum($basis_values);

        if ($sum_basis <= 0) {
            foreach ($basis_values as $apartment_id => $value) {
                $shares[$apartment_id] = 0.0;
            }

            return $shares;
        }

        $running_total = 0.0;
        $last_apartment_id = null;

        foreach ($basis_values as $apartment_id => $value) {
            $last_apartment_id = $apartment_id;
            $amount = round(((float) $value / $sum_basis) * (float) $cost_amount, 2);
            $shares[$apartment_id] = $amount;
            $running_total += $amount;
        }

        if ($last_apartment_id !== null) {
            $difference = round((float) $cost_amount - $running_total, 2);
            $shares[$last_apartment_id] += $difference;
        }

        return $shares;
    }

    protected static function get_tenant_time_data($apartment_tenant, $year){
        $year_start = $year . '-01-01';
        $year_end = $year . '-12-31';

        $occupancy_start = max($year_start, $apartment_tenant->move_in_date);
        $occupancy_end = !empty($apartment_tenant->move_out_date)
            ? min($year_end, $apartment_tenant->move_out_date)
            : $year_end;

        $year_days = self::is_leap_year($year) ? 366 : 365;

        if ($occupancy_end < $occupancy_start) {
            return [
                'occupied_days' => 0,
                'year_days' => $year_days,
                'factor' => 0.0,
            ];
        }

        $start = new DateTime($occupancy_start);
        $end = new DateTime($occupancy_end);
        $occupied_days = (int) $start->diff($end)->days + 1;

        return [
            'occupied_days' => $occupied_days,
            'year_days' => $year_days,
            'factor' => $occupied_days / $year_days,
        ];
    }

    protected static function create_empty_statement($apartment_tenant, $property_id, $year)
    {
        return [
            'property_id'         => (int) $property_id,
            'year'                => (int) $year,
            'apartment_tenant_id' => (int) $apartment_tenant->id,
            'tenant_id'           => (int) $apartment_tenant->tenant_id,
            'tenant_name'         => trim(
                ($apartment_tenant->salutation ?? '') . ' ' .
                ($apartment_tenant->first_name ?? '') . ' ' .
                ($apartment_tenant->last_name ?? '')
            ),
            'move_in_date'        => $apartment_tenant->move_in_date ?? '',
            'move_out_date'       => $apartment_tenant->move_out_date ?? '',
            'cost_items'          => [],
            'cost_sum'            => 0.0,
            'nk_advance_sum'      => 0.0,
            'hk_advance_sum'      => 0.0,
            'advance_sum'         => 0.0,
            'balance'             => 0.0,
        ];
    }

    protected static function get_statement_key($apartment_tenant)
    {
        return 'tenancy_' . (int) $apartment_tenant->id;
    }

    protected static function get_apartment_tenant($apartment_tenant_id)
    {
        global $wpdb;

        $table_links = $wpdb->prefix . 'vm_apartment_tenants';
        $table_apartments = $wpdb->prefix . 'vm_apartments';
        $table_properties = $wpdb->prefix . 'vm_properties';
        $table_tenants = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    at.*,
                    a.name AS apartment_name,
                    p.name AS property_name,
                    t.salutation,
                    t.first_name,
                    t.last_name
                 FROM $table_links at
                 LEFT JOIN $table_apartments a ON at.apartment_id = a.id
                 LEFT JOIN $table_properties p ON a.property_id = p.id
                 LEFT JOIN $table_tenants t ON at.tenant_id = t.id
                 WHERE at.id = %d
                   AND at.user_id = %d
                 LIMIT 1",
                (int) $apartment_tenant_id,
                get_current_user_id()
            )
        );
    }

    protected static function find_apartment_name($apartments, $apartment_id)
    {
        foreach ($apartments as $apartment) {
            if ((int) $apartment->id === (int) $apartment_id) {
                return $apartment->name ?? '';
            }
        }

        return '';
    }

    protected static function is_leap_year($year)
    {
        return (($year % 4 === 0) && ($year % 100 !== 0)) || ($year % 400 === 0);
    }

    public static function build_tenant_statement_grouped_by_tenant($property_id, $year)
    {
        $statements = self::build_tenant_statement($property_id, $year);

        if (empty($statements)) {
            return [];
        }

        $grouped = [];

        foreach ($statements as $statement) {
            $tenant_id = (int) ($statement['tenant_id'] ?? 0);

            if ($tenant_id <= 0) {
                continue;
            }

            $group_key = 'tenant_' . $tenant_id;

            if (!isset($grouped[$group_key])) {
                $grouped[$group_key] = [
                    'property_id'          => (int) ($statement['property_id'] ?? 0),
                    'year'                 => (int) ($statement['year'] ?? 0),
                    'tenant_id'            => $tenant_id,
                    'tenant_name'          => $statement['tenant_name'] ?? '',
                    'move_in_date'         => $statement['move_in_date'] ?? '',
                    'move_out_date'        => $statement['move_out_date'] ?? '',
                    'apartment_tenant_ids' => [],
                    'cost_items'           => [],
                    'cost_sum'             => 0.0,
                    'nk_advance_sum'       => 0.0,
                    'hk_advance_sum'       => 0.0,
                    'advance_sum'          => 0.0,
                    'balance'              => 0.0,
                ];
            }

            $grouped[$group_key]['apartment_tenant_ids'][] = (int) ($statement['apartment_tenant_id'] ?? 0);

            if (!empty($statement['move_in_date'])) {
                if (
                    empty($grouped[$group_key]['move_in_date']) ||
                    $statement['move_in_date'] < $grouped[$group_key]['move_in_date']
                ) {
                    $grouped[$group_key]['move_in_date'] = $statement['move_in_date'];
                }
            }

            if (!empty($statement['move_out_date'])) {
                if (
                    empty($grouped[$group_key]['move_out_date']) ||
                    $statement['move_out_date'] > $grouped[$group_key]['move_out_date']
                ) {
                    $grouped[$group_key]['move_out_date'] = $statement['move_out_date'];
                }
            }

            foreach (($statement['cost_items'] ?? []) as $item) {
                $grouped[$group_key]['cost_items'][] = $item;
            }

            $grouped[$group_key]['cost_sum'] += (float) ($statement['cost_sum'] ?? 0);
            $grouped[$group_key]['nk_advance_sum'] += (float) ($statement['nk_advance_sum'] ?? 0);
            $grouped[$group_key]['hk_advance_sum'] += (float) ($statement['hk_advance_sum'] ?? 0);
            $grouped[$group_key]['advance_sum'] += (float) ($statement['advance_sum'] ?? 0);
        }

        foreach ($grouped as $group_key => $group) {
            $grouped[$group_key]['cost_items'] = self::merge_cost_items($group['cost_items']);
            self::sort_cost_items_by_category($grouped[$group_key]['cost_items']);

            $recalculated_cost_sum = 0.0;
            foreach ($grouped[$group_key]['cost_items'] as $item) {
                $recalculated_cost_sum += (float) ($item['tenant_share'] ?? 0);
            }

            $grouped[$group_key]['cost_sum'] = round($recalculated_cost_sum, 2);
            $grouped[$group_key]['nk_advance_sum'] = round((float) $group['nk_advance_sum'], 2);
            $grouped[$group_key]['hk_advance_sum'] = round((float) $group['hk_advance_sum'], 2);
            $grouped[$group_key]['advance_sum'] = round((float) $group['advance_sum'], 2);
            $grouped[$group_key]['balance'] = round(
                $grouped[$group_key]['advance_sum'] - $grouped[$group_key]['cost_sum'],
                2
            );
            $grouped[$group_key]['apartment_tenant_ids'] = array_values(array_unique($group['apartment_tenant_ids']));
        }

        return array_values($grouped);
    }

    public static function build_property_statement($property_id, $year)
    {
        $property_id = (int) $property_id;
        $year = (int) $year;

        if ($property_id <= 0 || $year <= 0) {
            return [];
        }

        $property = Vermieter_Properties::get($property_id);
        $costs = Vermieter_Costs::get_by_property_and_year($property_id, $year);
        $apartments = Vermieter_Apartments::get_by_property($property_id);

        $tenant_statements = self::build_tenant_statement($property_id, $year);
        $grouped_tenant_statements = self::build_tenant_statement_grouped_by_tenant($property_id, $year);

        $cost_total = 0.0;
        foreach ($costs as $cost) {
            $cost_total += (float) ($cost->betrag ?? 0);
        }

        $advance_total = 0.0;
        foreach ($grouped_tenant_statements as $statement) {
            $advance_total += (float) ($statement['advance_sum'] ?? 0);
        }

        $balance_total = 0.0;
        foreach ($grouped_tenant_statements as $statement) {
            $balance_total += (float) ($statement['balance'] ?? 0);
        }

        return [
            'property'                  => $property,
            'property_id'               => $property_id,
            'year'                      => $year,
            'costs'                     => $costs,
            'cost_total'                => round($cost_total, 2),
            'apartments'                => $apartments,
            'tenant_statements'         => $tenant_statements,
            'grouped_tenant_statements' => $grouped_tenant_statements,
            'advance_total'             => round($advance_total, 2),
            'balance_total'             => round($balance_total, 2),
        ];
    }


    protected static function sort_cost_items_by_category(&$items)
    {
        if (empty($items) || !is_array($items)) {
            return;
        }

        usort($items, function ($a, $b) {
            $category_a = mb_strtolower(trim((string) ($a['category_name'] ?? '')));
            $category_b = mb_strtolower(trim((string) ($b['category_name'] ?? '')));

            if ($category_a !== $category_b) {
                return $category_a <=> $category_b;
            }

            $name_a = mb_strtolower(trim((string) ($a['cost_name'] ?? '')));
            $name_b = mb_strtolower(trim((string) ($b['cost_name'] ?? '')));

            if ($name_a !== $name_b) {
                return $name_a <=> $name_b;
            }

            $apartment_a = mb_strtolower(trim((string) ($a['apartment_name'] ?? '')));
            $apartment_b = mb_strtolower(trim((string) ($b['apartment_name'] ?? '')));

            return $apartment_a <=> $apartment_b;
        });
    }

    protected static function merge_cost_items($items)
    {
        $merged = [];

        foreach ($items as $item) {
            $cost_id = (int) ($item['cost_id'] ?? 0);
            $apartment_id = (int) ($item['apartment_id'] ?? 0);
            $occupied_days = (int) ($item['occupied_days'] ?? 0);
            $year_days = (int) ($item['year_days'] ?? 0);
            $tenant_factor = round((float) ($item['tenant_factor'] ?? 0), 6);

            $key = $cost_id . '_' . $apartment_id . '_' . $occupied_days . '_' . $year_days . '_' . $tenant_factor;

            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'cost_id'             => $cost_id,
                    'cost_name'           => $item['cost_name'] ?? '',
                    'category_name'       => $item['category_name'] ?? '',
                    'allocation_type'     => $item['allocation_type'] ?? '',
                    'apartment_id'        => $apartment_id,
                    'apartment_name'      => $item['apartment_name'] ?? '',
                    'total_cost'          => round((float) ($item['total_cost'] ?? 0), 2),
                    'tenant_value'        => round((float) ($item['tenant_value'] ?? 0), 2),
                    'total_value'         => round((float) ($item['total_value'] ?? 0), 2),
                    'share_before_factor' => round((float) ($item['share_before_factor'] ?? 0), 2),
                    'occupied_days'       => (int) ($item['occupied_days'] ?? 0),
                    'year_days'           => (int) ($item['year_days'] ?? 0),
                    'tenant_factor'       => round((float) ($item['tenant_factor'] ?? 0), 6),
                    'tenant_share'        => 0.0,
                ];
            }

            $merged[$key]['tenant_share'] += (float) ($item['tenant_share'] ?? 0);
        }

        foreach ($merged as $key => $item) {
            $merged[$key]['tenant_share'] = round((float) $item['tenant_share'], 2);
        }

        return array_values($merged);
    }
}