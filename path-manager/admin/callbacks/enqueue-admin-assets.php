<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mpm_enqueue_admin_assets() {

    wp_enqueue_script(
        'mpm-common',
        MPM_URL . 'assets/js/common.js',
        array(),
        filemtime(
        	MPM_PATH . 'assets/js/common.js'
    	),
        true
    );
	
}

add_action('admin_enqueue_scripts', 'mpm_enqueue_admin_assets');

