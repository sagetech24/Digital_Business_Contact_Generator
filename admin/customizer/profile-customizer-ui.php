<?php
/**
 * Profile Customizer admin UI (loaded by BSS_Business_Contact_Customizer).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_bss_pc_save_design', 'bss_pc_handle_save_design');
add_action('admin_post_bss_pc_reset_design', 'bss_pc_handle_reset_design');

function bss_pc_handle_save_design() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Forbidden', 'bss-business-contact'));
    }
    check_admin_referer('bss_pc_save_design', 'bss_pc_nonce');

    $org  = isset($_POST['bss_pc_org'])
        ? BSS_Business_Contact_Organization::sanitize_organization(wp_unslash($_POST['bss_pc_org']))
        : 'default';
    $post = BSS_Business_Contact_Card_Template::get_by_organization($org);

    if (!($post instanceof WP_Post)) {
        wp_die(esc_html__('No card template found for this organisation. Open Card Templates to seed defaults.', 'bss-business-contact'));
    }

    $design = bss_pc_design_from_post($_POST);
    BSS_Business_Contact_Card_Template::update_design($post->ID, $design);

    wp_safe_redirect(add_query_arg('updated', '1', bss_pc_admin_url('customizer', $org)));
    exit;
}

function bss_pc_handle_reset_design() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Forbidden', 'bss-business-contact'));
    }
    check_admin_referer('bss_pc_reset_design', 'bss_pc_reset_nonce');

    $org  = isset($_POST['bss_pc_org'])
        ? BSS_Business_Contact_Organization::sanitize_organization(wp_unslash($_POST['bss_pc_org']))
        : 'default';
    $post = BSS_Business_Contact_Card_Template::get_by_organization($org);

    if (!($post instanceof WP_Post)) {
        wp_die(esc_html__('No card template found for this organisation.', 'bss-business-contact'));
    }

    $defaults = get_option('profile_customizer_defaults', []);
    if (!is_array($defaults) || empty($defaults)) {
        $defaults = BSS_Business_Contact_Card_Template::get_design_defaults();
    } else {
        $defaults = array_merge(
            BSS_Business_Contact_Card_Template::get_design_defaults(),
            array_intersect_key($defaults, BSS_Business_Contact_Card_Template::get_design_defaults())
        );
    }

    BSS_Business_Contact_Card_Template::update_design($post->ID, $defaults);

    wp_safe_redirect(add_query_arg('reset', '1', bss_pc_admin_url('customizer', $org)));
    exit;
}

function render_profile_customizer() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'customizer';
    $org        = bss_pc_get_editing_org();

    BSS_Business_Contact_Card_Template::ensure_design_settings();

    $template_post = BSS_Business_Contact_Card_Template::get_by_organization($org);
    $GLOBALS['bss_pc_editing_design'] = $template_post instanceof WP_Post
        ? BSS_Business_Contact_Card_Template::get_design($template_post->ID)
        : BSS_Business_Contact_Card_Template::get_design_defaults();

    $labels = BSS_Business_Contact_Organization::get_organization_labels();
    $file   = $template_post instanceof WP_Post
        ? BSS_Business_Contact_Card_Template::get_template_file($template_post->ID)
        : '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Profile Customizer', 'bss-business-contact'); ?></h1>

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Design saved for this organisation.', 'bss-business-contact'); ?></p></div>
        <?php endif; ?>
        <?php if (isset($_GET['reset'])) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Design reset to defaults for this organisation.', 'bss-business-contact'); ?></p></div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper profile-customizer-tabs">
            <a href="<?php echo esc_url(bss_pc_admin_url('customizer', $org)); ?>" class="nav-tab <?php echo $active_tab == 'customizer' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Customizer', 'bss-business-contact'); ?></a>
            <a href="<?php echo esc_url(bss_pc_admin_url('help', $org)); ?>" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Help', 'bss-business-contact'); ?></a>
            <a href="<?php echo esc_url(bss_pc_admin_url('advanced', $org)); ?>" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Advanced', 'bss-business-contact'); ?></a>
        </h2>

        <?php if ($active_tab === 'customizer') : ?>
            <form method="get" action="<?php echo esc_url(admin_url('edit.php')); ?>" style="margin:16px 0;">
                <input type="hidden" name="post_type" value="person_details">
                <input type="hidden" name="page" value="bss-profile-customizer">
                <input type="hidden" name="tab" value="customizer">
                <label for="bss_pc_org_select"><strong><?php esc_html_e('Organisation / card template', 'bss-business-contact'); ?></strong></label>
                <select name="org" id="bss_pc_org_select" onchange="this.form.submit()" style="margin-left:8px; min-width:220px;">
                    <?php foreach ($labels as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($org, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($file) : ?>
                    <span class="description" style="margin-left:12px;">
                        <?php
                        printf(
                            /* translators: %s: template filename */
                            esc_html__('Template file: %s', 'bss-business-contact'),
                            '<code>' . esc_html($file) . '</code>'
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </form>

            <?php if (!($template_post instanceof WP_Post)) : ?>
                <div class="notice notice-warning"><p>
                    <?php esc_html_e('No card template exists for this organisation yet. Open Card Templates (they seed automatically), then return here.', 'bss-business-contact'); ?>
                </p></div>
            <?php else : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="bss_pc_save_design">
                    <input type="hidden" name="bss_pc_org" value="<?php echo esc_attr($org); ?>">
                    <?php wp_nonce_field('bss_pc_save_design', 'bss_pc_nonce'); ?>
                    <?php do_settings_sections('profile-customizer'); ?>
                    <?php submit_button(__('Save Changes', 'bss-business-contact')); ?>
                </form>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px;">
                    <input type="hidden" name="action" value="bss_pc_reset_design">
                    <input type="hidden" name="bss_pc_org" value="<?php echo esc_attr($org); ?>">
                    <?php wp_nonce_field('bss_pc_reset_design', 'bss_pc_reset_nonce'); ?>
                    <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Reset to Defaults', 'bss-business-contact'); ?>" onclick="return confirm('<?php echo esc_js(__('Reset design for this organisation to Advanced defaults?', 'bss-business-contact')); ?>');">
                </form>
            <?php endif; ?>

            <?php
            if (get_option('profile_customizer_debug_enabled') === 'yes') {
                debug_profile_customizer_output();
            }
            ?>
        <?php elseif ($active_tab === 'help') : ?>
            <?php render_profile_customizer_help(); ?>
        <?php elseif ($active_tab === 'advanced') : ?>
            <?php render_profile_customizer_advanced(); ?>
        <?php endif; ?>
    </div>

    <style>
        .profile-customizer-tabs {
            margin-top: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        .nav-tab {
            border-radius: 5px 5px 0 0;
        }
        .form-table input:not([type='checkbox']):not([type='radio']),
        .form-table textarea,
        .form-table select {
            width: 100px !important;
            max-width: 100%;
        }
        #background_image, #top_logo_image, #bottom_logo_image {
            width: 70% !important;
        }
    </style>
    <?php
}

