<?php namespace Premmerce\ProductBundles;

use Premmerce\SDK\V2\FileManager\FileManager;

/**
 * Class ProductBundlesApi
 * @package Premmerce\ProductBundles
 */
class ProductBundlesApi
{
    /**
     * @var ProductBundlesApi
     */
    private static $instance;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var ProductBundlesModel
     */
    private $model;

    /**
     * ProductBundlesApi constructor.
     *
     * @param FileManager $fileManager
     * @param ProductBundlesModel $model
     */
    public function __construct(FileManager $fileManager, ProductBundlesModel $model)
    {
        $this->fileManager = $fileManager;
        $this->model       = $model;
    }

    /**
     * @param FileManager $fileManager
     * @param ProductBundlesModel $model
     *
     * @return ProductBundlesApi
     */
    public static function getInstance($fileManager = null, $model = null)
    {
        if (! self::$instance) {
            self::$instance = new self($fileManager, $model);
        }

        return self::$instance;
    }

    /**
     * Generate bundles html by product id
     *
     * @param int $id
     * @return array
     */
    public function getBundlesHtml($id)
    {
        $countBundles = 0;

        $products = array( $id );
        $mainItem = wc_get_product($id);

        if (! $mainItem) {
            return array(
                'html'  => '',
                'count' => 0,
            );
        }

        foreach ($mainItem->get_children() as $child) {
            array_push($products, $child);
        }

        $data = array();
        foreach ($products as $product) {
            $mainProduct  = wc_get_product($product);
            $productsData = array();

            $bundles = $this->model->getBundlesByMainProduct($mainProduct->get_id());

            if ($bundles) {
                foreach ($bundles as $key => $bundle) {
                    if ($bundle->active && $this->model->checkBundleVisible($bundle->id)['status']) {
                        $productsData[ $key ] = $this->getBundleProductsData($mainProduct, $bundle);
                    }
                }

                if ($productsData) {
                    $data[] = array(
                        'mainItem' => $mainProduct,
                        'productsData' => $productsData,
                    );
                }

                $countBundles += count($bundles);
            }
        }

        $html = $this->fileManager->renderTemplate('frontend/product-bundles-list.php', array(
            'items' => $data,
        ));

        return array(
            'html'  => $html,
            'count' => $countBundles,
        );
    }

    /**
     * @param \WC_Product $mainProduct
     * @param object $bundle
     *
     * @return array
     */
    public function getBundleProductsData($mainProduct, $bundle)
    {
        $productData = array(
            'id'         => $bundle->id,
            'products'   => array(),
            'discounts'  => array(),
            'old_prices' => array(),
            'prices'     => array(),
            'total'      => wc_get_price_to_display($mainProduct, array('price' => $mainProduct->get_price())),
            'total_sale' => wc_get_price_to_display($mainProduct, array('price' => $mainProduct->get_price())),
            'max_value'  => $mainProduct->get_max_purchase_quantity()
        );

        $bundleProducts = $this->model->getBundleProductsByBundleId($bundle->id);

        foreach ($bundleProducts as $productDataArray) {
            $wcProduct = wc_get_product($productDataArray->product_id);

            $price = $productDataArray->discount ? $this->calculateDiscountPrice($wcProduct, $productDataArray->discount) : $wcProduct->get_regular_price();
            $productData['total_sale'] += wc_get_price_to_display($wcProduct, array('price' => $price));



            $productData['total'] += wc_get_price_to_display($wcProduct, array('price' => $wcProduct->get_regular_price()));

            $oldPrice = wc_get_price_to_display($wcProduct, array('price' => $wcProduct->get_regular_price()));

            array_push($productData['products'], $wcProduct);
            array_push($productData['discounts'], $productDataArray->discount);
            array_push($productData['old_prices'], $oldPrice);

            $productData['prices'][] = wc_get_price_to_display($wcProduct, array('price' => $price));

            if ($productData['max_value'] == - 1 || ($wcProduct->get_max_purchase_quantity() != - 1 && $wcProduct->get_max_purchase_quantity() < $productData['max_value'])) {
                $productData['max_value'] = $wcProduct->get_max_purchase_quantity();
            }
        }

        return $productData;
    }

    /**
     * @param \WC_Product $wcProduct
     * @param int $discount
     * @return float
     */
    private function calculateDiscountPrice($wcProduct, $discount)
    {
        return round($wcProduct->get_regular_price() - $wcProduct->get_regular_price() * $discount / 100, 2);
    }
}
