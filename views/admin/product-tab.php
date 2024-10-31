<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

use Premmerce\ProductBundles\ProductBundlesPlugin;

?>

<div id="premmerce-bundles" class="panel woocommerce_options_panel">
    <div class="options_group">
        <div class="product-bundle-panel" data-premmerce-bundle-panel>
            <?php $bundlesTable->display(); ?>
        </div>
    </div>

    <div class="options_group">
        <div class="product-bundle-panel" data-premmerce-bundle-create>
            <button type="button" class="button" data-premmerce-btn-add-bundle><?php _e('Add new bundle', ProductBundlesPlugin::DOMAIN); ?></button>
        </div>
    </div>
</div>

<div data-lang-name="confirm-delete" data-lang-value="<?= __("You are about to permanently delete these items from your site.\nThis action cannot be undone.\n 'Cancel' to stop, 'OK' to delete."); ?>"></div>