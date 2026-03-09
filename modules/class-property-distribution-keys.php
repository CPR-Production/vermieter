<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Property_Distribution_Keys {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_property_distribution_keys';
        $user_id = get_current_user_id();

        $property_id = (int) ($data['property_id'] ?? 0);
        $definition_id = (int) ($data['distribution_key_definition_id'] ?? 0);
        $applies_to_type_key = sanitize_text_field($data['applies_to_type_key'] ?? 'alle');

        if ($property_id <= 0 || $definition_id <= 0) {
            return false;
        }

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                 FROM $table
                 WHERE property_id = %d
                   AND distribution_key_definition_id = %d
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
                'user_id'                        => $user_id,
                'property_id'                    => $property_id,
                'distribution_key_definition_id' => $definition_id,
                'applies_to_type_key'            => $applies_to_type_key,
            ],
            ['%d', '%d', '%d', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get($id) {
        global $wpdb;

        $table_link = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_def  = $wpdb->prefix . 'vm_distribution_key_definitions';
        $table_prop = $wpdb->prefix . 'vm_properties';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    pk.*,
                    d.label,
                    d.unit_code,
                    d.total_value,
                    p.name AS property_name,
                    p.street AS property_street,
                    p.house_number AS property_house_number,
                    p.zip_code AS property_zip_code,
                    p.city AS property_city
                 FROM $table_link pk
                 LEFT JOIN $table_def d ON pk.distribution_key_definition_id = d.id
                 LEFT JOIN $table_prop p ON pk.property_id = p.id
                 WHERE pk.id = %d
                   AND pk.user_id = %d",
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

        $table_link = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_def  = $wpdb->prefix . 'vm_distribution_key_definitions';
        $table_prop = $wpdb->prefix . 'vm_properties';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pk.*,
                    d.label,
                    d.unit_code,
                    d.total_value,
                    p.name AS property_name,
                    p.street AS property_street,
                    p.house_number AS property_house_number,
                    p.zip_code AS property_zip_code,
                    p.city AS property_city
                 FROM $table_link pk
                 LEFT JOIN $table_def d ON pk.distribution_key_definition_id = d.id
                 LEFT JOIN $table_prop p ON pk.property_id = p.id
                 WHERE pk.user_id = %d
                 ORDER BY p.name ASC, pk.applies_to_type_key ASC, d.label ASC, d.unit_code ASC",
                $user_id
            )
        );
    }

    public static function get_by_property($property_id) {
        global $wpdb;

        $table_link = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_def  = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pk.*,
                    d.label,
                    d.unit_code,
                    d.total_value
                 FROM $table_link pk
                 LEFT JOIN $table_def d ON pk.distribution_key_definition_id = d.id
                 WHERE pk.property_id = %d
                   AND pk.user_id = %d
                 ORDER BY pk.applies_to_type_key ASC, d.label ASC, d.unit_code ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );
    }

    public static function get_by_property_and_type($property_id, $type_key = 'alle') {
        global $wpdb;

        $table_link = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_def  = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pk.*,
                    d.label,
                    d.unit_code,
                    d.total_value
                 FROM $table_link pk
                 LEFT JOIN $table_def d ON pk.distribution_key_definition_id = d.id
                 WHERE pk.property_id = %d
                   AND pk.user_id = %d
                   AND (pk.applies_to_type_key = %s OR pk.applies_to_type_key = 'alle')
                 ORDER BY pk.applies_to_type_key ASC, d.label ASC, d.unit_code ASC",
                (int) $property_id,
                get_current_user_id(),
                $type_key
            )
        );
    }

    public static function update($id, $data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_property_distribution_keys';
        $user_id = get_current_user_id();

        $id = (int) $id;
        $property_id = (int) ($data['property_id'] ?? 0);
        $definition_id = (int) ($data['distribution_key_definition_id'] ?? 0);
        $applies_to_type_key = sanitize_text_field($data['applies_to_type_key'] ?? 'alle');

        if ($id <= 0 || $property_id <= 0 || $definition_id <= 0) {
            return false;
        }

        $duplicate_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM $table
                WHERE property_id = %d
                AND distribution_key_definition_id = %d
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
                'property_id'                    => $property_id,
                'distribution_key_definition_id' => $definition_id,
                'applies_to_type_key'            => $applies_to_type_key,
            ],
            [
                'id'      => $id,
                'user_id' => $user_id,
            ],
            ['%d', '%d', '%s'],
            ['%d', '%d']
        );

        return $updated !== false;
    }
}