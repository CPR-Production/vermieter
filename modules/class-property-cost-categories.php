<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Property_Cost_Categories {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'message' => 'Bitte einloggen.',
            ];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $key_table = $wpdb->prefix . 'vm_property_distribution_keys';

        $user_id = get_current_user_id();
        $property_id = (int) ($data['property_id'] ?? 0);
        $definition_id = (int) ($data['cost_category_definition_id'] ?? 0);
        $allocation_type = sanitize_text_field($data['allocation_type'] ?? 'wohnflaeche');
        $property_distribution_key_id = !empty($data['property_distribution_key_id']) ? (int) $data['property_distribution_key_id'] : null;
        $applies_to_type_key = sanitize_text_field($data['applies_to_type_key'] ?? 'alle');
        $is_recurring = !empty($data['is_recurring']) ? 1 : 0;

        if ($property_id <= 0 || $definition_id <= 0) {
            return [
                'success' => false,
                'message' => 'Objekt und Kategorie sind erforderlich.',
            ];
        }

        if ($allocation_type !== 'distribution_key') {
            $property_distribution_key_id = null;
        }

        if (!empty($property_distribution_key_id)) {
            $valid_key_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                     FROM $key_table
                     WHERE id = %d
                       AND property_id = %d
                       AND applies_to_type_key IN (%s, 'alle')
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
                    'message' => 'Der gewählte Verteilerschlüssel passt nicht zu Objekt oder Typ.',
                ];
            }
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

        $data_row = [
            'user_id'                      => $user_id,
            'property_id'                  => $property_id,
            'cost_category_definition_id'  => $definition_id,
            'allocation_type'              => $allocation_type,
            'property_distribution_key_id' => $property_distribution_key_id,
            'applies_to_type_key'          => $applies_to_type_key,
            'is_recurring'                 => $is_recurring,
        ];

        $formats = ['%d', '%d', '%d', '%s', '%d', '%s', '%d'];

        if ($existing_id) {
            $updated = $wpdb->update(
                $table,
                $data_row,
                ['id' => (int) $existing_id],
                $formats,
                ['%d']
            );

            return [
                'success' => $updated !== false,
                'message' => $updated !== false
                    ? 'Objekt-Kostenkategorie aktualisiert.'
                    : 'Objekt-Kostenkategorie konnte nicht aktualisiert werden.',
                'id'      => (int) $existing_id,
            ];
        }

        $inserted = $wpdb->insert($table, $data_row, $formats);

        return [
            'success' => (bool) $inserted,
            'message' => $inserted
                ? 'Objekt-Kostenkategorie gespeichert.'
                : 'Objekt-Kostenkategorie konnte nicht gespeichert werden.',
            'id'      => $inserted ? (int) $wpdb->insert_id : 0,
        ];
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $definitions = $wpdb->prefix . 'vm_cost_category_definitions';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    pc.*,
                    d.name,
                    d.description
                 FROM $table pc
                 LEFT JOIN $definitions d ON pc.cost_category_definition_id = d.id
                 WHERE pc.id = %d
                   AND pc.user_id = %d",
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

        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $definitions = $wpdb->prefix . 'vm_cost_category_definitions';
        $properties = $wpdb->prefix . 'vm_properties';
        $keys = $wpdb->prefix . 'vm_property_distribution_keys';
        $key_defs = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pc.*,
                    d.name,
                    d.description,
                    p.name AS property_name,
                    kd.label AS distribution_key_label,
                    kd.unit_code AS distribution_key_unit_code
                 FROM $table pc
                 LEFT JOIN $definitions d ON pc.cost_category_definition_id = d.id
                 LEFT JOIN $properties p ON pc.property_id = p.id
                 LEFT JOIN $keys pk ON pc.property_distribution_key_id = pk.id
                 LEFT JOIN $key_defs kd ON pk.distribution_key_definition_id = kd.id
                 WHERE pc.user_id = %d
                 ORDER BY p.name ASC, pc.applies_to_type_key ASC, d.name ASC",
                $user_id
            )
        );
    }

    public static function get_by_property($property_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $definitions = $wpdb->prefix . 'vm_cost_category_definitions';
        $keys = $wpdb->prefix . 'vm_property_distribution_keys';
        $key_defs = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pc.*,
                    d.name,
                    d.description,
                    kd.label AS distribution_key_label,
                    kd.unit_code AS distribution_key_unit_code
                 FROM $table pc
                 LEFT JOIN $definitions d ON pc.cost_category_definition_id = d.id
                 LEFT JOIN $keys pk ON pc.property_distribution_key_id = pk.id
                 LEFT JOIN $key_defs kd ON pk.distribution_key_definition_id = kd.id
                 WHERE pc.property_id = %d
                   AND pc.user_id = %d
                 ORDER BY pc.applies_to_type_key ASC, d.name ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );
    }

    public static function get_by_property_and_type($property_id, $type_key = 'alle') {
        global $wpdb;

        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $definitions = $wpdb->prefix . 'vm_cost_category_definitions';
        $keys = $wpdb->prefix . 'vm_property_distribution_keys';
        $key_defs = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pc.*,
                    d.name,
                    d.description,
                    kd.label AS distribution_key_label,
                    kd.unit_code AS distribution_key_unit_code
                 FROM $table pc
                 LEFT JOIN $definitions d ON pc.cost_category_definition_id = d.id
                 LEFT JOIN $keys pk ON pc.property_distribution_key_id = pk.id
                 LEFT JOIN $key_defs kd ON pk.distribution_key_definition_id = kd.id
                 WHERE pc.property_id = %d
                   AND pc.user_id = %d
                   AND (pc.applies_to_type_key = %s OR pc.applies_to_type_key = 'alle')
                 ORDER BY pc.applies_to_type_key ASC, d.name ASC",
                (int) $property_id,
                get_current_user_id(),
                $type_key
            )
        );
    }
}