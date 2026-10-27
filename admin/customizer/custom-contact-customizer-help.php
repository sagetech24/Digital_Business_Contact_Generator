<?php
function render_profile_customizer_help() {
    ?>
    <div class="wrap">
        <h2>Help & Debug</h2>
        <h3>Common Issues</h3>
        <ul>
            <li>Check if all variables are registered correctly via <code>register_setting()</code> under the <code>register_profile_customizer_setting</code> function.</li>
            <li>Check if all options are saving correctly via <code>get_option()</code>.</li>
            <li>If reset to default settings is not working properly, check if variables name in the <code>custom-contact-customizer-advance.php</code> is similar to the variables in <code>custom-contact-customizer.php</code> </li>
            <li>If the advanced settings crashed, try clicking <code>Restore Safe Defaults</code> button under <code>Reset Defaults</code>. If it doesn't work, contact admin and in the database look for <code>profile_customizer_defaults</code> under <code>wp_options</code>. Delete <code>profile_customizer_defaults</code>.
        </ul>

        <h3>Editing</h3>
        <p>To add new fields or customize existing ones in the Profile Customizer, follow the steps below:</p>
        <ol>
            <li>
                <strong>Register the new setting:</strong><br>
                In the <code>register_profile_customizer_settings()</code> function, add a new <code>register_setting()</code> call for your variable.
            </li>
            <li>
                <strong>Add a new field to the form:</strong><br>
                Use <code>add_settings_field()</code> to render the input element associated with the new setting.
            </li>
            <li>
                <strong>Include the new setting in CSS output:</strong><br>
                In the <code>output_custom_profile_styles()</code> function:
                <ul>
                    <li>Retrieve the new setting value using <code>get_option()</code>.</li>
                    <li>Add or edit the corresponding CSS rules inside the <code>&lt;style&gt;</code> block.</li>
                </ul>
            </li>
            <li>
                <strong>Enable debug display (optional - RECOMMENDED):</strong><br>
                In the <code>debug_profile_customizer_output()</code> function:
                <ul>
                    <li>Use <code>get_option()</code> to retrieve the value of the new setting.</li>
                    <li>Use <code>echo</code> to output its value for debugging purposes.</li>
                </ul>
            </li>
            <li>
                <strong>Set a default value:</strong><br>
                In the <code>handle_profile_customizer_reset()</code> function:
                <ul>
                    <li>Add the new setting and its default value to the <code>$defaults</code> array.</li>
                </ul>
            </li>
            <li>
                <strong>Set default value in Advanced tab:</strong><br>
                In the <code>custom-contact-customizer-advance.php</code> file:
                <ul>
                    <li>Add the new setting and its default value to the <code>$defaults</code> array.</li>
                    <li>Make sure changes are synchronized with the <code>settings_fields('profile-customizer')</code> group if needed.</li>
                    <li>Refresh the advanced tab page and click Save Default Settings button to reload the new default settings.</li>
                </ul>
            </li>
        </ol>
        <p>Following this structure will ensure your additions are consistent with the rest of the customizer’s logic and styling.</p>

        <?php
        $debug_enabled = get_option('profile_customizer_debug_enabled', 'no');
        ?>

        <form method="post" action="">
            <input type="hidden" name="profile_customizer_debug_toggle" value="1">
            <h2>Debug Mode</h2>
            <p>Use this option to enable or disable debug output in the customizer tab.</p>
            <label style="display: inline-flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="debug_enabled" value="yes" <?php checked($debug_enabled, 'yes'); ?>>
                <span>Enable Debug Output</span>
            </label>
            <?php submit_button('Save Debug Setting'); ?>
        </form>

        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_customizer_debug_toggle'])) {
            $enabled = isset($_POST['debug_enabled']) ? 'yes' : 'no';
            update_option('profile_customizer_debug_enabled', $enabled);

            // Refresh to show updated state without resubmitting
            echo '<script>window.location.href = "' . admin_url('admin.php?page=profile-customizer&tab=help') . '";</script>';
            exit;
        }
        ?>

        <?php
        $advanced_debug_enabled = get_option('profile_advanced_customizer_debug_enabled', 'no');
        ?>

        <form method="post" action="">
            <input type="hidden" name="profile_advanced_customizer_debug_toggle" value="1">
            <h2>Advanced Debug Output</h2>
            <p>Use this option to enable or disable advanced debug output in the customizer tab.</p>
            <label style="display: inline-flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="advanced_debug_enabled" value="yes" <?php checked($advanced_debug_enabled, 'yes'); ?>>
                <span>Enable Advanced Debug Output</span>
            </label>
            <?php submit_button('Save Advanced Debug Setting'); ?>
        </form>

        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_advanced_customizer_debug_toggle'])) {
            $enabled = isset($_POST['advanced_debug_enabled']) ? 'yes' : 'no';
            update_option('profile_advanced_customizer_debug_enabled', $enabled);

            // Refresh to show updated state without resubmitting
            echo '<script>window.location.href = "' . admin_url('?page=profile-customizer&tab=help') . '";</script>';
            exit;
        }
        ?>

        <pre>
        <?php
            if (get_option('profile_advanced_customizer_debug_enabled') === 'yes') {
                $defaults = get_option('profile_customizer_defaults');
                if (is_array($defaults)) {
                    print_r($defaults);
                } else {
                    echo "⚠️ profile_customizer_defaults is not an array.\n";
                    var_dump($defaults);
                }
            } else {
                echo "Currently Disabled.\n";
            }
        ?>
        </pre>

        <h3>Reset Defaults</h3>
    <p>If your advanced tab crashed, use the button below to restore the default settings safely.</p>
        
    <form method="post">
        <input type="hidden" name="force_reset_defaults" value="1">
        <?php submit_button('Restore Safe Defaults'); ?>
    </form>
        
    <?php
    if (isset($_POST['force_reset_defaults'])) {
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
        update_option('profile_customizer_defaults', $hard_defaults);
        echo '<div class="updated"><p><strong>Defaults have been restored.</strong></p></div>';
        echo '<script>window.location.href = "' . esc_url(admin_url('admin.php?page=profile-customizer&tab=help')) . '";</script>';
        exit;
    }
    ?>
    </div>
    <?php
}