add_action('admin_init', 'register_profile_customizer_settings');

function register_profile_customizer_settings() {
    register_setting('profile-customizer', 'background_image');
    register_setting('profile-customizer', 'background_size');
    register_setting('profile-customizer', 'media_background_size');
    register_setting('profile-customizer', 'overlay_color');
    register_setting('profile-customizer', 'background_overlay');

    register_setting('profile-customizer', 'top_logo_image');
    register_setting('profile-customizer', 'top_logo_size');
    register_setting('profile-customizer', 'media_top_logo_size');

    register_setting('profile-customizer', 'top_box_color1');
    register_setting('profile-customizer', 'top_box_color2');
    register_setting('profile-customizer', 'top_box_angle');
    register_setting('profile-customizer', 'top_color_overlay');

    register_setting('profile-customizer', 'profile_border_color');
    register_setting('profile-customizer', 'profile_border_width');

    register_setting('profile-customizer', 'button_box_color1');
    register_setting('profile-customizer', 'button_box_color2');
    register_setting('profile-customizer', 'button_box_angle');

    register_setting('profile-customizer', 'footer_box_color1');
    register_setting('profile-customizer', 'footer_box_color2');
    register_setting('profile-customizer', 'footer_box_angle');

    register_setting('profile-customizer', 'box_shadow_offsetx');
    register_setting('profile-customizer', 'box_shadow_offsety');
    register_setting('profile-customizer', 'box_shadow_blur');
    register_setting('profile-customizer', 'box_shadow_color');
    register_setting('profile-customizer', 'box_shadow_transparency');

    register_setting('profile-customizer', 'spacer_width');
    register_setting('profile-customizer', 'spacer_height');
    register_setting('profile-customizer', 'spacer_color');

    register_setting('profile-customizer', 'content_gap');
    register_setting('profile-customizer', 'media_content_gap');

    register_setting('profile-customizer', 'name_font');
    register_setting('profile-customizer', 'name_font_weight');
    register_setting('profile-customizer', 'name_font_size');

    register_setting('profile-customizer', 'designation_font');
    register_setting('profile-customizer', 'designation_font_weight');
    register_setting('profile-customizer', 'designation_font_size');

    register_setting('profile-customizer', 'quote_font');
    register_setting('profile-customizer', 'quote_font_weight');
    register_setting('profile-customizer', 'quote_font_size');

    register_setting('profile-customizer', 'quote_box_color');
    register_setting('profile-customizer', 'quote_box_shadow_offsetx');
    register_setting('profile-customizer', 'quote_box_shadow_offsety');
    register_setting('profile-customizer', 'quote_box_shadow_blur');
    register_setting('profile-customizer', 'quote_box_shadow_color');
    register_setting('profile-customizer', 'quote_box_shadow_transparency');

    register_setting('profile-customizer', 'text_color');

    register_setting('profile-customizer', 'logo_color');

    register_setting('profile-customizer', 'link_color');
    register_setting('profile-customizer', 'link_hover_color');

    register_setting('profile-customizer', 'bottom_logo_image');
    register_setting('profile-customizer', 'bottom_logo_size_width');
    register_setting('profile-customizer', 'bottom_logo_size_height');
    register_setting('profile-customizer', 'media_bottom_logo_size_width');
    register_setting('profile-customizer', 'media_bottom_logo_size_height');

    register_setting('profile-customizer', 'address_link_color');
    register_setting('profile-customizer', 'address_link_hover_color');

    add_settings_section(
        'profile_customizer_section',
        'Customize Profile details',
        null,
        'profile-customizer'
    );

    //Background Image
    add_settings_field(
        'background_image',
        'Background Image',
        function() {
            $image_url = bss_pc_val('background_image', 'http://localhost:8000/wp-content/uploads/2025/05/bible-house-singapore-l090913-7.jpg');
            echo '<input type="text" id="background_image" name="background_image" value="' . esc_attr($image_url) . '" style="width:70%;"> ';
            echo '<input type="button" class="button" id="upload_bg_image_button" value="Upload Image">';
            echo '<div><img id="bg_image_preview" src="' . esc_url($image_url) . '" style="max-width:300px;margin-top:10px;"></div>';
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Background Size
    add_settings_field(
        'background_size',
        'Background Size',
        function() {
            $size = bss_pc_val('background_size', '1600');
            $media_size = bss_pc_val('media_background_size', '1600');
            echo "Desktop Size (px): <input type='number' name='background_size' value='$size'> ";          
            echo "Media Size (px): <input type='number' name='media_background_size' value='$media_size'> ";    
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Background Overlay Color
    add_settings_field(
        'overlay_color',
        'Background Overlay Color',
        function() {
            $color = bss_pc_val('overlay_color', '#E1FFF1');
            echo "Overlay Color: <input type='color' name='overlay_color' value='$color'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Background Overlay
    add_settings_field(
        'background_overlay',
        'Background Overlay (Background Brightness)',
        function() {
            $background_overlay = bss_pc_val('background_overlay', '40%');
            $overlay_percentage = [
                '0%' => [
                    'label' => '0%',
                    'percentage' => '00',
                ],
                '20%' => [
                    'label' => '20%',
                    'percentage' => '33',
                ],
                '40%' => [
                    'label' => '40%',
                    'percentage' => '66',
                ],
                '60%' => [
                    'label' => '60%',
                    'percentage' => '99',
                ],
                '80%' => [
                    'label' => '80%',
                    'percentage' => 'CC',
                ],
                '100%' => [
                    'label' => '100%',
                    'percentage' => 'FF'
                ],
            ];

            echo "<select name='background_overlay'>";
            foreach ($overlay_percentage as $value => $data) {
                $selected = selected($background_overlay, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Top Logo Image
    add_settings_field(
        'top_logo_image',
        'Top Logo Image',
        function() {
            $image_url = bss_pc_val('top_logo_image', 'https://www.biblesociety.sg/wp-content/uploads/2015/05/BSS-Logo-2023-NEW-2B-WHITE-3.png');
            echo '<input type="text" id="top_logo_image" name="top_logo_image" value="' . esc_attr($image_url) . '" style="width:70%;"> ';
            echo '<input type="button" class="button" id="upload_top_logo_image_button" value="Upload Image">';
            echo '<div><img id="top_logo_image_preview" src="' . esc_url($image_url) . '" style="max-width:300px;margin-top:10px;"></div>';
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Top Logo Size
    add_settings_field(
        'top_logo_size',
        'Top Logo Size',
        function() {
            $size = bss_pc_val('top_logo_size', '100');
            $media_size = bss_pc_val('media_top_logo_size', '100');
            echo "Desktop Size (width px): <input type='number' name='top_logo_size' value='$size'> ";          
            echo "Media Size (width px): <input type='number' name='media_top_logo_size' value='$media_size'> ";    
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Top Box Color
    add_settings_field(
        'top_box_color',
        'Top Box Background Color',
        function() {
            $color1 = bss_pc_val('top_box_color1', '#cdffd8');
            $color2 = bss_pc_val('top_box_color2', '#96ffe5');
            $angle = bss_pc_val('top_box_angle', '135');
            $top_color_background_overlay = bss_pc_val('top_color_overlay', '40%');
            $top_color_overlay_percentage = [
                '0%' => [
                    'label' => '0%',
                    'percentage' => '00',
                ],
                '20%' => [
                    'label' => '20%',
                    'percentage' => '33',
                ],
                '40%' => [
                    'label' => '40%',
                    'percentage' => '66',
                ],
                '60%' => [
                    'label' => '60%',
                    'percentage' => '99',
                ],
                '80%' => [
                    'label' => '80%',
                    'percentage' => 'CC',
                ],
                '100%' => [
                    'label' => '100%',
                    'percentage' => 'FF'
                ],
            ];
            echo "Start Color: <input type='color' name='top_box_color1' value='$color1'> ";
            echo "End Color: <input type='color' name='top_box_color2' value='$color2'> ";
            echo "Angle (deg): <input type='number' name='top_box_angle' value='$angle' min='0' max='360'>";
            echo "Overlay <select name='top_color_overlay'>";
            foreach ($top_color_overlay_percentage as $value => $data) {
                $selected = selected($top_color_background_overlay, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Profile Picture Border
    add_settings_field(
        'profile_picture_border',
        'Profile Picture Border',
        function() {
            $color = bss_pc_val('profile_border_color', '#000000');
            $width = bss_pc_val('profile_border_width', '5');
            echo "Color: <input type='color' name='profile_border_color' value='$color'> ";
            echo "Width (px): <input type='number' name='profile_border_width' value='$width' min='0'> ";            
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Bottom Box Color
    add_settings_field(
        'button_box_color',
        'Button Box Background Color',
        function() {
            $color1 = bss_pc_val('button_box_color1', '#96ffe5');
            $color2 = bss_pc_val('button_box_color2', '#cdffd8');
            $angle = bss_pc_val('button_box_angle', '135');
            echo "Start Color: <input type='color' name='button_box_color1' value='$color1'> ";
            echo "End Color: <input type='color' name='button_box_color2' value='$color2'> ";
            echo "Angle (deg): <input type='number' name='button_box_angle' value='$angle' min='0' max='360'>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Footer Box Color
    add_settings_field(
        'footer_box_color',
        'Footer Box Background Color',
        function() {
            $color1 = bss_pc_val('footer_box_color1', '#cdffd8');
            $color2 = bss_pc_val('footer_box_color2', '#96ffe5');
            $angle = bss_pc_val('footer_box_angle', '135');
            echo "Start Color: <input type='color' name='footer_box_color1' value='$color1'> ";
            echo "End Color: <input type='color' name='footer_box_color2' value='$color2'> ";
            echo "Angle (deg): <input type='number' name='footer_box_angle' value='$angle' min='0' max='360'>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Box Shadow
    add_settings_field(
        'box_shadow',
        'Box Shadow',
        function() {
            $offsetx = bss_pc_val('box_shadow_offsetx', '0');
            $offsety = bss_pc_val('box_shadow_offsety', '4');
            $blur = bss_pc_val('box_shadow_blur', '10');
            $color = bss_pc_val('box_shadow_color', '#000000');
            $transparency = bss_pc_val('box_shadow_transparency', '40%');
            $transparency_percentage = [
                '0%' => [
                    'label' => '0%',
                    'percentage' => '00',
                ],
                '20%' => [
                    'label' => '20%',
                    'percentage' => '33',
                ],
                '40%' => [
                    'label' => '40%',
                    'percentage' => '66',
                ],
                '60%' => [
                    'label' => '60%',
                    'percentage' => '99',
                ],
                '80%' => [
                    'label' => '80%',
                    'percentage' => 'CC',
                ],
                '100%' => [
                    'label' => '100%',
                    'percentage' => 'FF'
                ],
            ];
            echo "offset X (px): <input type='number' name='box_shadow_offsetx' value='$offsetx' min='0'> ";
            echo "offset Y (px): <input type='number' name='box_shadow_offsety' value='$offsety' min='0'> ";
            echo "Blur (px): <input type='number' name='box_shadow_blur' value='$blur' min='0'> ";
            echo "Shadow Color: <input type='color' name='box_shadow_color' value='$color'> ";
            echo "Transparency: <select name='box_shadow_transparency'>";
            foreach ($transparency_percentage as $value => $data) {
                $selected = selected($transparency, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Spacer
    add_settings_field(
        'spacer',
        'Spacer',
        function() {
            $width = bss_pc_val('spacer_width', '300');
            $height = bss_pc_val('spacer_height', '4');
            $color = bss_pc_val('spacer_color', '#22ffa3');
            echo "Max width (px): <input type='number' name='spacer_width' value='$width' min='0'> ";
            echo "Height (px): <input type='number' name='spacer_height' value='$height' min='0'> ";
            echo "Color: <input type='color' name='spacer_color' value='$color'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Content Gap
    add_settings_field(
        'content_gap',
        'Content Gap',
        function() {
            $gap = bss_pc_val('content_gap', '16');
            $media_gap = bss_pc_val('media_content_gap', '16');
            echo "Content Gap (px): <input type='number' name='content_gap' value='$gap' min='0'> ";
            echo "Media Content Gap (px): <input type='number' name='media_content_gap' value='$media_gap' min='0'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Name Font
    add_settings_field(
        'name_font_style',
        'Name Font',
        function() {
            $name_font = bss_pc_val('name_font', 'Arial'); // default font
            $name_fonts = [
                'Arial' => [
                    'label' => 'Arial',
                    'css' => "Arial, Helvetica, sans-serif",
                ],
                'Segoe UI' => [
                    'label' => 'Segoe UI',
                    'css' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                ],
                'Georgia' => [
                    'label' => 'Georgia',
                    'css' => ' "Georgia", "Times New Roman", serif',
                ],
                'Roboto' => [
                    'label' => 'Roboto',
                    'css' => '"Roboto", "Helvetica Neue", sans-serif',
                ]
            ];

            $name_font_weight = bss_pc_val('name_font_weight', 'Bold'); // default font
            $fonts_weight = [
                'Regular' => [
                    'label' => 'Regular',
                    'Weight' => 400,
                ],
                'Medium' => [
                    'label' => 'Medium',
                    'Weight' => 500,
                ],
                'SemiBold' => [
                    'label' => 'SemiBold',
                    'Weight' => 600,
                ],
                'Bold' => [
                    'label' => 'Bold',
                    'Weight' => 700,
                ]
            ];

            $name_font_size = bss_pc_val('name_font_size', 'XL');
            $font_sizes = [
                'Small' => [
                    'label' => 'Small',
                    'Size' => '0.8em',
                ],
                'Regular' => [
                    'label' => 'Regular',
                    'Size' => '1em',
                ],
                'Medium' => [
                    'label' => 'Medium',
                    'Size' => '1.2em',
                ],
                'Large' => [
                    'label' => 'Large',
                    'Size' => '1.5em',
                ],
                'XL' => [
                    'label' => 'XL',
                    'Size' => '2em',
                ]
            ];

            echo "Style: <select name='name_font'>";
            foreach ($name_fonts as $value => $data) {
                $selected = selected($name_font, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
            echo "<select name='name_font_weight'>";
            foreach ($fonts_weight as $value => $data) {
                $selected = selected($name_font_weight, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
            echo " Size: <select name='name_font_size'>";
            foreach ($font_sizes as $value => $data) {
                $selected = selected($name_font_size, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Designation Font
    add_settings_field(
        'designation_font_style',
        'Designation Font',
        function() {
            $designation_font = bss_pc_val('designation_font', 'Arial'); // default font
            $name_fonts = [
                'Arial' => [
                    'label' => 'Arial',
                    'css' => "Arial, Helvetica, sans-serif",
                ],
                'Segoe UI' => [
                    'label' => 'Segoe UI',
                    'css' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                ],
                'Georgia' => [
                    'label' => 'Georgia',
                    'css' => ' "Georgia", "Times New Roman", serif',
                ],
                'Roboto' => [
                    'label' => 'Roboto',
                    'css' => '"Roboto", "Helvetica Neue", sans-serif',
                ]
            ];

            $designation_font_weight = bss_pc_val('designation_font_weight', 'Bold'); // default font
            $fonts_weight = [
                'Regular' => [
                    'label' => 'Regular',
                    'Weight' => 400,
                ],
                'Medium' => [
                    'label' => 'Medium',
                    'Weight' => 500,
                ],
                'SemiBold' => [
                    'label' => 'SemiBold',
                    'Weight' => 600,
                ],
                'Bold' => [
                    'label' => 'Bold',
                    'Weight' => 700,
                ]
            ];

            $designation_font_size = bss_pc_val('designation_font_size', 'Regular');
            $font_sizes = [
                'Small' => [
                    'label' => 'Small',
                    'Size' => '0.8em',
                ],
                'Regular' => [
                    'label' => 'Regular',
                    'Size' => '1em',
                ],
                'Medium' => [
                    'label' => 'Medium',
                    'Size' => '1.2em',
                ],
                'Large' => [
                    'label' => 'Large',
                    'Size' => '1.5em',
                ],
                'XL' => [
                    'label' => 'XL',
                    'Size' => '2em',
                ]
            ];

            echo "Style: <select name='designation_font'>";
            foreach ($name_fonts as $value => $data) {
                $selected = selected($designation_font, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
            echo "<select name='designation_font_weight'>";
            foreach ($fonts_weight as $value => $data) {
                $selected = selected($designation_font_weight, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
            echo " Size: <select name='designation_font_size'>";
            foreach ($font_sizes as $value => $data) {
                $selected = selected($designation_font_size, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Quote Font
    add_settings_field(
        'quote_font_style',
        'Quote Font',
        function() {
            $quote_font = bss_pc_val('quote_font', 'Caveat'); // default font
            $quote_fonts = [
                'Caveat' => [
                    'label' => 'Caveat',
                    'css' => "'Caveat', cursive;",
                    'style' => 'italic',
                ],
                'Roboto' => [
                    'label' => 'Roboto',
                    'css' => "'Roboto', sans-serif",
                    'style' => 'normal',
                ],
                'Georgia' => [
                    'label' => 'Georgia',
                    'css' => 'Georgia, serif',
                    'style' => 'normal',
                ],
                'Arial' => [
                    'label' => 'Arial',
                    'css' => 'Arial, sans-serif',
                    'style' => 'normal',
                ],
                'Rubik Italic' => [
                    'label' => 'Rubik Italic',
                    'css' => "'Rubik', sans-serif",
                    'style' => 'italic',
                ],
            ];

            $quote_font_weight = bss_pc_val('quote_font_weight', 'Regular'); // default font
            $fonts_weight = [
                'Regular' => [
                    'label' => 'Regular',
                    'Weight' => 400,
                ],
                'Medium' => [
                    'label' => 'Medium',
                    'Weight' => 500,
                ],
                'SemiBold' => [
                    'label' => 'SemiBold',
                    'Weight' => 600,
                ],
                'Bold' => [
                    'label' => 'Bold',
                    'Weight' => 700,
                ]
            ];

            $quote_font_size = bss_pc_val('quote_font_size', 'Regular');
            $font_sizes = [
                'Small' => [
                    'label' => 'Small',
                    'Size' => '0.8em',
                ],
                'Regular' => [
                    'label' => 'Regular',
                    'Size' => '1em',
                ],
                'Medium' => [
                    'label' => 'Medium',
                    'Size' => '1.2em',
                ],
                'Large' => [
                    'label' => 'Large',
                    'Size' => '1.5em',
                ],
                'XL' => [
                    'label' => 'XL',
                    'Size' => '2em',
                ]
            ];

            echo "Style: <select name='quote_font'>";
            foreach ($quote_fonts as $value => $data) {
                $selected = selected($quote_font, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
            echo "<select name='quote_font_weight'>";
            foreach ($fonts_weight as $value => $data) {
                $selected = selected($quote_font_weight, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
            echo " Size: <select name='quote_font_size'>";
            foreach ($font_sizes as $value => $data) {
                $selected = selected($quote_font_size, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Quote Box Color
    add_settings_field(
        'quote_box',
        'Quote Box Background Color',
        function() {
            $color = bss_pc_val('quote_box_color', '#f0f0f0');
            echo "Color: <input type='color' name='quote_box_color' value='$color'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Quote Box Shadow
    add_settings_field(
        'quote_box_shadow',
        'Quote Box Shadow',
        function() {
            $offsetx = bss_pc_val('quote_box_shadow_offsetx', '10');
            $offsety = bss_pc_val('quote_box_shadow_offsety', '10');
            $blur = bss_pc_val('quote_box_shadow_blur', '0');
            $color = bss_pc_val('quote_box_shadow_color', '#c1c1c1');
            $transparency = bss_pc_val('quote_box_shadow_transparency', '0%');
            $transparency_percentage = [
                '0%' => [
                    'label' => '0%',
                    'percentage' => '00',
                ],
                '20%' => [
                    'label' => '20%',
                    'percentage' => '33',
                ],
                '40%' => [
                    'label' => '40%',
                    'percentage' => '66',
                ],
                '60%' => [
                    'label' => '60%',
                    'percentage' => '99',
                ],
                '80%' => [
                    'label' => '80%',
                    'percentage' => 'CC',
                ],
                '100%' => [
                    'label' => '100%',
                    'percentage' => 'FF'
                ],
            ];
            echo "offset X (px): <input type='number' name='quote_box_shadow_offsetx' value='$offsetx' min='0'> ";
            echo "offset Y (px): <input type='number' name='quote_box_shadow_offsety' value='$offsety' min='0'> ";
            echo "Blur (px): <input type='number' name='quote_box_shadow_blur' value='$blur' min='0'> ";
            echo "Shadow Color: <input type='color' name='quote_box_shadow_color' value='$color'> ";
            echo "Transparency: <select name='quote_box_shadow_transparency'>";
            foreach ($transparency_percentage as $value => $data) {
                $selected = selected($transparency, $value, false);
                echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($data['label']) . "</option>";
            }
            echo "</select>";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Text Color
    add_settings_field(
        'text_color',
        'Text Color',
        function() {
            $text = bss_pc_val('text_color', '#000000');
            echo "Text Color: <input type='color' name='text_color' value='$text'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Logo Color
    add_settings_field(
        'logo_color',
        'Logo Color',
        function() {
            $logo = bss_pc_val('logo_color', '#00ccff');
            echo "Logo Color: <input type='color' name='logo_color' value='$logo'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Link Color
    add_settings_field(
        'link_color',
        'Link Color',
        function() {
            $static = bss_pc_val('link_color', '#000000');
            $hover = bss_pc_val('link_hover_color', '#FFFF00');
            echo "Static: <input type='color' name='link_color' value='$static'> ";
            echo "Hover: <input type='color' name='link_hover_color' value='$hover'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Bottom Logo Image
    add_settings_field(
        'bottom_logo_image',
        'Bottom Logo Image',
        function() {
            $image_url = bss_pc_val('bottom_logo_image', 'https://www.biblesociety.sg/wp-content/uploads/2015/05/path114.png');
            echo '<input type="text" id="bottom_logo_image" name="bottom_logo_image" value="' . esc_attr($image_url) . '" style="width:70%;"> ';
            echo '<input type="button" class="button" id="upload_bottom_logo_image_button" value="Upload Image">';
            echo '<div><img id="bottom_logo_image_preview" src="' . esc_url($image_url) . '" style="max-width:300px;margin-top:10px;"></div>';
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Bottom Logo Size
    add_settings_field(
        'bottom_logo_size',
        'bottom Logo Size',
        function() {
            $size_width = bss_pc_val('bottom_logo_size_width', '100');
            $size_height = bss_pc_val('bottom_logo_size_height', '90');
            $media_size_width = bss_pc_val('media_bottom_logo_size_width', '100');
            $media_size_height = bss_pc_val('media_bottom_logo_size_height', '90');
            echo "Desktop Size (px) Width: <input type='number' name='bottom_logo_size_width' value='$size_width'> Height: <input type='number' name='bottom_logo_size_height' value='$size_height'>";          
            echo "Media Size (px) Width: <input type='number' name='media_bottom_logo_size_width' value='$media_size_width'> Height: <input type='number' name='media_bottom_logo_size_height' value='$media_size_height'> ";    
        },
        'profile-customizer',
        'profile_customizer_section'
    );

    //Address Link Color
    add_settings_field(
        'address_link_color',
        'Address Link Color',
        function() {
            $static = bss_pc_val('address_link_color', '#FFFFFF');
            $hover = bss_pc_val('address_link_hover_color', '#FFFF00');
            echo "Static: <input type='color' name='address_link_color' value='$static'> ";
            echo "Hover: <input type='color' name='address_link_hover_color' value='$hover'> ";
        },
        'profile-customizer',
        'profile_customizer_section'
    );
}

add_action('wp_head', 'output_custom_profile_styles');
function output_custom_profile_styles() {
    if (!is_singular('person_details')) {
        return;
    }

    if (!bss_pc_card_templates_ready()) {
        return;
    }

    $person_id = get_the_ID();
    if (!$person_id) {
        return;
    }

    $design = BSS_Business_Contact_Card_Template::get_design_for_person($person_id);
    bss_pc_render_design_css($design);
}

function debug_profile_customizer_output() {
    $design = bss_pc_get_editing_design();
    echo '<div style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;">';
    echo '<h3>Debug: design settings for ' . esc_html(bss_pc_get_editing_org()) . '</h3>';
    echo '<pre>' . esc_html(print_r($design, true)) . '</pre>';
    echo '</div>';
}
