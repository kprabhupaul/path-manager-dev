<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//  Store plugin version if it does not already exist.
add_option( 'mpm_version', MPM_VERSION );

//  Store default root path if it does not already exist.
add_option( 'mpm_root_path', WP_CONTENT_DIR );
