<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_PDF_Export
{
    public static function register()
    {
        add_action('wp_ajax_vm_render_statement_pdf_html', [__CLASS__, 'render_statement_pdf_html_ajax']);
    }

    public static function render_statement_pdf_html_ajax()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Bitte einloggen.'], 403);
        }

        check_ajax_referer('vm_pdf_export_nonce', 'nonce');

        $property_id = (int) ($_POST['property_id'] ?? 0);
        $year = (int) ($_POST['year'] ?? 0);
        $tenant_index_raw = isset($_POST['tenant_index']) ? sanitize_text_field(wp_unslash($_POST['tenant_index'])) : 'all';
        $tenant_index = ($tenant_index_raw === 'all') ? 'all' : (int) $tenant_index_raw;

        if ($property_id <= 0 || $year <= 0) {
            wp_send_json_error(['message' => 'Objekt oder Jahr fehlt.'], 400);
        }

        $properties = Vermieter_Properties::get_all();
        $statement = Vermieter_Nebenkosten_Billing::build_property_statement($property_id, $year);

        if (empty($statement) || empty($statement['property'])) {
            wp_send_json_error(['message' => 'Keine Abrechnungsdaten vorhanden.'], 404);
        }

        ob_start();
        echo '<!doctype html><html><head><meta charset="utf-8"><title>' . esc_html__('Nebenkostenabrechnung', 'vermieter') . '</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link rel="stylesheet" href="' . esc_url(VERMIETER_URL . 'assets/css/vermieter.css?ver=' . VERMIETER_VERSION) . '">';
        echo '<link rel="stylesheet" href="' . esc_url(VERMIETER_URL . 'assets/css/vm-pdf.css?ver=' . VERMIETER_VERSION) . '">';
        echo '<style>
            body { margin: 0; padding: 24px; background: #fff; color: #111; font-family: Arial, sans-serif; }
            .vm-wrap { max-width: none; }
            .vm-export-controls, .vm-screen-only, form { display: none !important; }
            .vm-tenant-statement { page-break-inside: avoid; break-inside: avoid; }
            .vm-pdf-page-break { page-break-after: always; break-after: page; }
            @page { size: A4 portrait; margin: 14mm; }
            @media print {
                body { padding: 0; }
                table { page-break-inside: auto; }
                tr { page-break-inside: avoid; page-break-after: auto; }
                .vm-tenant-statement { page-break-inside: avoid; break-inside: avoid; }
                .vm-pdf-page-break { page-break-after: always; break-after: page; }
            }
        </style>';
        echo '</head><body class="vm-pdf-body">';

        echo vm_render_template('nebenkostenabrechnung.php', [
            'properties'           => $properties,
            'selected_property_id' => $property_id,
            'selected_year'        => $year,
            'statement'            => $statement,
            'vm_pdf_mode'          => true,
            'vm_pdf_tenant_index'  => $tenant_index,
        ]);

        echo '<script>window.addEventListener("load",function(){setTimeout(function(){window.focus();window.print();},250);});</script>';
        echo '</body></html>';
        $html = ob_get_clean();

        $property_name = sanitize_title($statement['property']->name ?? 'objekt');
        $filename = 'nebenkostenabrechnung-' . $property_name . '-' . $year;
        if ($tenant_index !== 'all') {
            $filename .= '-mieter-' . ((int) $tenant_index + 1);
        }
        $filename .= '.pdf';

        wp_send_json_success([
            'html'     => $html,
            'filename' => $filename,
        ]);
    }
}
