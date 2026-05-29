<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Apportionment_Types {
    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_apportionment_types';

        if (!vm_table_exists($table)) {
            return [];
        }

        return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
    }

    public static function get_options() {
        $rows = self::get_all();
        $options = [];

        foreach ($rows as $row) {
            $options[$row->key_name] = $row->name;
        }

        return $options;
    }
}
