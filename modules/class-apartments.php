<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Apartments
{
    public static function add($data)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_apartments';

        $property_id = (int) ($data['property_id'] ?? 0);
        $name = sanitize_text_field($data['name'] ?? '');
        $type_key = sanitize_text_field($data['type_key'] ?? 'wohnung');
        $wohnflaeche = (float) ($data['wohnflaeche'] ?? 0);
        $personen = (int) ($data['personen'] ?? 0);
        $acquisition_date = !empty($data['acquisition_date']) ? sanitize_text_field($data['acquisition_date']) : null;
        $disposal_date = !empty($data['disposal_date']) ? sanitize_text_field($data['disposal_date']) : null;

        if ($property_id <= 0 || $name === '') {
            return false;
        }

        if (!empty($acquisition_date) && !empty($disposal_date) && $disposal_date < $acquisition_date) {
            return false;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'          => get_current_user_id(),
                'property_id'      => $property_id,
                'name'             => $name,
                'type_key'         => $type_key,
                'wohnflaeche'      => $wohnflaeche,
                'personen'         => $personen,
                'acquisition_date' => $acquisition_date,
                'disposal_date'    => $disposal_date,
            ],
            ['%d', '%d', '%s', '%s', '%f', '%d', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get($id)
    {
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

    public static function get_all_by_user($user_id = 0)
    {
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

    public static function get_by_property($property_id)
    {
        global $wpdb;

        $table_apartments = $wpdb->prefix . 'vm_apartments';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM $table_apartments
                 WHERE property_id = %d
                   AND user_id = %d
                 ORDER BY name ASC, id ASC",
                (int) $property_id,
                get_current_user_id()
            )
        );
    }

    public static function get_existing_dependencies($apartment_id)
    {
        global $wpdb;

        $apartment_id = (int) $apartment_id;
        $user_id = get_current_user_id();

        $dependencies = [];

        $table_apartment_tenants = $wpdb->prefix . 'vm_apartment_tenants';
        $table_distribution_values = $wpdb->prefix . 'vm_apartment_distribution_values';

        $tenant_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM $table_apartment_tenants
                 WHERE apartment_id = %d
                   AND user_id = %d",
                $apartment_id,
                $user_id
            )
        );

        if ($tenant_count > 0) {
            $dependencies[] = 'Mieter-Zuordnungen vorhanden';
        }

        $distribution_value_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM $table_distribution_values
                 WHERE apartment_id = %d
                   AND user_id = %d",
                $apartment_id,
                $user_id
            )
        );

        if ($distribution_value_count > 0) {
            $dependencies[] = 'Verteilwerte vorhanden';
        }

        return $dependencies;
    }

    public static function get_type_change_blockers($apartment_id, $new_type_key)
    {
        $apartment_id = (int) $apartment_id;
        $new_type_key = sanitize_text_field($new_type_key);

        $current = self::get($apartment_id);

        if (!$current) {
            return ['Wohnung nicht gefunden.'];
        }

        if ($current->type_key === $new_type_key) {
            return [];
        }

        return self::get_existing_dependencies($apartment_id);
    }

    public static function can_change_type($apartment_id, $new_type_key)
    {
        return count(self::get_type_change_blockers($apartment_id, $new_type_key)) === 0;
    }

    public static function update($id, $data)
    {
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'message' => 'Bitte einloggen.',
            ];
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
        $acquisition_date = !empty($data['acquisition_date']) ? sanitize_text_field($data['acquisition_date']) : null;
        $disposal_date = !empty($data['disposal_date']) ? sanitize_text_field($data['disposal_date']) : null;

        if ($id <= 0 || $property_id <= 0 || $name === '') {
            return [
                'success' => false,
                'message' => 'Objekt und Name sind erforderlich.',
            ];
        }

        if (!empty($acquisition_date) && !empty($disposal_date) && $disposal_date < $acquisition_date) {
            return [
                'success' => false,
                'message' => 'Verkauf / Bestandsende darf nicht vor dem Kaufdatum liegen.',
            ];
        }

        $blockers = self::get_type_change_blockers($id, $type_key);

        if (!empty($blockers)) {
            return [
                'success' => false,
                'message' => 'Der Typ kann nicht geändert werden: ' . implode(' ', $blockers),
                'blockers' => $blockers,
            ];
        }

        $updated = $wpdb->update(
            $table,
            [
                'property_id'      => $property_id,
                'name'             => $name,
                'type_key'         => $type_key,
                'wohnflaeche'      => $wohnflaeche,
                'personen'         => $personen,
                'acquisition_date' => $acquisition_date,
                'disposal_date'    => $disposal_date,
            ],
            [
                'id'      => $id,
                'user_id' => $user_id,
            ],
            ['%d', '%s', '%s', '%f', '%d', '%s', '%s'],
            ['%d', '%d']
        );

        return [
            'success' => $updated !== false,
            'message' => $updated !== false
                ? 'Wohnung aktualisiert.'
                : 'Wohnung konnte nicht aktualisiert werden.',
        ];
    }
}