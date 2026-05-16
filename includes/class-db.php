<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_DB {
    public static function install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_types                        = $wpdb->prefix . 'vm_apportionment_types';
        $table_properties                   = $wpdb->prefix . 'vm_properties';
        $table_apartments                   = $wpdb->prefix . 'vm_apartments';
        $table_costs                        = $wpdb->prefix . 'vm_costs';
        $table_cost_category_definitions    = $wpdb->prefix . 'vm_cost_category_definitions';
        $table_property_cost_categories     = $wpdb->prefix . 'vm_property_cost_categories';
        $table_distribution_key_definitions = $wpdb->prefix . 'vm_distribution_key_definitions';
        $table_property_distribution_keys   = $wpdb->prefix . 'vm_property_distribution_keys';
        $table_apartment_distribution_values = $wpdb->prefix . 'vm_apartment_distribution_values';
        $table_tenants                      = $wpdb->prefix . 'vm_tenants';
        $table_apartment_tenants            = $wpdb->prefix . 'vm_apartment_tenants';
        $table_tenancy_rent_terms           = $wpdb->prefix . 'vm_tenancy_rent_terms';
        $table_tenancy_advance_terms        = $wpdb->prefix . 'vm_tenancy_advance_terms';
        $table_tenant_payments              = $wpdb->prefix . 'vm_tenant_payments';
        $table_heating_statements           = $wpdb->prefix . 'vm_heating_statements';
        $table_heating_statement_items      = $wpdb->prefix . 'vm_heating_statement_items';
        $table_settings                     = $wpdb->prefix . 'vm_settings';


        $sql_settings = "CREATE TABLE $table_settings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value LONGTEXT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_setting_key (user_id, setting_key),
            KEY user_id (user_id),
            KEY setting_key (setting_key)
        ) $charset_collate;";

        $sql_tenancy_rent_terms = "CREATE TABLE $table_tenancy_rent_terms (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            apartment_tenant_id BIGINT UNSIGNED NOT NULL,
            valid_from DATE NOT NULL,
            cold_rent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY apartment_tenant_id (apartment_tenant_id),
            KEY valid_from (valid_from)
        ) $charset_collate;";

        $sql_tenancy_advance_terms = "CREATE TABLE $table_tenancy_advance_terms (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            apartment_tenant_id BIGINT UNSIGNED NOT NULL,
            valid_from DATE NOT NULL,
            nk_advance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            hk_advance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY apartment_tenant_id (apartment_tenant_id),
            KEY valid_from (valid_from)
        ) $charset_collate;";


        $sql_heating_statements = "CREATE TABLE $table_heating_statements (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            property_id BIGINT UNSIGNED NOT NULL,
            apartment_id BIGINT UNSIGNED NOT NULL,
            provider_name VARCHAR(190) NOT NULL DEFAULT 'Brunata Metrona',
            billing_year INT NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            statement_date DATE NULL,
            total_building_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            own_unit_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY property_id (property_id),
            KEY apartment_id (apartment_id),
            KEY billing_year (billing_year)
        ) $charset_collate;";

        $sql_heating_statement_items = "CREATE TABLE $table_heating_statement_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            statement_id BIGINT UNSIGNED NOT NULL,
            cost_id BIGINT UNSIGNED NULL,
            apartment_tenant_id BIGINT UNSIGNED NULL,
            cost_type VARCHAR(50) NOT NULL,
            split_type VARCHAR(50) NOT NULL,
            label VARCHAR(190) NOT NULL,
            property_cost_category_id BIGINT UNSIGNED NOT NULL,
            amount_building_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            amount_own_unit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            consumption_building_total DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
            consumption_own_unit DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
            consumption_unit VARCHAR(50) NULL,
            distribution_key VARCHAR(100) NULL,
            base_value_building DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
            base_value_own_unit DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
            price_per_unit DECIMAL(14,6) NOT NULL DEFAULT 0.000000,
            already_period_related TINYINT(1) NOT NULL DEFAULT 0,
            is_billable TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY statement_id (statement_id),
            KEY cost_id (cost_id),
            KEY apartment_tenant_id (apartment_tenant_id),
            KEY cost_type (cost_type),
            KEY split_type (split_type)
        ) $charset_collate;";

        $sql_tenant_payments = "CREATE TABLE $table_tenant_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            apartment_tenant_id BIGINT UNSIGNED NOT NULL,
            payment_month DATE NOT NULL,
            payment_date DATE NULL,
            amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            is_paid TINYINT(1) NOT NULL DEFAULT 0,
            note TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tenant_payment_month (apartment_tenant_id, payment_month),
            KEY user_id (user_id),
            KEY apartment_tenant_id (apartment_tenant_id),
            KEY payment_month (payment_month)
        ) $charset_collate;";

        $sql_tenants = "CREATE TABLE $table_tenants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            salutation VARCHAR(20) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(50) NULL,
            mailing_address TEXT NULL,
            iban VARCHAR(34) NULL,
            bank_name VARCHAR(190) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY last_name (last_name)
        ) $charset_collate;";

        $sql_apartment_tenants = "CREATE TABLE $table_apartment_tenants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            apartment_id BIGINT UNSIGNED NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            move_in_date DATE NOT NULL,
            move_out_date DATE NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY apartment_id (apartment_id),
            KEY tenant_id (tenant_id),
            KEY move_in_date (move_in_date),
            KEY move_out_date (move_out_date)
        ) $charset_collate;";

        $sql_cost_category_definitions = "CREATE TABLE $table_cost_category_definitions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            default_allocation_type VARCHAR(50) NOT NULL DEFAULT 'wohnflaeche',
            default_is_recurring TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY name (name),
            KEY default_allocation_type (default_allocation_type)
        ) $charset_collate;";

        $sql_property_cost_categories = "CREATE TABLE $table_property_cost_categories (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            property_id BIGINT UNSIGNED NOT NULL,
            cost_category_definition_id BIGINT UNSIGNED NOT NULL,
            allocation_type VARCHAR(50) NOT NULL DEFAULT 'wohnflaeche',
            property_distribution_key_id BIGINT UNSIGNED NULL,
            applies_to_type_key VARCHAR(50) NOT NULL DEFAULT 'alle',
            is_recurring TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY property_definition_type (property_id, cost_category_definition_id, applies_to_type_key),
            KEY user_id (user_id),
            KEY property_id (property_id),
            KEY cost_category_definition_id (cost_category_definition_id),
            KEY allocation_type (allocation_type),
            KEY property_distribution_key_id (property_distribution_key_id),
            KEY applies_to_type_key (applies_to_type_key)
        ) $charset_collate;";

        $sql_costs = "CREATE TABLE $table_costs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            property_id BIGINT UNSIGNED NOT NULL,
            property_cost_category_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            betrag DECIMAL(12,2) NOT NULL,
            invoice_date DATE NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            period_year INT NOT NULL,
            target_apartment_id BIGINT UNSIGNED NULL,
            apartment_tenant_id BIGINT UNSIGNED NULL,
            calculation_mode VARCHAR(50) NOT NULL DEFAULT 'allocation',
            source_type VARCHAR(50) NULL,
            source_id BIGINT UNSIGNED NULL,
            no_time_factor TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY property_id (property_id),
            KEY property_cost_category_id (property_cost_category_id),
            KEY period_year (period_year),
            KEY target_apartment_id (target_apartment_id),
            KEY apartment_tenant_id (apartment_tenant_id),
            KEY calculation_mode (calculation_mode),
            KEY source_type_source_id (source_type, source_id)
        ) $charset_collate;";

        $sql_distribution_key_definitions = "CREATE TABLE $table_distribution_key_definitions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            label VARCHAR(150) NOT NULL,
            unit_code VARCHAR(50) NOT NULL,
            total_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY label (label),
            KEY unit_code (unit_code)
        ) $charset_collate;";

        $sql_property_distribution_keys = "CREATE TABLE $table_property_distribution_keys (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            property_id BIGINT UNSIGNED NOT NULL,
            distribution_key_definition_id BIGINT UNSIGNED NOT NULL,
            applies_to_type_key VARCHAR(50) NOT NULL DEFAULT 'alle',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY property_definition_type (property_id, distribution_key_definition_id, applies_to_type_key),
            KEY user_id (user_id),
            KEY property_id (property_id),
            KEY distribution_key_definition_id (distribution_key_definition_id),
            KEY applies_to_type_key (applies_to_type_key)
        ) $charset_collate;";

        $sql_apartment_distribution_values = "CREATE TABLE $table_apartment_distribution_values (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            property_distribution_key_id BIGINT UNSIGNED NOT NULL,
            apartment_id BIGINT UNSIGNED NOT NULL,
            value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY property_key_apartment (property_distribution_key_id, apartment_id),
            KEY user_id (user_id),
            KEY property_distribution_key_id (property_distribution_key_id),
            KEY apartment_id (apartment_id)
        ) $charset_collate;";

        $sql_types = "CREATE TABLE $table_types (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            key_name VARCHAR(50) NOT NULL,
            description TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_key_name (key_name)
        ) $charset_collate;";

        $sql_properties = "CREATE TABLE $table_properties (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            street VARCHAR(150) NULL,
            house_number VARCHAR(20) NULL,
            zip_code VARCHAR(20) NULL,
            city VARCHAR(100) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        $sql_apartments = "CREATE TABLE $table_apartments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            property_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            type_key VARCHAR(50) NOT NULL DEFAULT 'wohnung',
            wohnflaeche DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            personen INT NOT NULL DEFAULT 0,
            acquisition_date DATE NULL,
            disposal_date DATE NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY property_id (property_id),
            KEY type_key (type_key),
            KEY acquisition_date (acquisition_date),
            KEY disposal_date (disposal_date)
        ) $charset_collate;";

        dbDelta($sql_types);
        dbDelta($sql_properties);
        dbDelta($sql_apartments);
        dbDelta($sql_costs);
        dbDelta($sql_cost_category_definitions);
        dbDelta($sql_property_cost_categories);
        dbDelta($sql_distribution_key_definitions);
        dbDelta($sql_property_distribution_keys);
        dbDelta($sql_apartment_distribution_values);
        dbDelta($sql_tenants);
        dbDelta($sql_apartment_tenants);
        dbDelta($sql_tenancy_rent_terms);
        dbDelta($sql_tenancy_advance_terms);
        dbDelta($sql_tenant_payments);
        dbDelta($sql_heating_statements);
        dbDelta($sql_heating_statement_items);
        dbDelta($sql_settings);

        self::seed_apportionment_types();
        self::install_default_distribution_key_definitions();
        self::install_default_cost_category_definitions();

        update_option('vermieter_db_version', VERMIETER_DB_VERSION);
    }

    private static function install_default_cost_category_definitions() {
        global $wpdb;

        $table = $wpdb->prefix . 'vm_cost_category_definitions';

        $defaults = [
            ['name' => 'Abwasser', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Gartenpflege', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Hausmeister', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Müllabfuhr', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Putzmaterial', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Sonstige Wartungen', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 0],
            ['name' => 'Stromkosten und Wasser', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Versicherungen', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Heizkosten', 'description' => 'Heizkosten laut Brunata-/Messdienstabrechnung', 'default_allocation_type' => 'brunata_statement', 'default_is_recurring' => 1],
            ['name' => 'Hausreinigung', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
            ['name' => 'Allgemeinstrom', 'description' => '', 'default_allocation_type' => 'distribution_key', 'default_is_recurring' => 1],
        ];

        foreach ($defaults as $row) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id
                    FROM $table
                    WHERE user_id = %d
                    AND name = %s
                    LIMIT 1",
                    0,
                    $row['name']
                )
            );

            if (!$exists) {
                $wpdb->insert(
                    $table,
                    [
                        'user_id'                 => 0,
                        'name'                    => $row['name'],
                        'description'             => $row['description'],
                        'default_allocation_type' => $row['default_allocation_type'],
                        'default_is_recurring'    => $row['default_is_recurring'],
                    ],
                    ['%d', '%s', '%s', '%s', '%d']
                );
            }
        }
    }

    public static function maybe_upgrade() {
        $installed = get_option('vermieter_db_version');

        if ($installed !== VERMIETER_DB_VERSION) {
            self::install();
        }
    }

    public static function seed_apportionment_types() {
        global $wpdb;

        $table = $wpdb->prefix . 'vm_apportionment_types';
        $defaults = [
            ['name' => 'Wohnfläche', 'key_name' => 'wohnflaeche', 'description' => 'Verteilung nach Quadratmetern'],
            ['name' => 'Personen', 'key_name' => 'personen', 'description' => 'Verteilung nach Personenzahl'],
            ['name' => 'Verteilerschlüssel', 'key_name' => 'distribution_key', 'description' => 'Verteilung über hinterlegte Schlüsselwerte je Wohnung'],
            ['name' => 'Lt. Abrechnung Brunata', 'key_name' => 'brunata_statement', 'description' => 'Fertige Brunata-/Messdienst-Abrechnungsbeträge je Nutzungszeitraum; keine weitere Verteilung und kein Zeitfaktor'],
        ];

        foreach ($defaults as $row) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE key_name = %s", $row['key_name']));
            if (!$exists) {
                $wpdb->insert($table, $row, ['%s', '%s', '%s']);
            }
        }
    }

    private static function install_default_distribution_key_definitions() {
    global $wpdb;

    $table = $wpdb->prefix . 'vm_distribution_key_definitions';

    // Wenn du aktuell userbezogen arbeitest, musst du entscheiden:
    // Soll der Default global für alle User oder pro User existieren?
    // Für dein jetziges Modell ist meist ein Eintrag mit user_id = 0 sinnvoll.

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $table
             WHERE user_id = %d
               AND label = %s
               AND unit_code = %s
               AND total_value = %f
             LIMIT 1",
            0,
            '1000-STEL',
            'Einheit',
            1000.00
        )
    );

    if (!$exists) {
        $wpdb->insert(
            $table,
            [
                'user_id'     => 0,
                'label'       => '1000-STEL',
                'unit_code'   => 'Einheit',
                'total_value' => 1000.00,
            ],
            ['%d', '%s', '%s', '%f']
        );
    }
}
}
