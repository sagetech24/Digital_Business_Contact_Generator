<?php
/**
 * Per-organisation design helpers for the Profile Customizer.
 * Storage lives on card_template post meta via BSS_Business_Contact_Card_Template.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Card template APIs are always available inside this plugin.
 */
function bss_pc_card_templates_ready() {
    return class_exists('BSS_Business_Contact_Card_Template')
        && class_exists('BSS_Business_Contact_Organization');
}

/**
 * Organisation key currently being edited in the admin customizer.
 *
 * @return string
 */
function bss_pc_get_editing_org() {
    if (!bss_pc_card_templates_ready()) {
        return 'default';
    }

    $org = isset($_REQUEST['org']) ? wp_unslash($_REQUEST['org']) : 'default';
    return BSS_Business_Contact_Organization::sanitize_organization($org);
}

/**
 * Design array for the organisation being edited (admin).
 *
 * @return array<string,string>
 */
function bss_pc_get_editing_design() {
    if (!empty($GLOBALS['bss_pc_editing_design']) && is_array($GLOBALS['bss_pc_editing_design'])) {
        return $GLOBALS['bss_pc_editing_design'];
    }

    if (!bss_pc_card_templates_ready()) {
        return [];
    }

    $design = BSS_Business_Contact_Card_Template::get_design_for_organization(bss_pc_get_editing_org());
    $GLOBALS['bss_pc_editing_design'] = $design;
    return $design;
}

/**
 * Read a design value for the current admin editing context.
 *
 * @param string $key
 * @param string $fallback
 * @return string
 */
function bss_pc_val($key, $fallback = '') {
    $design = bss_pc_get_editing_design();
    if (array_key_exists($key, $design) && $design[$key] !== '') {
        return (string) $design[$key];
    }
    return (string) $fallback;
}

/**
 * Build admin URL for customizer tabs while preserving organisation.
 *
 * @param string      $tab
 * @param string|null $org
 * @return string
 */
function bss_pc_admin_url($tab = 'customizer', $org = null) {
    if (class_exists('BSS_Business_Contact_Customizer')) {
        return BSS_Business_Contact_Customizer::admin_url($tab, $org);
    }

    if ($org === null) {
        $org = bss_pc_get_editing_org();
    }

    return add_query_arg(
        [
            'post_type' => 'person_details',
            'page'      => 'bss-profile-customizer',
            'tab'       => $tab,
            'org'       => $org,
        ],
        admin_url('edit.php')
    );
}

/**
 * Collect design fields from a POST payload.
 *
 * @param array $post
 * @return array<string,string>
 */
function bss_pc_design_from_post($post) {
    if (!bss_pc_card_templates_ready()) {
        return [];
    }

    $out = [];
    foreach (BSS_Business_Contact_Card_Template::get_design_keys() as $key) {
        if (isset($post[$key])) {
            $out[$key] = sanitize_text_field(wp_unslash($post[$key]));
        }
    }
    return $out;
}

/**
 * Overlay % label → hex alpha suffix.
 *
 * @return array<string,string>
 */
function bss_pc_overlay_hex_map() {
    return [
        '0%'   => '00',
        '20%'  => '33',
        '40%'  => '66',
        '60%'  => '99',
        '80%'  => 'CC',
        '100%' => 'FF',
    ];
}

/**
 * Render front-end <style> for a design pack.
 *
 * @param array<string,string> $d
 */
