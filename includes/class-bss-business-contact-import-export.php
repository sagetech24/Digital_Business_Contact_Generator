<?php
/**
 * Import / Export business cards as JSON for site-to-site migration.
 */

if (!defined('ABSPATH')) {
    exit;
}

class BSS_Business_Contact_Import_Export {

    const MENU_SLUG = 'bss-business-cards-import-export';
    const NONCE_ACTION = 'bss_bc_import_export';
    const EXPORT_ACTION = 'bss_bc_export_cards';
    const IMPORT_ACTION = 'bss_bc_import_cards';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_post_' . self::EXPORT_ACTION, [__CLASS__, 'handle_export']);
        add_action('admin_post_' . self::IMPORT_ACTION, [__CLASS__, 'handle_import']);
        add_action('admin_notices', [__CLASS__, 'render_admin_notices']);
    }

    public static function register_menu() {
        add_submenu_page(
            'edit.php?post_type=person_details',
            __('Import / Export', 'bss-business-contact'),
            __('Import / Export', 'bss-business-contact'),
            'edit_posts',
            self::MENU_SLUG,
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'bss-business-contact'));
        }

        $count = (int) wp_count_posts('person_details')->publish
            + (int) wp_count_posts('person_details')->draft
            + (int) wp_count_posts('person_details')->pending
            + (int) wp_count_posts('person_details')->private;

        include plugin_dir_path(dirname(__FILE__)) . 'admin/import-export-page.php';
    }

    public static function handle_export() {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('You do not have permission to export.', 'bss-business-contact'));
        }

        check_admin_referer(self::NONCE_ACTION);

        $payload = self::build_export_payload();
        $filename = 'bss-business-cards-' . gmdate('Y-m-d-His') . '.json';
        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            wp_die(esc_html__('Failed to encode export data.', 'bss-business-contact'));
        }

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    public static function handle_import() {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('You do not have permission to import.', 'bss-business-contact'));
        }

        check_admin_referer(self::NONCE_ACTION);

        $redirect = admin_url('edit.php?post_type=person_details&page=' . self::MENU_SLUG);

        if (empty($_FILES['bss_bc_import_file']['tmp_name'])) {
            self::set_notice('error', __('Please choose a JSON file to import.', 'bss-business-contact'));
            wp_safe_redirect($redirect);
            exit;
        }

        $file = $_FILES['bss_bc_import_file'];
        $name = isset($file['name']) ? (string) $file['name'] : '';
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($ext !== 'json') {
            self::set_notice('error', __('Only .json files are accepted.', 'bss-business-contact'));
            wp_safe_redirect($redirect);
            exit;
        }

        if (!empty($file['error'])) {
            self::set_notice('error', __('Upload failed. Please try again.', 'bss-business-contact'));
            wp_safe_redirect($redirect);
            exit;
        }

        $raw = file_get_contents($file['tmp_name']);
        if ($raw === false || $raw === '') {
            self::set_notice('error', __('Could not read the uploaded file.', 'bss-business-contact'));
            wp_safe_redirect($redirect);
            exit;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
            self::set_notice('error', __('Invalid export file. Expected a BSS Business Cards JSON export.', 'bss-business-contact'));
            wp_safe_redirect($redirect);
            exit;
        }

        $upsert_by_slug = !empty($_POST['bss_bc_upsert_by_slug']);
        $sideload_media = !empty($_POST['bss_bc_sideload_media']);
        $source_site_url = !empty($data['site_url']) ? untrailingslashit($data['site_url']) : '';

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = [];
        $media_rewrites = 0;

        foreach ($data['items'] as $index => $item) {
            if (!is_array($item)) {
                $skipped++;
                continue;
            }

            $title  = isset($item['title']) ? sanitize_text_field($item['title']) : '';
            $slug   = isset($item['slug']) ? sanitize_title($item['slug']) : sanitize_title($title);
            $status = isset($item['status']) ? sanitize_key($item['status']) : 'publish';
            $content = isset($item['content']) ? (string) $item['content'] : '';
            $variant = isset($item['template_variant']) && $item['template_variant'] !== ''
                ? sanitize_text_field($item['template_variant'])
                : 'BSS';
            $organization = isset($item['organization'])
                ? BSS_Business_Contact_Organization::sanitize_organization($item['organization'])
                : 'default';

            $allowed_status = ['publish', 'draft', 'pending', 'private'];
            if (!in_array($status, $allowed_status, true)) {
                $status = 'draft';
            }

            if ($title === '' && $content === '') {
                $skipped++;
                $errors[] = sprintf(
                    /* translators: %d: row index */
                    __('Item #%d skipped: missing title and content.', 'bss-business-contact'),
                    $index + 1
                );
                continue;
            }

            if ($sideload_media && $content !== '') {
                $result = self::sideload_and_rewrite_media($content, $source_site_url);
                $content = $result['content'];
                $media_rewrites += $result['rewritten'];
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }
            }

            $postarr = [
                'post_type'    => 'person_details',
                'post_title'   => $title !== '' ? $title : __('Untitled Person', 'bss-business-contact'),
                'post_name'    => $slug,
                'post_status'  => $status,
                'post_content' => $content,
            ];

            $existing_id = 0;
            if ($upsert_by_slug && $slug !== '') {
                $existing = get_page_by_path($slug, OBJECT, 'person_details');
                if ($existing instanceof WP_Post) {
                    $existing_id = (int) $existing->ID;
                }
            }

            if ($existing_id > 0) {
                $postarr['ID'] = $existing_id;
                $post_id = wp_update_post($postarr, true);
                if (is_wp_error($post_id)) {
                    $skipped++;
                    $errors[] = sprintf(
                        /* translators: 1: title, 2: error message */
                        __('Failed to update "%1$s": %2$s', 'bss-business-contact'),
                        $title,
                        $post_id->get_error_message()
                    );
                    continue;
                }
                $updated++;
            } else {
                $post_id = wp_insert_post($postarr, true);
                if (is_wp_error($post_id)) {
                    $skipped++;
                    $errors[] = sprintf(
                        /* translators: 1: title, 2: error message */
                        __('Failed to create "%1$s": %2$s', 'bss-business-contact'),
                        $title,
                        $post_id->get_error_message()
                    );
                    continue;
                }
                $created++;
            }

            update_post_meta($post_id, 'template_variant', $variant);
            update_post_meta($post_id, BSS_Business_Contact_Organization::META_KEY, $organization);
        }

        $message = sprintf(
            /* translators: 1: created count, 2: updated count, 3: skipped count, 4: media rewrite count */
            __('Import finished. Created: %1$d, Updated: %2$d, Skipped: %3$d, Media URLs rewritten: %4$d.', 'bss-business-contact'),
            $created,
            $updated,
            $skipped,
            $media_rewrites
        );

        if (!empty($errors)) {
            $message .= ' ' . __('Some items reported issues:', 'bss-business-contact') . ' ' . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= ' ' . sprintf(
                    /* translators: %d: remaining error count */
                    __('(+%d more)', 'bss-business-contact'),
                    count($errors) - 5
                );
            }
            self::set_notice('warning', $message);
        } else {
            self::set_notice('success', $message);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Build export payload for all person_details posts.
     *
     * @return array
     */
    public static function build_export_payload() {
        $posts = get_posts([
            'post_type'      => 'person_details',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ]);

        $items = [];
        foreach ($posts as $post) {
            $items[] = [
                'source_id'         => (int) $post->ID,
                'title'             => $post->post_title,
                'slug'              => $post->post_name,
                'status'            => $post->post_status,
                'organization'      => BSS_Business_Contact_Organization::get_organization($post->ID),
                'template_variant'  => get_post_meta($post->ID, 'template_variant', true) ?: 'BSS',
                'content'           => $post->post_content,
                'media_urls'        => self::extract_media_urls($post->post_content),
            ];
        }

        return [
            'plugin'      => 'BSS_Business_Contact',
            'version'     => '1.2',
            'exported_at' => gmdate('c'),
            'site_url'    => untrailingslashit(home_url()),
            'item_count'  => count($items),
            'items'       => $items,
            'notes'       => [
                'QR shortcodes use card_id from the WP QR Code Me vCard plugin. Re-link or remigrate those cards on the destination site if QR codes break.',
                'Organization controls the public URL prefix: default=/id/, hia=/hia/, hopevillage=/hopevillage/, sol=/sol/.',
            ],
        ];
    }

    /**
     * Extract http(s) media-like URLs from post content.
     *
     * @param string $content
     * @return string[]
     */
    public static function extract_media_urls($content) {
        if ($content === '') {
            return [];
        }

        preg_match_all('#https?://[^\s"\'<>]+#i', $content, $matches);
        if (empty($matches[0])) {
            return [];
        }

        $urls = [];
        foreach ($matches[0] as $url) {
            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
            $url = rtrim($url, '.,);]');
            if (self::is_sideloadable_url($url)) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param string $url
     * @return bool
     */
    private static function is_sideloadable_url($url) {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'vcf', 'vcard'];
        return in_array($ext, $allowed, true);
    }

    /**
     * Download media from content URLs into the Media Library and rewrite content.
     *
     * @param string $content
     * @param string $source_site_url
     * @return array{content:string,rewritten:int,errors:string[]}
     */
    private static function sideload_and_rewrite_media($content, $source_site_url = '') {
        $urls = self::extract_media_urls($content);
        $rewritten = 0;
        $errors = [];
        $map = [];

        foreach ($urls as $url) {
            if (isset($map[$url])) {
                continue;
            }

            // Skip URLs already on this site.
            $current = untrailingslashit(home_url());
            if (strpos($url, $current) === 0) {
                $map[$url] = $url;
                continue;
            }

            $new_url = self::sideload_url($url);
            if (is_wp_error($new_url)) {
                $errors[] = sprintf(
                    /* translators: 1: url, 2: error */
                    __('Media sideload failed for %1$s: %2$s', 'bss-business-contact'),
                    $url,
                    $new_url->get_error_message()
                );
                $map[$url] = $url;
                continue;
            }

            $map[$url] = $new_url;
            $rewritten++;
        }

        foreach ($map as $old => $new) {
            if ($old !== $new) {
                $content = str_replace($old, $new, $content);
            }
        }

        // Optional: rewrite remaining uploads paths from old site root to new site
        // when domain differs but relative upload paths were not caught above.
        if ($source_site_url && $source_site_url !== untrailingslashit(home_url())) {
            // Intentionally no blanket domain rewrite for non-media URLs.
        }

        return [
            'content'   => $content,
            'rewritten' => $rewritten,
            'errors'    => $errors,
        ];
    }

    /**
     * @param string $url
     * @return string|WP_Error New attachment URL
     */
    private static function sideload_url($url) {
        $tmp = download_url($url, 30);
        if (is_wp_error($tmp)) {
            return $tmp;
        }

        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $filename = $path ? basename($path) : 'imported-file';
        if ($filename === '' || $filename === '/') {
            $filename = 'imported-file';
        }

        $file_array = [
            'name'     => sanitize_file_name($filename),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, 0);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return $attachment_id;
        }

        $new_url = wp_get_attachment_url($attachment_id);
        if (!$new_url) {
            return new WP_Error('bss_bc_no_url', __('Attachment created but URL missing.', 'bss-business-contact'));
        }

        return $new_url;
    }

    /**
     * @param string $type success|error|warning
     * @param string $message
     */
    private static function set_notice($type, $message) {
        set_transient(
            'bss_bc_import_export_notice_' . get_current_user_id(),
            [
                'type'    => $type,
                'message' => $message,
            ],
            60
        );
    }

    public static function render_admin_notices() {
        if (!isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG) {
            return;
        }

        $notice = get_transient('bss_bc_import_export_notice_' . get_current_user_id());
        if (!$notice || empty($notice['message'])) {
            return;
        }

        delete_transient('bss_bc_import_export_notice_' . get_current_user_id());

        $class = 'notice-info';
        if ($notice['type'] === 'success') {
            $class = 'notice-success';
        } elseif ($notice['type'] === 'error') {
            $class = 'notice-error';
        } elseif ($notice['type'] === 'warning') {
            $class = 'notice-warning';
        }

        printf(
            '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($class),
            esc_html($notice['message'])
        );
    }
}
