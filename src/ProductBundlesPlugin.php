<?php

namespace Premmerce\ProductBundles;

use Premmerce\ProductBundles\Admin\Admin;
use Premmerce\ProductBundles\Admin\AdminProducts;
use Premmerce\ProductBundles\Frontend\Frontend;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\SDK\V2\Notifications\AdminNotifier;

/**
 * Class ProductBundlesPlugin
 * @package Premmerce\ProductBundles
 */
class ProductBundlesPlugin
{
    const DOMAIN = 'premmerce-product-bundles';

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var ProductBundlesModel
     */
    private $model;

    /**
     * @var AdminNotifier
     */
    private $notifier;

    /**
     * ProductBundlesPlugin constructor.
     *
     * @param string $file
     *
     * @internal param FileManager $fileManager
     * @internal param ProductBundlesModel $model
     */
    public function __construct($file)
    {
        $this->fileManager = new FileManager($file, 'premmerce-woocommerce-product-bundles');
        $this->model       = new ProductBundlesModel();
        $this->notifier    = new AdminNotifier();

        ProductBundlesApi::getInstance($this->fileManager, $this->model);

        $this->registerHooks();
    }

    /**
     * Register plugin hooks
     */
    private function registerHooks()
    {
        add_action('admin_init', array($this, 'checkRequirePlugins'));
        add_action('init', array($this, 'loadTextDomain'));
        add_action('rest_api_init', array($this, 'restApiInit'));
    }

    /**
     * Run plugin part
     */
    public function run()
    {
        $valid = count($this->validateRequiredPlugins()) === 0;

        if ($valid) {
            if (is_admin()) {
                new Admin($this->fileManager, $this->model);
                new AdminProducts($this->fileManager, $this->model);
            } else {
                $frontend = new Frontend($this->fileManager, $this->model);
                $GLOBALS['premmerce_bundles_frontend'] = $frontend;
            }
        }
    }

    /**
     * Fired when the plugin is activated
     */
    public function activate()
    {
        $this->model->createBundlesTable();
        $this->model->createBundleProductsTable();
    }

    /**
     * Check required plugins and push notifications
     */
    public function checkRequirePlugins()
    {
        $message = __('The %s plugin requires %s plugin to be active!', self::DOMAIN);

        $plugins = $this->validateRequiredPlugins();

        if (count($plugins)) {
            foreach ($plugins as $plugin) {
                $error = sprintf($message, 'Premmerce Woocommerce Product Bundles', $plugin);
                $this->notifier->push($error, AdminNotifier::ERROR, false);
            }
        }
    }

    /**
     * Validate required plugins
     *
     * @return array
     */
    private function validateRequiredPlugins()
    {
        $plugins = array();

        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        /**
         * Check if WooCommerce is active
         **/
        if (!(is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php'))) {
            $plugins[] = '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a>';
        }

        return $plugins;
    }

    /**
     * Fired during plugin uninstall
     * Delete tables
     */
    public static function uninstall()
    {
        global $wpdb;

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->get_blog_prefix() . 'premmerce_bundles';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $wpdb->get_blog_prefix() . 'premmerce_bundles_products';
        $wpdb->query($sql);
    }

    /**
     * Load plugin translations
     */
    public function loadTextDomain()
    {
        $name = $this->fileManager->getPluginName();
        load_plugin_textdomain(self::DOMAIN, false, $name . '/languages/');
    }

    /**
     * Create custom api routes
     */
    public function restApiInit()
    {
        register_rest_route('premmerce-bundles/v1', '/get-bundles-html', array(
            'methods'  => 'GET',
            'callback' => array($this, 'getBundlesHtml'),
        ));
    }

    /**
     * Get bundles html by main product id
     *
     * @return array
     */
    public function getBundlesHtml()
    {
        $htmlGenerator = ProductBundlesApi::getInstance();

        return $htmlGenerator->getBundlesHtml(isset($_GET['id']) ? $_GET['id'] : 0);
    }
}
