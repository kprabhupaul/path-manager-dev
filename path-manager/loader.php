<?php
defined( 'ABSPATH' ) || exit;

/*
 * Path Manager Admin Loader
 * Loads all admin helpers, callbacks, and page files.
 */


/*
 * ==========================
 * Helpers
 * ==========================
 */

$files = glob( __DIR__ . '/admin/helpers/*.php' );

if ( $files ) {
    foreach ( $files as $file ) {
        require_once $file;
    }
}

/*
 * ==========================
 * Callbacks
 * ==========================
 */

$files = glob( __DIR__ . '/admin/callbacks/*.php' );

if ( $files ) {
    foreach ( $files as $file ) {
        require_once $file;
    }
}

/*
 * ==========================
 * Pages
 * ==========================
 */

$files = glob( __DIR__ . '/admin/pages/*.php' );

if ( $files ) {
    foreach ( $files as $file ) {
        require_once $file;
    }
}
