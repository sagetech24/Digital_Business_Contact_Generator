<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'person_details_page_bss-profile-customizer') {
        return;
    }
    wp_enqueue_media();
});

function render_profile_customizer_advanced() {
    $stored_defaults = get_option('profile_customizer_defaults', []);
    $hard_defaults = [
        'background_image' => 'https://www.biblesociety.sg/wp-content/uploads/2015/05/image.jpeg',
        'background_size' => '1400',
        'media_background_size' => '700',
        'overlay_color' => '#FFFFFF',
        'background_overlay' => '0%',
        'top_logo_image' => 'https://www.biblesociety.sg/wp-content/uploads/2015/05/BSS-Logo-2023-NEW-2B-WHITE-3.png',
        'top_logo_size' => '100',
        'media_top_logo_size' => '100',
        'top_box_color1' => '#cdffd8',
        'top_box_color2' => '#96ffe5',
        'top_box_angle' => '135',
        'top_color_overlay' => '0%',
        'profile_border_color' => '#6CA38B',
        'profile_border_width' => '5',
        'button_box_color1' => '#FFFFFF',
        'button_box_color2' => '#FFFFFF',
        'button_box_angle' => '135',
        'footer_box_color1' => '#FFFFFF',
        'footer_box_color2' => '#FFFFFF',
        'footer_box_angle' => '135',
        'box_shadow_offsetx' => '10',
        'box_shadow_offsety' => '10',
        'box_shadow_blur' => '0',
        'box_shadow_color' => '#6CA38B',
        'box_shadow_transparency' => '40%',
        'spacer_width' => '300',
        'spacer_height' => '4',
        'spacer_color' => '#6CA38B',
        'content_gap' => '16',
        'media_content_gap' => '8',
        'name_font' => 'Ariel',
        'name_font_weight' => 'Bold',
        'name_font_size' => 'XL',
        'designation_font' => 'Ariel',
        'designation_font_weight' => 'Medium',
        'designation_font_size' => 'Regular',
        'quote_font' => 'Rubik Italic',
        'quote_font_weight' => 'Bold',
        'quote_font_size' => 'Small',
        'quote_box_color' => '#f0f0f0',
        'quote_box_shadow_offsetx' => '10',
        'quote_box_shadow_offsety' => '10',
        'quote_box_shadow_blur' => '0',
        'quote_box_shadow_color' => '#c1c1c1',
        'quote_box_shadow_transparency' => '100%',
        'text_color' => '#000000',
        'logo_color' => '#000000',
        'link_color' => '#000000',
        'link_hover_color' => '#FFFF00',
        'bottom_logo_image' => 'https://www.biblesociety.sg/wp-content/uploads/2015/05/path114.png',
        'bottom_logo_size_width' => '150',
        'bottom_logo_size_height' => '90',
        'media_bottom_logo_size_width' => '150',
        'media_bottom_logo_size_height' => '90',
        'address_link_color' => '#FFFFFF',
        'address_link_hover_color' => '#FFFF00',
    ];

    $defaults = array_merge($hard_defaults, $stored_defaults);

    // Define field types and options
    $select_fields = [
        'background_overlay' => ['0%', '20%', '40%', '60%', '80%', '100%'],
        'top_color_overlay' => ['0%', '20%', '40%', '60%', '80%', '100%'],
        'box_shadow_transparency' => ['0%', '20%', '40%', '60%', '80%', '100%'],
        'quote_box_shadow_transparency' => ['0%', '20%', '40%', '60%', '80%', '100%'],
        'name_font' => ['Ariel', 'Segoe UI', 'Georgia', 'Roboto'],
        'name_font_weight' => ['Regular', 'Medium', 'SemiBold', 'Bold'],
        'name_font_size' => ['Small', 'Regular', 'Medium', 'Large', 'XL'],
        'designation_font' => ['Ariel', 'Segoe UI', 'Georgia', 'Roboto'],
        'designation_font_weight' => ['Regular', 'Medium', 'SemiBold', 'Bold'],
        'designation_font_size' => ['Small', 'Regular', 'Medium', 'Large', 'XL'],
        'quote_font' => ['Caveat', 'Roboto', 'Georgia', 'Arial', 'Rubik Italic'],
        'quote_font_weight' => ['Regular', 'Medium', 'SemiBold', 'Bold'],
        'quote_font_size' => ['Small', 'Regular', 'Medium', 'Large', 'XL']
    ];

    $color_fields = array_filter(array_keys($defaults), fn($k) => str_ends_with($k, '_color1') || str_ends_with($k, '_color2') || str_ends_with($k, '_color'));

    // Handle form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_advanced_defaults'])) {
        $new_defaults = [];

        foreach ($defaults as $key => $value) {
            $new_defaults[$key] = sanitize_text_field($_POST[$key] ?? '');
        }

        update_option('profile_customizer_defaults', $new_defaults);

        echo '<div class="updated"><p>Defaults updated successfully.</p></div>';
        $defaults = $new_defaults;
    }

    ?>
    <div class="wrap">
        <h2>Advanced Settings</h2>
        <p class="description"><?php esc_html_e('These defaults are used when resetting an organisation’s design in the Customizer tab (per card template). They do not change live designs until you reset or seed a new template.', 'bss-business-contact'); ?></p>
        <h3>Edit Default Settings</h3>
        <form method="post">
            <table class="form-table">
                <?php //echo '<pre>'; print_r($defaults); echo '</pre>'; ?>
                <?php foreach ($defaults as $key => $value) : ?>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($key); ?></label></th>
                        <td>
                            <?php if (in_array($key, $color_fields)) : ?>
                                <input type="color" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                            <?php elseif (isset($select_fields[$key])) : ?>
                                <select name="<?php echo esc_attr($key); ?>">
                                    <?php foreach ($select_fields[$key] as $option) : ?>
                                        <option value="<?php echo esc_attr($option); ?>" <?php selected($value, $option); ?>>
                                            <?php echo esc_html($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($key === 'background_image') : ?>
                                <input type="text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                <input type="button" class="button bg_select-media-button" data-target="<?php echo esc_attr($key); ?>" value="Select Image">
                            <?php elseif ($key === 'top_logo_image') : ?>
                                <input type="text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                <input type="button" class="button top_select-media-button" data-target="<?php echo esc_attr($key); ?>" value="Select Image">
                            <?php elseif ($key === 'bottom_logo_image') : ?>
                                <input type="text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                <input type="button" class="button btm_select-media-button" data-target="<?php echo esc_attr($key); ?>" value="Select Image">
                            <?php else : ?>
                                <input type="text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p class="submit">
                <input type="submit" name="save_advanced_defaults" class="button-primary" value="Save Default Settings">
            </p>
        </form>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            let bg_mediaUploader;
            let top_mediaUploader;
            let btm_mediaUploader;
        
            document.querySelectorAll('.bg_select-media-button').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const bg_targetInputId = this.getAttribute('data-target');
                    const bg_targetInput = document.getElementById(bg_targetInputId);
                
                    // If already created, reuse it
                    if (bg_mediaUploader) {
                        bg_mediaUploader.open();
                        return;
                    }
                
                    bg_mediaUploader = wp.media({
                        title: 'Select Background Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                
                    bg_mediaUploader.on('select', function () {
                        const attachment = bg_mediaUploader.state().get('selection').first().toJSON();
                        bg_targetInput.value = attachment.url;
                    });
                
                    bg_mediaUploader.open();
                });
            });

            document.querySelectorAll('.top_select-media-button').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const top_targetInputId = this.getAttribute('data-target');
                    const top_targetInput = document.getElementById(top_targetInputId);
                
                    // If already created, reuse it
                    if (top_mediaUploader) {
                        top_mediaUploader.open();
                        return;
                    }
                
                    top_mediaUploader = wp.media({
                        title: 'Select Top Logo Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                
                    top_mediaUploader.on('select', function () {
                        const attachment = top_mediaUploader.state().get('selection').first().toJSON();
                        top_targetInput.value = attachment.url;
                    });
                
                    top_mediaUploader.open();
                });
            });

            document.querySelectorAll('.btm_select-media-button').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const btm_targetInputId = this.getAttribute('data-target');
                    const btm_targetInput = document.getElementById(btm_targetInputId);
                
                    // If already created, reuse it
                    if (btm_mediaUploader) {
                        btm_mediaUploader.open();
                        return;
                    }
                
                    btm_mediaUploader = wp.media({
                        title: 'Select Bottom Logo Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                
                    btm_mediaUploader.on('select', function () {
                        const attachment = btm_mediaUploader.state().get('selection').first().toJSON();
                        btm_targetInput.value = attachment.url;
                    });
                
                    btm_mediaUploader.open();
                });
            });
        });
        </script>
    </div>
    <?php
}