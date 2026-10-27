<?php
/**
 * Organization meta and organization-based permalink prefixes for person_details.
 *
 * default      → /id/{slug}
 * hia          → /hia/{slug}
 * hopevillage  → /hopevillage/{slug}
 * sol          → /sol/{slug}
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSS_Business_Contact_Organization {

    const META_KEY = 'bss_organization';
    const QUERY_VAR = 'bss_org_prefix';
    const NONCE_ACTION = 'bss_bc_save_organization';
    const NONCE_NAME = 'bss_bc_organization_nonce';

    /**
     * Organization value => URL path prefix.
     *
     * @return array<string,string>
     */
    public static function get_organizations() {
        return [
            'default'     => 'id',
            'hia'         => 'hia',
            'hopevillage' => 'hopevillage',
            'sol'         => 'sol',
        ];
    }

    /**
     * Labels for the admin dropdown.
     *
     * @return array<string,string>
     */
    public static function get_organization_labels() {
        return [
            'default'     => __('Default (BSS)', 'bss-business-contact'),
            'hia'         => __('HIA', 'bss-business-contact'),
            'hopevillage' => __('Hope Village', 'bss-business-contact'),
            'sol'         => __('SOL', 'bss-business-contact'),
        ];
    }

    public static function init() {
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
    }

    /**
     * Extra rewrite rules for non-default organization prefixes.
     * Default /id/{slug} comes from the CPT rewrite slug.
     */
    public static function register_rewrite_rules() {
        $prefixes = array_unique(array_values(self::get_organizations()));

        foreach ($prefixes as $prefix) {
            if ($prefix === 'id') {
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
     * @return string Organization key (default|hia|hopevillage|sol)
     */
    public static function get_organization($post_id) {
        $org = get_post_meta($post_id, self::META_KEY, true);
        $allowed = array_keys(self::get_organizations());

        if (!in_array($org, $allowed, true)) {
            return 'default';
        }

        return $org;
    }

    /**
     * @param string $organization
     * @return string URL path segment
     */
    public static function get_prefix_for_organization($organization) {
        $map = self::get_organizations();
        return isset($map[$organization]) ? $map[$organization] : 'id';
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
        return 'default';
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
        return in_array($value, $allowed, true) ? $value : 'default';
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
                : 'default';

            update_post_meta($post_id, self::META_KEY, $org);
            return;
        }

        // Ensure existing cards always have a value.
        if (get_post_meta($post_id, self::META_KEY, true) === '') {
            update_post_meta($post_id, self::META_KEY, 'default');
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
        $prefix = self::get_prefix_for_organization($org);
        $label  = isset($labels[$org]) ? $labels[$org] : $org;

        $label = $org === 'default' ? 'BSS' : $label;
        echo esc_html($label);
        // echo '<br><code>/' . esc_html($prefix) . '/</code>';
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
     * One-time rewrite flush after this feature is deployed.
     */
    public static function maybe_flush_rewrites() {
        $stored = get_option('bss_bc_org_rewrite_version');
        $needed = '1.2';

        if ($stored === $needed) {
            return;
        }

        self::register_rewrite_rules();
        flush_rewrite_rules(false);
        update_option('bss_bc_org_rewrite_version', $needed);
    }
}
