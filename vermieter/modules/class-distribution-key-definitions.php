<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Distribution_Key_Definitions {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_distribution_key_definitions';

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'     => get_current_user_id(),
                'label'       => sanitize_text_field($data['label'] ?? ''),
                'unit_code'   => sanitize_text_field($data['unit_code'] ?? ''),
                'total_value' => (float) ($data['total_value'] ?? 0),
            ],
            ['%d', '%s', '%s', '%f']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

/*     public static function get($id) {
        global $wpdb;

        $table = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM $table
                WHERE id = %d
                AND user_id IN (0, %d)",
                (int) $id,
                get_current_user_id()
            )
        );
    } */

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * 
                FROM $table 
                WHERE id = %d 
                AND user_id = %d",
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

        $table = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE user_id = 0 OR user_id = %d
                 ORDER BY label ASC, unit_code ASC, total_value ASC",
                $user_id
            )
        );
    }

    public static function get_or_create_default_stel() {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_distribution_key_definitions';
        $user_id = get_current_user_id();

        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table
                WHERE user_id = %d
                AND label = %s
                AND unit_code = %s
                AND total_value = %f
                LIMIT 1",
                $user_id,
                'Einheiten',
                'STEL',
                1000.00
            )
        );

        if ($id) {
            return (int) $id;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'     => $user_id,
                'label'       => '1000-stel',
                'unit_code'   => 'STEL',
                'total_value' => 1000.00,
            ],
            ['%d', '%s', '%s', '%f']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }
}