<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Billing {

    public static function generate($property_id, $year) {
        $property_id = (int) $property_id;
        $year = (int) $year;

        $apartments = Vermieter_Apartments::get_by_property($property_id);
        $costs = Vermieter_Costs::get_by_property_and_year($property_id, $year);

        if (empty($apartments) || empty($costs)) {
            return [];
        }

        $result = [];

        foreach ($apartments as $apartment) {
            $result[$apartment->id] = [
                'apartment' => $apartment,
                'items'     => [],
                'total'     => 0,
            ];
        }

        foreach ($costs as $cost) {
            $shares = self::calculate_shares($apartments, $cost);

            foreach ($shares as $apartment_id => $amount) {
                if (!isset($result[$apartment_id])) {
                    continue;
                }

                $result[$apartment_id]['items'][] = [
                    'cost_id'         => (int) $cost->id,
                    'cost_name'       => $cost->name,
                    'category_name'   => $cost->category_name ?: '—',
                    'allocation_type' => $cost->allocation_type ?: 'wohnflaeche',
                    'amount'          => $amount,
                ];

                $result[$apartment_id]['total'] += $amount;
            }
        }

        return $result;
    }

    private static function calculate_shares($apartments, $cost) {
        $shares = [];
        $cost_amount = (float) $cost->betrag;
        $allocation_type = $cost->allocation_type ?: 'wohnflaeche';

        switch ($allocation_type) {
            case 'personen':
                $basis_values = [];
                foreach ($apartments as $apartment) {
                    $basis_values[$apartment->id] = (float) $apartment->personen;
                }
                return self::distribute_by_basis($basis_values, $cost_amount);

            case 'distribution_key':
                $property_distribution_key_id = (int) $cost->property_distribution_key_id;
                $values = Vermieter_Apartment_Distribution_Values::get_values_by_property_distribution_key($property_distribution_key_id);
                return self::distribute_by_basis($values, $cost_amount);

            case 'wohnflaeche':
            default:
                $basis_values = [];
                foreach ($apartments as $apartment) {
                    $basis_values[$apartment->id] = (float) $apartment->wohnflaeche;
                }
                return self::distribute_by_basis($basis_values, $cost_amount);
        }
    }

    private static function distribute_by_basis($basis_values, $cost_amount) {
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
            $amount = round(((float) $value / $sum_basis) * $cost_amount, 2);
            $shares[$apartment_id] = $amount;
            $running_total += $amount;
        }

        if ($last_apartment_id !== null) {
            $difference = round($cost_amount - $running_total, 2);
            $shares[$last_apartment_id] += $difference;
        }

        return $shares;
    }
}