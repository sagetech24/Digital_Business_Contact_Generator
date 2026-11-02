<?php
/**
 * Plugin Name: Digital Business Contact Generator
 * Description: Create digital business contact using Digital Business Contact template
 * Version: 1.4
 * Author: Marnelle Apat
 */

require_once plugin_dir_path(__FILE__) . 'includes/class-bss-business-contact-import-export.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bss-business-contact-organization.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bss-business-contact-card-template.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-bss-business-contact-customizer.php';

BSS_Business_Contact_Import_Export::init();
BSS_Business_Contact_Organization::init();
BSS_Business_Contact_Card_Template::init();
BSS_Business_Contact_Customizer::init();

add_action('init', 'custom_contact_register_post_type');

/**
 * Default block-editor template for new person_details posts.
 *
 * @return array
 */
function bss_bc_person_details_block_template() {
    $label = static function ($text) {
        return [
            'core/paragraph',
            [
                'content'   => $text,
                'align'     => 'center',
                'className' => 'editor-only',
                'style'     => [
                    'typography' => [
                        'fontSize'   => '20px',
                        'fontWeight' => 'bold',
                    ],
                    'color' => [
                        'text' => '#0A0909',
                    ],
                ],
            ],
        ];
    };

    $field = static function ($placeholder) {
        return [
            'core/paragraph',
            [
                'content'     => '',
                'placeholder' => $placeholder,
                'className'   => 'editor-only-border',
                'style'       => [
                    'border' => [
                        'width' => '1px',
                        'style' => 'solid',
                        'color' => 'black',
                    ],
                ],
            ],
        ];
    };

    return [
        [
            'core/group',
            [],
            [
                $label('Insert Profile Picture'),
                // Minimal attrs only. Empty image must save as <figure><img alt=""/></figure>
                // (this WP version does not use a self-closing empty image).
                [
                    'core/image',
                    [
                        'align' => 'center',
                    ],
                ],
                $label('Position/Designation'),
                $field('Enter position or designation...'),
                $label('Quote'),
                $field('Enter quote here...'),
                $label('Insert QR'),
                [
                    'core/shortcode',
                    [
                        'text' => '[qr-code-contact-information-card card_id="42"]',
                    ],
                ],
                $label('QRCode VCF file'),
                $field('Enter vCard (.vcf) file URL here'),
                $label('Phone Number'),
                $field('Enter phone number'),
                $label('Email Address'),
                $field('Enter email address'),
            ],
        ],
    ];
}

function custom_contact_register_post_type() {
    register_post_type('person_details', array(
        'public' => true,
        'show_in_menu' => true,
        'show_in_rest' => true, // required for block editor + CPT template
        'has_archive' => true,
        'rewrite' => ['slug' => 'id', 'with_front' => false], //custom url
        'labels' => array(
            'name' => __('Digital Card Generator'),
            'singular_name' => __('Person'),
            'add_new' => __('Add New Person'),
            'add_new_item' => __('Add New Person'),
            'edit_item' => __('Edit Person'),
            'new_item' => __('New Person'),
            'view_item' => __('View Person'),
            'search_items' => __('Search People'),
            'not_found' => __('No people found'),
            'not_found_in_trash' => __('No people found in Trash'),
            'all_items' => __('All People'),
            'archives' => __('Person Archives'),
            'insert_into_item' => __('Insert into person'),
            'uploaded_to_this_item' => __('Uploaded to this person'),
            'featured_image' => __('Profile Image'),
            'set_featured_image' => __('Set profile image'),
            'remove_featured_image' => __('Remove profile image'),
            'use_featured_image' => __('Use as profile image'),
            'menu_name' => __('Digital Card Generator'),
            'name_admin_bar' => __('Person'),
        ),
        'supports' => array('title', 'editor', 'custom-fields'),
        'template_lock' => 'all',
        'template' => bss_bc_person_details_block_template(),
    ));
}

/**
 * Always use the block editor for person_details so the CPT template appears.
 * Theme (act-mini) and Classic Editor can otherwise force an empty classic editor.
 */
add_filter('use_block_editor_for_post_type', 'bss_bc_force_block_editor_for_person', 100, 2);
add_filter('use_block_editor_for_post', 'bss_bc_force_block_editor_for_person_post', 100, 2);
add_filter('gutenberg_can_edit_post_type', 'bss_bc_force_gutenberg_for_person_type', 100, 2);
add_filter('gutenberg_can_edit_post', 'bss_bc_force_gutenberg_for_person_post', 100, 2);
add_filter('classic_editor_enabled_editors_for_post_type', 'bss_bc_classic_editor_person_editors', 100, 2);

function bss_bc_force_block_editor_for_person($use, $post_type) {
    if ($post_type === 'person_details') {
        return true;
    }
    return $use;
}

function bss_bc_force_block_editor_for_person_post($use, $post) {
    if ($post instanceof WP_Post && $post->post_type === 'person_details') {
        return true;
    }
    return $use;
}

function bss_bc_force_gutenberg_for_person_type($can_edit, $post_type = '') {
    if ($post_type === 'person_details') {
        return true;
    }
    return $can_edit;
}

