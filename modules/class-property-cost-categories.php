<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Property_Cost_Categories {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'message' => 'Benutzer ist nicht eingeloggt.',
            ];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_property_cost_categories';
        $user_id = get_current_user_id();

        $property_id = (int) ($data['property_id'] ?? 0);
        $definition_id = (int) ($data['cost_category_definition_id'] ?? 0);
        $allocation_type = sanitize_text_field($data['allocation_type'] ?? 'wohnflaeche');
        $property_distribution_key_id = !empty($data['property_distribution_key_id'])
            ? (int) $data['property_distribution_key_id']
            : null;
        $is_recurring = !empty($data['is_recurring']) ? 1 : 0;

        if ($allocation_type !== 'distribution_key') {
            $property_distribution_key_id = null;
        }

        if ($property_id <= 0 && !empty($property_distribution_key_id)) {
            $key_table = $wpdb->prefix . 'vm_property_distribution_keys';

            $resolved_property_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT property_id
                    FROM $key_table
                    WHERE id = %d
                    AND user_id = %d
                    LIMIT 1",
                    $property_distribution_key_id,
                    $user_id
                )
            );

            if ($resolved_property_id) {
                $property_id = (int) $resolved_property_id;
            } else {
                return [
                    'success' => false,
                    'message' => 'Zum gewählten Objekt-Schlüssel konnte kein Objekt gefunden werden.',
                ];
            }
        }

        if ($property_id <= 0) {
            return [
                'success' => false,
                'message' => 'Es wurde kein gültiges Objekt ermittelt.',
            ];
        }

        if ($definition_id <= 0) {
            return [
                'success' => false,
                'message' => 'Es wurde keine gültige Kategoriedefinition gewählt.',
            ];
        }

        if ($allocation_type === 'distribution_key' && empty($property_distribution_key_id)) {
            return [
                'success' => false,
                'message' => 'Bei Verteilungsart "Verteilerschlüssel" muss ein Objekt-Schlüssel gewählt werden.',
            ];
        }

        if (!empty($property_distribution_key_id)) {
            $key_table = $wpdb->prefix . 'vm_property_distribution_keys';

            $valid_key_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM $key_table
                    WHERE id = %d
                    AND property_id = %d
                    AND user_id = %d
                    LIMIT 1",
                    $property_distribution_key_id,
                    $property_id,
                    $user_id
                )
            );

            if (!$valid_key_id) {
                return [
                    'success' => false,
                    'message' => 'Der gewählte Objekt-Schlüssel gehört nicht zum ermittelten Objekt.',
                ];
            }
        }

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                FROM $table
                WHERE property_id = %d
                AND cost_category_definition_id = %d
                AND user_id = %d
                LIMIT 1",
                $property_id,
                $definition_id,
                $user_id
            )
        );

        if ($existing_id) {
            $updated = $wpdb->update(
                $table,
                [
                    'allocation_type'              => $allocation_type,
                    'property_distribution_key_id' => $property_distribution_key_id,
                    'is_recurring'                 => $is_recurring,
                ],
                [
                    'id'      => (int) $existing_id,
                    'user_id' => $user_id,
                ],
                ['%s', '%d', '%d'],
                ['%d', '%d']
            );

            if ($updated === false) {
                return [
                    'success' => false,
                    'message' => 'DB-Fehler beim Aktualisieren: ' . $wpdb->last_error,
                ];
            }

            return [
                'success' => true,
                'message' => 'Objekt-Kategorie aktualisiert.',
                'id'      => (int) $existing_id,
            ];
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'                      => $user_id,
                'property_id'                  => $property_id,
                'cost_category_definition_id'  => $definition_id,
                'allocation_type'              => $allocation_type,
                'property_distribution_key_id' => $property_distribution_key_id,
                'is_recurring'                 => $is_recurring,
            ],
            ['%d', '%d', '%d', '%s', '%d', '%d']
        );

        if (!$inserted) {
            return [
                'success' => false,
                'message' => 'DB-Fehler beim Speichern: ' . $wpdb->last_error,
            ];
        }

        return [
            'success' => true,
            'message' => 'Objekt-Kategorie gespeichert.',
            'id'      => (int) $wpdb->insert_id,
        ];
    }

    public static function get($id) {
        global $wpdb;
        $table_link = $wpdb->prefix . 'vm_property_cost_categories';
        $table_def  = $wpdb->prefix . 'vm_cost_category_definitions';
        $table_prop = $wpdb->prefix . 'vm_properties';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    pc.*,
                    d.name,
                    d.description,
                    p.name AS property_name
                 FROM $table_link pc
                 LEFT JOIN $table_def d ON pc.cost_category_definition_id = d.id
                 LEFT JOIN $table_prop p ON pc.property_id = p.id
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

        $table_link = $wpdb->prefix . 'vm_property_cost_categories';
        $table_def  = $wpdb->prefix . 'vm_cost_category_definitions';
        $table_prop = $wpdb->prefix . 'vm_properties';
        $table_pdk  = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_dkd  = $wpdb->prefix . 'vm_distribution_key_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pc.*,
                    d.name AS category_name,
                    d.description,
                    p.name AS property_name,
                    dk.label AS key_label,
                    dk.unit_code AS key_unit_code,
                    dk.total_value AS key_total_value
                FROM $table_link pc
                LEFT JOIN $table_def d
                    ON pc.cost_category_definition_id = d.id
                LEFT JOIN $table_prop p
                    ON pc.property_id = p.id
                LEFT JOIN $table_pdk pdk
                    ON pc.property_distribution_key_id = pdk.id
                LEFT JOIN $table_dkd dk
                    ON pdk.distribution_key_definition_id = dk.id
                WHERE pc.user_id = %d
                ORDER BY p.name ASC, d.name ASC",
                $user_id
            )
        );
    }

    public static function get_by_property($property_id) {
        global $wpdb;

        $table_link = $wpdb->prefix . 'vm_property_cost_categories';
        $table_def  = $wpdb->prefix . 'vm_cost_category_definitions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    pc.*,
                    d.name,
                    d.description
                 FROM $table_link pc
                 LEFT JOIN $table_def d ON pc.cost_category_definition_id = d.id
                 WHERE pc.property_id = %d
                   AND pc.user_id = %d
                 ORDER BY d.name ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );
    }
}