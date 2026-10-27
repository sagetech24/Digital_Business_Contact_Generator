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

function custom_contact_register_post_type() {
    register_post_type('person_details', array(
        'public' => true,
        'show_in_menu' => true,
        'show_in_rest' => true, // needed for block editor
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
        'supports' => array('title', 'editor'),
        'template_lock' => 'all',
        'template' => array(
            array('core/group', array(), array(
                array('core/paragraph', array(
                    'content' => 'Insert Profile Picture',
                    'align' => 'center',
                    'className' => 'editor-only',
                    'style' => array(
                        'typography' => array(
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ),
                        'color' => array(
                            'text' => '#0A0909',
                        ),
                    ),
                )),
                array('core/image', array(
                    'align' => 'center',
                    'sizeSlug' => 'thumbnail',
                    'style' => array(
                        'border' => array(
                            'width' => '5px',
                            'style' => 'solid',
                            'color' => 'black',
                            'radius' => '100px'
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'content' => 'Position/Designation',
                    'align' => 'center',
                    'className' => 'editor-only',
                    'style' => array(
                        'typography' => array(
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ),
                        'color' => array(
                            'text' => '#0A0909',
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'placeholder' => 'Enter position or designation...',
                    'className' => 'editor-only-border',
                    'style' => array(
                        'border' => array(
                            'width' => '1px',
                            'style' => 'solid',
                            'color' => 'black'
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'content' => 'Quote',
                    'align' => 'center',
                    'className' => 'editor-only',
                    'style' => array(
                        'typography' => array(
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ),
                        'color' => array(
                            'text' => '#0A0909',
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'placeholder' => 'Enter quote here...',
                    'className' => 'editor-only-border',
                    'style' => array(
                        'border' => array(
                            'width' => '1px',
                            'style' => 'solid',
                            'color' => 'black'
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'content' => 'Insert QR',
                    'align' => 'center',
                    'className' => 'editor-only',
                    'style' => array(
                        'typography' => array(
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ),
                        'color' => array(
                            'text' => '#0A0909',
                        ),
                    )
                )),
                array('core/shortcode', array(
                    'placeholder' => '[qr-code-contact-information-card card_id="42"]'
                )),
                array('core/paragraph', array(
                    'content' => 'QRCode VCF file',
                    'align' => 'center',
                    'className' => 'editor-only',
                    'style' => array(
                        'typography' => array(
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ),
                        'color' => array(
                            'text' => '#0A0909',
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'placeholder' => 'Enter vCard (.vcf) file URL here',
                    'style' => array(
                        'border' => array(
                            'width' => '1px',
                            'style' => 'solid',
                            'color' => 'black'
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'content' => 'Phone Number',
                    'align' => 'center',
                    'className' => 'editor-only',
                    'style' => array(
                        'typography' => array(
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ),
                        'color' => array(
                            'text' => '#0A0909',
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'placeholder' => 'Enter phone number',
                    'className' => 'editor-only-border',
                    'style' => array(
                        'border' => array(
                            'width' => '1px',
                            'style' => 'solid',
                            'color' => 'black'
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'content' => 'Email Address',
                    'align' => 'center',
                    'className' => 'editor-only',
                    'style' => array(
                        'typography' => array(
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ),
                        'color' => array(
                            'text' => '#0A0909',
                        ),
                    )
                )),
                array('core/paragraph', array(
                    'placeholder' => 'Enter email address',
                    'className' => 'editor-only-border',
                    'style' => array(
                        'border' => array(
                            'width' => '1px',
                            'style' => 'solid',
                            'color' => 'black'
                        ),
                    )
                ))
            )),
        ),
    ));
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
    update_option('bss_bc_org_rewrite_version', '1.3');
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