function bss_bc_force_gutenberg_for_person_post($can_edit, $post = null) {
    if ($post instanceof WP_Post && $post->post_type === 'person_details') {
        return true;
    }
    return $can_edit;
}

function bss_bc_classic_editor_person_editors($editors, $post_type) {
    if ($post_type === 'person_details') {
        return [
            'block'   => true,
            'classic' => false,
        ];
    }
    return $editors;
}

/**
 * Ensure CPT args keep the block template even if another plugin alters registration.
 *
 * @param array  $args
 * @param string $post_type
 * @return array
 */
add_filter('register_post_type_args', 'bss_bc_ensure_person_details_template_args', 20, 2);
function bss_bc_ensure_person_details_template_args($args, $post_type) {
    if ($post_type !== 'person_details') {
        return $args;
    }

    $args['show_in_rest']  = true;
    $args['template']      = bss_bc_person_details_block_template();
    $args['template_lock'] = 'all';
    if (empty($args['supports']) || !is_array($args['supports'])) {
        $args['supports'] = ['title', 'editor', 'custom-fields'];
    } elseif (!in_array('editor', $args['supports'], true)) {
        $args['supports'][] = 'editor';
    }

    return $args;
}

/**
 * Serialized block markup matching bss_bc_person_details_block_template().
 * Used as default_content so new Persons are never an empty editor.
 *
 * @return string
 */
function bss_bc_person_details_default_block_content() {
    $label = static function ($text) {
        return '<!-- wp:paragraph {"align":"center","className":"editor-only","style":{"typography":{"fontSize":"20px","fontWeight":"bold"},"color":{"text":"#0A0909"}}} -->'
            . '<p class="has-text-align-center editor-only has-text-color" style="color:#0A0909;font-size:20px;font-weight:bold">'
            . esc_html($text)
            . '</p>'
            . '<!-- /wp:paragraph -->';
    };

    $field = static function ($placeholder) {
        return '<!-- wp:paragraph {"placeholder":'
            . wp_json_encode($placeholder)
            . ',"className":"editor-only-border","style":{"border":{"width":"1px","style":"solid","color":"black"}}} -->'
            . '<p class="editor-only-border"></p>'
            . '<!-- /wp:paragraph -->';
    };

    $parts = [
        '<!-- wp:group -->',
        '<div class="wp-block-group">',
        $label('Insert Profile Picture'),
        bss_bc_empty_profile_image_block_markup(),
        $label('Position/Designation'),
        $field('Enter position or designation...'),
        $label('Quote'),
        $field('Enter quote here...'),
        $label('Insert QR'),
        '<!-- wp:shortcode -->',
        '[qr-code-contact-information-card card_id="42"]',
        '<!-- /wp:shortcode -->',
        $label('QRCode VCF file'),
        $field('Enter vCard (.vcf) file URL here'),
        $label('Phone Number'),
        $field('Enter phone number'),
        $label('Email Address'),
        $field('Enter email address'),
        '</div>',
        '<!-- /wp:group -->',
    ];

    return implode("\n", $parts);
}

add_filter('default_content', 'bss_bc_person_details_default_content', 10, 2);
function bss_bc_person_details_default_content($content, $post) {
    if (!($post instanceof WP_Post) || $post->post_type !== 'person_details') {
        return $content;
    }

    if (is_string($content) && trim($content) !== '') {
        return $content;
    }

    return bss_bc_person_details_default_block_content();
}

/**
 * Prefill auto-draft Persons created on "Add New" so the template is present immediately.
 *
 * @param int     $post_id
 * @param WP_Post $post
 * @param bool    $update
 */
/**
 * Canonical empty profile Image block markup for this WP version.
 *
 * @return string
 */
function bss_bc_empty_profile_image_block_markup() {
    return "<!-- wp:image {\"align\":\"center\"} -->\n"
        . '<figure class="wp-block-image aligncenter"><img alt=""/></figure>' . "\n"
        . '<!-- /wp:image -->';
}

/**
 * Repair empty/invalid profile Image blocks that trigger "Attempt Block Recovery".
 *
 * This WordPress build always saves empty images as <figure><img alt=""/></figure>.
 * Self-closing image comments or empty figures without <img> are invalid.
 *
 * @param string $content
 * @return string
 */
function bss_bc_repair_empty_profile_image_blocks($content) {
    if (!is_string($content) || $content === '') {
        return $content;
    }

    $valid = bss_bc_empty_profile_image_block_markup();

    // Self-closing empty image comments.
    $content = preg_replace(
        '/<!--\s*wp:image(\s+\{.*?\})?\s*\/-->/is',
        $valid,
        $content
    );

    // Open/close image blocks with no real image src.
    $content = preg_replace_callback(
        '/<!--\s*wp:image(\s+\{.*?\})?\s*-->(.*?)<!--\s*\/wp:image\s*-->/is',
        static function ($m) use ($valid) {
            $inner = isset($m[2]) ? $m[2] : '';

            // Keep blocks that already have a real uploaded image.
            if (preg_match('/<img[^>]+src=["\'][^"\']+["\']/i', $inner)) {
                return $m[0];
            }

            return $valid;
        },
        $content
    );

    return is_string($content) ? $content : '';
}

