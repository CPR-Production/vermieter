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

        $applies_to_type_key = sanitize_text_field($data['applies_to_type_key'] ?? 'alle');
        $allocation_type = sanitize_text_field($data['allocation_type'] ?? 'wohnflaeche');
        $property_distribution_key_id = !empty($data['property_distribution_key_id']) ? (int) $data['property_distribution_key_id'] : null;

        if ($allocation_type !== 'distribution_key') {
            $property_distribution_key_id = null;
        }

        if (!empty($property_distribution_key_id)) {
            $key_table = $wpdb->prefix . 'vm_property_distribution_keys';

            $valid_key_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM $key_table
                    WHERE id = %d
                    AND property_id = %d
                    AND applies_to_type_key = %s
                    AND user_id = %d
                    LIMIT 1",
                    $property_distribution_key_id,
                    $property_id,
                    $applies_to_type_key,
                    $user_id
                )
            );

            if (!$valid_key_id) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Objekt-Schlüssel gehört nicht zum Objekt oder nicht zum gewählten Typ.',
                ];
            }
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'                      => $user_id,
                'property_id'                  => $property_id,
                'cost_category_definition_id'  => $definition_id,
                'allocation_type'              => $allocation_type,
                'property_distribution_key_id' => $property_distribution_key_id,
                'applies_to_type_key'          => $applies_to_type_key,
                'is_recurring'                 => $is_recurring,
            ],
            ['%d', '%d', '%d', '%s', '%d', '%s', '%d']
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