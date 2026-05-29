<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Properties {
    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_properties';

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'      => get_current_user_id(),
                'name'         => sanitize_text_field($data['name'] ?? ''),
                'street'       => sanitize_text_field($data['street'] ?? ''),
                'house_number' => sanitize_text_field($data['house_number'] ?? ''),
                'zip_code'     => sanitize_text_field($data['zip_code'] ?? ''),
                'city'         => sanitize_text_field($data['city'] ?? ''),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return false;
        }
        $property_id = (int) $wpdb->insert_id;

        $default_definition_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}vm_distribution_key_definitions
                WHERE user_id = %d
                AND label = %s
                AND unit_code = %s
                AND total_value = %f
                LIMIT 1",
                0,
                '1000-STEL',
                'Einheit',
                1000.00
            )
        );

        if ($default_definition_id) {
            Vermieter_Property_Distribution_Keys::add([
                'property_id' => $property_id,
                'distribution_key_definition_id' => (int) $default_definition_id,
            ]);
        }

        return $property_id;
    }
   
    public static function get_all($user_id = 0) {
        global $wpdb;

        $user_id = $user_id ?: get_current_user_id();
        $table = $wpdb->prefix . 'vm_properties';

        if (!vm_table_exists($table)) {
            return [];
        }

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY name ASC", $user_id)
        );
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_properties';

        if (!vm_table_exists($table)) {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d AND user_id = %d", (int) $id, get_current_user_id())
        );
    }
}
