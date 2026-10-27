<?php
/**
 * Profile Customizer — design settings per card_template / organisation.
 * Lives inside Digital Business Contact Generator (no separate plugin required).
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSS_Business_Contact_Customizer {

    const MENU_SLUG = 'bss-profile-customizer';
    const PARENT    = 'edit.php?post_type=person_details';

    public static function init() {
        $dir = plugin_dir_path(dirname(__FILE__)) . 'admin/customizer/';

        require_once $dir . 'custom-contact-customizer-storage.php';
        require_once $dir . 'custom-contact-customizer-help.php';
        require_once $dir . 'custom-contact-customizer-advanced.php';
        require_once $dir . 'profile-customizer-ui.php';

        add_action('admin_menu', [__CLASS__, 'register_menu'], 25);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function register_menu() {
        add_submenu_page(
            self::PARENT,
            __('Template Customizer', 'bss-business-contact'),
            __('Template Customizer', 'bss-business-contact'),
            'manage_options',
            self::MENU_SLUG,
            'render_profile_customizer'
        );
    }

    /**
     * @param string $hook
     */
    public static function enqueue_assets($hook) {
        // Hook for submenu under person_details CPT.
        if ($hook !== 'person_details_page_' . self::MENU_SLUG) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'bss-profile-customizer-script',
            plugins_url(
                'admin/customizer/assets/customizer.js',
                dirname(__DIR__) . '/BSS_Business_Contact.php'
            ),
            ['jquery'],
            '1.3',
            true
        );
    }

    /**
     * Admin URL for the in-plugin customizer.
     *
     * @param string      $tab
     * @param string|null $org
     * @return string
     */
    public static function admin_url($tab = 'customizer', $org = null) {
        if ($org === null) {
            $org = function_exists('bss_pc_get_editing_org') ? bss_pc_get_editing_org() : 'default';
        }

        return add_query_arg(
            [
                'post_type' => 'person_details',
                'page'      => self::MENU_SLUG,
                'tab'       => $tab,
                'org'       => $org,
            ],
            admin_url('edit.php')
        );
    }
}
