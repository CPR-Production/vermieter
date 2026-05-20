<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Shortcodes {
    public static function register() {
        add_shortcode('vermieter_properties', [__CLASS__, 'properties_shortcode']);
        add_shortcode('vermieter_apartments', [__CLASS__, 'apartments_shortcode']);
        add_shortcode('vermieter_form', [__CLASS__, 'billing_costs_shortcode']);
        add_shortcode('vermieter_rechnungen', [__CLASS__, 'vermieter_list_shortcode']);
        add_shortcode('vermieter_abrechnung', [__CLASS__, 'billing_shortcode']);
        add_shortcode('vermieter_cost_category_definitions', [__CLASS__, 'cost_category_definitions_shortcode']);
        add_shortcode('vermieter_property_cost_categories', [__CLASS__, 'property_cost_categories_shortcode']);
        add_shortcode('vermieter_distribution_key_definitions', [__CLASS__, 'distribution_key_definitions_shortcode']);
        add_shortcode('vermieter_property_distribution_keys', [__CLASS__, 'property_distribution_keys_shortcode']);
        add_shortcode('vermieter_costs_table', [__CLASS__, 'costs_table_shortcode']);
        add_shortcode('vermieter_tenants', [__CLASS__, 'tenants_shortcode']);
        add_shortcode('vermieter_apartment_tenants', [__CLASS__, 'apartment_tenants_shortcode']);
        add_shortcode('vermieter_property_dashboard', [__CLASS__, 'property_dashboard_shortcode']);
        add_shortcode('vermieter_tenancy_rent_terms', [__CLASS__, 'tenancy_rent_terms_shortcode']);
        add_shortcode('vermieter_tenancy_advance_terms', [__CLASS__, 'tenancy_advance_terms_shortcode']);
        add_shortcode('vermieter_tenant_payments', [__CLASS__, 'tenant_payments_shortcode']);
        add_shortcode('vermieter_mietkonto', [__CLASS__, 'mietkonto_shortcode']);
        add_shortcode('vermieter_nebenkostenabrechnung', [__CLASS__, 'nebenkostenabrechnung_shortcode']);
        add_shortcode('vermieter_heating_statements', [__CLASS__, 'heating_statements_shortcode']);
        add_action('wp_ajax_vm_update_cost_inline', [__CLASS__, 'update_cost_inline_ajax']);
        add_action('wp_ajax_vm_delete_cost_inline', [__CLASS__, 'delete_cost_inline_ajax']);
    }

    public static function update_cost_inline_ajax() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Bitte einloggen.'], 403);
        }

        check_ajax_referer('vm_costs_inline_nonce', 'nonce');

        $record_id = (int) ($_POST['id'] ?? 0);

        if ($record_id <= 0) {
            wp_send_json_error(['message' => 'Ungültige ID.'], 400);
        }

        $result = Vermieter_Costs::update($record_id, [
            'property_id'               => (int) ($_POST['property_id'] ?? 0),
            'property_cost_category_id' => (int) ($_POST['property_cost_category_id'] ?? 0),
            'name'                      => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'betrag'                    => isset($_POST['betrag'])
                ? (float) str_replace(',', '.', wp_unslash($_POST['betrag']))
                : 0,
            'invoice_date'              => sanitize_text_field(wp_unslash($_POST['invoice_date'] ?? '')),
            'period_start'              => sanitize_text_field(wp_unslash($_POST['period_start'] ?? '')),
            'period_end'                => sanitize_text_field(wp_unslash($_POST['period_end'] ?? '')),
            'period_year'               => (int) ($_POST['period_year'] ?? 0),
        ]);

        if (!$result) {
            wp_send_json_error(['message' => 'Kostenposition konnte nicht aktualisiert werden.']);
        }

        $item = Vermieter_Costs::get($record_id);

        if (!$item) {
            wp_send_json_error(['message' => 'Kostenposition nach dem Speichern nicht gefunden.']);
        }

        wp_send_json_success([
            'message' => 'Kostenposition aktualisiert.',
            'item'    => [
                'id'                        => (int) $item->id,
                'property_id'               => (int) $item->property_id,
                'property_cost_category_id' => (int) $item->property_cost_category_id,
                'category_name'             => (string) ($item->category_name ?? ''),
                'name'                      => (string) $item->name,
                'betrag'                    => (float) $item->betrag,
                'betrag_formatted'          => vm_format_money((float) $item->betrag),
                'invoice_date'              => (string) $item->invoice_date,
                'invoice_date_formatted'    => vm_format_date($item->invoice_date),
                'period_start'              => (string) $item->period_start,
                'period_end'                => (string) $item->period_end,
                'period_start_formatted'    => vm_format_date($item->period_start),
                'period_end_formatted'      => vm_format_date($item->period_end),
                'period_year'               => (int) $item->period_year,
                'is_recurring'              => !empty($item->is_recurring) ? 1 : 0,
                'is_recurring_label'        => !empty($item->is_recurring) ? 'Ja' : 'Nein',
            ],
        ]);
    }

    public static function delete_cost_inline_ajax() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Bitte einloggen.'], 403);
        }

        check_ajax_referer('vm_costs_inline_nonce', 'nonce');

        $record_id = (int) ($_POST['id'] ?? 0);

        if ($record_id <= 0) {
            wp_send_json_error(['message' => 'Ungültige ID.'], 400);
        }

        $result = Vermieter_Costs::delete($record_id);

        if (!$result) {
            wp_send_json_error(['message' => 'Kostenposition konnte nicht gelöscht werden.']);
        }

        wp_send_json_success([
            'message' => 'Kostenposition gelöscht.',
            'id'      => $record_id,
        ]);
    }


    public static function heating_statements_shortcode($atts) {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $atts = shortcode_atts([
            'property_id' => 0,
            'year'        => 0,
        ], $atts, 'vermieter_heating_statements');

        $message = '';
        $properties = Vermieter_Properties::get_all();
        $selected_property_id = (int) ($atts['property_id'] ?: ($_REQUEST['vm_property_id'] ?? 0));
        $selected_year = (int) ($atts['year'] ?: ($_REQUEST['vm_year'] ?? current_time('Y')));

        if ($selected_property_id <= 0 && !empty($properties)) {
            $selected_property_id = (int) $properties[0]->id;
        }

        if ($selected_year <= 0) {
            $selected_year = (int) current_time('Y');
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_heating_statement') {
            check_admin_referer('vm_save_heating_statement');

            $selected_property_id = (int) ($_POST['vm_property_id'] ?? 0);
            $selected_year = (int) ($_POST['vm_billing_year'] ?? current_time('Y'));

            $result = Vermieter_Heating_Statements::save([
                'property_id' => $selected_property_id,
                'apartment_id' => (int) ($_POST['vm_apartment_id'] ?? 0),
                'provider_name' => sanitize_text_field(wp_unslash($_POST['vm_provider_name'] ?? 'Brunata Metrona')),
                'billing_year' => $selected_year,
                'period_start' => sanitize_text_field(wp_unslash($_POST['vm_period_start'] ?? '')),
                'period_end' => sanitize_text_field(wp_unslash($_POST['vm_period_end'] ?? '')),
                'statement_date' => sanitize_text_field(wp_unslash($_POST['vm_statement_date'] ?? '')),
                'notes' => sanitize_textarea_field(wp_unslash($_POST['vm_notes'] ?? '')),
            ], $_POST['vm_rows'] ?? []);

            $message = $result
                ? 'Heizkostenabrechnung gespeichert. ' . (int) $result['created_costs'] . ' Position(en) wurden für die Nebenkostenabrechnung erzeugt.'
                : 'Heizkostenabrechnung konnte nicht gespeichert werden. Bitte Objekt, Einheit, Zeitraum und mindestens eine Kostenposition prüfen.';
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'delete_heating_statement') {
            check_admin_referer('vm_delete_heating_statement');
            $deleted = Vermieter_Heating_Statements::delete((int) ($_POST['vm_statement_id'] ?? 0));
            $message = $deleted ? 'Heizkostenabrechnung und erzeugte Kostenpositionen gelöscht.' : 'Löschen fehlgeschlagen.';
        }

        $apartments = $selected_property_id ? Vermieter_Apartments::get_by_property($selected_property_id) : [];
        $property_categories = $selected_property_id ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id) : [];
        // Übersicht bewusst nur nach Objekt filtern, nicht nach Einheit und nicht nach Jahr.
        // Sonst können bereits erfasste Heizkosten unsichtbar werden und versehentlich doppelt erfasst werden.
        $statements = Vermieter_Heating_Statements::get_all_by_user($selected_property_id, 0);
        $usage_rows = $selected_property_id ? Vermieter_Heating_Statements::get_usage_rows_by_property_and_year($selected_property_id, $selected_year) : [];

        return vm_render_template('form-heating-statements.php', [
            'message' => $message,
            'properties' => $properties,
            'apartments' => $apartments,
            'property_categories' => $property_categories,
            'selected_property_id' => $selected_property_id,
            'selected_year' => $selected_year,
            'default_rows' => Vermieter_Heating_Statements::get_default_rows(),
            'usage_rows' => $usage_rows,
            'statements' => $statements,
        ]);
    }

    public static function nebenkostenabrechnung_shortcode($atts) {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $atts = shortcode_atts([
            'property_id' => 0,
            'year'        => 0,
        ], $atts, 'vermieter_nebenkostenabrechnung');

        $properties = Vermieter_Properties::get_all();

        $selected_property_id = (int) $atts['property_id'];
        $selected_year = (int) $atts['year'];

        if ($selected_property_id <= 0 && isset($_GET['vm_property_id'])) {
            $selected_property_id = (int) $_GET['vm_property_id'];
        }

        if ($selected_year <= 0 && isset($_GET['vm_year'])) {
            $selected_year = (int) $_GET['vm_year'];
        }

        if ($selected_property_id <= 0 && !empty($properties)) {
            $selected_property_id = (int) $properties[0]->id;
        }

        if ($selected_year <= 0) {
            $selected_year = (int) current_time('Y');
        }

        $statement = [];
        if ($selected_property_id > 0 && $selected_year > 0) {
            $statement = Vermieter_Nebenkosten_Billing::build_property_statement($selected_property_id, $selected_year);
        }

        return vm_render_template('nebenkostenabrechnung.php', [
            'properties'           => $properties,
            'selected_property_id' => $selected_property_id,
            'selected_year'        => $selected_year,
            'statement'            => $statement,
        ]);
    }

    public static function mietkonto_shortcode($atts) {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $atts = shortcode_atts([
            'apartment_tenant_id' => 0,
        ], $atts, 'vermieter_mietkonto');

        $selected_id = (int) $atts['apartment_tenant_id'];

        if ($selected_id <= 0 && isset($_GET['vm_apartment_tenant_id'])) {
            $selected_id = (int) $_GET['vm_apartment_tenant_id'];
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_tenant_special_payment') {
            check_admin_referer('vm_save_tenant_special_payment');

            $selected_id = (int) ($_POST['vm_apartment_tenant_id'] ?? $selected_id);

            $id = Vermieter_Tenant_Special_Payments::add([
                'apartment_tenant_id' => $selected_id,
                'billing_year'        => (int) ($_POST['vm_billing_year'] ?? 0),
                'payment_date'        => sanitize_text_field(wp_unslash($_POST['vm_payment_date'] ?? '')),
                'amount'              => vm_post_decimal('vm_amount'),
                'payment_type'        => sanitize_key($_POST['vm_payment_type'] ?? 'settlement_payment'),
                'note'                => sanitize_textarea_field(wp_unslash($_POST['vm_note'] ?? '')),
            ]);

            $message = $id ? 'Zusatz-Zahlung gespeichert.' : 'Zusatz-Zahlung konnte nicht gespeichert werden.';
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'delete_tenant_special_payment') {
            check_admin_referer('vm_delete_tenant_special_payment');

            $selected_id = (int) ($_POST['vm_apartment_tenant_id'] ?? $selected_id);
            $deleted = Vermieter_Tenant_Special_Payments::delete((int) ($_POST['vm_special_payment_id'] ?? 0));
            $message = $deleted ? 'Zusatz-Zahlung gelöscht.' : 'Zusatz-Zahlung konnte nicht gelöscht werden.';
        }

        $apartment_tenants = Vermieter_Apartment_Tenants::get_all_by_user();
        $ledger_rows = $selected_id > 0
            ? Vermieter_Tenant_Payments::get_ledger_rows_by_apartment_tenant($selected_id)
            : [];
        $special_payments = $selected_id > 0
            ? Vermieter_Tenant_Special_Payments::get_by_apartment_tenant($selected_id)
            : [];

        return vm_render_template('mietkonto.php', [
            'message'           => $message,
            'apartment_tenants' => $apartment_tenants,
            'selected_id'       => $selected_id,
            'ledger_rows'       => $ledger_rows,
            'special_payments'  => $special_payments,
        ]);
    }

    public static function tenancy_rent_terms_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_tenancy_rent_term') {
            check_admin_referer('vm_save_tenancy_rent_term');

            $id = Vermieter_Tenancy_Rent_Terms::add([
                'apartment_tenant_id' => (int) ($_POST['vm_apartment_tenant_id'] ?? 0),
                'valid_from'          => sanitize_text_field(wp_unslash($_POST['vm_valid_from'] ?? '')),
                'cold_rent'           => vm_post_decimal('vm_cold_rent'),
            ]);

            $message = $id ? 'Kaltmiete gespeichert.' : 'Kaltmiete konnte nicht gespeichert werden.';
        }

        return vm_render_template('form-tenancy-rent-terms.php', [
            'message'           => $message,
            'apartment_tenants' => Vermieter_Apartment_Tenants::get_all_by_user(),
            'rent_terms'        => Vermieter_Tenancy_Rent_Terms::get_all_by_user(),
        ]);
    }

    public static function tenancy_advance_terms_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_tenancy_advance_term') {
            check_admin_referer('vm_save_tenancy_advance_term');

            $id = Vermieter_Tenancy_Advance_Terms::add([
                'apartment_tenant_id' => (int) ($_POST['vm_apartment_tenant_id'] ?? 0),
                'valid_from'          => sanitize_text_field(wp_unslash($_POST['vm_valid_from'] ?? '')),
                'nk_advance'          => vm_post_decimal('vm_nk_advance'),
                'hk_advance'          => vm_post_decimal('vm_hk_advance'),
            ]);

            $message = $id ? 'Nebenkosten-/Heizkosten-Vorauszahlung gespeichert.' : 'Vorauszahlung konnte nicht gespeichert werden.';
        }

        return vm_render_template('form-tenancy-advance-terms.php', [
            'message'           => $message,
            'apartment_tenants' => Vermieter_Apartment_Tenants::get_all_by_user(),
            'advance_terms'     => Vermieter_Tenancy_Advance_Terms::get_all_by_user(),
        ]);
    }

    public static function tenant_payments_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_open_payments_table') {
            check_admin_referer('vm_save_open_payments_table');

            $rows = $_POST['vm_rows'] ?? [];
            $saved = 0;

            foreach ($rows as $row) {
                $apartment_tenant_id = (int) ($row['apartment_tenant_id'] ?? 0);
                $payment_month = sanitize_text_field($row['payment_month'] ?? '');
                $payment_date = sanitize_text_field($row['payment_date'] ?? '');
                $is_paid = !empty($row['is_paid']) ? 1 : 0;

                if ($apartment_tenant_id <= 0 || $payment_month === '') {
                    continue;
                }

                $target = Vermieter_Tenant_Payments::get_monthly_target($apartment_tenant_id, $payment_month);

                $amount_paid = $is_paid
                    ? (float) $target['total_target']
                    : (isset($row['amount_paid']) ? (float) str_replace(',', '.', str_replace('.', '', (string) $row['amount_paid'])) : 0);

                $id = Vermieter_Tenant_Payments::add_or_update([
                    'apartment_tenant_id' => $apartment_tenant_id,
                    'payment_month'       => $payment_month,
                    'payment_date'        => $payment_date,
                    'amount_paid'         => $amount_paid,
                    'is_paid'             => $is_paid,
                    'note'                => sanitize_textarea_field($row['note'] ?? ''),
                ]);

                if ($id) {
                    $saved++;
                }
            }

            $message = $saved > 0 ? $saved . ' Zahlung(en) gespeichert.' : 'Keine Zahlung gespeichert.';
        }

        return vm_render_template('form-tenant-payments.php', [
            'message'       => $message,
            'open_rows'     => Vermieter_Tenant_Payments::get_open_payment_rows(),
            'payments'      => Vermieter_Tenant_Payments::get_all_by_user(),
        ]);
    }

    public static function property_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $atts = shortcode_atts([
            'property_id' => 0,
        ], $atts, 'vermieter_property_dashboard');

        $properties = Vermieter_Properties::get_all();
        $selected_property_id = (int) $atts['property_id'];

        if ($selected_property_id <= 0 && isset($_GET['vm_property_id'])) {
            $selected_property_id = (int) $_GET['vm_property_id'];
        }

        if ($selected_property_id <= 0 && !empty($properties)) {
            $selected_property_id = (int) $properties[0]->id;
        }

        $property = $selected_property_id ? Vermieter_Properties::get($selected_property_id) : null;
        $apartments = $selected_property_id ? Vermieter_Apartments::get_by_property($selected_property_id) : [];
        $cost_categories = $selected_property_id ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id) : [];
        $distribution_keys = $selected_property_id ? Vermieter_Property_Distribution_Keys::get_by_property($selected_property_id) : [];

        if (!empty($cost_categories)) {
            usort($cost_categories, function ($a, $b) {
                return strcasecmp(
                    (string) ($a->name ?? ''),
                    (string) ($b->name ?? '')
                );
            });
        }

        if (!empty($distribution_keys)) {
            usort($distribution_keys, function ($a, $b) {
                return strcasecmp(
                    (string) ($a->label ?? ''),
                    (string) ($b->label ?? '')
                );
            });
        }

        $cost_category_count = count($cost_categories);
        $distribution_key_count = count($distribution_keys);

        $apartment_tenants = [];
        $apartment_usage_summaries = [];
        $dashboard_year = (int) current_time('Y');

        $recorded_cost_years = $selected_property_id > 0
            ? Vermieter_Costs::get_recorded_years_by_property($selected_property_id)
            : [];

        $cost_status_matrix = [];
        $all_cost_years = [];

        if (!empty($apartments)) {
            foreach ($apartments as $apartment) {
                $apartment_tenants[$apartment->id] = Vermieter_Apartment_Tenants::get_by_apartment($apartment->id);
                $apartment_usage_summaries[$apartment->id] = Vermieter_Apartment_Tenants::get_usage_summary_by_apartment($apartment->id);

                $start_year = !empty($apartment->acquisition_date)
                    ? (int) date('Y', strtotime($apartment->acquisition_date))
                    : 0;

                $end_year = !empty($apartment->disposal_date)
                    ? (int) date('Y', strtotime($apartment->disposal_date))
                    : $dashboard_year;

                if ($start_year <= 0) {
                    continue;
                }

                if ($end_year < $start_year) {
                    $end_year = $start_year;
                }

                for ($year = $start_year; $year <= $end_year; $year++) {
                    $all_cost_years[$year] = $year;
                    $cost_status_matrix[$apartment->id][$year] = in_array($year, $recorded_cost_years, true)
                        ? 'complete'
                        : 'missing';
                }
            }
        }

        ksort($all_cost_years);

        return vm_render_template('property-dashboard.php', [
            'properties'                => $properties,
            'selected_property_id'      => $selected_property_id,
            'property'                  => $property,
            'apartments'                => $apartments,
            'cost_categories'           => $cost_categories,
            'cost_category_count'       => $cost_category_count,
            'distribution_key_count'    => $distribution_key_count,
            'distribution_keys'         => $distribution_keys,
            'apartment_tenants'         => $apartment_tenants,
            'apartment_usage_summaries' => $apartment_usage_summaries,
            'dashboard_year'            => $dashboard_year,
            'apportionment_types'       => Vermieter_Apportionment_Types::get_options(),
            'cost_status_matrix'        => $cost_status_matrix,
            'all_cost_years'            => array_values($all_cost_years),
        ]);
    }

    public static function tenants_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_tenant') {
            check_admin_referer('vm_save_tenant');

            $id = Vermieter_Tenants::add([
                'salutation' => sanitize_text_field(wp_unslash($_POST['vm_salutation'] ?? '')),
                'first_name' => sanitize_text_field(wp_unslash($_POST['vm_first_name'] ?? '')),
                'last_name'  => sanitize_text_field(wp_unslash($_POST['vm_last_name'] ?? '')),
                'email'      => sanitize_email(wp_unslash($_POST['vm_email'] ?? '')),
                'phone'      => sanitize_text_field(wp_unslash($_POST['vm_phone'] ?? '')),
                'mailing_address' => sanitize_textarea_field(wp_unslash($_POST['vm_mailing_address'] ?? '')),
                'iban'       => sanitize_text_field(wp_unslash($_POST['vm_iban'] ?? '')),
                'bank_name'  => sanitize_text_field(wp_unslash($_POST['vm_bank_name'] ?? '')),
            ]);

            $message = $id ? 'Mieter gespeichert.' : 'Mieter konnte nicht gespeichert werden.';
        }

        return vm_render_template('form-tenants.php', [
            'message' => $message,
            'tenants' => Vermieter_Tenants::get_all_by_user(),
        ]);
    }

    public static function apartment_tenants_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';
        $selected_year = isset($_REQUEST['vm_year']) ? (int) $_REQUEST['vm_year'] : (int) current_time('Y');
        $selected_property_id = isset($_REQUEST['property_id']) ? (int) $_REQUEST['property_id'] : 0;

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_apartment_tenant') {
            check_admin_referer('vm_save_apartment_tenant');

            $id = Vermieter_Apartment_Tenants::add([
                'apartment_id'  => (int) ($_POST['vm_apartment_id'] ?? 0),
                'tenant_id'     => (int) ($_POST['vm_tenant_id'] ?? 0),
                'move_in_date'  => sanitize_text_field(wp_unslash($_POST['vm_move_in_date'] ?? '')),
                'move_out_date' => sanitize_text_field(wp_unslash($_POST['vm_move_out_date'] ?? '')),
            ]);

            $message = $id
                ? 'Mieter der Wohnung zugeordnet.'
                : 'Zuordnung konnte nicht gespeichert werden. Bitte Zeiträume prüfen.';
        }

        $apartments = Vermieter_Apartments::get_all_by_user();

        if ($selected_property_id > 0) {
            $apartments = array_values(array_filter($apartments, function ($apartment) use ($selected_property_id) {
                return (int) ($apartment->property_id ?? 0) === $selected_property_id;
            }));
        }

        return vm_render_template('form-apartment-tenants.php', [
            'message'              => $message,
            'selected_year'        => $selected_year,
            'selected_property_id' => $selected_property_id,
            'apartments'           => $apartments,
            'tenants'              => Vermieter_Tenants::get_all_by_user(),
            'apartment_tenants'    => Vermieter_Apartment_Tenants::get_all_by_user(),
        ]);
    }

    public static function cost_category_definitions_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';
        $edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
        $edit_item = $edit_id ? Vermieter_Cost_Category_Definitions::get($edit_id) : null;

        if ($edit_item && (int) $edit_item->user_id === 0) {
            $edit_item = null;
            $edit_id = 0;
            $message = 'System-Kategoriedefinitionen können nicht bearbeitet werden.';
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_cost_category_definition') {
            check_admin_referer('vm_save_cost_category_definition');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);

            $data = [
                'name'                    => sanitize_text_field(wp_unslash($_POST['vm_name'] ?? '')),
                'description'             => sanitize_textarea_field(wp_unslash($_POST['vm_description'] ?? '')),
                'default_allocation_type' => sanitize_text_field(wp_unslash($_POST['vm_default_allocation_type'] ?? 'wohnflaeche')),
                'default_is_recurring'    => !empty($_POST['vm_default_is_recurring']) ? 1 : 0,
            ];

            if ($record_id > 0) {
                $result = Vermieter_Cost_Category_Definitions::update($record_id, $data);
                $message = $result
                    ? 'Kategoriedefinition aktualisiert.'
                    : 'Kategoriedefinition konnte nicht aktualisiert werden.';
            } else {
                $id = Vermieter_Cost_Category_Definitions::add($data);
                $message = $id
                    ? 'Kategoriedefinition gespeichert.'
                    : 'Kategoriedefinition konnte nicht gespeichert werden.';
            }

            $edit_item = null;
            $edit_id = 0;
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'delete_cost_category_definition') {
            check_admin_referer('vm_delete_cost_category_definition');

            $result = Vermieter_Cost_Category_Definitions::delete((int) ($_POST['vm_record_id'] ?? 0));
            $message = !empty($result['message'])
                ? $result['message']
                : 'Kategoriedefinition konnte nicht gelöscht werden.';

            $edit_item = null;
            $edit_id = 0;
        }

        return vm_render_template('form-cost-category-definitions.php', [
            'message'             => $message,
            'definitions'         => Vermieter_Cost_Category_Definitions::get_all_by_user(),
            'apportionment_types' => Vermieter_Apportionment_Types::get_options(),
            'edit_item'           => $edit_item,
            'edit_id'             => $edit_id,
        ]);
    }

    public static function property_cost_categories_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        $preselected_property_id = isset($_GET['property_id']) ? (int) $_GET['property_id'] : 0;

        $default_item = (object) [
            'property_id' => $preselected_property_id,
            'cost_category_definition_id' => 0,
            'applies_to_type_key' => 'alle',
            'property_distribution_key_id' => 0,
        ];

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_property_cost_category') {
            check_admin_referer('vm_save_property_cost_category');

            $result = Vermieter_Property_Cost_Categories::add([
                'property_id'                  => (int) ($_POST['vm_property_id'] ?? 0),
                'cost_category_definition_id'  => (int) ($_POST['vm_cost_category_definition_id'] ?? 0),
                'allocation_type'              => sanitize_text_field(wp_unslash($_POST['vm_allocation_type'] ?? 'wohnflaeche')),
                'property_distribution_key_id' => (int) ($_POST['vm_property_distribution_key_id'] ?? 0),
                'applies_to_type_key'          => sanitize_text_field(wp_unslash($_POST['vm_applies_to_type_key'] ?? 'alle')),
                'is_recurring'                 => !empty($_POST['vm_is_recurring']) ? 1 : 0,
            ]);

            $message = $result
                ? 'Zuordnung gespeichert.'
                : 'Speichern fehlgeschlagen.';
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'update_property_cost_category_inline') {
            check_admin_referer('vm_update_property_cost_category_inline');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);

            $result = Vermieter_Property_Cost_Categories::update($record_id, [
                'property_id' => (int) ($_POST['vm_property_id'] ?? 0),
                'cost_category_definition_id' => (int) ($_POST['vm_cost_category_definition_id'] ?? 0),
                'allocation_type' => sanitize_text_field(wp_unslash($_POST['vm_allocation_type'] ?? 'wohnflaeche')),
                'applies_to_type_key' => sanitize_text_field(wp_unslash($_POST['vm_applies_to_type_key'] ?? 'alle')),
                'property_distribution_key_id' => !empty($_POST['vm_property_distribution_key_id'])
                    ? (int) $_POST['vm_property_distribution_key_id']
                    : 0,
                'is_recurring' => !empty($_POST['vm_is_recurring']) ? 1 : 0,
            ]);

            $message = $result
                ? 'Zuordnung aktualisiert.'
                : 'Zuordnung konnte nicht aktualisiert werden.';

            $edit_item = null;
            $edit_id = 0;
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'delete_property_cost_category') {
            check_admin_referer('vm_delete_property_cost_category');

            $result = Vermieter_Property_Cost_Categories::delete((int) ($_POST['vm_record_id'] ?? 0));
            $message = !empty($result['message']) ? $result['message'] : 'Zuordnung konnte nicht gelöscht werden.';

            $edit_item = null;
            $edit_id = 0;
        }

        $edit_item = $edit_item ?: $default_item;
        $properties = Vermieter_Properties::get_all();
        $definitions = Vermieter_Cost_Category_Definitions::get_all_by_user();
        $distribution_keys = Vermieter_Property_Distribution_Keys::get_all_by_user();

        $assigned_categories = $preselected_property_id
            ? Vermieter_Property_Cost_Categories::get_by_property($preselected_property_id)
            : Vermieter_Property_Cost_Categories::get_all_by_user();

        $active_property = null;

        if ($preselected_property_id > 0) {
            foreach ($properties as $property) {
                if ((int) $property->id === (int) $preselected_property_id) {
                    $active_property = $property;
                    break;
                }
            }
        }

        return vm_render_template('form-property-cost-categories.php', [
            'message'             => $message,
            'properties'          => $properties,
            'definitions'         => $definitions,
            'active_property'     => $active_property,
            'assigned_categories' => $assigned_categories,
            'distribution_keys'   => $distribution_keys,
            'assigned_keys'       => $distribution_keys, // optional für bestehendes Formular oben
            'edit_item'           => $edit_item,
            'apportionment_types' => Vermieter_Apportionment_Types::get_options(),
        ]);
    }

    public static function costs_table_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';
        $properties = Vermieter_Properties::get_all();

        $selected_property_id = isset($_REQUEST['vm_property_id']) ? (int) $_REQUEST['vm_property_id'] : 0;
        $selected_year = isset($_REQUEST['vm_year']) ? (int) $_REQUEST['vm_year'] : 0;
        $edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
        $edit_item = $edit_id ? Vermieter_Costs::get($edit_id) : null;

        if ($edit_item) {
            $selected_property_id = (int) $edit_item->property_id;
            $selected_year = (int) $edit_item->period_year;
        }

        if ($selected_year <= 0) {
            $selected_year = (int) current_time('Y');
        }

        $period = [
            'year'  => $selected_year,
            'start' => $selected_year . '-01-01',
            'end'   => $selected_year . '-12-31',
        ];

        $property_categories = $selected_property_id
            ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id)
            : [];

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_costs_table') {
            check_admin_referer('vm_save_costs_table');

            $selected_property_id = (int) ($_POST['vm_property_id'] ?? 0);
            $selected_year = (int) ($_POST['vm_period_year'] ?? 0);

            if ($selected_year <= 0) {
                $selected_year = (int) current_time('Y');
            }

            $period = [
                'year'  => $selected_year,
                'start' => sanitize_text_field(wp_unslash($_POST['vm_period_start'] ?? ($selected_year . '-01-01'))),
                'end'   => sanitize_text_field(wp_unslash($_POST['vm_period_end'] ?? ($selected_year . '-12-31'))),
            ];

            $property_categories = $selected_property_id
                ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id)
                : [];

            $base_cost_data = [
                'property_id'  => $selected_property_id,
                'period_year'  => $selected_year,
                'invoice_date' => sanitize_text_field(wp_unslash($_POST['vm_invoice_date'] ?? '')),
                'period_start' => $period['start'],
                'period_end'   => $period['end'],
            ];

            $count = Vermieter_Costs::add_multiple(
                $base_cost_data,
                $_POST['vm_rows'] ?? []
            );

            $brunata_count = Vermieter_Costs::add_brunata_split_rows(
                $base_cost_data,
                $_POST['vm_brunata_rows'] ?? []
            );

            $total_count = $count + $brunata_count;
            $message = $total_count ? $total_count . ' Position(en) gespeichert.' : 'Keine Positionen gespeichert.';
            $edit_item = null;
            $edit_id = 0;
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'update_cost') {
            check_admin_referer('vm_update_cost');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);
            $selected_property_id = (int) ($_POST['vm_property_id'] ?? 0);
            $selected_year = (int) ($_POST['vm_period_year'] ?? 0);

            if ($selected_year <= 0) {
                $selected_year = (int) current_time('Y');
            }

            $period = [
                'year'  => $selected_year,
                'start' => sanitize_text_field(wp_unslash($_POST['vm_period_start'] ?? ($selected_year . '-01-01'))),
                'end'   => sanitize_text_field(wp_unslash($_POST['vm_period_end'] ?? ($selected_year . '-12-31'))),
            ];

            $result = Vermieter_Costs::update($record_id, [
                'property_id'               => $selected_property_id,
                'property_cost_category_id' => (int) ($_POST['vm_property_cost_category_id'] ?? 0),
                'name'                      => sanitize_text_field(wp_unslash($_POST['vm_name'] ?? '')),
                'betrag'                    => vm_post_decimal('vm_betrag'),
                'invoice_date'              => sanitize_text_field(wp_unslash($_POST['vm_invoice_date'] ?? '')),
                'period_start'              => $period['start'],
                'period_end'                => $period['end'],
                'period_year'               => $selected_year,
            ]);

            $message = $result ? 'Kostenposition aktualisiert.' : 'Kostenposition konnte nicht aktualisiert werden.';
            $property_categories = $selected_property_id
                ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id)
                : [];
            $edit_item = null;
            $edit_id = 0;
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'delete_cost') {
            check_admin_referer('vm_delete_cost');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);
            $selected_property_id = (int) ($_POST['vm_property_id'] ?? 0);
            $selected_year = (int) ($_POST['vm_year'] ?? 0);

            if ($selected_year <= 0) {
                $selected_year = (int) current_time('Y');
            }

            $result = Vermieter_Costs::delete($record_id);
            $message = $result ? 'Kostenposition gelöscht.' : 'Kostenposition konnte nicht gelöscht werden.';

            $property_categories = $selected_property_id
                ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id)
                : [];
            $edit_item = null;
            $edit_id = 0;
        }

        $costs = ($selected_property_id > 0 && $selected_year > 0)
            ? Vermieter_Costs::get_by_property_and_year($selected_property_id, $selected_year)
            : [];

        return vm_render_template('form-costs-table.php', [
            'message'              => $message,
            'period'               => $period,
            'properties'           => $properties,
            'selected_property_id' => $selected_property_id,
            'selected_year'        => $selected_year,
            'property_categories'  => $property_categories,
            'costs'                => $costs,
            'edit_item'            => $edit_item,
            'edit_id'              => $edit_id,
            'brunata_entry_rows'    => ($selected_property_id > 0 && $selected_year > 0) ? Vermieter_Costs::get_brunata_entry_rows($selected_property_id, $selected_year, $property_categories) : [],
        ]);
    }

    public static function property_distribution_keys_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';
        $distribution_values_map = [];
        $apartments_by_property_distribution_key = [];
        $preselected_property_id = isset($_GET['property_id']) ? (int) $_GET['property_id'] : 0;
        $edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
        $edit_item = $edit_id ? Vermieter_Property_Distribution_Keys::get($edit_id) : null;

        if (!$edit_item && $preselected_property_id > 0) {
            $edit_item = (object) [
                'id' => 0,
                'property_id' => $preselected_property_id,
                'distribution_key_definition_id' => 0,
                'applies_to_type_key' => 'alle',
            ];
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_property_distribution_key') {
            check_admin_referer('vm_save_property_distribution_key');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);

            if ($record_id > 0) {
                $result = Vermieter_Property_Distribution_Keys::update($record_id, [
                    'property_id'                    => (int) ($_POST['vm_property_id'] ?? 0),
                    'distribution_key_definition_id' => (int) ($_POST['vm_distribution_key_definition_id'] ?? 0),
                    'applies_to_type_key'            => sanitize_text_field(wp_unslash($_POST['vm_applies_to_type_key'] ?? 'alle')),
                ]);

                $message = $result ? 'Zuordnung aktualisiert.' : 'Zuordnung konnte nicht aktualisiert werden.';
                $edit_item = $record_id ? Vermieter_Property_Distribution_Keys::get($record_id) : null;
            } else {
                $id = Vermieter_Property_Distribution_Keys::add([
                    'property_id'                    => (int) ($_POST['vm_property_id'] ?? 0),
                    'distribution_key_definition_id' => (int) ($_POST['vm_distribution_key_definition_id'] ?? 0),
                    'applies_to_type_key'            => sanitize_text_field(wp_unslash($_POST['vm_applies_to_type_key'] ?? 'alle')),
                ]);

                $message = $id ? 'Schlüssel dem Objekt zugeordnet.' : 'Zuordnung konnte nicht gespeichert werden.';
            }
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'update_property_distribution_key_inline') {
            check_admin_referer('vm_update_property_distribution_key_inline');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);
            $result = Vermieter_Property_Distribution_Keys::update($record_id, [
                'property_id'                    => (int) ($_POST['vm_property_id'] ?? 0),
                'distribution_key_definition_id' => (int) ($_POST['vm_distribution_key_definition_id'] ?? 0),
                'applies_to_type_key'            => sanitize_text_field(wp_unslash($_POST['vm_applies_to_type_key'] ?? 'alle')),
            ]);

            $message = $result
                ? 'Zuordnung aktualisiert.'
                : 'Zuordnung konnte nicht aktualisiert werden.';

            $edit_item = null;
            $edit_id = 0;
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'delete_property_distribution_key') {
            check_admin_referer('vm_delete_property_distribution_key');

            $result = Vermieter_Property_Distribution_Keys::delete((int) ($_POST['vm_record_id'] ?? 0));
            $message = !empty($result['message']) ? $result['message'] : 'Zuordnung konnte nicht gelöscht werden.';

            $edit_item = null;
            $edit_id = 0;
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_inline_distribution_values') {
            check_admin_referer('vm_save_inline_distribution_values');

            $all_values = $_POST['vm_distribution_values'] ?? [];
            $saved_any = false;

            foreach ($all_values as $property_distribution_key_id => $values) {
                $property_distribution_key_id = (int) $property_distribution_key_id;

                if ($property_distribution_key_id > 0 && is_array($values)) {
                    Vermieter_Apartment_Distribution_Values::save_values($property_distribution_key_id, $values);
                    $saved_any = true;
                }
            }

            $message = $saved_any ? 'Verteilwerte gespeichert.' : 'Keine Verteilwerte gespeichert.';
        }

        $assigned_keys = Vermieter_Property_Distribution_Keys::get_all_by_user();

        foreach ($assigned_keys as $key) {
            $property_id = (int) $key->property_id;
            $property_distribution_key_id = (int) $key->id;

            $all_apartments = Vermieter_Apartments::get_by_property($property_id);

            $apartments_by_property_distribution_key[$property_distribution_key_id] = array_values(array_filter(
                $all_apartments,
                function ($apartment) use ($key) {
                    return $key->applies_to_type_key === 'alle'
                        || $apartment->type_key === $key->applies_to_type_key;
                }
            ));

            $distribution_values_map[$property_distribution_key_id] =
                Vermieter_Apartment_Distribution_Values::get_values_by_property_distribution_key($property_distribution_key_id);
        }

        return vm_render_template('form-property-distribution-keys.php', [
            'message'                                 => $message,
            'properties'                              => Vermieter_Properties::get_all(),
            'definitions'                             => Vermieter_Distribution_Key_Definitions::get_all_by_user(),
            'assigned_keys'                           => $assigned_keys,
            'edit_item'                               => $edit_item,
            'apartments_by_property_distribution_key' => $apartments_by_property_distribution_key,
            'distribution_values_map'                 => $distribution_values_map,
        ]);
    }

    public static function distribution_key_definitions_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_distribution_key_definition') {
            check_admin_referer('vm_save_distribution_key_definition');

            $id = Vermieter_Distribution_Key_Definitions::add([
                'label'       => sanitize_text_field(wp_unslash($_POST['vm_label'] ?? '')),
                'unit_code'   => sanitize_text_field(wp_unslash($_POST['vm_unit_code'] ?? '')),
                'total_value' => vm_post_decimal('vm_total_value'),
            ]);

            $message = $id ? 'Schlüsseldefinition gespeichert.' : 'Schlüsseldefinition konnte nicht gespeichert werden.';
        }

        return vm_render_template('form-distribution-key-definitions.php', [
            'message'     => $message,
            'definitions' => Vermieter_Distribution_Key_Definitions::get_all_by_user(),
        ]);
    }

    public static function cost_categories_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_cost_category') {
            check_admin_referer('vm_save_cost_category');

            $property_distribution_key_id = (int) ($_POST['vm_property_distribution_key_id'] ?? 0);
            $allocation_type = sanitize_text_field(wp_unslash($_POST['vm_allocation_type'] ?? 'wohnflaeche'));
            $property_id = 0;

            if ($property_distribution_key_id > 0) {
                $selected_key = Vermieter_Property_Distribution_Keys::get($property_distribution_key_id);
                if ($selected_key) {
                    $property_id = (int) $selected_key->property_id;
                }
            }

            $result = Vermieter_Property_Cost_Categories::add([
                'property_id'                  => $property_id,
                'cost_category_definition_id'  => (int) ($_POST['vm_cost_category_definition_id'] ?? 0),
                'allocation_type'              => $allocation_type,
                'property_distribution_key_id' => $property_distribution_key_id,
                'is_recurring'                 => !empty($_POST['vm_is_recurring']) ? 1 : 0,
            ]);

            $message = is_array($result) && !empty($result['message'])
                ? $result['message']
                : 'Objekt-Kategorie konnte nicht gespeichert werden.';
        }

        $properties = Vermieter_Properties::get_all();
        $assigned_keys = Vermieter_Property_Distribution_Keys::get_all_by_user();

        $assigned_keys_by_property = [];
        foreach ($assigned_keys as $key) {
            $assigned_keys_by_property[(int) $key->property_id][] = $key;
        }

        error_log('Vermieter Property Cost Categories: ungültiger Objekt-Schlüssel. property_distribution_key_id=' . $property_distribution_key_id);
        
        return vm_render_template('form-property-cost-categories.php', [
            'message'             => $message,
            'definitions'         => Vermieter_Cost_Category_Definitions::get_all_by_user(),
            'property_categories' => Vermieter_Property_Cost_Categories::get_all_by_user(),
            'assigned_keys'       => Vermieter_Property_Distribution_Keys::get_all_by_user(),
            'apportionment_types' => Vermieter_Apportionment_Types::get_options(),
        ]);
    }

    public static function vermieter_list_shortcode() { 
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';
        return 0;
    }

    public static function properties_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_property') {
            check_admin_referer('vm_save_property');

            $id = Vermieter_Properties::add([
                'name'         => sanitize_text_field(wp_unslash($_POST['vm_property_name'] ?? '')),
                'street'       => sanitize_text_field(wp_unslash($_POST['vm_property_street'] ?? '')),
                'house_number' => sanitize_text_field(wp_unslash($_POST['vm_property_house_number'] ?? '')),
                'zip_code'     => sanitize_text_field(wp_unslash($_POST['vm_property_zip_code'] ?? '')),
                'city'         => sanitize_text_field(wp_unslash($_POST['vm_property_city'] ?? '')),
            ]);

            $message = $id ? 'Objekt gespeichert.' : 'Objekt konnte nicht gespeichert werden.';
        }

        return vm_render_template('form-properties.php', [
            'message'    => $message,
            'properties' => Vermieter_Properties::get_all(),
        ]);
    }

    public static function apartments_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';
        $edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
        $edit_item = $edit_id ? Vermieter_Apartments::get($edit_id) : null;

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_apartment') {
            check_admin_referer('vm_save_apartment');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);

            $data = [
                'property_id'      => (int) ($_POST['vm_property_id'] ?? 0),
                'name'             => sanitize_text_field(wp_unslash($_POST['vm_apartment_name'] ?? '')),
                'type_key'         => sanitize_text_field(wp_unslash($_POST['vm_type_key'] ?? 'wohnung')),
                'wohnflaeche'      => vm_post_decimal('vm_wohnflaeche'),
                'personen'         => (int) ($_POST['vm_personen'] ?? 0),
                'acquisition_date' => sanitize_text_field(wp_unslash($_POST['vm_acquisition_date'] ?? '')),
                'disposal_date'    => sanitize_text_field(wp_unslash($_POST['vm_disposal_date'] ?? '')),
            ];

            if ($record_id > 0) {
                $result = Vermieter_Apartments::update($record_id, $data);

                $message = !empty($result['message'])
                    ? $result['message']
                    : 'Wohnung konnte nicht aktualisiert werden.';

                $edit_item = $record_id ? Vermieter_Apartments::get($record_id) : null;
            } else {
                $id = Vermieter_Apartments::add($data);
                $message = $id ? 'Wohnung gespeichert.' : 'Wohnung konnte nicht gespeichert werden.';
            }
        }

        return vm_render_template('form-apartments.php', [
            'message'    => $message,
            'properties' => Vermieter_Properties::get_all(),
            'apartments' => Vermieter_Apartments::get_all_by_user(),
            'edit_item'  => $edit_item,
        ]);
    }

    public static function billing_costs_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';
        $properties = Vermieter_Properties::get_all();

        $selected_property_id = isset($_REQUEST['vm_property_id']) ? (int) $_REQUEST['vm_property_id'] : 0;
        $selected_year = isset($_REQUEST['vm_year']) ? (int) $_REQUEST['vm_year'] : 0;
        $edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
        $edit_item = $edit_id ? Vermieter_Costs::get($edit_id) : null;

        if ($edit_item) {
            $selected_property_id = (int) $edit_item->property_id;
            $selected_year = (int) $edit_item->period_year;
        }

        if ($selected_year <= 0) {
            $selected_year = (int) current_time('Y');
        }

        $period = [
            'year'  => $selected_year,
            'start' => $selected_year . '-01-01',
            'end'   => $selected_year . '-12-31',
        ];

        $property_categories = $selected_property_id > 0
            ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id)
            : [];

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_cost') {
            check_admin_referer('vm_save_cost');

            $selected_property_id = (int) ($_POST['vm_property_id'] ?? 0);
            $selected_year = (int) ($_POST['vm_period_year'] ?? 0);

            if ($selected_year <= 0) {
                $selected_year = (int) current_time('Y');
            }

            $period = [
                'year'  => $selected_year,
                'start' => sanitize_text_field(wp_unslash($_POST['vm_period_start'] ?? ($selected_year . '-01-01'))),
                'end'   => sanitize_text_field(wp_unslash($_POST['vm_period_end'] ?? ($selected_year . '-12-31'))),
            ];

            $property_categories = $selected_property_id > 0
                ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id)
                : [];

            $id = Vermieter_Costs::add([
                'property_id'               => $selected_property_id,
                'property_cost_category_id' => (int) ($_POST['vm_property_cost_category_id'] ?? 0),
                'name'                      => sanitize_text_field(wp_unslash($_POST['vm_name'] ?? '')),
                'betrag'                    => vm_post_decimal('vm_betrag'),
                'invoice_date'              => sanitize_text_field(wp_unslash($_POST['vm_invoice_date'] ?? '')),
                'period_start'              => $period['start'],
                'period_end'                => $period['end'],
                'period_year'               => $selected_year,
            ]);

            $message = $id ? 'Kosten gespeichert.' : 'Kosten konnten nicht gespeichert werden.';
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'update_cost_single') {
            check_admin_referer('vm_update_cost_single');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);
            $selected_property_id = (int) ($_POST['vm_property_id'] ?? 0);
            $selected_year = (int) ($_POST['vm_period_year'] ?? 0);

            if ($selected_year <= 0) {
                $selected_year = (int) current_time('Y');
            }

            $period = [
                'year'  => $selected_year,
                'start' => sanitize_text_field(wp_unslash($_POST['vm_period_start'] ?? ($selected_year . '-01-01'))),
                'end'   => sanitize_text_field(wp_unslash($_POST['vm_period_end'] ?? ($selected_year . '-12-31'))),
            ];

            $property_categories = $selected_property_id > 0
                ? Vermieter_Property_Cost_Categories::get_by_property($selected_property_id)
                : [];

            $result = Vermieter_Costs::update($record_id, [
                'property_id'               => $selected_property_id,
                'property_cost_category_id' => (int) ($_POST['vm_property_cost_category_id'] ?? 0),
                'name'                      => sanitize_text_field(wp_unslash($_POST['vm_name'] ?? '')),
                'betrag'                    => vm_post_decimal('vm_betrag'),
                'invoice_date'              => sanitize_text_field(wp_unslash($_POST['vm_invoice_date'] ?? '')),
                'period_start'              => $period['start'],
                'period_end'                => $period['end'],
                'period_year'               => $selected_year,
            ]);

            $message = $result ? 'Kosten aktualisiert.' : 'Kosten konnten nicht aktualisiert werden.';
            $edit_item = null;
            $edit_id = 0;
        }

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'delete_cost_single') {
            check_admin_referer('vm_delete_cost_single');

            $record_id = (int) ($_POST['vm_record_id'] ?? 0);
            $selected_property_id = (int) ($_POST['vm_property_id'] ?? 0);
            $selected_year = (int) ($_POST['vm_year'] ?? 0);

            if ($selected_year <= 0) {
                $selected_year = (int) current_time('Y');
            }

            $result = Vermieter_Costs::delete($record_id);
            $message = $result ? 'Kosten gelöscht.' : 'Kosten konnten nicht gelöscht werden.';
            $edit_item = null;
            $edit_id = 0;
        }

        $costs = ($selected_property_id > 0 && $selected_year > 0)
            ? Vermieter_Costs::get_by_property_and_year($selected_property_id, $selected_year)
            : Vermieter_Costs::get_all_by_user();

        return vm_render_template('form-costs.php', [
            'message'              => $message,
            'period'               => $period,
            'properties'           => $properties,
            'selected_property_id' => $selected_property_id,
            'selected_year'        => $selected_year,
            'property_categories'  => $property_categories,
            'costs'                => $costs,
            'edit_item'            => $edit_item,
            'edit_id'              => $edit_id,
            'brunata_entry_rows'    => ($selected_property_id > 0 && $selected_year > 0) ? Vermieter_Costs::get_brunata_entry_rows($selected_property_id, $selected_year, $property_categories) : [],
        ]);
    }

    public static function billing_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $properties = Vermieter_Properties::get_all();
        $selected_property_id = isset($_GET['vm_property_id']) ? (int) $_GET['vm_property_id'] : (!empty($properties) ? (int) $properties[0]->id : 0);
        $selected_year = isset($_GET['vm_year']) ? (int) $_GET['vm_year'] : (int) current_time('Y');

        return vm_render_template('billing-table.php', [
            'properties'           => $properties,
            'selected_property_id' => $selected_property_id,
            'selected_year'        => $selected_year,
            'billing'              => $selected_property_id ? Vermieter_Billing::generate($selected_property_id, $selected_year) : [],
        ]);
    }

    public static function distribution_keys_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_distribution_key') {
            check_admin_referer('vm_save_distribution_key');

            $id = Vermieter_Distribution_Keys::add([
                'property_id' => (int) ($_POST['vm_property_id'] ?? 0),
                'label'       => sanitize_text_field(wp_unslash($_POST['vm_label'] ?? '')),
                'unit_code'   => sanitize_text_field(wp_unslash($_POST['vm_unit_code'] ?? '')),
                'total_value' => vm_post_decimal('vm_total_value'),
            ]);

            $message = $id ? 'Verteilerschlüssel gespeichert.' : 'Verteilerschlüssel konnte nicht gespeichert werden.';
        }

        return vm_render_template('form-distribution-keys.php', [
            'message'           => $message,
            'properties'        => Vermieter_Properties::get_all(),
            'distribution_keys' => Vermieter_Distribution_Keys::get_all_by_user(),
        ]);
    }

    public static function distribution_values_shortcode() {
        if (!is_user_logged_in()) {
            return 'Bitte einloggen.';
        }

        $properties = Vermieter_Properties::get_all();
        $selected_property_id = isset($_GET['vm_property_id']) ? (int) $_GET['vm_property_id'] : (!empty($properties) ? (int) $properties[0]->id : 0);
        $distribution_keys = $selected_property_id ? Vermieter_Distribution_Keys::get_by_property($selected_property_id) : [];
        $selected_distribution_key_id = isset($_GET['vm_distribution_key_id']) ? (int) $_GET['vm_distribution_key_id'] : (!empty($distribution_keys) ? (int) $distribution_keys[0]->id : 0);

        $message = '';

        if (isset($_POST['vm_action']) && $_POST['vm_action'] === 'save_distribution_values') {
            check_admin_referer('vm_save_distribution_values');

            $selected_distribution_key_id = (int) ($_POST['vm_distribution_key_id'] ?? 0);
            $result = Vermieter_Apartment_Distribution_Values::save_values(
                $selected_distribution_key_id,
                $_POST['vm_distribution_values'] ?? []
            );

            $message = $result ? 'Verteilwerte gespeichert.' : 'Verteilwerte konnten nicht gespeichert werden.';
        }

        $distribution_key = $selected_distribution_key_id ? Vermieter_Distribution_Keys::get($selected_distribution_key_id) : null;
        $apartments = $selected_property_id ? Vermieter_Apartments::get_by_property($selected_property_id) : [];
        $values = $selected_distribution_key_id ? Vermieter_Apartment_Distribution_Values::get_values_by_distribution_key($selected_distribution_key_id) : [];
        $sum_values = $selected_distribution_key_id ? Vermieter_Apartment_Distribution_Values::get_sum_by_distribution_key($selected_distribution_key_id) : 0;

        return vm_render_template('form-apartment-distribution-values.php', [
            'message'                      => $message,
            'properties'                   => $properties,
            'selected_property_id'         => $selected_property_id,
            'distribution_keys'            => $distribution_keys,
            'selected_distribution_key_id' => $selected_distribution_key_id,
            'distribution_key'             => $distribution_key,
            'apartments'                   => $apartments,
            'values'                       => $values,
            'sum_values'                   => $sum_values,
        ]);
    }
}
