<?php

// Create a helper function for easy SDK access.
function premmerce_ppb_fs() {
    global $premmerce_ppb_fs;

    if ( ! isset( $premmerce_ppb_fs ) ) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/freemius/start.php';

        $premmerce_ppb_fs = fs_dynamic_init( array(
            'id'                  => '1587',
            'slug'                => 'premmerce-woocommerce-product-bundles',
            'type'                => 'plugin',
            'public_key'          => 'pk_fa2dd19714a93f0030b729a78323a',
            'is_premium'          => false,
            'has_addons'          => false,
            'has_paid_plans'      => false,
            'menu'                => array(
                'slug'           => 'premmerce_product_bundles',
                'account'        => false,
                'contact'        => false,
                'support'        => false,
                'parent'         => array(
                    'slug' => 'premmerce',
                ),
            ),
        ) );
    }

    return $premmerce_ppb_fs;
}

// Init Freemius.
premmerce_ppb_fs();
// Signal that SDK was initiated.
do_action( 'premmerce_ppb_fs_loaded' );
