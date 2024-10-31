<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

use Premmerce\ProductBundles\ProductBundlesPlugin;

?>

<div class="wrap">

    <div class="notice notice-error is-dismissible" style="display: <?= !empty($errors) ? 'block': 'none' ?>">
        <p>
            <?= implode('<br>', $errors); ?>
        </p>
    </div>

    <div id="col-left">
        <div class="col-wrap">
            <div class="form-wrap">
                <form method="post" class="validate">
                    <input type="hidden" name="action" value="create">
                    <h3><?php _e('Add new bundle', ProductBundlesPlugin::DOMAIN); ?></h3>

                    <div class="form-field form-required">
                        <label><?php _e('Main product', ProductBundlesPlugin::DOMAIN); ?></label>
                        <select data-select="main_product" name="main_product">
                            <?php if (isset($oldValues['main_product']) && $oldValues['main_product']) : ?>
                                <option value="<?= $oldValues['main_product']; ?>"><?= get_post($oldValues['main_product'])->post_title; ?></option>
                            <?php else: ?>
                                <option value=""><?php _e('Select main product', ProductBundlesPlugin::DOMAIN); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-field form-required">
                        <table data-table="new_bundle_products" class="products-table wp-list-table widefat">
                            <thead>
                                <tr>
                                    <td width="73%"><?php _e('Name', ProductBundlesPlugin::DOMAIN); ?></td>
                                    <td width="20%"><?php _e('Discount (%)', ProductBundlesPlugin::DOMAIN); ?></td>
                                    <td width="7%"></td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($oldValues['products']['id'])) : ?>
                                    <?php for ($i = 0; $i < count($oldValues['products']['id']); $i++) : ?>

                                    <tr>
                                        <td>
                                            <div data-title="product_title"><?= get_post($oldValues['products']['id'][$i])->post_title; ?></div>
                                            <input type="hidden" name="products[id][]" value="<?= $oldValues['products']['id'][$i]; ?>">
                                        </td>
                                        <td>
                                            <input type="number"
                                                   min="0"
                                                   max="100"
                                                   name="products[discount][]"
                                                   value="<?= $oldValues['products']['discount'][$i]; ?>"
                                                   data-input-number
                                            >
                                        </td>
                                        <td><span data-span="delete_product_row" class="dashicons dashicons-no delete-product-row"></span></td>
                                    </tr>

                                    <?php endfor; ?>
                                <?php endif; ?>

                                <tr>
                                    <td>
                                        <select data-select="new_product">
                                            <option value="-1"><?php _e('Select attached product', ProductBundlesPlugin::DOMAIN); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>

                    <div class="form-field form-required">
                        <label for="enable-checkbox">
                            <input id="enable-checkbox"
                                <?php checked((isset($oldValues['active']) && $oldValues['active'] == 'on')); ?>
                                   type="checkbox"
                                   name="active"
                                   checked >
                            <?php _e('Enable bundle?', ProductBundlesPlugin::DOMAIN); ?>
                        </label>
                        <p class="description">
                            <?php _e('Enable this if you want to display this bundle in your store.', ProductBundlesPlugin::DOMAIN); ?>
                        </p>
                    </div>

                    <?php submit_button(__('Add new bundle', ProductBundlesPlugin::DOMAIN)); ?>
                </form>
            </div>
        </div>
    </div>

    <div id="col-right">
        <div class="col-wrap">
            <form action="" method="POST">
                <?php $bundlesTable->display(); ?>
            </form>
        </div>
    </div>

</div>
<div data-lang-name="confirm-delete" data-lang-value="<?= __("You are about to permanently delete these items from your site.\nThis action cannot be undone.\n 'Cancel' to stop, 'OK' to delete."); ?>"></div>
