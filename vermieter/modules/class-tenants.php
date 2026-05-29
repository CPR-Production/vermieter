<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Tenants {

    public static function add($data) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenants';

        if (!empty($data['iban']) && strlen(preg_replace('/\s+/', '', $data['iban'])) < 15) {
            return false;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'    => get_current_user_id(),
                'salutation' => sanitize_text_field($data['salutation'] ?? ''),
                'first_name' => vm_format_name(sanitize_text_field($data['first_name'] ?? '')),
                'last_name'  => vm_format_name(sanitize_text_field($data['last_name'] ?? '')),
                'email'      => sanitize_email($data['email'] ?? ''),
                'phone'      => sanitize_text_field($data['phone'] ?? ''),
                'mailing_address' => self::sanitize_mailing_address($data['mailing_address'] ?? ''),
                'iban'       => sanitize_text_field($data['iban'] ?? ''),
                'bank_name'  => sanitize_text_field($data['bank_name'] ?? ''),
            ],
            ['%d','%s','%s','%s','%s','%s','%s','%s','%s']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public static function get_all_by_user($user_id = 0) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE user_id = %d
                 ORDER BY last_name ASC, first_name ASC",
                $user_id
            )
        );
    }

    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_tenants';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE id = %d AND user_id = %d",
                (int) $id,
                get_current_user_id()
            )
        );
    }



    public static function sanitize_mailing_address($address) {
        $address = sanitize_textarea_field((string) $address);
        $parts = array_filter(array_map('trim', explode(';', $address)), function ($part) {
            return $part !== '';
        });

        return implode('; ', $parts);
    }

    public static function format_mailing_address_lines($address) {
        $parts = array_filter(array_map('trim', explode(';', (string) $address)), function ($part) {
            return $part !== '';
        });

        return array_values($parts);
    }

    public static function get_display_name($tenant) {
        if (!$tenant) {
            return '';
        }

        return trim($tenant->salutation . ' ' . $tenant->first_name . ' ' . $tenant->last_name);
    }
}