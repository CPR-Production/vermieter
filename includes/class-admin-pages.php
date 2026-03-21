<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Admin_Pages {

    public static function register_menu() {
        add_menu_page(
            'Vermieter Seiten',
            'Vermieter Seiten',
            'manage_options',
            'vermieter-admin-pages',
            [__CLASS__, 'render_admin_page'],
            'dashicons-admin-page',
            58
        );
    }

    public static function get_page_definitions() {
        return [
            [
                'key'       => 'property_dashboard',
                'title'     => 'Objekt-Dashboard',
                'slug'      => 'vermieter-objekt-dashboard',
                'shortcode' => '[vermieter_property_dashboard]',
                'method'    => 'Vermieter_Shortcodes::property_dashboard_shortcode',
                'note'      => 'Übersicht pro Objekt',
            ],
            [
                'key'       => 'properties',
                'title'     => 'Objekte',
                'slug'      => 'vermieter-objekte',
                'shortcode' => '[vermieter_properties]',
                'method'    => 'Vermieter_Shortcodes::properties_shortcode',
                'note'      => 'Objekte anlegen und anzeigen',
            ],
            [
                'key'       => 'apartments',
                'title'     => 'Wohnungen',
                'slug'      => 'vermieter-wohnungen',
                'shortcode' => '[vermieter_apartments]',
                'method'    => 'Vermieter_Shortcodes::apartments_shortcode',
                'note'      => 'Wohnungen / Einheiten verwalten',
            ],
            [
                'key'       => 'tenants',
                'title'     => 'Mieter',
                'slug'      => 'vermieter-mieter',
                'shortcode' => '[vermieter_tenants]',
                'method'    => 'Vermieter_Shortcodes::tenants_shortcode',
                'note'      => 'Mieter anlegen',
            ],
            [
                'key'       => 'apartment_tenants',
                'title'     => 'Mieter zu Wohnungen',
                'slug'      => 'vermieter-wohnung-mieter',
                'shortcode' => '[vermieter_apartment_tenants]',
                'method'    => 'Vermieter_Shortcodes::apartment_tenants_shortcode',
                'note'      => 'Mieter einer Wohnung zuordnen',
            ],
            [
                'key'       => 'distribution_key_definitions',
                'title'     => 'Schlüsseldefinitionen',
                'slug'      => 'vermieter-schluesseldefinitionen',
                'shortcode' => '[vermieter_distribution_key_definitions]',
                'method'    => 'Vermieter_Shortcodes::distribution_key_definitions_shortcode',
                'note'      => 'Definitionen für Verteilerschlüssel',
            ],
            [
                'key'       => 'property_distribution_keys',
                'title'     => 'Objekt-Schlüssel',
                'slug'      => 'vermieter-objekt-schluessel',
                'shortcode' => '[vermieter_property_distribution_keys]',
                'method'    => 'Vermieter_Shortcodes::property_distribution_keys_shortcode',
                'note'      => 'Schlüssel einem Objekt zuordnen',
            ],
            [
                'key'       => 'cost_category_definitions',
                'title'     => 'Kategoriedefinitionen',
                'slug'      => 'vermieter-kategoriedefinitionen',
                'shortcode' => '[vermieter_cost_category_definitions]',
                'method'    => 'Vermieter_Shortcodes::cost_category_definitions_shortcode',
                'note'      => 'Kostenkategorien definieren',
            ],
            [
                'key'       => 'property_cost_categories',
                'title'     => 'Objekt-Kostenkategorien',
                'slug'      => 'vermieter-objekt-kostenkategorien',
                'shortcode' => '[vermieter_property_cost_categories]',
                'method'    => 'Vermieter_Shortcodes::property_cost_categories_shortcode',
                'note'      => 'Kategorien einem Objekt zuordnen',
            ],
            [
                'key'       => 'costs_table',
                'title'     => 'Kosten erfassen',
                'slug'      => 'vermieter-kosten-erfassen',
                'shortcode' => '[vermieter_costs_table]',
                'method'    => 'Vermieter_Shortcodes::costs_table_shortcode',
                'note'      => 'Rechnungen / Positionen erfassen',
            ],
            [
                'key'       => 'tenancy_rent_terms',
                'title'     => 'Kaltmieten',
                'slug'      => 'vermieter-kaltmieten',
                'shortcode' => '[vermieter_tenancy_rent_terms]',
                'method'    => 'Vermieter_Shortcodes::tenancy_rent_terms_shortcode',
                'note'      => 'Kaltmiete je Mietverhältnis',
            ],
            [
                'key'       => 'tenancy_advance_terms',
                'title'     => 'Vorauszahlungen',
                'slug'      => 'vermieter-vorauszahlungen',
                'shortcode' => '[vermieter_tenancy_advance_terms]',
                'method'    => 'Vermieter_Shortcodes::tenancy_advance_terms_shortcode',
                'note'      => 'NK-/HK-Vorauszahlungen',
            ],
            [
                'key'       => 'tenant_payments',
                'title'     => 'Zahlungen',
                'slug'      => 'vermieter-zahlungen',
                'shortcode' => '[vermieter_tenant_payments]',
                'method'    => 'Vermieter_Shortcodes::tenant_payments_shortcode',
                'note'      => 'Zahlungen verwalten',
            ],
            [
                'key'       => 'mietkonto',
                'title'     => 'Mietkonto',
                'slug'      => 'vermieter-mietkonto',
                'shortcode' => '[vermieter_mietkonto]',
                'method'    => 'Vermieter_Shortcodes::mietkonto_shortcode',
                'note'      => 'Mietkonto anzeigen',
            ],
            [
                'key'       => 'nebenkostenabrechnung',
                'title'     => 'Nebenkostenabrechnung',
                'slug'      => 'vermieter-nebenkostenabrechnung',
                'shortcode' => '[vermieter_nebenkostenabrechnung]',
                'method'    => 'Vermieter_Shortcodes::nebenkostenabrechnung_shortcode',
                'note'      => 'Abrechnung je Objekt und Jahr',
            ],
            [
                'key'       => 'legacy_form',
                'title'     => 'Kostenformular (alt)',
                'slug'      => 'vermieter-kostenformular-alt',
                'shortcode' => '[vermieter_form]',
                'method'    => 'Vermieter_Shortcodes::billing_costs_shortcode',
                'note'      => 'Legacy-Shortcode',
            ],
            [
                'key'       => 'legacy_rechnungen',
                'title'     => 'Rechnungen (alt)',
                'slug'      => 'vermieter-rechnungen-alt',
                'shortcode' => '[vermieter_rechnungen]',
                'method'    => 'Vermieter_Shortcodes::vermieter_list_shortcode',
                'note'      => 'Legacy-Shortcode',
            ],
            [
                'key'       => 'legacy_abrechnung',
                'title'     => 'Abrechnung (alt)',
                'slug'      => 'vermieter-abrechnung-alt',
                'shortcode' => '[vermieter_abrechnung]',
                'method'    => 'Vermieter_Shortcodes::billing_shortcode',
                'note'      => 'Legacy-Shortcode',
            ],
        ];
    }

    public static function find_page_by_slug($slug) {
        return get_page_by_path($slug, OBJECT, 'page');
    }

    public static function create_page($definition) {
        $existing = self::find_page_by_slug($definition['slug']);

        if ($existing) {
            return (int) $existing->ID;
        }

        return wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $definition['title'],
            'post_name'    => $definition['slug'],
            'post_content' => $definition['shortcode'],
        ]);
    }

    public static function extract_shortcode_tag($shortcode) {
        $shortcode = trim((string) $shortcode);
        $shortcode = trim($shortcode, '[]');

        $parts = preg_split('/\s+/', $shortcode);
        return !empty($parts[0]) ? $parts[0] : '';
    }

    public static function get_pages_using_shortcode($shortcode_tag) {
        if ($shortcode_tag === '') {
            return [];
        }

        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => ['publish', 'draft', 'private', 'pending'],
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (empty($pages)) {
            return [];
        }

        $matches = [];

        foreach ($pages as $page) {
            if (!isset($page->post_content) || $page->post_content === '') {
                continue;
            }

            if (has_shortcode($page->post_content, $shortcode_tag)) {
                $matches[] = $page;
            }
        }

        return $matches;
    }

    public static function get_usage_label($definition, $used_pages) {
        $expected_page = self::find_page_by_slug($definition['slug']);
        $expected_page_id = $expected_page ? (int) $expected_page->ID : 0;
        $count = count($used_pages);

        if ($count === 0) {
            return 'Nicht verwendet';
        }

        if ($count === 1 && $expected_page_id > 0 && (int) $used_pages[0]->ID === $expected_page_id) {
            return 'OK';
        }

        if ($count === 1 && $expected_page_id === 0) {
            return 'Verwendet, aber Zielseite fehlt';
        }

        if ($count === 1) {
            return 'Auf anderer Seite verwendet';
        }

        return 'Doppelt / mehrfach verwendet';
    }

    public static function handle_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['vm_admin_pages_action'])) {
            return;
        }

        check_admin_referer('vm_admin_pages_action', 'vm_admin_pages_nonce');

        $action = sanitize_text_field(wp_unslash($_POST['vm_admin_pages_action']));
        $definitions = self::get_page_definitions();

        if ($action === 'create_all_pages') {
            $processed = 0;

            foreach ($definitions as $definition) {
                $result = self::create_page($definition);

                if ($result && !is_wp_error($result)) {
                    $processed++;
                }
            }

            add_settings_error(
                'vermieter_admin_pages',
                'pages_created',
                $processed . ' Seite(n) erstellt oder bereits vorhanden.',
                'updated'
            );
        }

        if ($action === 'create_single_page') {
            $page_key = sanitize_text_field(wp_unslash($_POST['vm_page_key'] ?? ''));

            foreach ($definitions as $definition) {
                if ($definition['key'] !== $page_key) {
                    continue;
                }

                $result = self::create_page($definition);

                if ($result && !is_wp_error($result)) {
                    add_settings_error(
                        'vermieter_admin_pages',
                        'page_created_' . $page_key,
                        'Seite "' . $definition['title'] . '" wurde erstellt oder war bereits vorhanden.',
                        'updated'
                    );
                } else {
                    add_settings_error(
                        'vermieter_admin_pages',
                        'page_error_' . $page_key,
                        'Seite "' . $definition['title'] . '" konnte nicht erstellt werden.',
                        'error'
                    );
                }

                break;
            }
        }
    }

    public static function handle_structure_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['vm_structure_action'])) {
            return;
        }

        check_admin_referer('vm_structure_action', 'vm_structure_nonce');

        $action = sanitize_text_field(wp_unslash($_POST['vm_structure_action']));

        if ($action === 'apply_structure') {
            $variant = sanitize_text_field(wp_unslash($_POST['vm_structure_variant'] ?? 'workflow'));
            $rebuild_menu = !empty($_POST['vm_rebuild_menu']);

            $result = Vermieter_Site_Structure::apply_structure($variant, $rebuild_menu);

            add_settings_error(
                'vermieter_admin_pages',
                'structure_result',
                $result['message'],
                !empty($result['success']) ? 'updated' : 'error'
            );
        }
    }

    public static function render_used_pages($used_pages) {
        if (empty($used_pages)) {
            echo '—';
            return;
        }

        foreach ($used_pages as $index => $page) {
            if ($index > 0) {
                echo '<br>';
            }

            echo '<a href="' . esc_url(get_edit_post_link($page->ID)) . '">';
            echo esc_html($page->post_title ?: '(ohne Titel)');
            echo '</a>';
            echo ' <span style="color:#666;">(' . esc_html($page->post_name) . ')</span>';
        }
    }

    public static function render_admin_page() {
        self::handle_actions();
        self::handle_structure_actions();

        $definitions = self::get_page_definitions();
        $active_variant = Vermieter_Site_Structure::get_active_variant();
        $variants = Vermieter_Site_Structure::get_structure_variants();
        $overview = Vermieter_Site_Structure::get_structure_overview();
        ?>
        <div class="wrap">
            <h1>Vermieter Seiten</h1>
            <p>
                Hier kannst du die benötigten WordPress-Seiten automatisch erstellen,
                ihre Verwendung prüfen und die Navigationsstruktur steuern.
            </p>

            <?php settings_errors('vermieter_admin_pages'); ?>

            <h2>Navigationsstruktur</h2>
            <p>
                Hier kannst du zwischen geführtem Workflow und logisch gruppierter Struktur umschalten.
                Die Inhaltsseiten bleiben gleich, nur die Hierarchie wird geändert.
            </p>

            <form method="post" style="margin:20px 0;">
                <?php wp_nonce_field('vm_structure_action', 'vm_structure_nonce'); ?>
                <input type="hidden" name="vm_structure_action" value="apply_structure">

                <select name="vm_structure_variant">
                    <?php foreach ($variants as $variant_key => $variant) : ?>
                        <option value="<?php echo esc_attr($variant_key); ?>" <?php selected($active_variant, $variant_key); ?>>
                            <?php echo esc_html($variant['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label style="margin-left:16px;">
                    <input type="checkbox" name="vm_rebuild_menu" value="1" checked>
                    Menü neu aufbauen
                </label>

                <p style="margin:8px 0 0; color:#666;">
                    Wenn die Checkbox aktiv ist, wird das Vermieter-Menü neu erstellt bzw. aktualisiert.
                    Wenn sie deaktiviert ist, wird nur die Seitenstruktur geändert.
                </p>

                <?php submit_button('Struktur anwenden', 'secondary', 'submit', false); ?>
            </form>

            <p><strong>Aktiv:</strong> <?php echo esc_html($overview['active_label']); ?></p>

            <table class="widefat striped" style="margin-bottom:30px;">
                <thead>
                    <tr>
                        <th>Gruppe</th>
                        <th>Gruppen-Seite</th>
                        <th>Unterseiten</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overview['groups'] as $group) : ?>
                        <tr>
                            <td><?php echo esc_html($group['title']); ?></td>
                            <td>
                                <?php if (!empty($group['page'])) : ?>
                                    <a href="<?php echo esc_url(get_edit_post_link($group['page']->ID)); ?>">
                                        <?php echo esc_html($group['page']->post_title); ?>
                                    </a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php foreach ($group['children'] as $index => $child) : ?>
                                    <?php if ($index > 0) : ?><br><?php endif; ?>
                                    <?php if (!empty($child['page'])) : ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($child['page']->ID)); ?>">
                                            <?php echo esc_html($child['title']); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html($child['title']); ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Seiten und Shortcodes</h2>

            <form method="post" style="margin:20px 0;">
                <?php wp_nonce_field('vm_admin_pages_action', 'vm_admin_pages_nonce'); ?>
                <input type="hidden" name="vm_admin_pages_action" value="create_all_pages">
                <?php submit_button('Alle Seiten erstellen', 'primary', 'submit', false); ?>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Slug</th>
                        <th>Shortcode</th>
                        <th>Methode / Hook</th>
                        <th>Hinweis</th>
                        <th>Status</th>
                        <th>Verwendung</th>
                        <th>Gefundene Seiten</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($definitions as $definition) : ?>
                        <?php
                        $page = self::find_page_by_slug($definition['slug']);
                        $shortcode_tag = self::extract_shortcode_tag($definition['shortcode']);
                        $used_pages = self::get_pages_using_shortcode($shortcode_tag);
                        $usage_label = self::get_usage_label($definition, $used_pages);
                        ?>
                        <tr>
                            <td><?php echo esc_html($definition['title']); ?></td>
                            <td><code><?php echo esc_html($definition['slug']); ?></code></td>
                            <td><code><?php echo esc_html($definition['shortcode']); ?></code></td>
                            <td><code><?php echo esc_html($definition['method']); ?></code></td>
                            <td><?php echo esc_html($definition['note']); ?></td>
                            <td>
                                <?php if ($page) : ?>
                                    <strong>Vorhanden</strong><br>
                                    <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>">Bearbeiten</a>
                                    |
                                    <a href="<?php echo esc_url(get_permalink($page->ID)); ?>" target="_blank" rel="noopener noreferrer">Ansehen</a>
                                <?php else : ?>
                                    <strong>Nicht vorhanden</strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $color = '#666';

                                if ($usage_label === 'OK') {
                                    $color = 'green';
                                } elseif ($usage_label === 'Doppelt / mehrfach verwendet') {
                                    $color = 'red';
                                } elseif (
                                    $usage_label === 'Auf anderer Seite verwendet' ||
                                    $usage_label === 'Verwendet, aber Zielseite fehlt'
                                ) {
                                    $color = '#d97706';
                                }

                                echo '<strong style="color:' . esc_attr($color) . ';">' . esc_html($usage_label) . '</strong>';
                                ?>
                            </td>
                            <td>
                                <?php self::render_used_pages($used_pages); ?>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field('vm_admin_pages_action', 'vm_admin_pages_nonce'); ?>
                                    <input type="hidden" name="vm_admin_pages_action" value="create_single_page">
                                    <input type="hidden" name="vm_page_key" value="<?php echo esc_attr($definition['key']); ?>">
                                    <?php submit_button('Seite erstellen', 'secondary small', 'submit', false); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}