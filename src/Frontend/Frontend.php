<?php namespace Premmerce\ProductBundles\Frontend;

use Premmerce\ProductBundles\ProductBundlesApi;
use Premmerce\ProductBundles\ProductBundlesModel;
use Premmerce\ProductBundles\ProductBundlesPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;

/**
 * Class Frontend
 * @package Premmerce\ProductBundles\Frontend
 */
class Frontend
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
     * @var ProductBundlesApi
     */
    private $api;

    /**
     * Frontend constructor.
     *
     * @param FileManager $fileManager
     * @param ProductBundlesModel $model
     */
    public function __construct(FileManager $fileManager, ProductBundlesModel $model)
    {
        $this->registerHooks();

        $this->fileManager = $fileManager;
        $this->model       = $model;

        $this->api = new ProductBundlesApi($this->fileManager, $this->model);

        add_shortcode('premmerce_get_bundles_by_main_product_id', array($this, 'shortcodeGetBundlesByMainProductId'));
        add_shortcode('premmerce_get_bundle_by_id', array($this, 'shortcodePremmerceGetBundleById'));
    }

    /**
     * Shortcode render bundles by main product id
     *
     * @param array $atts
     *
     * @return bool
     */
    public function shortcodeGetBundlesByMainProductId($atts)
    {
        $atts = shortcode_atts(array('id' => 0), $atts);

        if ($atts['id']) {
            $mainProduct  = wc_get_product($atts['id']);
            $productsData = array();

            $bundles = $this->model->getBundlesByMainProduct($atts['id']);

            $html = '';
            if ($bundles) {
                foreach ($bundles as $key => $bundle) {
                    if ($bundle->active && $this->model->checkBundleVisible($bundle->id)['status']) {
                        $productsData[$key] = $this->api->getBundleProductsData($mainProduct, $bundle);
                    }
                }

                $items[] = array(
                    'mainItem'     => $mainProduct,
                    'productsData' => $productsData,
                );

                $html = $this->fileManager->renderTemplate('frontend/product-bundles-list.php', array(
                    'items' => $items
                ));
            }

            $this->renderProductBundle($html);
        }

        return false;
    }

    /**
     * Shortcode render bundle by id
     *
     * @param array $atts
     *
     * @return bool
     */
    public function shortcodePremmerceGetBundleById($atts)
    {
        $atts = shortcode_atts(array('id' => 0), $atts);

        if ($atts['id']) {
            $productsData = array();

            $bundle = $this->model->getBundleById($atts['id']);

            $html = '';
            if ($bundle) {
                $mainProduct = wc_get_product($bundle->main_product_id);

                if ($bundle->active && $this->model->checkBundleVisible($bundle->id)['status']) {
                    $productsData[] = $this->api->getBundleProductsData($mainProduct, $bundle);
                }

                $items[] = array(
                    'mainItem'     => $mainProduct,
                    'productsData' => $productsData,
                );

                $html = $this->fileManager->renderTemplate('frontend/product-bundles-list.php', array(
                    'items' => $items
                ));
            }

            $this->renderProductBundle($html);
        }

        return false;
    }

    /**
     * Register frontend hooks
     */
    private function registerHooks()
    {
        add_action('woocommerce_after_single_product_summary', array($this, 'renderProductBundle'), 10);
        add_action('woocommerce_add_to_cart', array($this, 'addToCart'), 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'getCartItemFromSession'), 10, 2);
        add_filter('woocommerce_cart_item_remove_link', array($this, 'cartItemRemoveLink'), 10, 3);
        add_action('woocommerce_cart_item_removed', array($this, 'cartItemRemoved'), 10, 2);
        add_filter('woocommerce_cart_contents_count', array($this, 'cartContentsCount'));
        add_filter('woocommerce_cart_item_quantity', array($this, 'cartItemQuantity'), 1, 2);
        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'updateCartItemQuantity'), 10, 2);
        add_action('woocommerce_before_cart_item_quantity_zero', array($this, 'updateCartItemQuantity'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'addOrderItemMeta'), 10, 3);
        add_filter(
            'woocommerce_add_to_cart_sold_individually_found_in_cart',
            array($this, 'soldIndividuallyFoundInCart'),
            10,
            3
        );

        add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'hideItemMeta'), 10, 1);

        add_action('woocommerce_before_calculate_totals', array($this, 'checkCartBundles'), 10, 1);

        add_action('woocommerce_cart_item_restored', array($this, 'undoDeleteBundle'), 10, 2);
    }

    /**
     * Undo delte bundle from cart
     *
     * @param string $cartItemKey
     * @param \WC_Cart $cart
     */
    public function undoDeleteBundle($cartItemKey, \WC_Cart $cart)
    {
        $item = $cart->get_cart_item($cartItemKey);

        if ($item && isset($item['premmerce_bundle_id'])) {
            $bundleData = $this->getBundleData($item['premmerce_bundle_id']);
            $quantity   = $item['quantity'];

            $items      = $bundleData['items'];
            $variations = $bundleData['variations'];
            $discounts  = $bundleData['discounts'];

            $this->addToCartBundleItems($cartItemKey, $items, $variations, $discounts, $quantity);
        }
    }

    /**
     * Check bundle products, prices, discounts in cart
     *
     * @param \WC_Cart $cart
     */
    public function checkCartBundles($cart)
    {
        $items = $cart->cart_contents;

        $cartBundles = array();
        foreach ($items as $key => $item) {
            if (isset($item['premmerce_bundle_id'])) {
                $cartBundles[$key]['id']      = $item['premmerce_bundle_id'];
                $cartBundles[$key]['items'][] = $key;
            }

            if (isset($item['premmerce_parent_key'])) {
                $cartBundles[$item['premmerce_parent_key']]['items'][] = $key;
            }
        }

        foreach ($cartBundles as $parentItemKey => $bundle) {
            if (isset($bundle['id'])) {
                $bundleData = $this->model->getBundleById($bundle['id']);
                if ($bundleData) {
                    if ($this->model->checkBundleVisible($bundle['id'])['status']) {
                        $bundleProducts  = $this->model->getBundleProductsByBundleId($bundle['id']);
                        $cartProductsIds = $this->getCartProductsIdsByKeys($cart, $bundle);

                        if ($this->checkBundleProducts(
                            $cartProductsIds,
                            $bundleProducts,
                            $bundleData->main_product_id
                        )) {
                            // состав одинаковый
                            foreach ($cartProductsIds as $key => $id) {
                                $wcProduct = wc_get_product($id);

                                if ($wcProduct) {
                                    if ($parentItemKey == $key) {
                                        $regularPrice = $wcProduct->get_price('edit');
                                    } else {
                                        $regularPrice = $wcProduct->get_regular_price('edit');
                                    }

                                    $discount = $regularPrice * $this->getProductDiscountById(
                                        $id,
                                        $bundleProducts
                                    ) / 100;


                                    $wcProduct->set_price($regularPrice - $discount);
                                    $wcProduct->apply_changes();

                                    WC()->cart->cart_contents[$key]['data'] = $wcProduct;
                                }
                            }
                        } else {
                            // состав разный
                            $this->deleteBundlItemsFromCart($cart, $bundle, $parentItemKey);
                        }
                    } else {
                        // товар из бандла недоступен
                        $this->deleteBundlItemsFromCart($cart, $bundle, $parentItemKey);
                    }
                } else {
                    // бандл удален
                    $this->deleteBundlItemsFromCart($cart, $bundle, $parentItemKey);
                }
            } else {
                // нет главного товара, а есть прикрепленній, удалить его
                $this->deleteBundlItemsFromCart($cart, $bundle, $parentItemKey);
            }
        }
    }

    /**
     * @param array $cartProductsIds
     * @param array $bundleProducts
     * @param int $mainProductId
     *
     * @return bool
     */
    private function checkBundleProducts($cartProductsIds, $bundleProducts, $mainProductId)
    {
        $bundleProductsIds[] = (int)$mainProductId;
        foreach ($bundleProducts as $product) {
            $bundleProductsIds[] = (int)$product->product_id;
        }

        $cartProductsIds = array_values($cartProductsIds);

        if (count($cartProductsIds) == count($bundleProductsIds)) {
            return array_diff($cartProductsIds, $bundleProductsIds) == array_diff($bundleProductsIds, $cartProductsIds);
        }

        return false;
    }

    /**
     * @param \WC_Cart $cart
     * @param array $cartBundle
     *
     * @return array
     */
    private function getCartProductsIdsByKeys($cart, $cartBundle)
    {
        $cartProductsIds = array();

        foreach ($cartBundle['items'] as $cartItemKey) {
            if ($item = $cart->get_cart_item($cartItemKey)) {
                $cartProductsIds[$cartItemKey] = (int)$item['variation_id'] ? $item['variation_id'] : $item['product_id'];
            }
        }

        return $cartProductsIds;
    }

    /**
     * @param int $productId
     * @param array $bundleProducts
     *
     * @return int
     */
    private function getProductDiscountById($productId, $bundleProducts)
    {
        foreach ($bundleProducts as $product) {
            if ($product->product_id == $productId) {
                return $product->discount;
            }
        }

        return 0;
    }

    /**
     * @param \WC_Cart $cart
     * @param array $bundle
     * @param string $parentItemKey
     *
     * @throws \Exception
     */
    private function deleteBundlItemsFromCart($cart, $bundle, $parentItemKey)
    {
        foreach ($bundle['items'] as $item) {
            $cart->remove_cart_item($item);
        }

        if ($item = $cart->removed_cart_contents[$parentItemKey]) {
            $id   = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
            $name = wc_get_product($id)->get_title();

            add_action('storefront_content_top', function () use ($name) {
                wc_print_notice(
                    sprintf(
                        __(
                            '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.',
                            ProductBundlesPlugin::DOMAIN
                        ),
                        $name
                    ),
                    'error'
                );
            }, 11);
        }
    }

    /**
     * Render bundles in product page
     *
     * @param string $html
     */
    public function renderProductBundle($html = '')
    {
        wp_enqueue_style('premmerce-product-bundles', $this->fileManager->locateAsset('frontend/css/frontend.css'));

        if (empty($html)) {
            $html = $this->api->getBundlesHtml(get_the_ID())['html'];
        }

        echo $html;
    }

    /**
     * Add bundle products to cart
     *
     * @param string $cartItemKey
     * @param int $productId
     *
     * @throws \Exception
     */
    public function addToCart($cartItemKey, $productId)
    {
        if (isset($_POST['bundle_id'])) {
            $itemContent = WC()->cart->get_cart_item($cartItemKey);

            WC()->cart->set_quantity($cartItemKey, $itemContent['quantity'] - 1);

            $bundleData = $this->getBundleData($_POST['bundle_id']);
            $quantity   = 1;

            $items      = $bundleData['items'];
            $variations = $bundleData['variations'];
            $discounts  = $bundleData['discounts'];

            $checkNotInStock = $this->checkNotInStock($items, $quantity, $variations);

            if ($checkNotInStock['status']) {
                throw new \Exception(sprintf(
                    __('You cannot add another "%1$s" to your cart.', ProductBundlesPlugin::DOMAIN),
                    $checkNotInStock['title'],
                    wc_format_stock_quantity_for_display($checkNotInStock['max_quantity'], $checkNotInStock['product'])
                ));
            }

            $parentCartId = WC()->cart->generate_cart_id(
                $itemContent['product_id'],
                $itemContent['variation_id'],
                '',
                array('premmerce_bundle_id' => $_POST['bundle_id'])
            );

            if (WC()->cart->find_product_in_cart($parentCartId)) {
                WC()->cart->set_quantity(
                    $parentCartId,
                    WC()->cart->get_cart_item($parentCartId)['quantity'] + $quantity
                );
            } else {
                $itemContent['key']                      = $parentCartId;
                $itemContent['quantity']                 = $quantity;
                $itemContent['premmerce_bundle_id']      = $_POST['bundle_id'];
                WC()->cart->cart_contents[$parentCartId] = $itemContent;
            }

            $this->addToCartBundleItems($parentCartId, $items, $variations, $discounts, $quantity);
        }
    }

    /**
     * Add all bundle items to cart
     *
     * @param string $parentItemKey
     * @param array $items
     * @param array $variations
     * @param array $discounts
     * @param int $quantity
     */
    private function addToCartBundleItems($parentItemKey, $items, $variations, $discounts, $quantity)
    {
        foreach ($items as $key => $item) {
            $id        = $variations[$key] ? $variations[$key] : $item;
            $wcProduct = wc_get_product($id);

            if ($wcProduct) {
                $regularPrice = $wcProduct->get_regular_price();
                $discount     = $regularPrice * $discounts[$key] / 100;

                $wcProduct->set_price($regularPrice - $discount);
                $wcProduct->apply_changes();

                $variation = 0;
                if (isset($variations[$key])) {
                    $variation = $variations[$key];
                }

                $cartId = WC()->cart->generate_cart_id(
                    $wcProduct->get_id(),
                    $variation,
                    '',
                    array('premmerce_parent_key' => $parentItemKey)
                );

                if (! WC()->cart->find_product_in_cart($cartId)) {
                    WC()->cart->cart_contents[$cartId] = array(
                        'key'                  => $cartId,
                        'product_id'           => $wcProduct->get_id(),
                        'variation_id'         => $variations[$key],
                        'variation'            => $wcProduct->get_type() == 'variation' ? $wcProduct->get_variation_attributes() : array(),
                        'quantity'             => $quantity,
                        'data'                 => $wcProduct,
                        'premmerce_parent_key' => $parentItemKey,
                    );
                }
            }
        }
    }

    /**
     * Get bundle data array from DB
     *
     * @param int $id
     *
     * @return array
     */
    private function getBundleData($id)
    {
        $items      = array();
        $variations = array();
        $discounts  = array();

        $bundleProducts = $this->model->getBundleProductsByBundleId($id);

        foreach ($bundleProducts as $product) {
            $wcProduct = wc_get_product($product->product_id);

            array_push($items, $wcProduct->get_parent_id() ? $wcProduct->get_parent_id() : $wcProduct->get_id());
            array_push($variations, ! $wcProduct->get_parent_id() ? $wcProduct->get_parent_id() : $wcProduct->get_id());
            array_push($discounts, $product->discount);
        }

        return array(
            'items'      => $items,
            'variations' => $variations,
            'discounts'  => $discounts
        );
    }

    /**
     * Check products which not in stock
     *
     * @param array $items
     * @param int $quantity
     *
     * @return array
     */
    private function checkNotInStock($items, $quantity, $variations)
    {
        foreach ($items as $key => $item) {
            $wcProduct = $variations[$key] !== 0 ? wc_get_product($variations[$key]) : wc_get_product($item);


            if ($wcProduct->managing_stock() && $wcProduct->get_max_purchase_quantity() < $quantity && $wcProduct->get_max_purchase_quantity() != -1) {
                return array(
                    'status'       => true,
                    'max_quantity' => $wcProduct->get_max_purchase_quantity(),
                    'title'        => $wcProduct->get_title(),
                    'product'      => $wcProduct
                );
            }

            foreach (WC()->cart->get_cart() as $cartItem) {
                if ($wcProduct->get_id() == $cartItem['product_id'] &&
                    $cartItem['quantity'] + $quantity > $wcProduct->get_max_purchase_quantity() && $wcProduct->get_max_purchase_quantity() != -1
                ) {
                    return array(
                        'status'       => true,
                        'max_quantity' => $wcProduct->get_max_purchase_quantity(),
                        'title'        => $wcProduct->get_title(),
                        'product'      => $wcProduct
                    );
                }
            }
        }

        return array(
            'status' => false
        );
    }

    /**
     * Generate item data from session
     *
     * @param array $cartItem
     * @param array $itemSessionValues
     *
     * @return array
     */
    public function getCartItemFromSession($cartItem, $itemSessionValues)
    {
        if (isset($itemSessionValues['premmerce_parent_key'])) {
            $cartItem['premmerce_parent_key'] = $itemSessionValues['premmerce_parent_key'];

            if (isset($cartItem['data']->subscription_sign_up_fee)) {
                $cartItem['data']->subscription_sign_up_fee = 0;
            }

            $cartItem['data']->set_price($cartItem['line_total'] / $cartItem['quantity']);
            $cartItem['data']->apply_changes();
        }

        return $cartItem;
    }

    /**
     * Remove remove link if product in bundle
     *
     * @param string $link
     * @param string $cartItemKey
     *
     * @return string
     */
    public function cartItemRemoveLink($link, $cartItemKey)
    {
        $item = WC()->cart->get_cart_item($cartItemKey);
        if ($item && isset($item['premmerce_parent_key'])) {
            return '';
        }

        return $link;
    }

    /**
     * @param string $cartItemKey
     * @param \WC_Cart $cart
     */
    public function cartItemRemoved($cartItemKey, \WC_Cart $cart)
    {
        $items = $cart->get_cart();

        if ($items) {
            foreach ($items as $key => $item) {
                if (isset($item['premmerce_parent_key']) && $item['premmerce_parent_key'] == $cartItemKey) {
                    unset($cart->cart_contents[$key]);
                }
            }
        }
    }

    /**
     * Not count bundle products
     *
     * @param int $count
     *
     * @return int
     */
    public function cartContentsCount($count)
    {
        $cartContents = WC()->cart->get_cart();
        $bundledItems = 0;

        if ($cartContents) {
            foreach ($cartContents as $item) {
                if (! empty($item['premmerce_parent_key'])) {
                    $bundledItems += $item['quantity'];
                }
            }
        }

        return intval($count - $bundledItems);
    }

    /**
     * No can change quantity if this bundle product
     *
     * @param string $quantity
     * @param string $cartItemKey
     *
     * @return string
     */
    public function cartItemQuantity($quantity, $cartItemKey)
    {
        $item = WC()->cart->get_cart_item($cartItemKey);
        if ($item && isset($item['premmerce_parent_key'])) {
            return $item['quantity'];
        }

        return $quantity;
    }

    /**
     * Update quantity bundle products
     *
     * @param string $cartItemKey
     * @param int $quantity
     */
    public function updateCartItemQuantity($cartItemKey, $quantity = 0)
    {
        $cartItem = WC()->cart->get_cart_item($cartItemKey);

        if ($cartItem && isset($cartItem['premmerce_bundle_id'])) {
            $items = WC()->cart->get_cart();

            foreach ($items as $key => $item) {
                if (isset($item['premmerce_parent_key']) && $item['premmerce_parent_key'] == $cartItemKey) {
                    WC()->cart->set_quantity($key, $quantity);
                }
            }
        }
    }

    /**
     * Add parent_id to order meta
     *
     * @param \WC_Order_Item $item
     * @param string $key
     * @param array $values
     */
    public function addOrderItemMeta($item, $key, $values)
    {
        if (isset($values['premmerce_parent_key'])) {
            $parentItem = WC()->cart->get_cart_item($values['premmerce_parent_key']);
            $parentId   = $parentItem['variation_id'] ? $parentItem['variation_id'] : $parentItem['product_id'];
            $item->add_meta_data('premmerce_parent_id', $parentId);
        }
    }

    /**
     * Hide meta premmerce_parent_key on front
     *
     * @param array $metas
     *
     * @return mixed
     */
    public function hideItemMeta($metas)
    {
        foreach ($metas as $key => $data) {
            if ($data->key == 'premmerce_parent_id') {
                unset($metas[$key]);
            }
        }

        return $metas;
    }

    /**
     * @param bool $inCart
     * @param int $productId
     * @param int $variationId
     *
     * @return bool
     */
    public function soldIndividuallyFoundInCart($inCart, $productId, $variationId)
    {
        $inCart = $this->checkCartItems(WC()->cart->get_cart(), $productId, $variationId);

        if ($inCart) {
            return $inCart;
        }

        if (isset($_POST['bundle_id'])) {
            $bundleData = $this->getBundleData($_POST['bundle_id']);

            $bundleProducts   = $bundleData['items'];
            $bundleVariations = $bundleData['variations'];

            foreach ($bundleProducts as $key => $productId) {
                $inCart = $this->checkCartItems(WC()->cart->get_cart(), $productId, $bundleVariations[$key]);

                if ($inCart) {
                    return $inCart;
                }
            }
        }

        return $inCart;
    }

    /**
     * Check cart items for exists products
     *
     * @param array $items
     * @param int $productId
     * @param int $variationId
     *
     * @return bool
     */
    private function checkCartItems($items, $productId, $variationId)
    {
        foreach ($items as $item) {
            if ((! $variationId && $item['product_id'] == $productId) || ($variationId && $item['variation_id'] == $variationId)) {
                return true;
            }
        }
    }
}
