<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Apartments {
    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartments';

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'      => get_current_user_id(),
                'property_id'  => (int) ($data['property_id'] ?? 0),
                'name'         => sanitize_text_field($data['name'] ?? ''),
                'type_key'     => sanitize_text_field($data['type_key'] ?? 'wohnung'),
                'wohnflaeche'  => (float) ($data['wohnflaeche'] ?? 0),
                'personen'     => (int) ($data['personen'] ?? 0),
            ],
            ['%d', '%d', '%s', '%s', '%f', '%d']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get_by_property($property_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartments';

        if (!vm_table_exists($table)) {
            return [];
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE property_id = %d AND user_id = %d ORDER BY name ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );
    }

    public static function get_all_by_user($user_id = 0) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table_apartments = $wpdb->prefix . 'vm_apartments';
        $table_properties = $wpdb->prefix . 'vm_properties';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    a.*,
                    p.name AS property_name
                FROM $table_apartments a
                LEFT JOIN $table_properties p ON a.property_id = p.id
                WHERE a.user_id = %d
                ORDER BY p.name ASC, a.name ASC",
                $user_id
            )
        );
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartments';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table
                WHERE id = %d
                AND user_id = %d",
                (int) $id,
                get_current_user_id()
            )
        );
    }

    public static function update($id, $data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartments';
        $user_id = get_current_user_id();

        $id = (int) $id;
        $property_id = (int) ($data['property_id'] ?? 0);
        $name = sanitize_text_field($data['name'] ?? '');
        $type_key = sanitize_text_field($data['type_key'] ?? 'wohnung');
        $wohnflaeche = (float) ($data['wohnflaeche'] ?? 0);
        $personen = (int) ($data['personen'] ?? 0);

        if ($id <= 0 || $property_id <= 0 || $name === '') {
            return false;
        }

        $updated = $wpdb->update(
            $table,
            [
                'property_id' => $property_id,
                'name'        => $name,
                'type_key'    => $type_key,
                'wohnflaeche' => $wohnflaeche,
                'personen'    => $personen,
            ],
            [
                'id'      => $id,
                'user_id' => $user_id,
            ],
            ['%d', '%s', '%s', '%f', '%d'],
            ['%d', '%d']
        );

        return $updated !== false;
    }
}
