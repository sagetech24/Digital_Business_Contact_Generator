<?php
/**
 * Hope Village organisation card template.
 * Mapped via card_template CPT (organisation: hopevillage).
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $css_url = class_exists('BSS_Business_Contact_Card_Template')
        ? BSS_Business_Contact_Card_Template::get_stylesheet_url_for_template(__FILE__)
        : plugin_dir_url(__FILE__) . '../css/single-person_details_hopevillage.css';
    echo '<link rel="stylesheet" href="' . esc_url($css_url) . '" type="text/css" media="all">';
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Allura&family=Dancing+Script&family=Great+Vibes&family=Pacifico&family=Satisfy&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title><?php wp_title(); ?></title>
    <?php
    // Load WordPress head actions (like styles/scripts)
    wp_head();
    ?>
    <style>
      /* Fallback background in case your CSS doesn't load */
      body {
        background: none;
        min-height: 100vh;
        color: #fff;
        margin: 0;
        padding: 2rem;
        font-family: sans-serif;
        overflow-x: hidden;
      }
    </style>
</head>
<body <?php body_class('bss-card-org-hopevillage'); ?>>

<div class="single-person_inner">

<div class="top-box">
    <div class="logo"></div>
</div>

<div class="profile-image">
<?php
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        // Get the post content
        $content = get_the_content();

        // Use regex to extract the first <img> tag
        if (preg_match('/<img[^>]+>/i', $content, $matches)) {
            echo $matches[0]; // Output only the first image tag
        }

    endwhile;
endif;
?>
</div>

<div class="bottom-box">
    <div class="contact-content">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            // Title
            echo '<h2 class="Name-fonts">' . get_the_title() . '</h2>';

            // Get the raw content
            $content = get_the_content();

            // Remove all <img> tags from the content using regex
            $content_without_images = preg_replace('/<img[^>]+\>/i', '', $content);

            // Output the filtered content (with images removed) and apply the_content filters for formatting
            // Extract all <p> tags
            if (preg_match_all('/<p[^>]*>.*?<\/p>/is', $content_without_images, $matches)) {
                
                //For debugging use
                //echo '<pre>';
                //var_dump($matches);
                //echo '</pre>';

                // Display Position/Designation
                if (isset($matches[0][2])) {  
                    echo '<div class="Designation-fonts">' . $matches[0][2] . '</div>';
                }
                
                echo '<hr class="short-spacer">';

                // Display Quote
                echo '<div class="Quote-box">';
                if (isset($matches[0][4])) {  
                    echo '<div class="Quote-fonts">' . $matches[0][4] . '</div>';
                }
                echo '</div>';
            }

        endwhile;
    endif;
    ?>
    </div>

    <div class="QR-Code">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            // Get the raw content
                $content = get_the_content();

                // Remove all <img> tags from the content using regex
                $content_without_images = preg_replace('/<img[^>]+\>/i', '', $content);

                // Search for [qrcode] shortcode (can be alone or within <p> tags)
                if (preg_match('/\[qr-code-contact-information-card[^\]]*\]/i', $content_without_images, $shortcode_match)) {
                    // Output the filtered content (with images removed) and apply the_content filters for formatting
                    if (preg_match_all('/<p[^>]*>.*?<\/p>/is', $content_without_images, $matches)) {
                        // Set vcf file
                        if (isset($matches[0][7])) {  
                            $vcf_url = trim(strip_tags($matches[0][7]));
                            echo '<a class="mobile-qr-link" href="' . $vcf_url . '" download>';
                            echo do_shortcode($shortcode_match[0]);
                            echo '</a>';
                        } 
                    }
                }

        endwhile;
    endif;
    ?>
    </div>
</div>


<div class="footer-box">
    <div class= "footer-content">
        <?php
        if ( have_posts() ) :
            while ( have_posts() ) : the_post();
                // Get the raw content
                $content = get_the_content();

                // Remove all <img> tags from the content using regex
                $content_without_images = preg_replace('/<img[^>]+\>/i', '', $content);

                // Output the filtered content (with images removed) and apply the_content filters for formatting
                // Extract all <p> tags
                if (preg_match_all('/<p[^>]*>.*?<\/p>/is', $content_without_images, $matches)) {

                    // Display Phone Number
                    if (isset($matches[0][9])) {  
                        echo '<div class="footer-fonts"><div><i class="fas fa-phone"></i> +65 ' . strip_tags($matches[0][9]) . '</div></div>';
                    } 

                    // Display Email
                    if (isset($matches[0][11])) {  
                        echo '<div class="footer-fonts"><a href="mailto:' . esc_attr(strip_tags($matches[0][11])) . '"><i class="fas fa-envelope"></i> ' . esc_html(strip_tags($matches[0][11])) . '</a></div>';
                    }
                }

            endwhile;
        endif;
        ?>

        <div class="bible-link">
            <a href="https://www.biblesociety.sg">
                <i class="fas fa-globe"></i> https://biblesociety.sg
            </a>
        </div>
    </div>
</div>

<div class="mission-logo">
    <div class="mission-logo-img"></div>
    <div class="footer-address">
        <a href="https://www.google.com/maps/place/Bible+House/@1.2935511,103.8461974,17z/data=!3m1!4b1!4m6!3m5!1s0x31da177f18354b8b:0x41405f3eb6e65b7a!8m2!3d1.2935511!4d103.8487723!16s%2Fg%2F1tfv2n38?entry=ttu&g_ep=EgoyMDI1MDYxMS4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noopener noreferrer">
            7 Armenian St, Bible House #04-01, Singapore 179932
        </a>
    </div>
</div>

</div>

<?php
// Load WordPress footer actions (like scripts or enqueued JS)
wp_footer();
?>

</body>
</html>