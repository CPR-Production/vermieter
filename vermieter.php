<?php
/*
Plugin Name: Vermieter / Nebenkostenabrechnung
Description: Nebenkostenverwaltung für Vermieter
Version: 0.10.3
Author: Christian Husemann
*/

if (!defined('ABSPATH')) {
    exit;
}

define('VERMIETER_VERSION', '0.10.3');
define('VERMIETER_DB_VERSION', '0.9.0');
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
require_once VERMIETER_PATH . 'modules/class-tenancy-rent-terms.php';
require_once VERMIETER_PATH . 'modules/class-tenancy-advance-terms.php';
require_once VERMIETER_PATH . 'modules/class-tenant-payments.php';
require_once VERMIETER_PATH . 'modules/class-apportionment-types.php';
require_once VERMIETER_PATH . 'modules/class-properties.php';
require_once VERMIETER_PATH . 'modules/class-apartments.php';
require_once VERMIETER_PATH . 'modules/class-billing.php';
require_once VERMIETER_PATH . 'modules/class-apartment-distribution-values.php';
require_once VERMIETER_PATH . 'modules/class-costs.php';
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
add_action('admin_menu', ['Vermieter_Admin_Pages', 'register_menu']);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'vermieter-css',
        VERMIETER_URL . 'assets/css/vermieter.css',
        [],
        VERMIETER_VERSION
    );
});