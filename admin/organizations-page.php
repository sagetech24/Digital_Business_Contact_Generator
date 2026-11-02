<?php
/**
 * Organisations admin screen: list, add, remove.
 */

if (!defined('ABSPATH')) {
    exit;
}

$labels  = BSS_Business_Contact_Organization::get_organization_labels();
$map     = BSS_Business_Contact_Organization::get_organizations();
$default = BSS_Business_Contact_Organization::DEFAULT_KEY;

$add_url    = admin_url('admin-post.php');
$remove_url = admin_url('admin-post.php');
?>
<div class="wrap">
    <h1><?php esc_html_e('Organisations', 'bss-business-contact'); ?></h1>
    <p class="description">
        <?php esc_html_e('Manage organisations used for digital cards. Each organisation has its own public URL prefix and card template. Default (BSS) is always available as the fallback and cannot be removed.', 'bss-business-contact'); ?>
    </p>

    <h2><?php esc_html_e('Current organisations', 'bss-business-contact'); ?></h2>
    <table class="widefat striped" style="max-width:720px;">
        <thead>
            <tr>
                <th><?php esc_html_e('Label', 'bss-business-contact'); ?></th>
                <th><?php esc_html_e('Key', 'bss-business-contact'); ?></th>
                <th><?php esc_html_e('URL prefix', 'bss-business-contact'); ?></th>
                <th><?php esc_html_e('Actions', 'bss-business-contact'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($labels as $key => $label) : ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($label); ?></strong>
                        <?php if ($key === $default) : ?>
                            <span class="description"> — <?php esc_html_e('fallback', 'bss-business-contact'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><code><?php echo esc_html($key); ?></code></td>
                    <td><code>/<?php echo esc_html($map[$key]); ?>/{slug}</code></td>
                    <td>
                        <?php if ($key === $default) : ?>
                            <span class="description"><?php esc_html_e('Protected', 'bss-business-contact'); ?></span>
                        <?php else : ?>
                            <form method="post" action="<?php echo esc_url($remove_url); ?>" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(__('Remove this organisation? People using it will be moved to Default (BSS), and its card template will be trashed.', 'bss-business-contact')); ?>');">
                                <?php wp_nonce_field(BSS_Business_Contact_Organization::ADMIN_NONCE_ACTION); ?>
                                <input type="hidden" name="action" value="<?php echo esc_attr(BSS_Business_Contact_Organization::REMOVE_ACTION); ?>">
                                <input type="hidden" name="bss_org_key" value="<?php echo esc_attr($key); ?>">
                                <button type="submit" class="button button-link-delete"><?php esc_html_e('Remove', 'bss-business-contact'); ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h2><?php esc_html_e('Add organisation', 'bss-business-contact'); ?></h2>
    <form method="post" action="<?php echo esc_url($add_url); ?>" style="max-width:480px;">
        <?php wp_nonce_field(BSS_Business_Contact_Organization::ADMIN_NONCE_ACTION); ?>
        <input type="hidden" name="action" value="<?php echo esc_attr(BSS_Business_Contact_Organization::ADD_ACTION); ?>">

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bss_org_label"><?php esc_html_e('Label', 'bss-business-contact'); ?></label>
                </th>
                <td>
                    <input type="text" class="regular-text" name="bss_org_label" id="bss_org_label" required maxlength="100" placeholder="<?php esc_attr_e('e.g. Acme Corp', 'bss-business-contact'); ?>">
                    <p class="description"><?php esc_html_e('Shown in admin dropdowns. A unique key is generated from this label.', 'bss-business-contact'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bss_org_prefix"><?php esc_html_e('URL prefix', 'bss-business-contact'); ?></label>
                </th>
                <td>
                    <code>/</code>
                    <input type="text" class="regular-text" name="bss_org_prefix" id="bss_org_prefix" required maxlength="60" pattern="[a-zA-Z0-9\-]+" placeholder="<?php esc_attr_e('e.g. acme', 'bss-business-contact'); ?>" style="width:12em;">
                    <code>/{slug}</code>
                    <p class="description"><?php esc_html_e('Public path segment for cards in this organisation. Must be unique. Cannot use “id” (reserved for Default).', 'bss-business-contact'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Add organisation', 'bss-business-contact')); ?>
    </form>

    <p class="description">
        <?php esc_html_e('New organisations use the Default (BSS) card template file until you change the template on the Card Templates screen. After adding or removing organisations, permalinks are refreshed automatically.', 'bss-business-contact'); ?>
    </p>
</div>
