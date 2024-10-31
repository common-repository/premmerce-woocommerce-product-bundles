<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

if(function_exists('premmerce_ppb_fs') && premmerce_ppb_fs()->is_registered()){
    premmerce_ppb_fs()->add_filter('hide_account_tabs', '__return_true');
    premmerce_ppb_fs()->_account_page_load();
    premmerce_ppb_fs()->_account_page_render();
}
