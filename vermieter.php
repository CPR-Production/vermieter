<?php
/*
Plugin Name: Vermieter / Nebenkostenabrechnung
Description: Nebenkostenverwaltung für Vermieter
Version: 0.11.3
Author: Christian Husemann
*/

if (!defined('ABSPATH')) {
    exit;
}

define('VERMIETER_VERSION', '0.11.3');
define('VERMIETER_DB_VERSION', '0.11.3');
define('VERMIETER_PLUGIN_FILE', __FILE__);
define('VERMIETER_PATH', plugin_dir_path(__FILE__));
define('VERMIETER_URL', plugin_dir_url(__FILE__));

require_once VERMIETER_PATH . 'includes/helpers.php';
require_once VERMIETER_PATH . 'includes/class-activator.php';
require_once VERMIETER_PATH . 'includes/class-db.php';
require_once VERMIETER_PATH . 'includes/class-shortcodes.php';
require_once VERMIETER_PATH . 'includes/class-admin-pages.php';
require_once VERMIETER_PATH . 'includes/class-site-structure.php';

require_once VERMIETER_PATH . 'modules/class-nebenkosten-billing.php';
require_once VERMIETER_PATH . 'modules/class-pdf-export.php';
require_once VERMIETER_PATH . 'modules/class-tenancy-rent-terms.php';
require_once VERMIETER_PATH . 'modules/class-tenancy-advance-terms.php';
require_once VERMIETER_PATH . 'modules/class-tenant-payments.php';
require_once VERMIETER_PATH . 'modules/class-apportionment-types.php';
require_once VERMIETER_PATH . 'modules/class-properties.php';
require_once VERMIETER_PATH . 'modules/class-apartments.php';
require_once VERMIETER_PATH . 'modules/class-billing.php';
require_once VERMIETER_PATH . 'modules/class-apartment-distribution-values.php';
require_once VERMIETER_PATH . 'modules/class-costs.php';
require_once VERMIETER_PATH . 'modules/class-heating-statements.php';
require_once VERMIETER_PATH . 'modules/class-cost-category-definitions.php';
require_once VERMIETER_PATH . 'modules/class-property-cost-categories.php';
require_once VERMIETER_PATH . 'modules/class-tenants.php';
require_once VERMIETER_PATH . 'modules/class-apartment-tenants.php';
require_once VERMIETER_PATH . 'modules/class-distribution-key-definitions.php';
require_once VERMIETER_PATH . 'modules/class-property-distribution-keys.php';
require_once VERMIETER_PATH . 'modules/class-apartment-distribution-values.php';

register_activation_hook(VERMIETER_PLUGIN_FILE, ['Vermieter_Activator', 'activate']);
register_activation_hook(VERMIETER_PLUGIN_FILE, ['Vermieter_Site_Structure', 'install_default_structure']);
add_action('plugins_loaded', ['Vermieter_DB', 'maybe_upgrade']);
add_action('init', ['Vermieter_Shortcodes', 'register']);
add_action('init', ['Vermieter_PDF_Export', 'register']);
add_action('admin_menu', ['Vermieter_Admin_Pages', 'register_menu']);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'vermieter-css',
        VERMIETER_URL . 'assets/css/vermieter.css',
        [],
        VERMIETER_VERSION
    );
});

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'vm-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        [],
        '6.5.0'
    );
});


add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'vermieter-pdf-export',
        VERMIETER_URL . 'assets/js/vm-pdf-export.js',
        [],
        VERMIETER_VERSION,
        true
    );

    wp_localize_script('vermieter-pdf-export', 'vmPdfExport', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('vm_pdf_export_nonce'),
        'labels'  => [
            'loading' => 'PDF-Ansicht wird vorbereitet …',
            'error'   => 'PDF-Ansicht konnte nicht erstellt werden.',
        ],
    ]);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script(
        'vermieter-costs-inline',
        VERMIETER_URL . 'assets/js/vm-costs-inline.js',
        [],
        VERMIETER_VERSION,
        true
    );

    wp_localize_script('vermieter-costs-inline', 'vmCostsInline', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('vm_costs_inline_nonce'),
        'labels'  => [
            'save'    => 'Speichern',
            'cancel'  => 'Abbrechen',
            'delete'  => 'Löschen',
            'saving'  => 'Speichert …',
            'error'   => 'Speichern fehlgeschlagen.',
            'confirm' => 'Diese Kostenposition wirklich löschen?',
        ],
    ]);
});