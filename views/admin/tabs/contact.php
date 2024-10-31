<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

if(function_exists('premmerce_ppb_fs')){
    premmerce_ppb_fs()->_contact_page_render();
}
