<?php namespace Premmerce\ProductBundles\Frontend;

class ProductBundlesFunctions
{
    private static $_instance = null;

    /**
     * Static class initialization
     *
     * @return ProductBundlesFunctions
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function checkBundleInCart($id)
    {
        if ($id) {
            $items = WC()->cart->get_cart();

            foreach ($items as $item) {
                if (isset($item['premmerce_bundle_id']) && $item['premmerce_bundle_id'] == $id) {
                    return true;
                }
            }
        }

        return false;
    }
}
