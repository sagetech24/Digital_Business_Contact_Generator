<?php
/**
 * Card Template CPT: one design pack per organization (1:1).
 *
 * Maps organization → PHP template file under /template/.
 * person_details preview/view resolves via the person's bss_organization.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSS_Business_Contact_Card_Template {

    const POST_TYPE = 'card_template';
    const ORG_META_KEY = 'bss_organization';
    const FILE_META_KEY = 'bss_template_file';
    const DESIGN_META_KEY = '_bss_design_settings';
    const NONCE_ACTION = 'bss_bc_save_card_template';
    const NONCE_NAME = 'bss_bc_card_template_nonce';
    const SEED_OPTION = 'bss_bc_card_templates_seeded';
    const DESIGN_MIGRATED_OPTION = 'bss_bc_design_migrated';

    /**
     * Default organization → template filename map.
     *
     * @return array<string,string>
     */
    public static function get_default_file_map() {
        return [
            'default'     => 'single-person_details_BSS.php',
            'hia'         => 'single-person_details_HIA.php',
            'hopevillage' => 'single-person_details_hopevillage.php',
            'sol'         => 'single-person_details_SOL.php',
            'ibex'        => 'single-person_details_IBEX.php',
        ];
    }

    /**
     * Stylesheet URL that matches a template PHP basename
     * (e.g. single-person_details_IBEX.php → css/single-person_details_IBEX.css).
     *
     * Returns an empty string when no matching CSS file exists so templates
     * with inline styles work without a missing-file link or forced fallback.
     *
     * @param string $template_php Absolute or relative .php template path/filename.
     * @return string URL or empty string
     */
    public static function get_stylesheet_url_for_template($template_php) {
        $base = pathinfo($template_php, PATHINFO_FILENAME);
        if ($base === '' || $base === '.' || $base === '..') {
            return '';
        }

        $css_file = $base . '.css';
        $css_path = dirname(dirname(__FILE__)) . '/css/' . $css_file;

        if (!is_file($css_path)) {
            return '';
        }

        $main = dirname(__DIR__) . '/Digital_Business_Contact_Generator.php';
        return plugins_url('css/' . $css_file, $main);
    }

    /**
     * Echo a stylesheet link only when a matching CSS file exists.
     *
     * @param string $template_php Absolute or relative .php template path/filename.
     */
    public static function print_stylesheet_link_for_template($template_php) {
        $url = self::get_stylesheet_url_for_template($template_php);
        if ($url === '') {
            return;
        }

        echo '<link rel="stylesheet" href="' . esc_url($url) . '" type="text/css" media="all">' . "\n";
    }

    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'save_meta'], 10, 2);
        add_action('admin_notices', [__CLASS__, 'admin_notices']);
        add_action('post_edit_form_tag', [__CLASS__, 'enable_multipart_form']);

        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [__CLASS__, 'admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [__CLASS__, 'render_admin_column'], 10, 2);

        add_action('admin_init', [__CLASS__, 'maybe_seed_defaults']);
        add_action('admin_init', [__CLASS__, 'maybe_ensure_design_settings'], 30);
        add_action('admin_post_bss_bc_seed_card_templates', [__CLASS__, 'handle_seed_request']);
    }

    /**
     * Allow file uploads on the card_template edit screen.
     *
     * @param WP_Post $post
     */
    public static function enable_multipart_form($post) {
        if ($post instanceof WP_Post && $post->post_type === self::POST_TYPE) {
            echo ' enctype="multipart/form-data"';
        }
    }

    /**
     * Absolute path to the plugin CSS directory.
     *
     * @return string
     */
    public static function get_css_dir() {
        return trailingslashit(dirname(__DIR__) . '/css');
    }

    /**
     * Built-in template filenames that must not be overwritten by uploads.
     *
     * @return string[]
     */
    public static function get_protected_template_files() {
        return array_values(array_unique(self::get_default_file_map()));
    }

    /**
     * Store a transient admin notice for the current user.
     *
     * @param string $message
     * @param string $type success|error|warning
     */
    public static function set_admin_notice($message, $type = 'error') {
        set_transient(
            'bss_bc_card_template_notice_' . get_current_user_id(),
            [
                'message' => (string) $message,
                'type'    => in_array($type, ['success', 'error', 'warning'], true) ? $type : 'error',
            ],
            45
        );
    }

    /**
     * Handle an uploaded .php template into /template/.
     *
     * @param array $file $_FILES element
     * @return string|WP_Error Filename on success
     */
    public static function handle_template_upload(array $file) {
        if (!current_user_can('manage_options')) {
            return new WP_Error('bss_bc_tpl_cap', __('You do not have permission to upload templates.', 'bss-business-contact'));
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('bss_bc_tpl_empty', __('No template file was uploaded.', 'bss-business-contact'));
        }

        if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('bss_bc_tpl_upload', __('Template upload failed. Please try again.', 'bss-business-contact'));
        }

        $name = isset($file['name']) ? basename(sanitize_file_name((string) $file['name'])) : '';
        if ($name === '' || strtolower(substr($name, -4)) !== '.php') {
            return new WP_Error('bss_bc_tpl_ext', __('Template must be a .php file.', 'bss-business-contact'));
        }

        // Basic sanity: readable PHP-ish content.
        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false || $contents === '') {
            return new WP_Error('bss_bc_tpl_read', __('Could not read the uploaded template.', 'bss-business-contact'));
        }
        if (strpos(ltrim($contents), '<?') !== 0) {
            return new WP_Error(
                'bss_bc_tpl_php',
                __('Uploaded file does not look like a PHP template (missing opening PHP tag).', 'bss-business-contact')
            );
        }

        if (in_array($name, self::get_protected_template_files(), true)) {
            $base = pathinfo($name, PATHINFO_FILENAME);
            $name = $base . '-custom.php';
        }

        // Avoid clobbering another protected rename target; uniquify if needed.
        $dir = self::get_template_dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            return new WP_Error(
                'bss_bc_tpl_dir',
                __('The template directory is not writable. Check plugin folder permissions.', 'bss-business-contact')
            );
        }

        $target = $dir . $name;
        if (file_exists($target) && in_array($name, self::get_protected_template_files(), true)) {
            return new WP_Error('bss_bc_tpl_protected', __('That template filename is protected and cannot be overwritten.', 'bss-business-contact'));
        }

        if (file_exists($target)) {
            $base = pathinfo($name, PATHINFO_FILENAME);
            $name = $base . '-' . time() . '.php';
            $target = $dir . $name;
        }

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return new WP_Error('bss_bc_tpl_move', __('Could not save the uploaded template file.', 'bss-business-contact'));
        }

        // Restrict permissions on uploaded PHP.
        chmod($target, 0644);

        return $name;
    }

    /**
     * Handle an optional matching .css upload into /css/.
     *
     * @param array  $file            $_FILES element
     * @param string $template_php    Template filename that was (or will be) assigned
     * @return string|WP_Error|null   CSS filename, error, or null if nothing uploaded
     */
    public static function handle_stylesheet_upload(array $file, $template_php) {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error('bss_bc_css_cap', __('You do not have permission to upload stylesheets.', 'bss-business-contact'));
        }

        if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('bss_bc_css_upload', __('Stylesheet upload failed. Please try again.', 'bss-business-contact'));
        }

        $orig = isset($file['name']) ? basename(sanitize_file_name((string) $file['name'])) : '';
        if ($orig === '' || strtolower(substr($orig, -4)) !== '.css') {
            return new WP_Error('bss_bc_css_ext', __('Stylesheet must be a .css file.', 'bss-business-contact'));
        }

        // Name CSS to match the PHP template basename so get_stylesheet_url_for_template finds it.
        $base = pathinfo((string) $template_php, PATHINFO_FILENAME);
        if ($base === '' || $base === '.' || $base === '..') {
            $base = pathinfo($orig, PATHINFO_FILENAME);
        }
        $name = $base . '.css';

        $dir = self::get_css_dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            return new WP_Error(
                'bss_bc_css_dir',
                __('The CSS directory is not writable. Check plugin folder permissions.', 'bss-business-contact')
            );
        }

        $protected_css = [];
        foreach (self::get_protected_template_files() as $php) {
            $protected_css[] = pathinfo($php, PATHINFO_FILENAME) . '.css';
        }

        if (in_array($name, $protected_css, true) && file_exists($dir . $name)) {
            return new WP_Error(
                'bss_bc_css_protected',
                __('That stylesheet matches a built-in template and cannot be overwritten. Rename your PHP template first.', 'bss-business-contact')
            );
        }

        $target = $dir . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return new WP_Error('bss_bc_css_move', __('Could not save the uploaded stylesheet.', 'bss-business-contact'));
        }

        chmod($target, 0644);
        return $name;
    }

    /**
     * Hard-coded design defaults (used when a card_template has no saved design yet).
     *
     * @return array<string,string>
     */
    /**
     * Organisation-specific design defaults (merged over get_design_defaults()).
     *
     * @param string $organization
     * @return array<string,string>
     */
    public static function get_design_defaults_for_organization($organization) {
        $defaults = self::get_design_defaults();
        $organization = is_string($organization) ? $organization : 'default';

        if ($organization === 'ibex') {
            // Prefer the Media Library asset used in Profile Customizer for IBEX.
            $defaults['background_image']      = content_url('/uploads/2023/04/IBEx.jpg');
            $defaults['background_size']       = '100%';
            $defaults['media_background_size'] = '100%';
            $defaults['overlay_color']         = '#FFFFFF';
            $defaults['background_overlay']    = '0%';
            $defaults['profile_border_color']  = '#2a2a2a';
            $defaults['profile_border_width']  = '2';
            $defaults['text_color']            = '#2f3430';
        }

        return $defaults;
    }

    public static function get_design_defaults() {
        return [
            'background_image'                => 'https://www.biblesociety.sg/wp-content/uploads/2015/05/image.jpeg',
            'background_size'                 => '1600',
            'media_background_size'           => '1600',
            'overlay_color'                   => '#E1FFF1',
            'background_overlay'              => '40%',
            'top_logo_image'                  => 'https://www.biblesociety.sg/wp-content/uploads/2015/05/BSS-Logo-2023-NEW-2B-WHITE-3.png',
            'top_logo_size'                   => '100',
            'media_top_logo_size'             => '100',
            'top_box_color1'                  => '#cdffd8',
            'top_box_color2'                  => '#96ffe5',
            'top_box_angle'                   => '135',
            'top_color_overlay'               => '40%',
            'profile_border_color'            => '#000000',
            'profile_border_width'            => '5',
            'button_box_color1'               => '#96ffe5',
            'button_box_color2'               => '#cdffd8',
            'button_box_angle'                => '135',
            'footer_box_color1'               => '#cdffd8',
            'footer_box_color2'               => '#96ffe5',
            'footer_box_angle'                => '135',
            'box_shadow_offsetx'              => '0',
            'box_shadow_offsety'              => '4',
            'box_shadow_blur'                 => '10',
            'box_shadow_color'                => '#000000',
            'box_shadow_transparency'         => '40%',
            'spacer_width'                    => '300',
            'spacer_height'                   => '4',
            'spacer_color'                    => '#22ffa3',
            'content_gap'                     => '16',
            'media_content_gap'               => '16',
            'name_font'                       => 'Arial',
            'name_font_weight'                => 'Bold',
            'name_font_size'                  => 'XL',
            'designation_font'                => 'Arial',
            'designation_font_weight'         => 'Bold',
            'designation_font_size'           => 'Regular',
            'quote_font'                      => 'Caveat',
            'quote_font_weight'               => 'Bold',
            'quote_font_size'                 => 'Regular',
            'quote_box_color'                 => '#f0f0f0',
            'quote_box_shadow_offsetx'        => '10',
            'quote_box_shadow_offsety'        => '10',
            'quote_box_shadow_blur'           => '0',
            'quote_box_shadow_color'          => '#c1c1c1',
            'quote_box_shadow_transparency'   => '0%',
            'text_color'                      => '#000000',
            'logo_color'                      => '#00ccff',
            'link_color'                      => '#000000',
            'link_hover_color'                => '#FFFF00',
            'bottom_logo_image'               => 'https://www.biblesociety.sg/wp-content/uploads/2015/05/path114.png',
            'bottom_logo_size_width'          => '150',
            'bottom_logo_size_height'         => '90',
            'media_bottom_logo_size_width'    => '150',
            'media_bottom_logo_size_height'   => '90',
            'address_link_color'              => '#FFFFFF',
            'address_link_hover_color'        => '#FFFF00',
        ];
    }

    /**
     * @return string[]
     */
    public static function get_design_keys() {
        return array_keys(self::get_design_defaults());
    }

    /**
     * Read legacy flat options from the old customizer (site-wide).
     *
     * @return array<string,string>
     */
    public static function collect_legacy_global_design() {
        $out = [];
        foreach (self::get_design_keys() as $key) {
            $val = get_option($key, null);
            if ($val !== null && $val !== false && $val !== '') {
                $out[$key] = is_string($val) ? $val : (string) $val;
            }
        }
        return $out;
    }

    /**
     * Sanitize a design settings array against known keys.
     *
     * @param mixed $input
     * @return array<string,string>
     */
    public static function sanitize_design($input) {
        $defaults = self::get_design_defaults();
        $out      = $defaults;

        if (!is_array($input)) {
            return $out;
        }

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $out[$key] = sanitize_text_field((string) $input[$key]);
        }

        return $out;
    }

    /**
     * @param int         $template_id card_template post ID
     * @param string|null $organization Optional org key for org-specific defaults
     * @return array<string,string>
     */
    public static function get_design($template_id, $organization = null) {
        $template_id = (int) $template_id;
        $stored      = get_post_meta($template_id, self::DESIGN_META_KEY, true);
        $advanced    = get_option('profile_customizer_defaults', []);

        if ($organization === null && $template_id > 0) {
            $organization = self::get_organization($template_id);
        }

        $base = self::get_design_defaults_for_organization($organization ? $organization : 'default');

        if (is_array($advanced) && !empty($advanced) && $organization !== 'ibex') {
            $base = array_merge($base, array_intersect_key($advanced, $base));
        }

        if (!is_array($stored) || empty($stored)) {
            return $base;
        }

        return array_merge($base, array_intersect_key($stored, $base));
    }

    /**
     * @param int   $template_id
     * @param array $design
     */
    public static function update_design($template_id, $design) {
        $template_id = (int) $template_id;
        if ($template_id <= 0) {
            return;
        }
        update_post_meta($template_id, self::DESIGN_META_KEY, self::sanitize_design($design));
    }

    /**
     * @param string $organization
     * @return array<string,string>
     */
    public static function get_design_for_organization($organization) {
        $post = self::get_by_organization($organization);
        if ($post instanceof WP_Post) {
            return self::get_design($post->ID, $organization);
        }
        return self::get_design_defaults_for_organization($organization);
    }

    /**
     * @param int $person_id person_details post ID
     * @return array<string,string>
     */
    public static function get_design_for_person($person_id) {
        $org = BSS_Business_Contact_Organization::get_organization($person_id);
        return self::get_design_for_organization($org);
    }

    /**
     * Ensure every card_template has design meta; migrate legacy globals into Default once.
     */
    public static function ensure_design_settings() {
        $labels   = BSS_Business_Contact_Organization::get_organization_labels();
        $migrated = get_option(self::DESIGN_MIGRATED_OPTION) === '1';
        $legacy   = !$migrated ? self::collect_legacy_global_design() : [];

        foreach (array_keys($labels) as $org) {
            $post = self::get_by_organization($org);
            if (!($post instanceof WP_Post)) {
                continue;
            }

            $existing = get_post_meta($post->ID, self::DESIGN_META_KEY, true);
            if (is_array($existing) && !empty($existing)) {
                continue;
            }

            if ($org === 'default' && !empty($legacy)) {
                self::update_design($post->ID, array_merge(self::get_design_defaults_for_organization($org), $legacy));
            } else {
                $advanced = get_option('profile_customizer_defaults', []);
                $design   = self::get_design_defaults_for_organization($org);
                if ($org !== 'ibex' && is_array($advanced) && !empty($advanced)) {
                    $design = array_merge($design, array_intersect_key($advanced, $design));
                }
                self::update_design($post->ID, $design);
            }
        }

        update_option(self::DESIGN_MIGRATED_OPTION, '1');
    }

    public static function maybe_ensure_design_settings() {
        if (get_option(self::DESIGN_MIGRATED_OPTION) === '1') {
            // Still fill any new org templates missing design meta.
            $labels = BSS_Business_Contact_Organization::get_organization_labels();
            foreach (array_keys($labels) as $org) {
                $post = self::get_by_organization($org);
                if (!($post instanceof WP_Post)) {
                    continue;
                }
                $existing = get_post_meta($post->ID, self::DESIGN_META_KEY, true);
                if (!is_array($existing) || empty($existing)) {
                    self::ensure_design_settings();
                    return;
                }
            }
            return;
        }
        self::ensure_design_settings();
    }

    /**
     * Absolute path to the plugin template directory.
     *
     * @return string
     */
    public static function get_template_dir() {
        return trailingslashit(dirname(__DIR__) . '/template');
    }

    public static function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'               => __('Card Templates', 'bss-business-contact'),
                'singular_name'      => __('Card Template', 'bss-business-contact'),
                'add_new'            => __('Add New Template', 'bss-business-contact'),
                'add_new_item'       => __('Add New Card Template', 'bss-business-contact'),
                'edit_item'          => __('Edit Card Template', 'bss-business-contact'),
                'new_item'           => __('New Card Template', 'bss-business-contact'),
                'view_item'          => __('View Card Template', 'bss-business-contact'),
                'search_items'       => __('Search Card Templates', 'bss-business-contact'),
                'not_found'          => __('No card templates found', 'bss-business-contact'),
                'not_found_in_trash' => __('No card templates found in Trash', 'bss-business-contact'),
                'menu_name'          => __('Card Templates', 'bss-business-contact'),
                'all_items'          => __('Card Templates', 'bss-business-contact'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=person_details',
            'show_in_admin_bar'   => false,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'rewrite'             => false,
            'supports'            => ['title'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ]);
    }

    /**
     * Filenames present in the template directory (PHP only).
     *
     * @return string[]
     */
    public static function get_available_template_files() {
        $dir   = self::get_template_dir();
        $files = [];

        if (!is_dir($dir)) {
            return $files;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (substr($entry, -4) !== '.php') {
                continue;
            }
            if (!is_file($dir . $entry)) {
                continue;
            }
            $files[] = $entry;
        }

        sort($files, SORT_STRING);
        return $files;
    }

    /**
     * Sanitize a template filename against the whitelist on disk.
     *
     * @param mixed $value
     * @return string
     */
    public static function sanitize_template_file($value) {
        $value = is_string($value) ? basename(sanitize_file_name($value)) : '';
        $allowed = self::get_available_template_files();

        if ($value !== '' && in_array($value, $allowed, true)) {
            return $value;
        }

        $defaults = self::get_default_file_map();
        return $defaults['default'];
    }

    /**
     * @param int $post_id card_template ID
     * @return string Organization key
     */
    public static function get_organization($post_id) {
        $org = get_post_meta($post_id, self::ORG_META_KEY, true);
        return BSS_Business_Contact_Organization::sanitize_organization($org);
    }

    /**
     * @param int $post_id card_template ID
     * @return string Filename
     */
    public static function get_template_file($post_id) {
        $file = get_post_meta($post_id, self::FILE_META_KEY, true);
        return self::sanitize_template_file($file);
    }

    /**
     * Find the card_template post for an organization (published).
     *
     * @param string $organization
     * @return WP_Post|null
     */
    public static function get_by_organization($organization) {
        $organization = BSS_Business_Contact_Organization::sanitize_organization($organization);

        $query = new WP_Query([
            'post_type'              => self::POST_TYPE,
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'meta_query'             => [
                [
                    'key'   => self::ORG_META_KEY,
                    'value' => $organization,
                ],
            ],
        ]);

        if (empty($query->posts)) {
            return null;
        }

        return $query->posts[0];
    }

    /**
     * Absolute filesystem path for an organization's template file.
     *
     * @param string $organization
     * @return string Path that exists, or empty string
     */
    public static function get_template_path_for_organization($organization) {
        $organization = BSS_Business_Contact_Organization::sanitize_organization($organization);
        $post         = self::get_by_organization($organization);

        if ($post instanceof WP_Post) {
            $file = self::get_template_file($post->ID);
            $path = self::get_template_dir() . $file;
            if (is_file($path)) {
                return $path;
            }
        }

        // Fallback: default file map, then BSS file.
        $map      = self::get_default_file_map();
        $fallback = isset($map[$organization]) ? $map[$organization] : $map['default'];
        $path     = self::get_template_dir() . $fallback;

        if (is_file($path)) {
            return $path;
        }

        $bss = self::get_template_dir() . $map['default'];
        return is_file($bss) ? $bss : '';
    }

    /**
     * Resolve template path for a person_details post.
     *
     * @param int $person_id
     * @return string
     */
    public static function get_template_path_for_person($person_id) {
        $org = BSS_Business_Contact_Organization::get_organization($person_id);
        return self::get_template_path_for_organization($org);
    }

    /**
     * Whether another published card_template already owns this organization.
     *
     * @param string $organization
     * @param int    $exclude_post_id
     * @return int Conflicting post ID, or 0
     */
    public static function find_organization_conflict($organization, $exclude_post_id = 0) {
        $organization = BSS_Business_Contact_Organization::sanitize_organization($organization);

        $query = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'post__not_in'   => $exclude_post_id ? [(int) $exclude_post_id] : [],
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'   => self::ORG_META_KEY,
                    'value' => $organization,
                ],
            ],
        ]);

        return !empty($query->posts) ? (int) $query->posts[0] : 0;
    }

    public static function register_meta_boxes() {
        add_meta_box(
            'bss_bc_card_template_settings',
            __('Template settings', 'bss-business-contact'),
            [__CLASS__, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * @param WP_Post $post
     */
    public static function render_meta_box($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $current_org  = self::get_organization($post->ID);
        $current_file = get_post_meta($post->ID, self::FILE_META_KEY, true);
        if ($current_file === '') {
            $map          = self::get_default_file_map();
            $current_file = isset($map[$current_org]) ? $map[$current_org] : $map['default'];
        }

        $labels = BSS_Business_Contact_Organization::get_organization_labels();
        $files  = self::get_available_template_files();
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bss_card_template_organization"><?php esc_html_e('Organisation', 'bss-business-contact'); ?></label>
                </th>
                <td>
                    <select name="bss_card_template_organization" id="bss_card_template_organization" style="min-width:220px;">
                        <?php foreach ($labels as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_org, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('One card template per organisation. Person cards with this organisation will load the file below.', 'bss-business-contact'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bss_card_template_file"><?php esc_html_e('Template file', 'bss-business-contact'); ?></label>
                </th>
                <td>
                    <select name="bss_card_template_file" id="bss_card_template_file" style="min-width:320px;">
                        <?php foreach ($files as $file) : ?>
                            <option value="<?php echo esc_attr($file); ?>" <?php selected($current_file, $file); ?>>
                                <?php echo esc_html($file); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: template directory path */
                            esc_html__('Existing files from: %s', 'bss-business-contact'),
                            '<code>' . esc_html(self::get_template_dir()) . '</code>'
                        );
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bss_card_template_upload"><?php esc_html_e('Upload template', 'bss-business-contact'); ?></label>
                </th>
                <td>
                    <input type="file" name="bss_card_template_upload" id="bss_card_template_upload" accept=".php,text/x-php,application/x-php">
                    <p class="description">
                        <?php esc_html_e('Optional. Upload a .php card template to assign to this organisation. Built-in templates cannot be overwritten; a “-custom” name is used instead. If a file is uploaded, it is selected automatically on save.', 'bss-business-contact'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bss_card_template_css_upload"><?php esc_html_e('Upload stylesheet', 'bss-business-contact'); ?></label>
                </th>
                <td>
                    <input type="file" name="bss_card_template_css_upload" id="bss_card_template_css_upload" accept=".css,text/css">
                    <p class="description">
                        <?php esc_html_e('Optional. Upload only if styles are not inline in the PHP template. Saved as the same basename as the PHP file. If no matching CSS exists, none is loaded — the template still works normally.', 'bss-business-contact'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * @param int     $post_id
     * @param WP_Post $post
     */
    public static function save_meta($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!isset($_POST[self::NONCE_NAME])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $org = isset($_POST['bss_card_template_organization'])
            ? BSS_Business_Contact_Organization::sanitize_organization(wp_unslash($_POST['bss_card_template_organization']))
            : 'default';

        $file = isset($_POST['bss_card_template_file'])
            ? self::sanitize_template_file(wp_unslash($_POST['bss_card_template_file']))
            : self::get_default_file_map()['default'];

        $uploaded_php = false;
        $upload_error = false;
        if (!empty($_FILES['bss_card_template_upload']['tmp_name'])) {
            $upload = self::handle_template_upload($_FILES['bss_card_template_upload']);
            if (is_wp_error($upload)) {
                self::set_admin_notice($upload->get_error_message(), 'error');
                $upload_error = true;
            } else {
                $file         = $upload;
                $uploaded_php = true;
            }
        }

        if (!empty($_FILES['bss_card_template_css_upload']['tmp_name'])) {
            $css = self::handle_stylesheet_upload($_FILES['bss_card_template_css_upload'], $file);
            if (is_wp_error($css)) {
                self::set_admin_notice($css->get_error_message(), 'error');
            } elseif (is_string($css) && $css !== '' && !$upload_error) {
                self::set_admin_notice(
                    $uploaded_php
                        ? sprintf(
                            /* translators: 1: php filename, 2: css filename */
                            __('Template “%1$s” and stylesheet “%2$s” uploaded and assigned.', 'bss-business-contact'),
                            $file,
                            $css
                        )
                        : sprintf(
                            /* translators: %s: css filename */
                            __('Stylesheet “%s” uploaded for the selected template.', 'bss-business-contact'),
                            $css
                        ),
                    'success'
                );
            }
        } elseif ($uploaded_php) {
            self::set_admin_notice(
                sprintf(
                    /* translators: %s: php filename */
                    __('Template “%s” uploaded and assigned.', 'bss-business-contact'),
                    $file
                ),
                'success'
            );
        }

        // Re-sanitize after upload so the new file is accepted (now on disk).
        $file = self::sanitize_template_file($file);

        $conflict = self::find_organization_conflict($org, $post_id);
        if ($conflict) {
            set_transient(
                'bss_bc_card_template_error_' . get_current_user_id(),
                sprintf(
                    /* translators: 1: organization key, 2: conflicting post ID */
                    __('Organisation "%1$s" is already assigned to another card template (ID %2$d). Choose a different organisation.', 'bss-business-contact'),
                    $org,
                    $conflict
                ),
                45
            );
            // Keep previous org if conflict; still save file.
            $existing_org = get_post_meta($post_id, self::ORG_META_KEY, true);
            if ($existing_org !== '') {
                $org = BSS_Business_Contact_Organization::sanitize_organization($existing_org);
            } else {
                return;
            }
        }

        update_post_meta($post_id, self::ORG_META_KEY, $org);
        update_post_meta($post_id, self::FILE_META_KEY, $file);
    }

    public static function admin_notices() {
        $uid = get_current_user_id();

        $legacy_key = 'bss_bc_card_template_error_' . $uid;
        $legacy     = get_transient($legacy_key);
        if ($legacy) {
            delete_transient($legacy_key);
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($legacy) . '</p></div>';
        }

        $notice_key = 'bss_bc_card_template_notice_' . $uid;
        $notice     = get_transient($notice_key);
        if (is_array($notice) && !empty($notice['message'])) {
            delete_transient($notice_key);
            $type  = isset($notice['type']) ? $notice['type'] : 'error';
            $class = $type === 'success' ? 'notice-success' : ($type === 'warning' ? 'notice-warning' : 'notice-error');
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    public static function admin_columns($columns) {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['bss_organization']  = __('Organisation', 'bss-business-contact');
                $new['bss_template_file'] = __('Template file', 'bss-business-contact');
                $new['bss_design']         = __('Design', 'bss-business-contact');
            }
        }
        // Drop date to keep the management list focused.
        unset($new['date']);
        return $new;
    }

    /**
     * @param string $column
     * @param int    $post_id
     */
    public static function render_admin_column($column, $post_id) {
        if ($column === 'bss_organization') {
            $org    = self::get_organization($post_id);
            $labels = BSS_Business_Contact_Organization::get_organization_labels();
            $label  = isset($labels[$org]) ? $labels[$org] : $org;
            echo esc_html($label);
            echo '<br><code>' . esc_html($org) . '</code>';
            return;
        }

        if ($column === 'bss_template_file') {
            $file = self::get_template_file($post_id);
            $path = self::get_template_dir() . $file;
            echo '<code>' . esc_html($file) . '</code>';
            if (!is_file($path)) {
                echo ' <span style="color:#b32d2e;">(' . esc_html__('missing', 'bss-business-contact') . ')</span>';
            }
            return;
        }

        if ($column === 'bss_design') {
            $org = self::get_organization($post_id);
            $url = class_exists('BSS_Business_Contact_Customizer')
                ? BSS_Business_Contact_Customizer::admin_url('customizer', $org)
                : add_query_arg(
                    [
                        'post_type' => 'person_details',
                        'page'      => 'bss-profile-customizer',
                        'tab'       => 'customizer',
                        'org'       => $org,
                    ],
                    admin_url('edit.php')
                );
            printf(
                '<a class="button button-small" href="%s">%s</a>',
                esc_url($url),
                esc_html__('Customize', 'bss-business-contact')
            );
        }
    }

    /**
     * Seed one published card_template per organization if missing.
     */
    public static function seed_defaults() {
        $labels = BSS_Business_Contact_Organization::get_organization_labels();
        $map    = self::get_default_file_map();

        foreach ($labels as $org => $label) {
            if (self::get_by_organization($org) instanceof WP_Post) {
                continue;
            }

            // Also skip if a non-published conflict exists.
            if (self::find_organization_conflict($org, 0)) {
                continue;
            }

            $file = isset($map[$org]) ? $map[$org] : $map['default'];
            if (!in_array($file, self::get_available_template_files(), true)) {
                $file = $map['default'];
            }

            $post_id = wp_insert_post([
                'post_type'   => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title'  => $label,
            ], true);

            if (is_wp_error($post_id) || !$post_id) {
                continue;
            }

            update_post_meta($post_id, self::ORG_META_KEY, $org);
            update_post_meta($post_id, self::FILE_META_KEY, $file);
        }

        update_option(self::SEED_OPTION, '1.0');
        self::ensure_design_settings();
    }

    public static function maybe_seed_defaults() {
        // Always run: seed_defaults() skips organisations that already have a card_template.
        self::seed_defaults();
    }

    public static function handle_seed_request() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'bss-business-contact'));
        }
        check_admin_referer('bss_bc_seed_card_templates');
        self::seed_defaults();
        wp_safe_redirect(admin_url('edit.php?post_type=' . self::POST_TYPE . '&bss_seeded=1'));
        exit;
    }
}
