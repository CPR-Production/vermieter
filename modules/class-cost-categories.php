<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Cost_Categories {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_cost_categories';

        $allocation_type = sanitize_text_field($data['allocation_type'] ?? 'wohnflaeche');
        $property_distribution_key_id = !empty($data['property_distribution_key_id']) ? (int) $data['property_distribution_key_id'] : null;

        if ($allocation_type !== 'distribution_key') {
            $property_distribution_key_id = null;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'                      => get_current_user_id(),
                'property_id'                  => (int) ($data['property_id'] ?? 0),
                'name'                         => sanitize_text_field($data['name'] ?? ''),
                'description'                  => sanitize_textarea_field($data['description'] ?? ''),
                'allocation_type'              => $allocation_type,
                'property_distribution_key_id' => $property_distribution_key_id,
                'is_recurring'                 => !empty($data['is_recurring']) ? 1 : 0,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%d']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_cost_categories';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND user_id = %d",
                (int) $id,
                get_current_user_id()
            )
        );
    }

    public static function get_all_by_user($user_id = 0) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'vm_cost_categories';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE user_id = %d
                 ORDER BY name ASC",
                $user_id
            )
        );
    }

    public static function get_by_property($property_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_cost_categories';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE property_id = %d
                   AND user_id = %d
                 ORDER BY name ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );
    }
}