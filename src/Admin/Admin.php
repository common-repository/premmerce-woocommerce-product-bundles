<?php

namespace Premmerce\ProductBundles\Admin;

use Premmerce\ProductBundles\ProductBundlesModel;
use Premmerce\ProductBundles\ProductBundlesPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;

/**
 * Class Admin
 * @package Premmerce\ProductBundles
 */
class Admin
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var ProductBundlesModel
     */
    private $model;

    /**
     * Admin constructor.
     *
     * @param FileManager $fileManager
     * @param ProductBundlesModel $model
     */
    public function __construct(FileManager $fileManager, ProductBundlesModel $model)
    {
        $this->registerHooks();

        $this->fileManager = $fileManager;
        $this->model       = $model;
    }

    /**
     * Register admin hooks
     */
    public function registerHooks()
    {
        add_action('admin_menu', array($this, 'addMenuPage'));
        add_action('admin_menu', array($this, 'addFullPack'), 100);

        add_action('woocommerce_before_order_itemmeta', array($this, 'beforeOrderItemMeta'), 10, 4);
        add_filter('woocommerce_hidden_order_itemmeta', array($this, 'hiddenOrderItemMeta'), 10, 1);
        add_action('wp_ajax_get_products', array($this, 'ajaxGetProducts'));

        add_action('before_delete_post', array($this, 'deleteBundle'));
    }

    /**
     * Delete bundle when deleted main product or attached product
     *
     * @param $postId
     */
    public function deleteBundle($postId)
    {
        if (wc_get_product($postId)) {
            $bundleId = $this->model->getBundleIdByProductId($postId);

            if ($bundleId) {
                $this->model->deleteBundle((int) $bundleId);
            }
        }
    }

    /**
     * Get and json encode array of product titles
     */
    public function ajaxGetProducts()
    {
        $search = isset($_GET['s']) ? $_GET['s'] : '';
        $exceptions = array_filter(explode(',', $_GET['exceptions']));

        $postId = isset($_GET['postId']) ? $_GET['postId'] : '';

        $products = $this->model->searchProductsByString($search, $exceptions, $postId);

        $productTitles = array_column($products, 'post_title', 'ID');

        echo json_encode($productTitles, JSON_UNESCAPED_UNICODE);
        wp_die();
    }

    /**
     * Add Premmerce bundles in main menu
     */
    public function addMenuPage()
    {
        global $admin_page_hooks;

        $premmerceMenuExists = isset($admin_page_hooks['premmerce']);

        $svg = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="20" height="16" style="fill:#82878c" viewBox="0 0 20 16"><g id="Rectangle_7"> <path d="M17.8,4l-0.5,1C15.8,7.3,14.4,8,14,8c0,0,0,0,0,0H8h0V4.3C8,4.1,8.1,4,8.3,4H17.8 M4,0H1C0.4,0,0,0.4,0,1c0,0.6,0.4,1,1,1 h1.7C2.9,2,3,2.1,3,2.3V12c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1V1C5,0.4,4.6,0,4,0L4,0z M18,2H7.3C6.6,2,6,2.6,6,3.3V12 c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1v-1.7C8,10.1,8.1,10,8.3,10H14c1.1,0,3.2-1.1,5-4l0.7-1.4C20,4,20,3.2,19.5,2.6 C19.1,2.2,18.6,2,18,2L18,2z M14,11h-4c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1h4c0.6,0,1-0.4,1-1C15,11.4,14.6,11,14,11L14,11z M14,14 c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1c0.6,0,1-0.4,1-1C15,14.4,14.6,14,14,14L14,14z M4,14c-0.6,0-1,0.4-1,1c0,0.6,0.4,1,1,1 c0.6,0,1-0.4,1-1C5,14.4,4.6,14,4,14L4,14z"/></g></svg>';
        $svg = 'data:image/svg+xml;base64,' . base64_encode($svg);

        if (! $premmerceMenuExists) {
            add_menu_page(
                'Premmerce',
                'Premmerce',
                'manage_options',
                'premmerce',
                '',
                $svg
            );
        }

        add_submenu_page(
            'premmerce',
            __('Product bundles', 'premmerce-product-bundles'),
            __('Product bundles', 'premmerce-product-bundles'),
            'manage_options',
            'premmerce_product_bundles',
            array( $this, 'menuContent' )
        );

        if (! $premmerceMenuExists) {
            global $submenu;
            unset($submenu['premmerce'][0]);
        }
    }

    public function addFullPack()
    {
        global $submenu;

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        $premmerceInstalled = array_key_exists('premmerce-premium/premmerce.php', $plugins)
                              || array_key_exists('premmerce/premmerce.php', $plugins);

        if (!$premmerceInstalled) {
            $submenu['premmerce'][999] = array(
                'Get premmerce full pack',
                'manage_options',
                admin_url('plugin-install.php?tab=plugin-information&plugin=premmerce'),
            );
        }
    }

    /**
     * Render menu page and process actions
     */
    public function menuContent()
    {
        $this->switchPage();
    }

    /**
     * Array controller
     *
     * @return array
     */
    private function switchAction()
    {
        if (isset($_POST['action']) && $_POST['action'] != -1) {
            $action = $_POST['action'];
        } elseif (isset($_POST['action2']) && $_POST['action2'] != -1) {
            $action = $_POST['action2'];
        } else {
            $action = null;
        }

        switch ($action) {
            case 'create':
                return $this->processingCreate();

            case 'edit':
                return $this->processingEdit();

            case 'delete':
                return $this->processingDelete();

            case 'activate':
                return $this->processingActivate();

            case 'deactivate':
                return $this->processingDeactivate();
        }
    }

    /**
     *  Page controller
     */
    private function switchPage()
    {
        wp_enqueue_script('select2', $this->fileManager->locateAsset('admin/js/select2.min.js'));
        wp_enqueue_script('jquery-tiptip', $this->fileManager->locateAsset('admin/js/jquery.tipTip.js'));
        wp_enqueue_script('premmerce-product-bundles', $this->fileManager->locateAsset('admin/js/admin.js'));

        wp_enqueue_style('select2', $this->fileManager->locateAsset('admin/css/select2.min.css'));
        wp_enqueue_style('tipTip', $this->fileManager->locateAsset('admin/css/tipTip.css'));
        wp_enqueue_style('premmerce-product-bundles', $this->fileManager->locateAsset('admin/css/admin.css'));

        $current = isset($_GET['tab'])? $_GET['tab'] : 'bundles';

        $tabs['bundles'] = __('Bundles', ProductBundlesPlugin::DOMAIN);

        if (function_exists('premmerce_ppb_fs')) {
            $tabs['contact'] = __('Contact Us', ProductBundlesPlugin::DOMAIN);
            if (premmerce_ppb_fs()->is_registered()) {
                $tabs['account'] = __('Account', ProductBundlesPlugin::DOMAIN);
            }
        }

        $bundleAction = isset($_GET['bundle_action']) ? $_GET['bundle_action'] : null;

        if (!$bundleAction) {
            $this->fileManager->includeTemplate('admin/main.php', array('current' => $current, 'tabs' => $tabs));
        }

        if ($current == 'bundles') {
            switch ($bundleAction) {
                case 'edit':
                    $this->pageEdit();
                    break;

                case 'delete':
                    $this->pageDelete();
                    break;

                default:
                    $this->defaultPage();
            }
        } else {
            $this->fileManager->includeTemplate("admin/tabs/{$current}.php");
        }
    }

    /**
     * Processing create request
     *
     * @return array
     */
    private function processingCreate()
    {
        $formData = $this->getFormData();

        if (empty($formData['errors'])) {
            $formData['oldValues'] = array();

            $this->model->createItem(array(
                'main_product_id' => $_POST['main_product'],
                'active'          => isset($_POST['active']),
            ), 'bundles');

            $lastId = $this->model->getLastInsertId();

            foreach ($_POST['products']['id'] as $key => $value) {
                $this->model->createItem(array(
                    'bundle_id'  => $lastId,
                    'product_id' => $value,
                    'discount'   => $_POST['products']['discount'][$key],
                ), 'bundles_products');
            }
        }

        return array(
            'oldValues' => $formData['oldValues'],
            'errors'    => $formData['errors']
        );
    }

    /**
     * Processing edit request
     *
     * @return array
     */
    private function processingEdit()
    {
        $successMessage = '';
        $formData = $this->getFormData();

        if (empty($formData['errors'])) {
            $this->model->updateItem('bundles', array(
                'main_product_id' => $_POST['main_product'],
                'active'          => isset($_POST['active']),
            ), $_GET['id']);

            $this->model->deleteItem('bundles_products', array( 'bundle_id' => $_GET['id'] ));

            foreach ($_POST['products']['id'] as $key => $value) {
                $this->model->createItem(array(
                    'bundle_id'  => $_GET['id'],
                    'product_id' => $value,
                    'discount'   => $_POST['products']['discount'][ $key ],
                ), 'bundles_products');
            }

            $successMessage = __('Bundle updated.', ProductBundlesPlugin::DOMAIN);
        }

        return array(
            'oldValues'      => $formData['oldValues'],
            'errors'         => $formData['errors'],
            'successMessage' => $successMessage
        );
    }

    /**
     * Get validate date from form
     *
     * @return array
     */
    private function getFormData()
    {
        $oldValues = array();
        $errors = $this->validationBundleForm($_POST);

        $oldValues['main_product'] = isset($_POST['main_product']) ? $_POST['main_product'] : '';
        $oldValues['products']     = ! empty($_POST['products']) ? $_POST['products'] : null;
        $oldValues['active']       = isset($_POST['active']) ? $_POST['active'] : '';

        return array(
            'oldValues' => $oldValues,
            'errors'    => $errors
        );
    }

    /**
     * Delete bundle and relations in DB
     *
     * @return array
     */
    private function processingDelete()
    {
        $this->sortBundlesIds($_POST['ids'], function ($id) {
            $this->model->deleteBundle($id);
        });

        return array();
    }

    /**
     * Activate bundles
     *
     * @return array
     */
    private function processingActivate()
    {
        $this->sortBundlesIds($_POST['ids'], function ($id) {
            $this->model->updateItem('bundles', array(
                'active' => 1
            ), $id);
        });

        return array();
    }

    /**
     * Deactivate bundles
     *
     * @return array
     */
    private function processingDeactivate()
    {
        $this->sortBundlesIds($_POST['ids'], function ($id) {
            $this->model->updateItem('bundles', array(
                'active' => 0
            ), $id);
        });

        return array();
    }

    /**
     * @param array $ids
     * @param $callback
     */
    private function sortBundlesIds($ids, $callback)
    {
        if (! empty($ids)) {
            foreach ($ids as $id) {
                $callback($id);
            }
        }
    }

    /**
     * Validate add or edit bundle form
     *
     * @param array $data
     *
     * @return array
     */
    private function validationBundleForm($data)
    {
        $errors = array();

        if (! isset($data['main_product']) || ! $data['main_product']) {
            $errors[] = __('Main product required', ProductBundlesPlugin::DOMAIN);
        }

        if (! empty($data['products'])) {
            foreach ($data['products']['id'] as $key => $value) {
                if ($value == $data['main_product'] &&
                    !in_array(__('Product in the bundle can not be the same as main product', ProductBundlesPlugin::DOMAIN), $errors)) {
                    $errors[] = __('Product in the bundle can not be the same as main product', ProductBundlesPlugin::DOMAIN);
                }

                if (((int) $data['products']['discount'][ $key ] > 100 || (int) $data['products']['discount'][ $key ] < 0) &&
                     ! in_array(__('Discount must be from 0 to 100', ProductBundlesPlugin::DOMAIN), $errors)
                ) {
                    $errors[] = __('Discount must be from 0 to 100', ProductBundlesPlugin::DOMAIN);
                }
            }
        } else {
            $errors[] = __('Attached products required', ProductBundlesPlugin::DOMAIN);
        }

        return $errors;
    }

    /**
     * Default action in menu page
     */
    private function defaultPage()
    {
        $actionData = $this->switchAction();

        $bundlesTable = new BundlesTable($this->model, $this->fileManager);

        $this->fileManager->includeTemplate('admin/menu-page.php', array(
            'api'          => $this->model,
            'bundlesTable' => $bundlesTable,
            'oldValues'    => isset($actionData['oldValues']) ? $actionData['oldValues'] : array(),
            'errors'       => isset($actionData['errors']) ? $actionData['errors'] : array(),
        ));
    }

    /**
     * Edit bundle in admin page
     */
    private function pageEdit()
    {
        $actionData = $this->switchAction();

        $bundle = $this->model->getBundleById($_GET['id']);

        if ($bundle) {
            $bundleProducts = $this->model->getBundleProductsByBundleId($_GET['id']);
            ;
            $visibleErrors = $this->model->checkBundleVisible($_GET['id'])['errors'];

            $actionDataErrors = array();
            if (isset($actionData['errors'])) {
                $actionDataErrors = $actionData['errors'];
            }

            $errors = array($actionDataErrors, $visibleErrors);

            $this->fileManager->includeTemplate('admin/menu-page-edit.php', array(
                'api' => $this->model,
                'bundle' => $bundle,
                'bundleProducts' => $bundleProducts,
                'oldValues' => $actionData['oldValues'],
                'errors' => $errors,
                'successMessage' => $actionData['successMessage'],
            ));
        } else {
            $this->fileManager->includeTemplate('admin/unexist.php');
        }
    }

    /**
     * Delete bundle and redirect to list
     */
    private function pageDelete()
    {
        $this->model->deleteBundle($_GET['id']);

        wp_redirect(menu_page_url('premmerce_product_bundles'));
        exit;
    }

    /**
     *
     * Mark bundled in order
     *
     * @param int $itemId
     */
    public function beforeOrderItemMeta($itemId)
    {
        if (($parentId = wc_get_order_item_meta($itemId, 'premmerce_parent_id', true))) {
            echo sprintf(esc_html__('(bundled in %s)', ProductBundlesPlugin::DOMAIN), get_the_title($parentId));
        }
    }

    /**
     * Hide premmerce_parent_id in order meta
     *
     * @param array $hidden
     *
     * @return array
     */
    public function hiddenOrderItemMeta($hidden)
    {
        return array_merge($hidden, array( 'premmerce_parent_id' ));
    }
}
