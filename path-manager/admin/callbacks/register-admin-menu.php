<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Path Manager admin menu and sub-menus.
 */
function mpm_register_admin_menu() {

    add_menu_page(
        'Path Manager',
        'Path Manager',
        'manage_options',
        'mpm',
        'mpm_home_page',
        'dashicons-category',
        30
    );

    add_submenu_page(
        'mpm',
        'Home',
        'Home',
        'manage_options',
        'mpm',
        'mpm_home_page'
    );
	
	add_submenu_page(
		'mpm',
		'Settings',
		'Settings',
		'manage_options',
		'mpm-settings',
		'mpm_settings_page'
	);
	
	add_submenu_page(
    	'mpm',
    	'Logs',
    	'Logs',
    	'manage_options',
    	'mpm-logs',
    	'mpm_logs_page'
	);

    add_submenu_page(
        'mpm',
        'Edit File',
		null,
        'manage_options',
        'mpm-edit-file',
        'mpm_edit_file_page'
    );
}

add_action( 'admin_menu', 'mpm_register_admin_menu' );
