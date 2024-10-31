<?php namespace Premmerce\ProductBundles\Admin;

use Premmerce\ProductBundles\ProductBundlesModel;
use Premmerce\ProductBundles\ProductBundlesPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;

/**
 * Class BundlesTable
 * @package Premmerce\ProductBundles\Admin
 */
class BundlesTable extends \WP_List_Table
{
    /**
     * @var ProductBundlesModel
     */
    private $model;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var bool
     */
    private $isProductPage;

    /**
     * @var int
     */
    private $productId;

    /**
     * BundlesTable constructor.
     *
     * @param ProductBundlesModel $model
     * @param FileManager $fileManager
     * @param bool $isProductPage
     * @param int $productId
     */
    public function __construct(ProductBundlesModel $model, FileManager $fileManager, $isProductPage = false, $productId = 0)
    {
        $this->model       = $model;
        $this->fileManager = $fileManager;

        $this->isProductPage = $isProductPage;
        $this->productId     = $productId;

        parent::__construct(array(
            'singular' => __('types', ProductBundlesPlugin::DOMAIN),
            'plural'   => __('type', ProductBundlesPlugin::DOMAIN),
            'ajax'     => false,
        ));

        $this->_column_headers = array(
            $this->get_columns(),
        );
        $this->prepare_items();
    }

    /**
     * Fill checkbox field
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_cb($item)
    {
        return '<input type="checkbox" name="ids[]" id="cb-select-' . $item->id . '" value="' . $item->id . '">';
    }

    /**
     * Fill name field
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_name($item)
    {
        $delType = 'delete';
        if ($this->isProductPage) {
            $delType = 'ajax-delete';
        }

        $bundleDelUrl   = admin_url('admin.php') . '?' . build_query(array(
            'page'          => 'premmerce_product_bundles',
            'bundle_action' => 'delete',
            'id'            => $item->id
        ));
        $bundleEditUrl  = admin_url('admin.php') . '?' . build_query(array(
            'page'          => 'premmerce_product_bundles',
            'bundle_action' => 'edit',
            'id'            => $item->id
        ));


        return $this->fileManager->renderTemplate(
            'admin/bundles-table-main-item-column.php',
            array(
                'item'             => $item,
                'delType'          => $delType,
                'status'           => $this->model->checkBundleVisible($item->id)['status']? '' : ' — ' . __('Hidden', ProductBundlesPlugin::DOMAIN),
                'bundleEditUrl'    => $bundleEditUrl,
                'bundleDelUrl'     => $bundleDelUrl
            )
        );
    }

    public function display()
    {
        $this->fileManager->includeTemplate('admin/table-display.php', array(
            'table'         => $this,
            'isProductPage' => $this->isProductPage,
        ));
    }

    /**
     * Fill roles field
     *
     * @param $item
     *
     * @return string
     */
    protected function column_active($item)
    {
        if ($item->active == 1) {
            return
                '<span data-span="tiptip-active" 
                        class="dashicons dashicons-yes" 
                        data-tip="' . __('Yes', ProductBundlesPlugin::DOMAIN) . '">
                </span>';
        }

        return '-';
    }

    /**
     * Fill products field
     *
     * @param $item
     *
     * @return string
     */
    protected function column_products($item)
    {
        $attachedProducts = '';

        $productBundles = $this->model->getBundleProductsByBundleId($item->id);

        foreach ($productBundles as $product) {
            $attachedProducts .= get_the_title($product->product_id) . '<br>';
        }

        return
            '<span class="dashicons dashicons-info" 
                    data-span="tiptip-products" 
                    viewBox="0 0 23.625 23.625" 
                    data-tip="' . $attachedProducts . '" 
                    width="16px" 
                    height="16px">
            </span>';
    }

    /**
     * Return array with columns titles
     *
     * @return array
     */
    public function get_columns()
    {
        if (!$this->isProductPage) {
            $data['cb'] = '<input type="checkbox">';
        }

        $data['name']     = __('Main product', ProductBundlesPlugin::DOMAIN);
        $data['products'] = __('Attached products', ProductBundlesPlugin::DOMAIN);
        $data['active']   = __('Enabled', ProductBundlesPlugin::DOMAIN);

        return $data;
    }

    /**
     * Set actions list for bulk
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $data = array(
            'delete'     => __('Delete', ProductBundlesPlugin::DOMAIN),
            'activate'   => __('Activate', ProductBundlesPlugin::DOMAIN),
            'deactivate' => __('Deactivate', ProductBundlesPlugin::DOMAIN),
        );

        return $this->isProductPage? array() : $data;
    }

    /**
     * Set items data in table
     */
    public function prepare_items()
    {
        if ($this->isProductPage) {
            $product = wc_get_product($this->productId);

            $ids = array();
            if ($product->get_type() == 'variable') {
                $ids = $product->get_children();
            }

            $ids[] = $product->get_id();

            $data = $this->model->getBundlesByMainProducts($ids);
        } else {
            $data = $this->model->getBundles();

            $perPage     = 20;
            $currentPage = $this->get_pagenum();
            $totalItems  = count($data);

            $this->set_pagination_args(array(
                'total_items' => $totalItems,
                'per_page'    => $perPage,
            ));

            $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);
        }

        $this->items = $data;
    }

    /**
     * Render if no items
     */
    public function no_items()
    {
        _e('No bundles found.', ProductBundlesPlugin::DOMAIN);
    }

    /**
     * Render tablenav block
     *
     * @param string $which
     */
    protected function display_tablenav($which)
    {
        if (!$this->isProductPage) {
            if ($which === 'top') {
                wp_nonce_field('bulk-' . $this->_args['plural']);
            }

            $this->fileManager->includeTemplate('admin/bundles-bulk-actions.php', array(
                'which' => $which,
                'table' => $this,
            ));
        }
    }
}
