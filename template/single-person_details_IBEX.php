<?php
/**
 * IBEX organisation card template.
 * Mapped via card_template CPT (organisation: ibex).
 * Styles: css/single-person_details_IBEX.css (isolated from other templates).
 * Background / logos come from Profile Customizer design settings for IBEX.
 */

if (!defined('ABSPATH')) {
    exit;
}

$person_id = get_the_ID();
$design    = ($person_id && class_exists('BSS_Business_Contact_Card_Template'))
    ? BSS_Business_Contact_Card_Template::get_design_for_person($person_id)
    : [];

$ibex_bg = !empty($design['background_image']) ? (string) $design['background_image'] : '';
$ibex_logo = !empty($design['bottom_logo_image']) ? (string) $design['bottom_logo_image'] : '';

$logo_w = isset($design['bottom_logo_size_width']) && $design['bottom_logo_size_width'] !== ''
    ? (int) $design['bottom_logo_size_width']
    : 150;
$logo_h = isset($design['bottom_logo_size_height']) && $design['bottom_logo_size_height'] !== ''
    ? (int) $design['bottom_logo_size_height']
    : 90;
$media_logo_w = isset($design['media_bottom_logo_size_width']) && $design['media_bottom_logo_size_width'] !== ''
    ? (int) $design['media_bottom_logo_size_width']
    : $logo_w;
$media_logo_h = isset($design['media_bottom_logo_size_height']) && $design['media_bottom_logo_size_height'] !== ''
    ? (int) $design['media_bottom_logo_size_height']
    : $logo_h;

$has_bg = $ibex_bg !== '';
$has_logo = $ibex_logo !== '';
$card_classes = 'ibex-card' . ($has_bg ? ' has-ibex-bg' : '');

$logo_style_vars = $has_logo
    ? sprintf(
        '--ibex-logo-w: %dpx; --ibex-logo-h: %dpx; --ibex-logo-media-w: %dpx; --ibex-logo-media-h: %dpx;',
        max(1, $logo_w),
        max(1, $logo_h),
        max(1, $media_logo_w),
        max(1, $media_logo_h)
    )
    : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    if (class_exists('BSS_Business_Contact_Card_Template')) {
        BSS_Business_Contact_Card_Template::print_stylesheet_link_for_template(__FILE__);
    }
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bss-card-org-ibex ibex-card-page'); ?>>

