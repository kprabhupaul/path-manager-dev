<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validate a file or folder name.
 *
 * @param string $name File or folder name.
 * @return string|false Validated name or false when invalid.
 */
function mpm_validate_asset_name( $name ) {

    $name = trim( $name );

    /*
     * Name is required.
     */
    if ( $name === '' ) {
        return false;
    }

    /*
     * A name must represent only one file/folder name.
     * Path separators and traversal are not allowed.
     */
    if (
        str_contains( $name, '/' ) ||
        str_contains( $name, '\\' ) ||
        str_contains( $name, '..' )
    ) {
        return false;
    }

    /*
     * WordPress filename sanitization.
     */
    $safe_name = sanitize_file_name( $name );

    /*
     * Reject names that were changed during sanitization.
     *
     * This prevents silently converting an unsafe name
     * into a different name.
     */
    if (
        $safe_name === '' ||
        $safe_name !== $name
    ) {
        return false;
    }

    return $safe_name;
}
