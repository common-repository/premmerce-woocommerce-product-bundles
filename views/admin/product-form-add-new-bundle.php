<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

use Premmerce\ProductBundles\ProductBundlesPlugin;

?>

<div data-premmerce-bundle-form>
    <?php if ($productType == 'variable'): ?>
        <p class="form-field form-required">
            <label><?php _e('Main product', ProductBundlesPlugin::DOMAIN); ?></label>
            <select data-select="main_product2" name="main_product">
                <?php if (isset($oldValues['main_product']) && $oldValues['main_product']) : ?>
                    <option value="<?= $oldValues['main_product']; ?>"><?= get_post($oldValues['main_product'])->post_title; ?></option>
                <?php else: ?>
                    <option value=""><?php _e('Select main product', ProductBundlesPlugin::DOMAIN); ?></option>
                <?php endif; ?>
            </select>
        </p>
    <?php endif; ?>


    <p class="form-field form-required">
        <table data-table="new_bundle_products" class="products-table w -list-table widefat">
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
                        <td><input type="number" min="0" max="100" name="products[discount][]" value="<?= $oldValues['products']['discount'][$i]; ?>"></td>
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

    </p>

    <p class="form-field form-required">
        <label for="enable-checkbox"><?php _e('Enable bundle?', ProductBundlesPlugin::DOMAIN); ?></label>
        <input id="enable-checkbox" type="checkbox" name="active">
        <span class="description"><?php _e('Enable this if you want to display this bundle in your store.', ProductBundlesPlugin::DOMAIN); ?></span>
    </p>

    <button type="button" class="button" data-premmerce-btn-cancel-bundle><?php _e('Cancel', ProductBundlesPlugin::DOMAIN); ?></button>
    <button type="button" class="button button-primary" data-premmerce-btn-save-bundle><?php _e('Save bundle', ProductBundlesPlugin::DOMAIN); ?></button>
</div>