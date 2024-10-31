<?php
if ( ! defined('WPINC')) {
    die;
}

use Premmerce\ProductBundles\ProductBundlesPlugin;

if ( ! count($items)) {
    return;
}

global $wp;

?>

<div class="product-bundle">
    <h2><?= __('Bundles', ProductBundlesPlugin::DOMAIN); ?></h2>
    <?php foreach ($items as $item): ?>
        <?php foreach ($item['productsData'] as $key => $productData) : ?>
            <div class="product-bundle__item">

                <div class="product-bundle__products">

                    <article class="product-bundle__product">
                        <?php $product = $item['mainItem']; ?>
                        <!-- Additional Product BEGIN -->
                        <aside class="product-bundle__product-image">
                            <a href="<?= get_permalink($item['mainItem']->get_id()); ?>">
                                <?php
                                if($product->get_image_id()){
                                    $img = get_the_post_thumbnail($product->get_id(), 'thumbnail');
                                    echo $img ?: get_the_post_thumbnail($product->get_parent_id(), 'thumbnail');
                                } else {
                                    echo wc_placeholder_img('thumbnail');
                                }
                                ?>
                            </a>
                        </aside>
                        <!-- Title -->
                        <h3 class="product-bundle__product-title">
                            <a href="<?= get_permalink($item['mainItem']->get_id()); ?>">
                                <?= get_the_title($item['mainItem']->get_id()); ?>
                            </a>
                        </h3>
                        <?php if ($price_html = $item['mainItem']->get_price_html()): ?>
                            <div class="product-bundle__product-price">
                                <?php echo $price_html; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <?php foreach ($productData['products'] as $productKey => $product) : ?>

                        <article class="product-bundle__product">
                            <!-- Additional Product BEGIN -->
                            <aside class="product-bundle__product-image">
                                <a href="<?= get_permalink($product->get_id()); ?>">
                                    <?php
                                    if($product->get_image_id()){
                                        $img = get_the_post_thumbnail($product->get_id(), 'thumbnail');
                                        echo $img ?: get_the_post_thumbnail($product->get_parent_id(), 'thumbnail');
                                    } else {
                                        echo wc_placeholder_img('thumbnail');
                                    }
                                    ?>
                                </a>
                                <div class="product-bundle__discount-label">
                                    -<?php echo $productData['discounts'][$productKey]; ?>%
                                </div>
                            </aside>
                            <!-- Title -->
                            <h3 class="product-bundle__product-title">
                                <a href="<?= get_permalink($product->get_id()); ?>">
                                    <?php echo get_the_title($product->get_id()); ?>
                                </a>
                            </h3>
                            <?php if ($price_html = $product->get_price_html()): ?>
                                <div class="product-bundle__product-price">
                                    <?php $showDiscount = $productData['old_prices'][$productKey] != $productData['prices'][$productKey]; ?>

                                    <?php if ($showDiscount): ?>
                                        <del>
                                            <?php echo wc_price($productData['old_prices'][$productKey]); ?>
                                        </del>
                                    <?php endif; ?>

                                    <?php
                                    $priceHtml = wc_price($productData['prices'][$productKey]);
                                    if ($showDiscount) {
                                        $priceHtml = '<ins>' . $priceHtml . '</ins>';
                                    }

                                    echo $priceHtml . $product->get_price_suffix();
                                    ?>
                                </div>
                            <?php endif; ?>
                        </article>

                    <?php endforeach; ?>
                </div><!-- /.product-bundle__products  -->

                <div class="product-bundle__purchase">
                    <div class="product-bundle__purchase-inner">
                        <div class="product-bundle__price">
                            <div class="price">
                                <?php if ($productData['total_sale'] < $productData['total']): ?>
                                    <del>
                                        <span class="woocommerce-Price-amount amount"><?= wc_price($productData['total']); ?></span>
                                    </del>
                                <?php endif; ?>

                                <ins>
                                    <span class="woocommerce-Price-amount amount"><?= wc_price($productData['total_sale']) . $item['mainItem']->get_price_suffix(); ?></span>
                                </ins>
                            </div>
                        </div>
                        <div class="product-bundle__discount">
                            <span class="product-bundle__discount-title">
                                    <?php _e('You save', ProductBundlesPlugin::DOMAIN); ?>
                                </span>
                            <span class="product-bundle__discount-val">
                                    <?php echo wc_price($productData['total'] - $productData['total_sale']); ?>
                                </span>
                        </div>
                        <div class="product-bundle__btn">
                            <form method="post" action="<?php echo home_url($wp->request); ?>">
                                <input type="hidden" name="bundle_id" value="<?= $productData['id']; ?>">
                                <button type="submit" name="add-to-cart"
                                        value="<?= esc_attr($item['mainItem']->get_id()); ?>"
                                        class="button alt"><?= esc_html($item['mainItem']->single_add_to_cart_text()); ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

