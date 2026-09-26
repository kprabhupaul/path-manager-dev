<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalize a relative MPM root-relative path.
 *
 * Converts Windows separators to forward slashes,
 * removes duplicate slashes, and trims leading/trailing slashes.
 *
 * @param string $path Relative path.
 * @return string Normalized relative path.
 */
function mpm_normalize_path( $path ) {

    $path = trim( $path );

    $path = str_replace(
        '\\',
        '/',
        $path
    );

    $path = preg_replace(
        '#/+#',
        '/',
        $path
    );

    return trim(
        $path,
        '/'
    );
}
