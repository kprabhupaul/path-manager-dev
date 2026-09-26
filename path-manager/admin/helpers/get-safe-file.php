<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get a safe absolute file path inside the MPM root path.
 *
 * @param string $relative_path Relative file path.
 * @return string|false Absolute file path or false when invalid.
 */
function mpm_get_safe_file( $relative_path ) {

    $root_path = realpath( MPM_ROOT_PATH );

    if (
        $root_path === false ||
        ! is_dir( $root_path )
    ) {
        return false;
    }

    $root_path = untrailingslashit( $root_path );

    $relative_path = mpm_normalize_path(
        $relative_path
    );

    /*
     * A file path cannot be empty.
     */
    if ( $relative_path === '' ) {
        return false;
    }

    /*
     * Prevent path traversal.
     */
    if ( str_contains( $relative_path, '..' ) ) {
        return false;
    }

    /*
     * Resolve the requested file.
     */
    $file = realpath(
        $root_path .
        DIRECTORY_SEPARATOR .
        $relative_path
    );

    if (
        $file === false ||
        ! is_file( $file )
    ) {
        return false;
    }

    /*
     * Make sure the resolved file is still inside
     * the MPM root path. This also blocks symlinks that
     * resolve outside the allowed root.
     */
    $root_prefix = trailingslashit(
        $root_path
    );

    if (
        ! str_starts_with(
            $file,
            $root_prefix
        )
    ) {
        return false;
    }

    return $file;
}
