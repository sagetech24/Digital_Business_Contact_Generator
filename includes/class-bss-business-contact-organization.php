<?php
/**
 * Organization meta and organization-based permalink prefixes for person_details.
 *
 * `default` is hard-coded (prefix /id/) and always used as the fallback.
 * Additional organisations are stored in the bss_bc_organizations option
 * and managed from the Organisations admin screen.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSS_Business_Contact_Organization {

    const META_KEY = 'bss_organization';
    const QUERY_VAR = 'bss_org_prefix';
    const NONCE_ACTION = 'bss_bc_save_organization';
    const NONCE_NAME = 'bss_bc_organization_nonce';

    const OPTION_KEY = 'bss_bc_organizations';
    const OPTION_SEEDED = 'bss_bc_organizations_seeded';
    const MENU_SLUG = 'bss-business-organizations';
    const ADD_ACTION = 'bss_bc_add_organization';
    const REMOVE_ACTION = 'bss_bc_remove_organization';
    const ADMIN_NONCE_ACTION = 'bss_bc_manage_organizations';

    /** Hard-coded fallback organisation key. */
    const DEFAULT_KEY = 'default';

    /** Hard-coded URL prefix for the fallback organisation. */
    const DEFAULT_PREFIX = 'id';

    /**
     * Built-in organisations seeded into the option on first run.
     * (Excludes hard-coded default.)
     *
     * @return array<string,array{label:string,prefix:string}>
     */
    public static function get_builtin_custom_organizations() {
        return [
            'hia' => [
                'label'  => 'HIA',
                'prefix' => 'hia',
            ],
            'hopevillage' => [
                'label'  => 'Hope Village',
                'prefix' => 'hopevillage',
            ],
            'sol' => [
                'label'  => 'SOL',
                'prefix' => 'sol',
            ],
            'ibex' => [
                'label'  => 'IBEX',
                'prefix' => 'ibex',
            ],
        ];
    }

    /**
     * Hard-coded default label (translatable at read time).
     *
     * @return string
     */
    public static function get_default_label() {
        return __('Default (BSS)', 'bss-business-contact');
    }

    /**
     * Ensure the custom-organisations option is seeded once.
     */
    public static function maybe_seed_custom_organizations() {
        if (get_option(self::OPTION_SEEDED) === '1') {
            return;
        }

        $existing = get_option(self::OPTION_KEY, null);
        if (!is_array($existing) || empty($existing)) {
            update_option(self::OPTION_KEY, self::get_builtin_custom_organizations(), false);
        }

        update_option(self::OPTION_SEEDED, '1', false);
    }

    /**
     * Custom organisations from the option (never includes default).
     *
     * @return array<string,array{label:string,prefix:string}>
     */
    public static function get_custom_organizations() {
        self::maybe_seed_custom_organizations();

        $stored = get_option(self::OPTION_KEY, []);
        if (!is_array($stored)) {
            return [];
        }

        $out = [];
        foreach ($stored as $key => $row) {
            $key = sanitize_key((string) $key);
            if ($key === '' || $key === self::DEFAULT_KEY) {
                continue;
            }
            if (!is_array($row)) {
                continue;
            }

            $label  = isset($row['label']) ? sanitize_text_field((string) $row['label']) : $key;
            $prefix = isset($row['prefix']) ? sanitize_title((string) $row['prefix']) : $key;
            $prefix = self::sanitize_prefix($prefix);

            if ($label === '' || $prefix === '') {
                continue;
            }

            $out[$key] = [
                'label'  => $label,
                'prefix' => $prefix,
            ];
        }

        return $out;
    }

    /**
     * Persist custom organisations (default is never stored).
     *
     * @param array<string,array{label:string,prefix:string}> $orgs
     */
    public static function save_custom_organizations(array $orgs) {
        unset($orgs[self::DEFAULT_KEY]);
        update_option(self::OPTION_KEY, $orgs, false);
        self::bump_rewrite_version();
    }

    /**
     * Organization value => URL path prefix (default + custom).
     *
     * @return array<string,string>
     */
    public static function get_organizations() {
        $map = [
            self::DEFAULT_KEY => self::DEFAULT_PREFIX,
        ];

        foreach (self::get_custom_organizations() as $key => $row) {
            $map[$key] = $row['prefix'];
        }

        return $map;
    }

    /**
     * Labels for the admin dropdown (default + custom).
     *
     * @return array<string,string>
     */
    public static function get_organization_labels() {
        $labels = [
            self::DEFAULT_KEY => self::get_default_label(),
        ];

        foreach (self::get_custom_organizations() as $key => $row) {
            $labels[$key] = $row['label'];
        }

        return $labels;
    }

    /**
     * Sanitize a URL path prefix segment.
     *
     * @param mixed $prefix
     * @return string
     */
    public static function sanitize_prefix($prefix) {
        $prefix = is_string($prefix) ? sanitize_title($prefix) : '';
        $prefix = strtolower($prefix);
        $prefix = preg_replace('/[^a-z0-9\-]+/', '', $prefix);
        return is_string($prefix) ? trim($prefix, '-') : '';
    }

    /**
     * Derive a unique organisation key from a label.
     *
     * @param string $label
     * @param array<string,mixed>|null $existing Keys already taken
     * @return string
     */
    public static function generate_organization_key($label, $existing = null) {
        $base = sanitize_key($label);
        if ($base === '' || $base === self::DEFAULT_KEY) {
            $base = 'org';
        }

        if ($existing === null) {
            $existing = self::get_organizations();
        }

        $key = $base;
        $i   = 2;
        while (isset($existing[$key])) {
            $key = $base . '-' . $i;
            $i++;
        }

        return $key;
    }

    /**
     * Add a custom organisation.
     *
     * @param string      $label
     * @param string      $prefix
     * @param string|null $key Optional explicit key
     * @return string|WP_Error Organisation key on success
     */
    public static function add_organization($label, $prefix, $key = null) {
        $label  = sanitize_text_field((string) $label);
        $prefix = self::sanitize_prefix($prefix);

        if ($label === '') {
            return new WP_Error('bss_bc_org_label', __('Organisation label is required.', 'bss-business-contact'));
        }

        if ($prefix === '') {
            return new WP_Error('bss_bc_org_prefix', __('URL prefix is required.', 'bss-business-contact'));
        }

        if ($prefix === self::DEFAULT_PREFIX) {
            return new WP_Error(
                'bss_bc_org_prefix_reserved',
                __('That URL prefix is reserved for the default organisation.', 'bss-business-contact')
            );
        }

        $map = self::get_organizations();
        foreach ($map as $existing_key => $existing_prefix) {
            if ($existing_prefix === $prefix) {
                return new WP_Error(
                    'bss_bc_org_prefix_taken',
                    __('That URL prefix is already used by another organisation.', 'bss-business-contact')
                );
            }
        }

        if ($key === null || $key === '') {
            $key = self::generate_organization_key($label, $map);
        } else {
            $key = sanitize_key((string) $key);
        }

        if ($key === '' || $key === self::DEFAULT_KEY) {
            return new WP_Error('bss_bc_org_key', __('Invalid organisation key.', 'bss-business-contact'));
        }

        if (isset($map[$key])) {
            return new WP_Error('bss_bc_org_exists', __('An organisation with that key already exists.', 'bss-business-contact'));
        }

        $custom = self::get_custom_organizations();
        $custom[$key] = [
            'label'  => $label,
            'prefix' => $prefix,
        ];
        self::save_custom_organizations($custom);

        if (class_exists('BSS_Business_Contact_Card_Template')) {
            BSS_Business_Contact_Card_Template::seed_defaults();
        }

        return $key;
    }

    /**
     * Remove a custom organisation. People are reassigned to default;
     * the organisation's card_template is moved to trash.
     *
     * @param string $key
     * @return true|WP_Error
     */
    public static function remove_organization($key) {
        $key = sanitize_key((string) $key);

        if ($key === '' || $key === self::DEFAULT_KEY) {
            return new WP_Error(
                'bss_bc_org_protected',
                __('The default organisation cannot be removed.', 'bss-business-contact')
            );
        }

        $custom = self::get_custom_organizations();
        if (!isset($custom[$key])) {
            return new WP_Error('bss_bc_org_missing', __('Organisation not found.', 'bss-business-contact'));
        }

        self::reassign_people_to_default($key);
        self::trash_card_template_for_organization($key);

        unset($custom[$key]);
        self::save_custom_organizations($custom);

        return true;
    }

    /**
     * @param string $organization
     */
    public static function reassign_people_to_default($organization) {
        $organization = sanitize_key((string) $organization);
        if ($organization === '' || $organization === self::DEFAULT_KEY) {
            return;
        }

        $q = new WP_Query([
            'post_type'      => 'person_details',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => self::META_KEY,
                    'value' => $organization,
                ],
            ],
            'no_found_rows'  => true,
        ]);

        foreach ($q->posts as $post_id) {
            update_post_meta((int) $post_id, self::META_KEY, self::DEFAULT_KEY);
        }
    }

    /**
     * @param string $organization
     */
    public static function trash_card_template_for_organization($organization) {
        if (!class_exists('BSS_Business_Contact_Card_Template')) {
            return;
        }

        $post = BSS_Business_Contact_Card_Template::get_by_organization($organization);
        if ($post instanceof WP_Post) {
            wp_trash_post($post->ID);
        }
    }

    /**
     * Force rewrite rules to re-register and flush on next admin_init.
     */
    public static function bump_rewrite_version() {
        $current = (string) get_option('bss_bc_org_rewrite_version', '0');
        update_option('bss_bc_org_rewrite_version', $current . '-pending', false);
    }

    public static function init() {
        add_action('init', [__CLASS__, 'maybe_seed_custom_organizations'], 5);
        add_action('init', [__CLASS__, 'register_rewrite_rules'], 20);
        add_filter('query_vars', [__CLASS__, 'register_query_var']);
        add_filter('post_type_link', [__CLASS__, 'filter_post_type_link'], 10, 2);

        add_action('add_meta_boxes', [__CLASS__, 'register_meta_box']);
        add_action('save_post_person_details', [__CLASS__, 'save_organization_meta'], 10, 2);

        add_filter('manage_person_details_posts_columns', [__CLASS__, 'add_admin_column']);
        add_action('manage_person_details_posts_custom_column', [__CLASS__, 'render_admin_column'], 10, 2);
        add_filter('manage_edit-person_details_sortable_columns', [__CLASS__, 'make_admin_column_sortable']);
        add_action('pre_get_posts', [__CLASS__, 'sort_admin_column']);
        add_filter('posts_clauses', [__CLASS__, 'sort_admin_column_clauses'], 10, 2);

        add_action('template_redirect', [__CLASS__, 'enforce_organization_prefix']);
        add_action('admin_init', [__CLASS__, 'maybe_flush_rewrites']);

        add_action('admin_menu', [__CLASS__, 'register_menu'], 20);
        add_action('admin_post_' . self::ADD_ACTION, [__CLASS__, 'handle_add']);
        add_action('admin_post_' . self::REMOVE_ACTION, [__CLASS__, 'handle_remove']);
        add_action('admin_notices', [__CLASS__, 'render_admin_notices']);
    }

    public static function register_menu() {
        add_submenu_page(
            'edit.php?post_type=person_details',
            __('Organisations', 'bss-business-contact'),
            __('Organisations', 'bss-business-contact'),
            'manage_options',
            self::MENU_SLUG,
            [__CLASS__, 'render_admin_page']
        );
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'bss-business-contact'));
        }

        include plugin_dir_path(dirname(__FILE__)) . 'admin/organizations-page.php';
    }

    public static function handle_add() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'bss-business-contact'));
        }

        check_admin_referer(self::ADMIN_NONCE_ACTION);

        $label  = isset($_POST['bss_org_label']) ? wp_unslash($_POST['bss_org_label']) : '';
        $prefix = isset($_POST['bss_org_prefix']) ? wp_unslash($_POST['bss_org_prefix']) : '';

        $result = self::add_organization($label, $prefix);
        $redirect = admin_url('edit.php?post_type=person_details&page=' . self::MENU_SLUG);

        if (is_wp_error($result)) {
            $redirect = add_query_arg(
                [
                    'bss_org_error' => rawurlencode($result->get_error_message()),
                ],
                $redirect
            );
        } else {
            $redirect = add_query_arg('bss_org_added', '1', $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public static function handle_remove() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'bss-business-contact'));
        }

        check_admin_referer(self::ADMIN_NONCE_ACTION);

        $key = isset($_POST['bss_org_key']) ? sanitize_key(wp_unslash($_POST['bss_org_key'])) : '';
        $result = self::remove_organization($key);
        $redirect = admin_url('edit.php?post_type=person_details&page=' . self::MENU_SLUG);

        if (is_wp_error($result)) {
            $redirect = add_query_arg(
                [
                    'bss_org_error' => rawurlencode($result->get_error_message()),
                ],
                $redirect
            );
        } else {
            $redirect = add_query_arg('bss_org_removed', '1', $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public static function render_admin_notices() {
        if (!is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'person_details_page_' . self::MENU_SLUG) {
            return;
        }

        if (isset($_GET['bss_org_added'])) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__('Organisation added. A card template was created if one did not already exist.', 'bss-business-contact')
                . '</p></div>';
        }

        if (isset($_GET['bss_org_removed'])) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__('Organisation removed. People using it were moved to Default (BSS).', 'bss-business-contact')
                . '</p></div>';
        }

        if (!empty($_GET['bss_org_error'])) {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html(rawurldecode(wp_unslash($_GET['bss_org_error'])))
                . '</p></div>';
        }
    }

    /**
     * Extra rewrite rules for non-default organization prefixes.
     * Default /id/{slug} comes from the CPT rewrite slug.
     */
    public static function register_rewrite_rules() {
        $prefixes = array_unique(array_values(self::get_organizations()));

        foreach ($prefixes as $prefix) {
            if ($prefix === self::DEFAULT_PREFIX) {
                // Handled by CPT rewrite slug "id".
                continue;
            }

            add_rewrite_rule(
                '^' . preg_quote($prefix, '/') . '/([^/]+)/?$',
                'index.php?person_details=$matches[1]&' . self::QUERY_VAR . '=' . $prefix,
                'top'
            );
        }
    }

    /**
     * @param string[] $vars
     * @return string[]
     */
    public static function register_query_var($vars) {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * @param int $post_id
     * @return string Organization key
     */
    public static function get_organization($post_id) {
        $org = get_post_meta($post_id, self::META_KEY, true);
        $allowed = array_keys(self::get_organizations());

        if (!in_array($org, $allowed, true)) {
            return self::DEFAULT_KEY;
        }

        return $org;
    }

    /**
     * @param string $organization
     * @return string URL path segment
     */
    public static function get_prefix_for_organization($organization) {
        $map = self::get_organizations();
        return isset($map[$organization]) ? $map[$organization] : self::DEFAULT_PREFIX;
    }

    /**
     * @param string $prefix
     * @return string Organization key
     */
    public static function get_organization_for_prefix($prefix) {
        foreach (self::get_organizations() as $org => $org_prefix) {
            if ($org_prefix === $prefix) {
                return $org;
            }
        }
        return self::DEFAULT_KEY;
    }

    /**
     * Sanitize organization value; fall back to default.
     *
     * @param mixed $value
     * @return string
     */
    public static function sanitize_organization($value) {
        $value = is_string($value) ? sanitize_key($value) : '';
        $allowed = array_keys(self::get_organizations());
        return in_array($value, $allowed, true) ? $value : self::DEFAULT_KEY;
    }

    /**
     * Build public permalink using organization prefix.
     *
     * @param string  $post_link
     * @param WP_Post $post
     * @return string
     */
    public static function filter_post_type_link($post_link, $post) {
        if (!($post instanceof WP_Post) || $post->post_type !== 'person_details') {
            return $post_link;
        }

        if ($post->post_name === '') {
            return $post_link;
        }

        $org    = self::get_organization($post->ID);
        $prefix = self::get_prefix_for_organization($org);

        return home_url(user_trailingslashit($prefix . '/' . $post->post_name));
    }

    public static function register_meta_box() {
        add_meta_box(
            'bss_bc_organization',
            __('Organization', 'bss-business-contact'),
            [__CLASS__, 'render_meta_box'],
            'person_details',
            'side',
            'high'
        );
    }

    /**
     * @param WP_Post $post
     */
    public static function render_meta_box($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $current = self::get_organization($post->ID);
        $labels  = self::get_organization_labels();
        $map     = self::get_organizations();
        ?>
        <p>
            <label for="bss_organization"><strong><?php esc_html_e('Organisation', 'bss-business-contact'); ?></strong></label>
        </p>
        <select name="bss_organization" id="bss_organization" style="width:80%;">
            <?php foreach ($labels as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description" style="margin-top:8px;">
            <?php esc_html_e('Organisation membership of the person. This will affect the public URL prefix for this card:', 'bss-business-contact'); ?>
        </p>
        <ul style="margin:6px 0 0 1.2em; list-style:disc;">
            <?php foreach ($labels as $value => $label) : ?>
                <li>
                    <?php
                    printf(
                        /* translators: 1: organization label, 2: example path */
                        esc_html__('%1$s → /%2$s/{slug}', 'bss-business-contact'),
                        esc_html($label),
                        esc_html($map[$value])
                    );
                    ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    /**
     * @param int     $post_id
     * @param WP_Post $post
     */
    public static function save_organization_meta($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Import / programmatic saves may set meta without the metabox nonce.
        if (isset($_POST[self::NONCE_NAME])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
                return;
            }

            $org = isset($_POST['bss_organization'])
                ? self::sanitize_organization(wp_unslash($_POST['bss_organization']))
                : self::DEFAULT_KEY;

            update_post_meta($post_id, self::META_KEY, $org);
            return;
        }

        // Ensure existing cards always have a value.
        if (get_post_meta($post_id, self::META_KEY, true) === '') {
            update_post_meta($post_id, self::META_KEY, self::DEFAULT_KEY);
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    public static function add_admin_column($columns) {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['bss_organization'] = __('Organisation', 'bss-business-contact');
            }
        }
        return $new;
    }

    /**
     * Mark Organisation column as sortable in the admin list.
     *
     * @param array $columns
     * @return array
     */
    public static function make_admin_column_sortable($columns) {
        $columns['bss_organization'] = 'bss_organization';
        return $columns;
    }

    /**
     * Flag the person_details list query for organisation sorting.
     *
     * Avoid meta_key / INNER JOIN so cards without the meta (Default / BSS)
     * remain in the result set.
     *
     * @param WP_Query $query
     */
    public static function sort_admin_column($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('orderby') !== 'bss_organization') {
            return;
        }

        if ($query->get('post_type') !== 'person_details') {
            return;
        }

        $query->set('bss_bc_sort_organization', 1);
        // Prevent core from applying a meta INNER JOIN that drops missing meta.
        $query->set('meta_key', '');
        $query->set('orderby', 'none');
    }

    /**
     * LEFT JOIN organisation meta and sort, treating missing/empty as "default".
     *
     * @param array    $clauses
     * @param WP_Query $query
     * @return array
     */
    public static function sort_admin_column_clauses($clauses, $query) {
        if (!is_admin() || !$query->is_main_query()) {
            return $clauses;
        }

        if (!(int) $query->get('bss_bc_sort_organization')) {
            return $clauses;
        }

        global $wpdb;

        $order = strtoupper((string) $query->get('order')) === 'DESC' ? 'DESC' : 'ASC';
        $alias = 'bss_org_sort';

        $clauses['join'] .= $wpdb->prepare(
            " LEFT JOIN {$wpdb->postmeta} AS {$alias} ON ({$wpdb->posts}.ID = {$alias}.post_id AND {$alias}.meta_key = %s)",
            self::META_KEY
        );

        $clauses['orderby'] = "COALESCE(NULLIF({$alias}.meta_value, ''), 'default') {$order}, {$wpdb->posts}.post_title ASC";

        return $clauses;
    }

    /**
     * @param string $column
     * @param int    $post_id
     */
    public static function render_admin_column($column, $post_id) {
        if ($column !== 'bss_organization') {
            return;
        }

        $org    = self::get_organization($post_id);
        $labels = self::get_organization_labels();
        $label  = isset($labels[$org]) ? $labels[$org] : $org;

        $label = $org === self::DEFAULT_KEY ? 'BSS' : $label;
        echo esc_html($label);
    }

    /**
     * If a card is opened under the wrong prefix, redirect to the correct permalink.
     * Also tags default /id/ requests with the expected org for consistency.
     */
    public static function enforce_organization_prefix() {
        if (!is_singular('person_details')) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $correct_url = get_permalink($post_id);
        if (!$correct_url) {
            return;
        }

        $request_path = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_path = (string) wp_parse_url($request_path, PHP_URL_PATH);
        $correct_path = (string) wp_parse_url($correct_url, PHP_URL_PATH);

        if ($request_path === '' || $correct_path === '') {
            return;
        }

        $request_path = untrailingslashit($request_path);
        $correct_path = untrailingslashit($correct_path);

        if (strcasecmp($request_path, $correct_path) !== 0) {
            wp_safe_redirect($correct_url, 301);
            exit;
        }
    }

    /**
     * Rewrite flush after organisations change or feature deploy.
     */
    public static function maybe_flush_rewrites() {
        $stored = get_option('bss_bc_org_rewrite_version');
        $needed = '1.4';

        if ($stored === $needed) {
            return;
        }

        self::register_rewrite_rules();
        flush_rewrite_rules(false);
        update_option('bss_bc_org_rewrite_version', $needed);
    }
}
