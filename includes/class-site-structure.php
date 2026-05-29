<?php
if (!defined('ABSPATH')) {
    exit;
}

class Vermieter_Site_Structure {

    const OPTION_VARIANT = 'vermieter_navigation_variant';
    const OPTION_MENU_ID = 'vermieter_navigation_menu_id';
    const DEFAULT_MENU_NAME = 'Vermieter';

    public static function get_base_pages() {
        return [
            'dashboard' => [
                'title'     => 'Dashboard',
                'slug'      => 'vermieter-dashboard',
                'shortcode' => '[vermieter_property_dashboard]',
            ],
            'properties' => [
                'title'     => 'Objekte',
                'slug'      => 'vermieter-objekte',
                'shortcode' => '[vermieter_properties]',
            ],
            'apartments' => [
                'title'     => 'Wohnungen',
                'slug'      => 'vermieter-wohnungen',
                'shortcode' => '[vermieter_apartments]',
            ],
            'tenants' => [
                'title'     => 'Mieter',
                'slug'      => 'vermieter-mieter',
                'shortcode' => '[vermieter_tenants]',
            ],
            'apartment_tenants' => [
                'title'     => 'Mieter zu Wohnungen',
                'slug'      => 'vermieter-wohnung-mieter',
                'shortcode' => '[vermieter_apartment_tenants]',
            ],
            'tenancy_rent_terms' => [
                'title'     => 'Kaltmieten',
                'slug'      => 'vermieter-kaltmieten',
                'shortcode' => '[vermieter_tenancy_rent_terms]',
            ],
            'tenancy_advance_terms' => [
                'title'     => 'Vorauszahlungen',
                'slug'      => 'vermieter-vorauszahlungen',
                'shortcode' => '[vermieter_tenancy_advance_terms]',
            ],
            'tenant_payments' => [
                'title'     => 'Zahlungen',
                'slug'      => 'vermieter-zahlungen',
                'shortcode' => '[vermieter_tenant_payments]',
            ],
            'mietkonto' => [
                'title'     => 'Mietkonto',
                'slug'      => 'vermieter-mietkonto',
                'shortcode' => '[vermieter_mietkonto]',
            ],
            'distribution_key_definitions' => [
                'title'     => 'Schlüsseldefinitionen',
                'slug'      => 'vermieter-schluesseldefinitionen',
                'shortcode' => '[vermieter_distribution_key_definitions]',
            ],
            'property_distribution_keys' => [
                'title'     => 'Objekt-Schlüssel',
                'slug'      => 'vermieter-objekt-schluessel',
                'shortcode' => '[vermieter_property_distribution_keys]',
            ],
            'cost_category_definitions' => [
                'title'     => 'Kategoriedefinitionen',
                'slug'      => 'vermieter-kategoriedefinitionen',
                'shortcode' => '[vermieter_cost_category_definitions]',
            ],
            'property_cost_categories' => [
                'title'     => 'Objekt-Kostenkategorien',
                'slug'      => 'vermieter-objekt-kostenkategorien',
                'shortcode' => '[vermieter_property_cost_categories]',
            ],
            'costs_table' => [
                'title'     => 'Kosten erfassen',
                'slug'      => 'vermieter-kosten-erfassen',
                'shortcode' => '[vermieter_costs_table]',
            ],
            'nebenkostenabrechnung' => [
                'title'     => 'Nebenkostenabrechnung',
                'slug'      => 'vermieter-nebenkostenabrechnung',
                'shortcode' => '[vermieter_nebenkostenabrechnung]',
            ],
        ];
    }
    public static function get_structure_variants() {
        return [
            'workflow' => [
                'label' => 'Geführter Workflow',
                'groups' => [
                    'einrichten' => [
                        'title' => 'Einrichten',
                        'slug'  => 'vermieter-einrichten',
                        'children' => [
                            'dashboard',
                            'properties',
                            'apartments',
                            'tenants',
                            'apartment_tenants',
                            'distribution_key_definitions',
                            'property_distribution_keys',
                            'cost_category_definitions',
                            'property_cost_categories',
                        ],
                    ],
                    'verwalten' => [
                        'title' => 'Verwalten',
                        'slug'  => 'vermieter-verwalten',
                        'children' => [
                            'tenancy_rent_terms',
                            'tenancy_advance_terms',
                            'tenant_payments',
                            'mietkonto',
                        ],
                    ],
                    'abrechnen' => [
                        'title' => 'Abrechnen',
                        'slug'  => 'vermieter-abrechnen',
                        'children' => [
                            'costs_table',
                            'nebenkostenabrechnung',
                        ],
                    ],
                ],
            ],
            'grouped' => [
                'label' => 'Logisch gruppiert',
                'groups' => [
                    'stammdaten' => [
                        'title' => 'Stammdaten',
                        'slug'  => 'vermieter-stammdaten',
                        'children' => [
                            'dashboard',
                            'properties',
                            'apartments',
                            'tenants',
                            'apartment_tenants',
                        ],
                    ],
                    'mieten-zahlungen' => [
                        'title' => 'Mieten & Zahlungen',
                        'slug'  => 'vermieter-mieten-zahlungen',
                        'children' => [
                            'tenancy_rent_terms',
                            'tenancy_advance_terms',
                            'tenant_payments',
                            'mietkonto',
                        ],
                    ],
                    'nebenkosten' => [
                        'title' => 'Nebenkosten',
                        'slug'  => 'vermieter-nebenkosten',
                        'children' => [
                            'distribution_key_definitions',
                            'property_distribution_keys',
                            'cost_category_definitions',
                            'property_cost_categories',
                            'costs_table',
                            'nebenkostenabrechnung',
                        ],
                    ],
                ],
            ],
        ];
    }
    public static function get_active_variant() {
        $variant = get_option(self::OPTION_VARIANT, 'workflow');
        $variants = self::get_structure_variants();

        return isset($variants[$variant]) ? $variant : 'workflow';
    }
    public static function set_active_variant($variant) {
        $variants = self::get_structure_variants();

        if (!isset($variants[$variant])) {
            return false;
        }

        update_option(self::OPTION_VARIANT, $variant);
        return true;
    }
    public static function find_page_by_slug($slug) {
        return get_page_by_path($slug, OBJECT, 'page');
    }
    public static function create_or_get_page($title, $slug, $content = '', $parent_id = 0) {
        $existing = self::find_page_by_slug($slug);

        if ($existing) {
            return (int) $existing->ID;
        }

        $page_id = wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
            'post_parent'  => (int) $parent_id,
        ]);

        if (is_wp_error($page_id) || !$page_id) {
            return 0;
        }

        return (int) $page_id;
    }
    public static function ensure_base_pages() {
        $base_pages = self::get_base_pages();
        $page_ids = [];

        foreach ($base_pages as $key => $page) {
            $page_id = self::create_or_get_page(
                $page['title'],
                $page['slug'],
                $page['shortcode'],
                0
            );

            if ($page_id > 0) {
                $page_ids[$key] = $page_id;
            }
        }

        return $page_ids;
    }
    public static function ensure_group_pages($variant) {
        $variants = self::get_structure_variants();

        if (!isset($variants[$variant])) {
            return [];
        }

        $group_ids = [];

        foreach ($variants[$variant]['groups'] as $group_key => $group) {
            $group_id = self::create_or_get_page(
                $group['title'],
                $group['slug'],
                '',
                0
            );

            if ($group_id > 0) {
                $group_ids[$group_key] = $group_id;
            }
        }

        return $group_ids;
    }
    public static function clear_base_page_parents() {
        $base_pages = self::get_base_pages();

        foreach ($base_pages as $page) {
            $existing = self::find_page_by_slug($page['slug']);

            if ($existing) {
                wp_update_post([
                    'ID'          => (int) $existing->ID,
                    'post_parent' => 0,
                ]);
            }
        }
    }
    public static function get_or_create_menu() {
        $menu_id = (int) get_option(self::OPTION_MENU_ID, 0);

        if ($menu_id > 0 && wp_get_nav_menu_object($menu_id)) {
            return $menu_id;
        }

        $menu = wp_get_nav_menu_object(self::DEFAULT_MENU_NAME);

        if ($menu) {
            update_option(self::OPTION_MENU_ID, (int) $menu->term_id);
            return (int) $menu->term_id;
        }

        $menu_id = wp_create_nav_menu(self::DEFAULT_MENU_NAME);

        if (is_wp_error($menu_id) || !$menu_id) {
            return 0;
        }

        update_option(self::OPTION_MENU_ID, (int) $menu_id);

        return (int) $menu_id;
    }
    public static function clear_menu($menu_id) {
        $items = wp_get_nav_menu_items($menu_id);

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            wp_delete_post($item->ID, true);
        }
    }
    public static function add_menu_item($menu_id, $title, $page_id, $parent_item_id = 0) {
        return wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'     => $title,
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $page_id,
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
            'menu-item-parent-id' => (int) $parent_item_id,
        ]);
    }
    public static function build_menu($variant, $base_page_ids, $group_ids) {
        $variants = self::get_structure_variants();

        if (!isset($variants[$variant])) {
            return;
        }

        $menu_id = self::get_or_create_menu();

        if ($menu_id <= 0) {
            return;
        }

        self::clear_menu($menu_id);

        foreach ($variants[$variant]['groups'] as $group_key => $group) {
            $group_page_id = (int) ($group_ids[$group_key] ?? 0);

            if ($group_page_id <= 0) {
                continue;
            }

            $parent_item_id = self::add_menu_item(
                $menu_id,
                $group['title'],
                $group_page_id,
                0
            );

            foreach ($group['children'] as $child_key) {
                $child_page_id = (int) ($base_page_ids[$child_key] ?? 0);

                if ($child_page_id <= 0) {
                    continue;
                }

                self::add_menu_item(
                    $menu_id,
                    get_the_title($child_page_id),
                    $child_page_id,
                    $parent_item_id
                );
            }
        }
    }
    public static function apply_structure($variant, $rebuild_menu = true) {
        $variants = self::get_structure_variants();

        if (!isset($variants[$variant])) {
            return [
                'success' => false,
                'message' => 'Ungültige Strukturvariante.',
            ];
        }

        $base_page_ids = self::ensure_base_pages();
        $group_ids = self::ensure_group_pages($variant);

        self::clear_base_page_parents();

        foreach ($variants[$variant]['groups'] as $group_key => $group) {
            $parent_id = (int) ($group_ids[$group_key] ?? 0);

            foreach ($group['children'] as $child_key) {
                $child_id = (int) ($base_page_ids[$child_key] ?? 0);

                if ($parent_id > 0 && $child_id > 0) {
                    wp_update_post([
                        'ID'          => $child_id,
                        'post_parent' => $parent_id,
                    ]);
                }
            }
        }

        if ($rebuild_menu) {
            self::build_menu($variant, $base_page_ids, $group_ids);
        }

        self::set_active_variant($variant);

        return [
            'success' => true,
            'message' => $rebuild_menu
                ? 'Struktur "' . $variants[$variant]['label'] . '" wurde angewendet und das Menü wurde neu aufgebaut.'
                : 'Struktur "' . $variants[$variant]['label'] . '" wurde angewendet. Das Menü blieb unverändert.',
        ];
    }
    public static function get_structure_overview() {
        $variant = self::get_active_variant();
        $variants = self::get_structure_variants();
        $base_pages = self::get_base_pages();

        $overview = [
            'active_variant' => $variant,
            'active_label'   => $variants[$variant]['label'],
            'groups'         => [],
        ];

        foreach ($variants[$variant]['groups'] as $group_key => $group) {
            $group_page = self::find_page_by_slug($group['slug']);

            $children = [];

            foreach ($group['children'] as $child_key) {
                $child_page = self::find_page_by_slug($base_pages[$child_key]['slug']);

                $children[] = [
                    'key'    => $child_key,
                    'title'  => $base_pages[$child_key]['title'],
                    'slug'   => $base_pages[$child_key]['slug'],
                    'page'   => $child_page,
                ];
            }

            $overview['groups'][] = [
                'key'      => $group_key,
                'title'    => $group['title'],
                'slug'     => $group['slug'],
                'page'     => $group_page,
                'children' => $children,
            ];
        }

        return $overview;
    }
    public static function install_default_structure() {
        $installed = get_option('vermieter_default_structure_installed', 0);

        if ((int) $installed === 1) {
            return;
        }

        self::apply_structure('workflow');
        update_option('vermieter_default_structure_installed', 1);
    }
    public static function assign_menu_to_location($menu_id) {
        $locations = get_theme_mod('nav_menu_locations');

        if (!is_array($locations)) {
            $locations = [];
        }

        // Versuch automatisch "primary" oder erstes verfügbares Menü
        $registered = get_registered_nav_menus();

        if (isset($registered['primary'])) {
            $locations['primary'] = $menu_id;
        } else {
            $first = array_key_first($registered);
            if ($first) {
                $locations[$first] = $menu_id;
            }
        }

        set_theme_mod('nav_menu_locations', $locations);
    }
}