add_filter('content_edit_pre', 'bss_bc_repair_person_details_image_on_edit', 10, 2);
function bss_bc_repair_person_details_image_on_edit($content, $post_id) {
    $post = get_post($post_id);
    if (!($post instanceof WP_Post) || $post->post_type !== 'person_details') {
        return $content;
    }
    return bss_bc_repair_empty_profile_image_blocks($content);
}

add_action('wp_insert_post', 'bss_bc_seed_person_details_auto_draft', 20, 3);
function bss_bc_seed_person_details_auto_draft($post_id, $post, $update) {
    if ($update || !($post instanceof WP_Post) || $post->post_type !== 'person_details') {
        return;
    }

    if ($post->post_status !== 'auto-draft') {
        return;
    }

    $content = trim((string) $post->post_content);
    $seed    = bss_bc_person_details_default_block_content();

    if ($content !== '' && $content === bss_bc_repair_empty_profile_image_blocks($content)) {
        return;
    }

    if ($content === '') {
        $new_content = $seed;
    } else {
        $new_content = bss_bc_repair_empty_profile_image_blocks($content);
        if ($new_content === $content) {
            return;
        }
    }

    // Avoid recursion.
    remove_action('wp_insert_post', 'bss_bc_seed_person_details_auto_draft', 20);
    wp_update_post([
        'ID'           => (int) $post_id,
        'post_content' => $new_content,
    ]);
    add_action('wp_insert_post', 'bss_bc_seed_person_details_auto_draft', 20, 3);
}

add_filter('enter_title_here', 'custom_change_title_placeholder', 20);
function custom_change_title_placeholder($title) {
    $screen = get_current_screen();
    if ($screen->post_type == 'person_details') {
        $title = 'Enter Name';
    }
    return $title;
}

// Change the label in the admin table (e.g., All Persons list)
add_filter('manage_person_details_posts_columns', 'custom_change_title_column_label');
function custom_change_title_column_label($columns) {
    if (isset($columns['title'])) {
        $columns['title'] = 'Full Name';
    }
    return $columns;
}

add_action('wp_head', function() {
    if (is_singular('person_details')) {
        $bg_url = get_theme_mod('person_bg_image');
        if ($bg_url) {
            echo "<style>
                body {
                    background-image: url('$bg_url');
                    background-size: cover;
                    background-repeat: no-repeat;
                    background-position: center;
                }
            </style>";
        }
    }
});

add_filter('single_template', 'bss_custom_single_template');

/**
 * Load the PHP template file mapped to this person's organisation via card_template.
 */
function bss_custom_single_template($single_template) {
    if (!is_singular('person_details')) {
        return $single_template;
    }

    $person_id = get_the_ID();
    if (!$person_id) {
        return $single_template;
    }

    $custom_template = BSS_Business_Contact_Card_Template::get_template_path_for_person($person_id);
    if ($custom_template !== '' && file_exists($custom_template)) {
        return $custom_template;
    }

    return $single_template;
}

add_action('save_post_person_details', function($post_id) {
    if (get_post_meta($post_id, 'template_variant', true) === '') {
        update_post_meta($post_id, 'template_variant', 'BSS');
    }
});

function my_custom_template_enqueue_styles() {
    if (!is_singular('person_details')) {
        return;
    }

    $person_id = get_the_ID();
    if (!$person_id) {
        return;
    }

    // Enqueue base CSS whenever an org-mapped card template file is in use.
    $path = BSS_Business_Contact_Card_Template::get_template_path_for_person($person_id);
    if ($path !== '') {
        wp_enqueue_style(
            'my-custom-template-style-bss',
            plugin_dir_url(__FILE__) . 'css/style.css',
            array(),
            '1.3'
        );
    }
}

add_filter('redirect_canonical', 'disable_redirect_for_person_details', 10, 2);
function disable_redirect_for_person_details($redirect_url, $requested_url) {
    if (is_singular('person_details')) {
        return false; // disable canonical redirect
    }
    return $redirect_url;
}

register_activation_hook(__FILE__, 'bss_rewrite_flush');
function bss_rewrite_flush() {
    // Register post types and organization rewrite rules before flushing.
    custom_contact_register_post_type();
    BSS_Business_Contact_Card_Template::register_post_type();
    BSS_Business_Contact_Organization::register_rewrite_rules();
    BSS_Business_Contact_Card_Template::seed_defaults();
    BSS_Business_Contact_Card_Template::ensure_design_settings();
    flush_rewrite_rules();
    update_option('bss_bc_org_rewrite_version', '1.4');
    BSS_Business_Contact_Organization::maybe_seed_custom_organizations();
}


//add_action('wp_enqueue_scripts', 'my_custom_template_enqueue_styles');

//add_filter('template_include', 'custom_person_details_template');
//
//function custom_person_details_template($template) {
//    if (is_singular('person_details')) {
//        return plugin_dir_path(__FILE__) . 'templates/single-person_details-custom.php';
//    }
//    return $template;
//}