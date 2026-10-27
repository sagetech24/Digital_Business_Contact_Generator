<?php
/**
 * Admin UI: Import / Export Business Cards
 *
 * Variables expected from BSS_Business_Contact_Import_Export::render_page():
 * @var int $count
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Import / Export Business Cards', 'bss-business-contact'); ?></h1>
    <p>
        <?php esc_html_e('Migrate BSS Business Cards between WordPress sites. Export a JSON file on the source site, install this plugin on the destination site, then import that file.', 'bss-business-contact'); ?>
    </p>

    <hr>

    <h2><?php esc_html_e('Export', 'bss-business-contact'); ?></h2>
    <p>
        <?php
        printf(
            /* translators: %d: number of cards */
            esc_html__('Download all business cards as a JSON file. Currently %d card(s) will be included (all statuses except trash).', 'bss-business-contact'),
            (int) $count
        );
        ?>
    </p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="<?php echo esc_attr(BSS_Business_Contact_Import_Export::EXPORT_ACTION); ?>">
        <?php wp_nonce_field(BSS_Business_Contact_Import_Export::NONCE_ACTION); ?>
        <?php submit_button(__('Download JSON Export', 'bss-business-contact'), 'primary', 'submit', false); ?>
    </form>

    <hr>

    <h2><?php esc_html_e('Import', 'bss-business-contact'); ?></h2>
    <p>
        <?php esc_html_e('Upload a JSON file previously exported from this plugin. Cards are created as new posts unless you enable update-by-slug.', 'bss-business-contact'); ?>
    </p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo esc_attr(BSS_Business_Contact_Import_Export::IMPORT_ACTION); ?>">
        <?php wp_nonce_field(BSS_Business_Contact_Import_Export::NONCE_ACTION); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bss_bc_import_file"><?php esc_html_e('JSON file', 'bss-business-contact'); ?></label>
                </th>
                <td>
                    <input type="file" name="bss_bc_import_file" id="bss_bc_import_file" accept=".json,application/json" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Options', 'bss-business-contact'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="bss_bc_upsert_by_slug" value="1" checked>
                            <?php esc_html_e('Update existing cards that share the same slug (otherwise always create new cards)', 'bss-business-contact'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="bss_bc_sideload_media" value="1" checked>
                            <?php esc_html_e('Download images and .vcf files from the source URLs into this site’s Media Library and rewrite URLs in content', 'bss-business-contact'); ?>
                        </label>
                    </fieldset>
                    <p class="description">
                        <?php esc_html_e('Sideload requires the source site (or those media URLs) to still be reachable when you import.', 'bss-business-contact'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Import JSON', 'bss-business-contact')); ?>
    </form>

    <hr>

    <h2><?php esc_html_e('Notes', 'bss-business-contact'); ?></h2>
    <ul style="list-style: disc; margin-left: 1.5em;">
        <li><?php esc_html_e('Export includes title, slug, status, organization, template_variant, and full post content (block editor HTML).', 'bss-business-contact'); ?></li>
        <li><?php esc_html_e('Organization sets the public URL prefix (default → /id/, hia → /hia/, hopevillage → /hopevillage/, sol → /sol/).', 'bss-business-contact'); ?></li>
        <li><?php esc_html_e('QR shortcodes reference card_id values from the WP QR Code Me vCard plugin. After import, re-link those shortcodes or migrate QR cards separately if QR codes do not display.', 'bss-business-contact'); ?></li>
        <li><?php esc_html_e('After importing on a new site, visit Settings → Permalinks and click Save if card URLs return 404.', 'bss-business-contact'); ?></li>
    </ul>
</div>
