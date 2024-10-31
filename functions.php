<?php

use Premmerce\ProductBundles\ProductBundlesApi;
use Premmerce\ProductBundles\Frontend\ProductBundlesFunctions;

/**
 * Get bundle html by main product id
 *
 * @param int $id
 * @return string
 */
function premmerce_bundles_get_bundles_html($id)
{
    $htmlGenerator = ProductBundlesApi::getInstance();

    return $htmlGenerator->getBundlesHtml($id);
}

if ( ! function_exists( 'premmerce_bundles' ) ) {

    function premmerce_bundles() {

        return ProductBundlesFunctions::getInstance();

    }

}