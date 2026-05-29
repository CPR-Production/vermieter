<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Apartment_Distribution_Values {

    public static function save_values($property_distribution_key_id, $values) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartment_distribution_values';
        $user_id = get_current_user_id();

        $property_distribution_key_id = (int) $property_distribution_key_id;

        foreach ($values as $apartment_id => $value) {
            $apartment_id = (int) $apartment_id;
            $value = (float) str_replace(',', '.', $value);

            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table
                     WHERE property_distribution_key_id = %d
                       AND apartment_id = %d
                       AND user_id = %d",
                    $property_distribution_key_id,
                    $apartment_id,
                    $user_id
                )
            );

            if ($existing_id) {
                $wpdb->update(
                    $table,
                    ['value' => $value],
                    ['id' => $existing_id],
                    ['%f'],
                    ['%d']
                );
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'user_id'                      => $user_id,
                        'property_distribution_key_id' => $property_distribution_key_id,
                        'apartment_id'                 => $apartment_id,
                        'value'                        => $value,
                    ],
                    ['%d', '%d', '%d', '%f']
                );
            }
        }

        return true;
    }

    public static function get_values_by_property_distribution_key($property_distribution_key_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartment_distribution_values';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE property_distribution_key_id = %d
                   AND user_id = %d",
                (int) $property_distribution_key_id,
                get_current_user_id()
            )
        );

        $values = [];

        foreach ($rows as $row) {
            $values[(int) $row->apartment_id] = (float) $row->value;
        }

        return $values;
    }

    public static function get_sum_by_property_distribution_key($property_distribution_key_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartment_distribution_values';

        $sum = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(value) FROM $table
                 WHERE property_distribution_key_id = %d
                   AND user_id = %d",
                (int) $property_distribution_key_id,
                get_current_user_id()
            )
        );

        return (float) $sum;
    }
}