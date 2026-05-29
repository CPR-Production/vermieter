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

    public static function update($id, $data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_cost_category_definitions';
        $user_id = get_current_user_id();
        $id = (int) $id;

        if ($id <= 0) {
            return false;
        }

        $updated = $wpdb->update(
            $table,
            [
                'name'                    => sanitize_text_field($data['name'] ?? ''),
                'description'             => sanitize_textarea_field($data['description'] ?? ''),
                'default_allocation_type' => sanitize_text_field($data['default_allocation_type'] ?? 'wohnflaeche'),
                'default_is_recurring'    => !empty($data['default_is_recurring']) ? 1 : 0,
            ],
            [
                'id'      => $id,
                'user_id' => $user_id,
            ],
            ['%s', '%s', '%s', '%d'],
            ['%d', '%d']
        );

        return $updated !== false;
    }

    public static function delete($id) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'message' => 'Bitte einloggen.',
            ];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_cost_category_definitions';
        $property_categories_table = $wpdb->prefix . 'vm_property_cost_categories';
        $user_id = get_current_user_id();
        $id = (int) $id;

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Ungültige Kategoriedefinition.',
            ];
        }

        $item = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM $table
                WHERE id = %d
                AND user_id = %d
                LIMIT 1",
                $id,
                $user_id
            )
        );

        if (!$item) {
            return [
                'success' => false,
                'message' => 'Kategoriedefinition nicht gefunden oder nicht löschbar.',
            ];
        }

        $usage_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM $property_categories_table
                WHERE cost_category_definition_id = %d
                AND user_id = %d",
                $id,
                $user_id
            )
        );

        if ($usage_count > 0) {
            return [
                'success' => false,
                'message' => 'Die Kategoriedefinition wird bereits in Objekt-Zuordnungen verwendet und kann nicht gelöscht werden.',
            ];
        }

        $deleted = $wpdb->delete(
            $table,
            [
                'id'      => $id,
                'user_id' => $user_id,
            ],
            ['%d', '%d']
        );

        return [
            'success' => $deleted !== false && $deleted > 0,
            'message' => ($deleted !== false && $deleted > 0)
                ? 'Kategoriedefinition gelöscht.'
                : 'Kategoriedefinition konnte nicht gelöscht werden.',
        ];
    }

    public static function is_editable($id) {
        $item = self::get($id);

        return $item && (int) $item->user_id !== 0;
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