<div class="<?php echo esc_attr($card_classes); ?>"<?php echo $has_bg ? ' style="--ibex-card-bg: url(\'' . esc_url($ibex_bg) . '\');"' : ''; ?>>
    <?php if (!$has_bg) : ?>
    <div class="ibex-card__wave" aria-hidden="true">
        <svg class="ibex-card__wave-svg" viewBox="0 0 400 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path fill="#1f6b3a" d="M0,0 H400 V70 C340,95 280,55 220,78 C150,105 90,60 0,88 Z"/>
            <path fill="#3f9a4a" d="M0,40 C80,20 140,70 210,48 C290,22 340,70 400,45 V95 C330,115 270,75 200,100 C120,128 60,85 0,110 Z"/>
            <path fill="#b7db57" d="M0,78 C70,58 130,110 200,88 C280,62 340,108 400,82 V130 C320,150 250,115 180,138 C100,162 50,125 0,145 Z"/>
        </svg>
    </div>
    <?php endif; ?>
    <div class="ibex-card__photo">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                $content = get_the_content();
                if (preg_match('/<img[^>]+>/i', $content, $matches)) {
                    echo $matches[0];
                }
            endwhile;
            rewind_posts();
        endif;
        ?>
    </div>
    
    <div class="ibex-card__body">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                echo '<h1 class="ibex-card__name Name-fonts">' . esc_html(get_the_title()) . '</h1>';
                echo '<div class="ibex-card__divider" aria-hidden="true">'
                    . '<span class="ibex-card__divider-dot"></span>'
                    . '<span class="ibex-card__divider-line"></span>'
                    . '<span class="ibex-card__divider-dot"></span>'
                    . '<span class="ibex-card__divider-line"></span>'
                    . '<span class="ibex-card__divider-dot"></span>'
                    . '</div>';

                $content = get_the_content();
                $content_without_images = preg_replace('/<img[^>]+\>/i', '', $content);
                $matches = [];

                if (preg_match_all('/<p[^>]*>.*?<\/p>/is', $content_without_images, $matches)) {
                    // Position / designation (editor block index 2)
                    if (!empty($matches[0][2])) {
                        $designation = trim(wp_kses_post($matches[0][2]));
                        if ($designation !== '' || $designation !== '&nbsp;') {
                            echo '<div class="ibex-card__role Designation-fonts">' . $designation . '</div>';
                        }
                    }

                    // Quote field used as secondary line / staff ID (editor block index 4)
                    if (!empty($matches[0][4])) {
                        $extra = trim(wp_strip_all_tags($matches[0][4]));
                        if ($extra !== '') {
                            echo '<div class="ibex-card__meta Quote-fonts">' . esc_html($extra) . '</div>';
                        }
                    }
                }

                echo '<div class="ibex-card__qr">';
                if (preg_match('/\[qr-code-contact-information-card[^\]]*\]/i', $content_without_images, $shortcode_match)) {
                    $vcf_url = '';
                    if (!empty($matches[0][7])) {
                        $vcf_url = trim(wp_strip_all_tags($matches[0][7]));
                    }

                    if ($vcf_url !== '') {
                        echo '<a class="ibex-card__qr-link" href="' . esc_url($vcf_url) . '" download>';
                        echo do_shortcode($shortcode_match[0]);
                        echo '</a>';
                    } else {
                        echo do_shortcode($shortcode_match[0]);
                    }
                }
                echo '</div>';

                // Phone / email / website from person editor fields (paragraph indices).
                $phone_raw = !empty($matches[0][9]) ? trim(wp_strip_all_tags($matches[0][9])) : '';
                $email_raw = !empty($matches[0][11]) ? trim(wp_strip_all_tags($matches[0][11])) : '';
                $website_raw = !empty($matches[0][13]) ? trim(wp_strip_all_tags($matches[0][13])) : '';

                $phone_display = '';
                $phone_href = '';
                if ($phone_raw !== '' && strcasecmp($phone_raw, 'Enter phone number') !== 0) {
                    if (preg_match('/^\+?\s*65/', $phone_raw)) {
                        $phone_display = preg_replace('/^\+?\s*65\s*/', '+65 ', $phone_raw);
                    } else {
                        $phone_display = '+65 ' . $phone_raw;
                    }
                    $phone_href = 'tel:' . preg_replace('/[^\d+]/', '', $phone_display);
                }

                $email_display = '';
                if ($email_raw !== '' && strcasecmp($email_raw, 'Enter email address') !== 0 && is_email($email_raw)) {
                    $email_display = $email_raw;
                }

                $website_display = '';
                $website_href = '';
                if ($website_raw !== '' && strcasecmp($website_raw, 'Enter website URL') !== 0) {
                    $website_href = $website_raw;
                    if (!preg_match('#^https?://#i', $website_href)) {
                        $website_href = 'https://' . ltrim($website_href, '/');
                    }
                    $website_display = $website_raw;
                    if (!preg_match('#^https?://#i', $website_display)) {
                        $website_display = $website_href;
                    }
                } else {
                    $website_href = 'https://biblesociety.sg';
                    $website_display = 'https://biblesociety.sg';
                }

                $icon_phone = '<svg class="ibex-card__contact-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.85 21 3 13.15 3 3a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.46.57 3.58a1 1 0 0 1-.25 1.02l-2.2 2.19Z"/></svg>';
                $icon_email = '<svg class="ibex-card__contact-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5L4 8V6l8 5 8-5v2Z"/></svg>';
                $icon_web = '<svg class="ibex-card__contact-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.93 6h-2.6a15.7 15.7 0 0 0-1.36-3.52A8.03 8.03 0 0 1 18.93 8ZM12 4c.7 0 1.86 1.54 2.5 4H9.5C10.14 5.54 11.3 4 12 4ZM4.26 14A7.96 7.96 0 0 1 4 12c0-.69.09-1.36.26-2h2.91A17.5 17.5 0 0 0 7 12c0 .68.06 1.35.17 2H4.26Zm.81 2h2.6c.3 1.25.74 2.45 1.36 3.52A8.03 8.03 0 0 1 5.07 16ZM8.09 8H5.07A8.03 8.03 0 0 1 9.03 4.48C8.41 5.55 7.97 6.75 8.09 8ZM12 20c-.7 0-1.86-1.54-2.5-4h5C13.86 18.46 12.7 20 12 20Zm3.09-6H8.91A15.6 15.6 0 0 1 8.75 12c0-.68.06-1.35.16-2h6.18c.1.65.16 1.32.16 2s-.06 1.35-.16 2Zm.84 5.52c.62-1.07 1.06-2.27 1.36-3.52h2.6a8.03 8.03 0 0 1-3.96 3.52ZM16.83 14c.11-.65.17-1.32.17-2s-.06-1.35-.17-2h2.91c.17.64.26 1.31.26 2s-.09 1.36-.26 2h-2.91Z"/></svg>';

                if ($phone_display !== '' || $email_display !== '' || $website_display !== '') {
                    echo '<div class="ibex-card__contact">';
                    if ($phone_display !== '') {
                        echo '<a class="ibex-card__contact-row" href="' . esc_url($phone_href) . '">'
                            . $icon_phone
                            . '<span>' . esc_html($phone_display) . '</span>'
                            . '</a>';
                    }
                    if ($email_display !== '') {
                        echo '<a class="ibex-card__contact-row" href="' . esc_url('mailto:' . $email_display) . '">'
                            . $icon_email
                            . '<span>' . esc_html($email_display) . '</span>'
                            . '</a>';
                    }
                    if ($website_display !== '') {
                        echo '<a class="ibex-card__contact-row" href="' . esc_url($website_href) . '" target="_blank" rel="noopener noreferrer">'
                            . $icon_web
                            . '<span>' . esc_html($website_display) . '</span>'
                            . '</a>';
                    }
                    echo '</div>';
                }
            endwhile;
        endif;
        ?>
    </div>

    <?php if ($has_logo) : ?>
    <div class="ibex-card__brand"<?php echo $logo_style_vars !== '' ? ' style="' . esc_attr($logo_style_vars) . '"' : ''; ?>>
        <img
            class="ibex-card__logo"
            src="<?php echo esc_url($ibex_logo); ?>"
            alt="<?php echo esc_attr__('Organisation logo', 'bss-business-contact'); ?>"
            width="<?php echo esc_attr((string) max(1, $logo_w)); ?>"
            height="<?php echo esc_attr((string) max(1, $logo_h)); ?>"
        >
    </div>
    <?php else : ?>
    <div class="ibex-card__brand ibex-card__brand--spacer" aria-hidden="true"></div>
    <?php endif; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
