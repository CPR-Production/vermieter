<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Cost_Category_Definitions {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_cost_category_definitions';

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'                 => get_current_user_id(),
                'name'                    => sanitize_text_field($data['name'] ?? ''),
                'description'             => sanitize_textarea_field($data['description'] ?? ''),
                'default_allocation_type' => sanitize_text_field($data['default_allocation_type'] ?? 'wohnflaeche'),
                'default_is_recurring'    => !empty($data['default_is_recurring']) ? 1 : 0,
            ],
            ['%d', '%s', '%s', '%s', '%d']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get_all_by_user($user_id = 0) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'vm_cost_category_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM $table
                 WHERE user_id = 0 OR user_id = %d
                 ORDER BY name ASC",
                $user_id
            )
        );
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_cost_category_definitions';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                 FROM $table
                 WHERE id = %d
                   AND (user_id = 0 OR user_id = %d)",
                (int) $id,
                get_current_user_id()
            )
        );
    }
}