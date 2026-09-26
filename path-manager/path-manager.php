<?php
/**
 * Plugin Name: 		Path Manager
 * Plugin URI:			https://github.com/kprabhupaul/path-manager
 * Description:			Path Manager Plugin
 * Version:				1.0.0
 * Requires at least:	6.0
 * Requires PHP:		8.1
 * Author:				Pratap Kumar Kotti
 * Author URI:			https://github.com/kprabhupaul 
 * License:				GPL-2.0-or-later
 * License URI:			https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:			path-manager
 * Requires Plugins:	kotti-libs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MPM_VERSION', '1.0.0' );
define( 'MPM_PATH', plugin_dir_path( __FILE__ ) );
define( 'MPM_URL', plugin_dir_url( __FILE__ ) );

define( 'MPM_ROOT_PATH', get_option( 'mpm_root_path', WP_CONTENT_DIR ) );

require_once __DIR__ . '/loader.php';

//Plugin activation callback.
function mpm_run_activation() {

	require_once __DIR__ . '/activate.php';
    
}

register_activation_hook( __FILE__, 'mpm_run_activation' );
