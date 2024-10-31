<?php

use Premmerce\ProductBundles\ProductBundlesPlugin;

/**
 * Premmerce plugin
 *
 *
 * @wordpress-plugin
 * Plugin Name:       Premmerce Frequently Bought Together for WooCommerce
 * Plugin URI:        https://premmerce.com/woocommerce-product-bundles/
 * Description:       Plugin create product bundles in WooCommerce.
 * Version:           1.0.9
 * Author:            Premmerce
 * Author URI:        http://premmerce.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       premmerce-product-bundles
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7
 */

// If this file is called directly, abort.
if ( ! defined('WPINC')) {
    die;
}


call_user_func(function () {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    if ( ! get_option('premmerce_version')) {
        require_once plugin_dir_path(__FILE__) . '/freemius.php';
    }

    $main = new ProductBundlesPlugin(__FILE__);

    register_activation_hook(__FILE__, [$main, 'activate']);

    if (function_exists('premmerce_ppb_fs')) {
        premmerce_ppb_fs()->add_action('after_uninstall', [ProductBundlesPlugin::class, 'uninstall']);
    } else {
        register_uninstall_hook(__FILE__, [ProductBundlesPlugin::class, 'uninstall']);
    }

    $main->run();
});
