<?php
if ( ! defined('WPINC')) {
    die;
}

use Premmerce\ProductBundles\ProductBundlesPlugin;

?>

<div class="error">
    <p>
        <?php echo __('You attempted to edit an item that doesn\'t exist. Perhaps it was deleted?',
            ProductBundlesPlugin::DOMAIN); ?>
    </p>
</div>