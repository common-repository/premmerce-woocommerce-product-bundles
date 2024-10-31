<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

use Premmerce\ProductBundles\ProductBundlesPlugin;

?>

<div class="wrap">
    <h2><?php _e('Edit bundle', ProductBundlesPlugin::DOMAIN); ?></h2>

    <?php foreach ($errors as $errorBlock) : ?>

    <div class="notice notice-error is-dismissible" style="display: <?= !empty($errorBlock) ? 'block': 'none' ?>">
        <p>
            <?= implode('<br>', $errorBlock); ?>
        </p>
    </div>

    <?php endforeach; ?>

    <div id="message" class="updated" style="display: <?= $successMessage ? 'block': 'none' ?>">
        <p>
            <?php
                if (isset($successMessage)) {
                    echo $successMessage;
                }
            ?>
        </p>
        <p>
            <a href="<?= menu_page_url('premmerce_product_bundles', false) ?>"><?= '← ' . __('Back to Premmerce Woocommerce Product Bundles', ProductBundlesPlugin::DOMAIN); ?></a>
        </p>
    </div>

    <form id="edittag" method="post" class="validate">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $bundle->id; ?>">

        <table class="form-table">
            <tr class="form-field">
                <th scope="row"><?php _e('Main product', ProductBundlesPlugin::DOMAIN); ?></th>
                <td>
                    <select data-select="main_product" name="main_product">
                        <?php $mainProductId = isset($oldValues['main_product']) ? $oldValues['main_product'] : $bundle->main_product_id; ?>

                        <option value="<?= $mainProductId; ?>"><?= get_post($mainProductId)->post_title; ?></option>
                    </select>
                </td>
            </tr>

            <tr class="form-field">
                <th scope="row"><?php _e('Attached products', ProductBundlesPlugin::DOMAIN); ?></th>
                <td>
                    <table data-table="new_bundle_products" class="products-table wp-list-table widefat fixed striped posts">
                        <thead>
                            <tr>
                                <td width="73%"><?php _e('Product title', ProductBundlesPlugin::DOMAIN); ?></td>
                                <td width="20%"><?php _e('Discount (%)', ProductBundlesPlugin::DOMAIN); ?></td>
                                <td width="7%"></td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (isset($oldValues['products']) ? $oldValues['products']['id'] : $bundleProducts as $key => $value) : ?>

                            <tr>
                                <td>
                                    <div data-title="product_title"><?= is_object($value) ? get_post($value->product_id)->post_title : get_post($value)->post_title; ?></div>
                                    <input type="hidden" name="products[id][]" value="<?= is_object($value) ? $value->product_id : $value; ?>">
                                </td>
                                <td><input type="number" min="0" max="100" name="products[discount][]" value="<?= is_object($value) ? $value->discount : $oldValues['products']['discount'][$key]; ?>"></td>
                                <td><span data-span="delete_product_row" class="dashicons dashicons-no delete-product-row"></span></td>
                            </tr>

                            <?php endforeach; ?>

                            <tr>
                                <td>
                                    <select data-select="new_product">
                                        <option value="-1"><?php _e('Select attached product', ProductBundlesPlugin::DOMAIN); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>

            <tr class="form-field">
                <th scope="row"></th>
                <td>
                    <label>
                        <input <?php checked(isset($oldValues['active']) ? $oldValues['active'] == 'on' : $bundle->active == 1); ?> type="checkbox" name="active">
                        <?php _e('Enable bundle?', ProductBundlesPlugin::DOMAIN); ?>
                    </label>
                    <p class="description">
                        <?php _e('Enable this if you want to display this bundle in your store.', ProductBundlesPlugin::DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>

        <div class="edit-tag-actions">
            <input type="submit" class="button button-primary" value="<?php _e('Update', ProductBundlesPlugin::DOMAIN) ?>" />

            <span id="delete-link">
                <a data-link="delete" class="delete" href="<?= menu_page_url('premmerce_product_bundles', false); ?>&bundle_action=delete&id=<?= $bundle->id ?>"><?php _e('Delete', ProductBundlesPlugin::DOMAIN) ?></a>
            </span>
        </div>
    </form>
</div>
<div data-lang-name="confirm-delete" data-lang-value="<?= __("You are about to permanently delete these items from your site.\nThis action cannot be undone.\n 'Cancel' to stop, 'OK' to delete."); ?>"></div>
