<?php namespace Premmerce\ProductBundles\Admin;

use Premmerce\ProductBundles\ProductBundlesPlugin;
use Premmerce\ProductBundles\ProductBundlesModel;
use Premmerce\SDK\V2\FileManager\FileManager;

class AdminProducts
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
     * AdminProducts constructor.
     * @param FileManager $fileManager
     * @param ProductBundlesModel $model
     */
    public function __construct(FileManager $fileManager, ProductBundlesModel $model)
    {
        $this->fileManager = $fileManager;
        $this->model       = $model;

        add_filter('woocommerce_product_data_tabs', array($this, 'addTabs'));
        add_action('woocommerce_product_data_panels', array($this, 'renderTabs'));

        add_action('wp_ajax_premmerceGetFormAddNewBundle', array($this, 'ajaxGetFormAddNewBundle'));
        add_action('wp_ajax_premmerceSaveBundle', array($this, 'ajaxSaveBundle'));
        add_action('wp_ajax_premmerceDeleteBundle', array($this, 'ajaxDeleteBundle'));
    }

    /**
     *  Return form for  create new bundle
     */
    public function ajaxGetFormAddNewBundle()
    {
        $this->registerAssets();

        $template = $this->fileManager->renderTemplate('admin/product/product-form-add-new-bundle.php', array(
            'productType' => isset($_POST['product_type']) ? $_POST['product_type'] : 'simple',
        ));

        wp_send_json($template);
    }

    /**
     *  Save new bundle
     */
    public function ajaxSaveBundle()
    {
        parse_str($_POST['data'], $data);

        if ($_POST['product_type'] == 'variable') {
            $postId = $data['main_product'] ? $data['main_product'] : null;
        } else {
            $postId = $_POST['postId'];
        }

        if ($postId) {
            $this->model->createItem(array(
                'main_product_id' => $postId,
                'active' => isset($data['active']),
            ), 'bundles');

            $lastId = $this->model->getLastInsertId();

            if ($lastId) {
                foreach ($data['products']['id'] as $key => $value) {
                    $this->model->createItem(array(
                        'bundle_id' => $lastId,
                        'product_id' => $value,
                        'discount' => $data['products']['discount'][$key],
                    ), 'bundles_products');
                }
            }
        }

        wp_send_json($this->getTableHtml($_POST['postId']));
    }

    /**
     *  Delete bundle
     */
    public function ajaxDeleteBundle()
    {
        if (isset($_POST['bundleId'])) {
            $this->model->deleteBundle($_POST['bundleId']);
        }

        wp_send_json($this->getTableHtml($_POST['postId']));
    }

    /**
     * Get bundle table to buffer and return it
     *
     * @param int $postId
     *
     * @return string
     */
    private function getTableHtml($postId)
    {
        $bundlesTable = new BundlesTable($this->model, $this->fileManager, true, $postId);

        ob_start();
        $bundlesTable->display();
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Add new tab to product tabs
     *
     * @param array $productDataTabs
     *
     * @return mixed
     */
    public function addTabs($productDataTabs)
    {
        if (!strpos($_SERVER['REQUEST_URI'], 'post-new')) {
            $productDataTabs['premmerce-bundles'] = array(
                'label' => __('Bundles', ProductBundlesPlugin::DOMAIN),
                'target' => 'premmerce-bundles',
                'class' => array('show_if_simple', 'show_if_variable'),
                'priority' => 80,
            );
        }

        return $productDataTabs;
    }

    /**
     *  Render product tab
     */
    public function renderTabs()
    {
        global $post;

        $this->registerAssets();

        $id = $post->ID;

        $bundlesTable = new BundlesTable($this->model, $this->fileManager, true, $id);

        $this->fileManager->includeTemplate('admin/product/product-tab.php', array(
            'bundlesTable'      => $bundlesTable
        ));
    }

    /**
     *  Includes js and css files
     */
    private function registerAssets()
    {
        wp_enqueue_script('select2', $this->fileManager->locateAsset('admin/js/select2.min.js'));
        wp_enqueue_script('jquery-tiptip', $this->fileManager->locateAsset('admin/js/jquery.tipTip.js'));
        wp_enqueue_script('premmerce-product-bundles', $this->fileManager->locateAsset('admin/js/admin.js'));

        wp_enqueue_style('select2', $this->fileManager->locateAsset('admin/css/select2.min.css'));
        wp_enqueue_style('tipTip', $this->fileManager->locateAsset('admin/css/tipTip.css'));
        wp_enqueue_style('premmerce-product-bundles', $this->fileManager->locateAsset('admin/css/admin.css'));
    }
}