function bss_pc_render_design_css($d) {
    if (!is_array($d) || empty($d)) {
        return;
    }

    $overlay_percentage = bss_pc_overlay_hex_map();

    $name_fonts = [
        'Arial'    => ['css' => 'Arial, Helvetica, sans-serif'],
        'Segoe UI' => ['css' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"],
        'Georgia'  => ['css' => '"Georgia", "Times New Roman", serif'],
        'Roboto'   => ['css' => '"Roboto", "Helvetica Neue", sans-serif'],
        'Ariel'    => ['css' => 'Arial, Helvetica, sans-serif'],
    ];

    $quote_fonts = [
        'Caveat'       => ['css' => "'Caveat', cursive", 'style' => 'italic'],
        'Roboto'       => ['css' => "'Roboto', sans-serif", 'style' => 'normal'],
        'Georgia'      => ['css' => 'Georgia, serif', 'style' => 'normal'],
        'Arial'        => ['css' => 'Arial, sans-serif', 'style' => 'normal'],
        'Rubik Italic' => ['css' => 'Rubik, sans-serif', 'style' => 'italic'],
        'Caveat Script'=> ['css' => "'Caveat', cursive", 'style' => 'italic'],
    ];

    $fonts_weight = [
        'Regular'  => 400,
        'Medium'   => 500,
        'SemiBold' => 600,
        'Bold'     => 700,
    ];

    $font_sizes = [
        'Small'   => '0.8em',
        'Regular' => '1em',
        'Medium'  => '1.2em',
        'Large'   => '1.5em',
        'XL'      => '2em',
    ];

    $g = static function ($key, $fallback = '') use ($d) {
        return isset($d[$key]) && $d[$key] !== '' ? $d[$key] : $fallback;
    };

    $image_url                = $g('background_image');
    $background_size          = $g('background_size', '1600');
    $media_background_size    = $g('media_background_size', '1600');
    $overlay_color            = $g('overlay_color', '#E1FFF1');
    $background_overlay       = $g('background_overlay', '40%');
    $top_logo_image           = $g('top_logo_image');
    $top_logo_size            = $g('top_logo_size', '100');
    $media_top_logo_size      = $g('media_top_logo_size', '100');
    $top_color1               = $g('top_box_color1', '#cdffd8');
    $top_color2               = $g('top_box_color2', '#96ffe5');
    $top_angle                = $g('top_box_angle', '135');
    $top_color_overlay        = $g('top_color_overlay', '40%');
    $profile_border_color     = $g('profile_border_color', '#000000');
    $profile_border_width     = $g('profile_border_width', '5');
    $button_color1            = $g('button_box_color1', '#96ffe5');
    $button_color2            = $g('button_box_color2', '#cdffd8');
    $button_angle             = $g('button_box_angle', '135');
    $footer_color1            = $g('footer_box_color1', '#cdffd8');
    $footer_color2            = $g('footer_box_color2', '#96ffe5');
    $footer_angle             = $g('footer_box_angle', '135');
    $spacer_width             = $g('spacer_width', '300');
    $spacer_height            = $g('spacer_height', '4');
    $spacer_color             = $g('spacer_color', '#22ffa3');
    $box_shadow_offsetx       = $g('box_shadow_offsetx', '0');
    $box_shadow_offsety       = $g('box_shadow_offsety', '4');
    $box_shadow_blur          = $g('box_shadow_blur', '10');
    $box_shadow_color         = $g('box_shadow_color', '#000000');
    $box_shadow_transparency  = $g('box_shadow_transparency', '40%');
    $content_gap              = $g('content_gap', '16');
    $media_content_gap        = $g('media_content_gap', '16');
    $name_font                = $g('name_font', 'Arial');
    $name_font_weight_key     = $g('name_font_weight', 'Bold');
    $name_font_size_key       = $g('name_font_size', 'XL');
    $designation_font         = $g('designation_font', 'Arial');
    $designation_font_weight_key = $g('designation_font_weight', 'Bold');
    $designation_font_size_key   = $g('designation_font_size', 'Regular');
    $quote_font               = $g('quote_font', 'Caveat');
    $quote_font_weight_key    = $g('quote_font_weight', 'Bold');
    $quote_font_size_key      = $g('quote_font_size', 'Regular');
    $quote_box_color          = $g('quote_box_color', '#f0f0f0');
    $quote_box_shadow_offsetx = $g('quote_box_shadow_offsetx', '10');
    $quote_box_shadow_offsety = $g('quote_box_shadow_offsety', '10');
    $quote_box_shadow_blur    = $g('quote_box_shadow_blur', '0');
    $quote_box_shadow_color   = $g('quote_box_shadow_color', '#c1c1c1');
    $quote_box_shadow_transparency = $g('quote_box_shadow_transparency', '0%');
    $text_color               = $g('text_color', '#000000');
    $logo_color               = $g('logo_color', '#00ccff');
    $static_link_color        = $g('link_color', '#000000');
    $hover_link_color         = $g('link_hover_color', '#FFFF00');
    $bottom_logo_image        = $g('bottom_logo_image');
    $bottom_logo_size_width   = $g('bottom_logo_size_width', '150');
    $bottom_logo_size_height  = $g('bottom_logo_size_height', '90');
    $bottom_logo_media_size_width  = $g('media_bottom_logo_size_width', '150');
    $bottom_logo_media_size_height = $g('media_bottom_logo_size_height', '90');
    $address_static_link_color = $g('address_link_color', '#FFFFFF');
    $address_hover_link_color  = $g('address_link_hover_color', '#FFFF00');

    $percentage = isset($overlay_percentage[$background_overlay])
        ? $overlay_percentage[$background_overlay]
        : $overlay_percentage['40%'];

    $top_color_percentage = isset($overlay_percentage[$top_color_overlay])
        ? $overlay_percentage[$top_color_overlay]
        : $overlay_percentage['40%'];

    $quote_box_color_percentage = isset($overlay_percentage[$quote_box_shadow_transparency])
        ? $overlay_percentage[$quote_box_shadow_transparency]
        : $overlay_percentage['0%'];

    $shadow_hex = isset($overlay_percentage[$box_shadow_transparency])
        ? $overlay_percentage[$box_shadow_transparency]
        : $overlay_percentage['40%'];

    $name_font_css = isset($name_fonts[$name_font]) ? $name_fonts[$name_font]['css'] : 'Arial, Helvetica, sans-serif';
    $name_font_weight = isset($fonts_weight[$name_font_weight_key]) ? $fonts_weight[$name_font_weight_key] : 700;
    $name_font_size = isset($font_sizes[$name_font_size_key]) ? $font_sizes[$name_font_size_key] : '2em';

    $designation_font_css = isset($name_fonts[$designation_font]) ? $name_fonts[$designation_font]['css'] : 'Arial, Helvetica, sans-serif';
    $designation_font_weight = isset($fonts_weight[$designation_font_weight_key]) ? $fonts_weight[$designation_font_weight_key] : 700;
    $designation_font_size = isset($font_sizes[$designation_font_size_key]) ? $font_sizes[$designation_font_size_key] : '1em';

    $quote_font_css = isset($quote_fonts[$quote_font]) ? $quote_fonts[$quote_font]['css'] : "'Caveat', cursive";
    $quote_font_style = isset($quote_fonts[$quote_font]) ? $quote_fonts[$quote_font]['style'] : 'italic';
    $quote_font_weight = isset($fonts_weight[$quote_font_weight_key]) ? $fonts_weight[$quote_font_weight_key] : 700;
    $quote_font_size = isset($font_sizes[$quote_font_size_key]) ? $font_sizes[$quote_font_size_key] : '1em';
    ?>
    <style id="bss-profile-customizer-css">
        .single-person_inner {
            background: url('<?php echo esc_url($image_url); ?>') no-repeat center center;
            background-size: <?php echo esc_attr($background_size); ?>px auto;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }

        @media (max-width: 750px) {
            .single-person_inner {
                background: url('<?php echo esc_url($image_url); ?>') no-repeat center center;
                background-size: <?php echo esc_attr($media_background_size); ?>px auto;
                min-height: 100vh;
                padding: 0;
                margin: 0;
            }
        }

        .single-person_inner::before {
            background: <?php echo esc_attr($overlay_color . $percentage); ?>;
        }

        .top-box {
            background: linear-gradient(<?php echo esc_attr($top_angle); ?>deg, <?php echo esc_attr($top_color1 . $top_color_percentage); ?>, <?php echo esc_attr($top_color2 . $top_color_percentage); ?>);
        }

        .logo {
            background-image: url('<?php echo esc_url($top_logo_image); ?>');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            width: <?php echo esc_attr($top_logo_size); ?>px;
        }

        @media (max-width: 750px) {
            .logo {
                width: <?php echo esc_attr($media_top_logo_size); ?>px;
            }
        }

        .profile-image img {
            border-color: <?php echo esc_attr($profile_border_color); ?> !important;
            border-width: <?php echo esc_attr($profile_border_width); ?>px !important;
        }

        .bottom-box {
            background: linear-gradient(<?php echo esc_attr($button_angle); ?>deg, <?php echo esc_attr($button_color1); ?>, <?php echo esc_attr($button_color2); ?>);
        }

        .footer-box {
            background: linear-gradient(<?php echo esc_attr($footer_angle); ?>deg, <?php echo esc_attr($footer_color1); ?>, <?php echo esc_attr($footer_color2); ?>);
        }

        .bottom-box, .footer-box {
            box-shadow: <?php echo esc_attr($box_shadow_offsetx); ?>px <?php echo esc_attr($box_shadow_offsety); ?>px <?php echo esc_attr($box_shadow_blur); ?>px <?php echo esc_attr($box_shadow_color . $shadow_hex); ?>;
        }

        .short-spacer {
            max-width: <?php echo esc_html($spacer_width); ?>px;
            height: <?php echo esc_html($spacer_height); ?>px;
            background-color: <?php echo esc_html($spacer_color); ?>;
        }

        @media (max-width: 480px) {
            .bottom-box { gap: <?php echo esc_html($media_content_gap); ?>px; }
            .contact-content { gap: <?php echo esc_html($media_content_gap); ?>px; }
        }

        @media (min-width: 1000px) {
            .bottom-box { gap: <?php echo esc_html($content_gap); ?>px; }
            .contact-content { gap: <?php echo esc_html($content_gap); ?>px; }
        }

        .Name-fonts {
            font-family: <?php echo esc_html($name_font_css); ?>;
            font-weight: <?php echo esc_html($name_font_weight); ?>;
            font-size: <?php echo esc_html($name_font_size); ?>;
        }

        .Designation-fonts {
            font-family: <?php echo esc_html($designation_font_css); ?>;
            font-weight: <?php echo esc_html($designation_font_weight); ?>;
            font-size: <?php echo esc_html($designation_font_size); ?>;
        }

        .Quote-box {
            background: <?php echo esc_attr($quote_box_color); ?>;
            box-shadow: <?php echo esc_attr($quote_box_shadow_offsetx); ?>px <?php echo esc_attr($quote_box_shadow_offsety); ?>px <?php echo esc_attr($quote_box_shadow_blur); ?>px <?php echo esc_attr($quote_box_shadow_color . $quote_box_color_percentage); ?>;
        }

        .Quote-fonts {
            font-family: <?php echo esc_html($quote_font_css); ?>;
            font-weight: <?php echo esc_html($quote_font_weight); ?>;
            font-style: <?php echo esc_html($quote_font_style); ?>;
            font-size: <?php echo esc_html($quote_font_size); ?>;
        }

        .Name-fonts, .Designation-fonts, .Quote-fonts {
            color: <?php echo esc_html($text_color); ?>;
        }

        .mission-logo-img {
            background-image: url('<?php echo esc_url($bottom_logo_image); ?>');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            width: <?php echo esc_attr($bottom_logo_size_width); ?>px;
            height: <?php echo esc_attr($bottom_logo_size_height); ?>px;
        }

        @media (max-width: 750px) {
            .mission-logo-img {
                width: <?php echo esc_attr($bottom_logo_media_size_width); ?>px;
                height: <?php echo esc_attr($bottom_logo_media_size_height); ?>px;
            }
        }

        .bible-link a, .footer-fonts a, .footer-fonts {
            color: <?php echo esc_html($static_link_color); ?>;
        }

        .bible-link a:hover, .footer-fonts a:hover {
            color: <?php echo esc_html($hover_link_color); ?>;
        }

        .footer-fonts i, .bible-link i {
            color: <?php echo esc_html($logo_color); ?>;
        }

        .footer-address a, .footer-address a:visited {
            color: <?php echo esc_html($address_static_link_color); ?>;
        }

        .footer-address a:hover {
            color: <?php echo esc_html($address_hover_link_color); ?>;
        }
    </style>
    <?php
}
