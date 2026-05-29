<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Tenancy_Rent_Terms
{
    public static function add($data)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenancy_rent_terms';

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'            => get_current_user_id(),
                'apartment_tenant_id'=> (int) ($data['apartment_tenant_id'] ?? 0),
                'valid_from'         => sanitize_text_field($data['valid_from'] ?? ''),
                'cold_rent'          => (float) ($data['cold_rent'] ?? 0),
            ],
            ['%d', '%d', '%s', '%f']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get_by_apartment_tenant($apartment_tenant_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenancy_rent_terms';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM $table
                 WHERE apartment_tenant_id = %d
                   AND user_id = %d
                 ORDER BY valid_from ASC, id ASC",
                (int) $apartment_tenant_id,
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

        $table = $wpdb->prefix . 'vm_tenancy_rent_terms';
        $table_links = $wpdb->prefix . 'vm_apartment_tenants';
        $table_apartments = $wpdb->prefix . 'vm_apartments';
        $table_tenants = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    rt.*,
                    a.name AS apartment_name,
                    t.salutation,
                    t.first_name,
                    t.last_name
                 FROM $table rt
                 LEFT JOIN $table_links at ON rt.apartment_tenant_id = at.id
                 LEFT JOIN $table_apartments a ON at.apartment_id = a.id
                 LEFT JOIN $table_tenants t ON at.tenant_id = t.id
                 WHERE rt.user_id = %d
                 ORDER BY a.name ASC, t.last_name ASC, rt.valid_from ASC",
                $user_id
            )
        );
    }

    public static function get_valid_cold_rent_for_date($apartment_tenant_id, $date) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenancy_rent_terms';

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT cold_rent
                FROM $table
                WHERE apartment_tenant_id = %d
                AND user_id = %d
                AND valid_from <= %s
                ORDER BY valid_from DESC, id DESC
                LIMIT 1",
                (int) $apartment_tenant_id,
                get_current_user_id(),
                $date
            )
        );

        return (float) $value;
    }
}