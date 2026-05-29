<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Property_Cost_Categories
{
    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $user_id = get_current_user_id();

        $allocation_type = sanitize_text_field($data['allocation_type'] ?? 'wohnflaeche');
        $is_recurring = !empty($data['is_recurring']) ? 1 : 0;

        $property_id = (int) ($data['property_id'] ?? 0);
        $definition_id = (int) ($data['cost_category_definition_id'] ?? 0);
        $applies_to_type_key = sanitize_text_field($data['applies_to_type_key'] ?? 'alle');
        $property_distribution_key_id = !empty($data['property_distribution_key_id']) ? (int) $data['property_distribution_key_id'] : null;

        if ($property_id <= 0 || $definition_id <= 0) {
            return false;
        }

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                 FROM $table
                 WHERE property_id = %d
                   AND cost_category_definition_id = %d
                   AND applies_to_type_key = %s
                   AND user_id = %d
                 LIMIT 1",
                $property_id,
                $definition_id,
                $applies_to_type_key,
                $user_id
            )
        );

        if ($existing_id) {
            return (int) $existing_id;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'property_id' => $property_id,
                'cost_category_definition_id' => $definition_id,
                'allocation_type' => $allocation_type,
                'property_distribution_key_id' => $property_distribution_key_id,
                'applies_to_type_key' => $applies_to_type_key,
                'is_recurring' => $is_recurring,
            ],
            ['%d', '%d', '%d', '%s', '%d', '%s', '%d']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function update($id, $data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $user_id = get_current_user_id();

        $id = (int) $id;
        $property_id = (int) ($data['property_id'] ?? 0);
        $definition_id = (int) ($data['cost_category_definition_id'] ?? 0);
        $applies_to_type_key = sanitize_text_field($data['applies_to_type_key'] ?? 'alle');
        $property_distribution_key_id = !empty($data['property_distribution_key_id']) ? (int) $data['property_distribution_key_id'] : null;
        $allocation_type = sanitize_text_field($data['allocation_type'] ?? 'wohnflaeche');
        $is_recurring = !empty($data['is_recurring']) ? 1 : 0;

        if ($id <= 0 || $property_id <= 0 || $definition_id <= 0) {
            return false;
        }

        $duplicate_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                 FROM $table
                 WHERE property_id = %d
                   AND cost_category_definition_id = %d
                   AND applies_to_type_key = %s
                   AND user_id = %d
                   AND id != %d
                 LIMIT 1",
                $property_id,
                $definition_id,
                $applies_to_type_key,
                $user_id,
                $id
            )
        );

        if ($duplicate_id) {
            return false;
        }

        $updated = $wpdb->update(
            $table,
            [
                'property_id' => $property_id,
                'cost_category_definition_id' => $definition_id,
                'allocation_type' => $allocation_type,
                'applies_to_type_key' => $applies_to_type_key,
                'property_distribution_key_id' => $property_distribution_key_id,
                'is_recurring' => $is_recurring,
            ],
            [
                'id' => $id,
                'user_id' => $user_id,
            ],
            ['%d', '%d', '%s', '%s', '%d', '%d'],
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
        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $costs_table = $wpdb->prefix . 'vm_costs';
        $user_id = get_current_user_id();
        $id = (int) $id;

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Ungültige Zuordnung.',
            ];
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                 FROM $table
                 WHERE id = %d
                   AND user_id = %d
                 LIMIT 1",
                $id,
                $user_id
            )
        );

        if (!$existing) {
            return [
                'success' => false,
                'message' => 'Zuordnung nicht gefunden.',
            ];
        }

        $usage_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM $costs_table
                 WHERE property_cost_category_id = %d
                   AND user_id = %d",
                $id,
                $user_id
            )
        );

        if ($usage_count > 0) {
            return [
                'success' => false,
                'message' => 'Die Zuordnung wird bereits in Kosten verwendet und kann nicht gelöscht werden.',
            ];
        }

        $deleted = $wpdb->delete(
            $table,
            [
                'id' => $id,
                'user_id' => $user_id,
            ],
            ['%d', '%d']
        );

        return [
            'success' => $deleted !== false && $deleted > 0,
            'message' => ($deleted !== false && $deleted > 0)
                ? 'Zuordnung gelöscht.'
                : 'Zuordnung konnte nicht gelöscht werden.',
        ];
    }

    public static function get($id) {
        global $wpdb;

        $table_link = $wpdb->prefix . 'vm_property_cost_categories';
        $table_def = $wpdb->prefix . 'vm_cost_category_definitions';
        $table_prop = $wpdb->prefix . 'vm_properties';
        $table_keys = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_key_defs = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    pcc.*,
                    d.name AS name,
                    d.name AS category_name,
                    d.description AS category_description,
                    p.name AS property_name,
                    p.street AS property_street,
                    p.house_number AS property_house_number,
                    p.zip_code AS property_zip_code,
                    p.city AS property_city,
                    pk.distribution_key_definition_id,
                    kd.label AS distribution_key_label,
                    kd.label AS distribution_label,
                    kd.unit_code AS distribution_key_unit_code,
                    kd.unit_code AS distribution_unit_code,
                    kd.total_value AS distribution_total_value
                FROM $table_link pcc
                LEFT JOIN $table_def d ON pcc.cost_category_definition_id = d.id
                LEFT JOIN $table_prop p ON pcc.property_id = p.id
                LEFT JOIN $table_keys pk ON pcc.property_distribution_key_id = pk.id
                LEFT JOIN $table_key_defs kd ON pk.distribution_key_definition_id = kd.id
                WHERE pcc.id = %d
                AND pcc.user_id = %d
                LIMIT 1",
                (int) $id,
                get_current_user_id()
            )
        );
    }
    
    public static function get_all_by_user($user_id = 0){
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table_link = $wpdb->prefix . 'vm_property_cost_categories';
        $table_def = $wpdb->prefix . 'vm_cost_category_definitions';
        $table_prop = $wpdb->prefix . 'vm_properties';
        $table_keys = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_key_defs = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pcc.*,
                    d.name AS name,
                    d.name AS category_name,
                    d.description AS category_description,
                    pcc.allocation_type,
                    pcc.is_recurring,
                    p.name AS property_name,
                    p.street AS property_street,
                    p.house_number AS property_house_number,
                    p.zip_code AS property_zip_code,
                    p.city AS property_city,
                    pk.distribution_key_definition_id,
                    kd.label AS distribution_key_label,
                    kd.label AS distribution_label,
                    kd.unit_code AS distribution_key_unit_code,
                    kd.unit_code AS distribution_unit_code,
                    kd.total_value AS distribution_total_value
                FROM $table_link pcc
                LEFT JOIN $table_def d ON pcc.cost_category_definition_id = d.id
                LEFT JOIN $table_prop p ON pcc.property_id = p.id
                LEFT JOIN $table_keys pk ON pcc.property_distribution_key_id = pk.id
                LEFT JOIN $table_key_defs kd ON pk.distribution_key_definition_id = kd.id
                WHERE pcc.user_id = %d
                ORDER BY p.name ASC, pcc.applies_to_type_key ASC, d.name ASC",
                $user_id
            )
        );
    }

    public static function get_by_property($property_id){
        global $wpdb;

        $table_link = $wpdb->prefix . 'vm_property_cost_categories';
        $table_def = $wpdb->prefix . 'vm_cost_category_definitions';
        $table_keys = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_key_defs = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pcc.*,
                    d.name AS name,
                    d.name AS category_name,
                    d.description AS category_description,
                    pcc.allocation_type,
                    pcc.is_recurring,
                    pk.distribution_key_definition_id,
                    kd.label AS distribution_key_label,
                    kd.label AS distribution_label,
                    kd.unit_code AS distribution_key_unit_code,
                    kd.unit_code AS distribution_unit_code,
                    kd.total_value AS distribution_total_value
                FROM $table_link pcc
                LEFT JOIN $table_def d ON pcc.cost_category_definition_id = d.id
                LEFT JOIN $table_keys pk ON pcc.property_distribution_key_id = pk.id
                LEFT JOIN $table_key_defs kd ON pk.distribution_key_definition_id = kd.id
                WHERE pcc.property_id = %d
                AND pcc.user_id = %d
                ORDER BY pcc.applies_to_type_key ASC, d.name ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );
    }
}