<?php
if (!defined('ABSPATH')) {
    exit;
}

function vm_table_exists($table_name) {
    global $wpdb;
    $like = $wpdb->esc_like($table_name);
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
    return $found === $table_name;
}

function vm_format_name($name) {

    $name = trim($name);

    if ($name === '') {
        return '';
    }

    $name = mb_strtolower($name, 'UTF-8');
    $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");

    return $name;
}

function vm_default_period() {
    $year = (int) current_time('Y');

    return [
        'start' => $year . '-01-01',
        'end'   => $year . '-12-31',
        'year'  => $year,
    ];
}

function vm_default_lastperiod() {
    $year = (int) (current_time('Y') - 1);

    return [
        'start' => $year . '-01-01',
        'end'   => $year . '-12-31',
        'year'  => $year,
    ];
}

function vm_render_template($template, $vars = []) {
    $file = VERMIETER_PATH . 'templates/' . ltrim($template, '/');

    if (!file_exists($file)) {
        return '';
    }

    if (!empty($vars)) {
        extract($vars, EXTR_SKIP);
    }

    ob_start();
    include $file;
    return ob_get_clean();
}

function vm_post_decimal($key, $default = 0) {
    if (!isset($_POST[$key])) {
        return (float) $default;
    }

    return (float) str_replace(',', '.', sanitize_text_field(wp_unslash($_POST[$key])));
}

function vm_log($data) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    error_log('[Vermieter] ' . print_r($data, true));
}

function vm_format_type($type_key) {
    $map = [
        'wohnung'    => 'Wohnung',
        'garage'     => 'Garage',
        'stellplatz' => 'Stellplatz',
        'keller'     => 'Keller',
        'alle'       => 'Alle Typen',
    ];

    return $map[$type_key] ?? ucfirst($type_key);
}

function vm_format_money($value) {
    return number_format((float) $value, 2, ',', '.') . ' €';
}

function vm_format_number($value) {
    return number_format((float) $value, 2, ',', '.');
}

function vm_format_date($date) {
    if (!$date) return '—';
    return date('d.m.Y', strtotime($date));
}

function vm_yes_no($value) {
    return (int)$value === 1 ? 'Ja' : 'Nein';
}
function vm_label($key) {
    $labels = [
        'property' => 'Objekt',
        'apartment' => 'Wohnung',
        'tenant' => 'Mieter',
        'distribution_key' => 'Verteilerschlüssel',
    ];

    return $labels[$key] ?? $key;
}