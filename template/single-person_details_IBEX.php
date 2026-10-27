<?php
/**
 * IBEX organisation card template.
 * Mapped via card_template CPT (organisation: ibex).
 * Styles: css/single-person_details_IBEX.css (isolated from other templates).
 */

if (!defined('ABSPATH')) {
    exit;
}

$ibex_css = class_exists('BSS_Business_Contact_Card_Template')
    ? BSS_Business_Contact_Card_Template::get_stylesheet_url_for_template(__FILE__)
    : plugin_dir_url(__FILE__) . '../css/single-person_details_IBEX.css';
$ibex_logo = plugin_dir_url(dirname(__FILE__)) . 'assets/ibex-logo.png';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo esc_url($ibex_css); ?>" type="text/css" media="all">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bss-card-org-ibex ibex-card-page'); ?>>

<div class="ibex-card">
    <div class="ibex-card__wave" aria-hidden="true">
        <svg class="ibex-card__wave-svg" viewBox="0 0 400 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path fill="#1f6b3a" d="M0,0 H400 V70 C340,95 280,55 220,78 C150,105 90,60 0,88 Z"/>
            <path fill="#3f9a4a" d="M0,40 C80,20 140,70 210,48 C290,22 340,70 400,45 V95 C330,115 270,75 200,100 C120,128 60,85 0,110 Z"/>
            <path fill="#b7db57" d="M0,78 C70,58 130,110 200,88 C280,62 340,108 400,82 V130 C320,150 250,115 180,138 C100,162 50,125 0,145 Z"/>
        </svg>
    </div>

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

                echo '<h1 class="ibex-card__name">' . esc_html(get_the_title()) . '</h1>';
                echo '<div class="ibex-card__divider" aria-hidden="true"><span></span><span></span><span></span></div>';

                $content = get_the_content();
                $content_without_images = preg_replace('/<img[^>]+\>/i', '', $content);
                $matches = [];

                if (preg_match_all('/<p[^>]*>.*?<\/p>/is', $content_without_images, $matches)) {
                    // Position / designation (editor block index 2)
                    if (!empty($matches[0][2])) {
                        $designation = trim(wp_kses_post($matches[0][2]));
                        if ($designation !== '') {
                            echo '<div class="ibex-card__role">' . $designation . '</div>';
                        }
                    }

                    // Quote field used as secondary line / staff ID (editor block index 4)
                    if (!empty($matches[0][4])) {
                        $extra = trim(wp_strip_all_tags($matches[0][4]));
                        if ($extra !== '') {
                            echo '<div class="ibex-card__meta">' . esc_html($extra) . '</div>';
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
            endwhile;
        endif;
        ?>
    </div>

    <div class="ibex-card__brand">
        <img
            class="ibex-card__logo"
            src="<?php echo esc_url($ibex_logo); ?>"
            alt="IBEX — International Bible Experiences"
            width="220"
            height="66"
        >
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
