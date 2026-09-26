<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get a safe absolute directory path inside the MPM root path.
 *
 * @param string $relative_path Relative directory path.
 * @return string|false Absolute directory path or false when invalid.
 */
function mpm_get_safe_directory( $relative_path ) {

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
     * Empty path means the MPM root path.
     */
    if ( $relative_path === '' ) {
        return $root_path;
    }

    /*
     * Prevent path traversal.
     */
    if ( str_contains( $relative_path, '..' ) ) {
        return false;
    }

    /*
     * Resolve the requested directory.
     */
    $directory = realpath(
        $root_path .
        DIRECTORY_SEPARATOR .
        $relative_path
    );

    if (
        $directory === false ||
        ! is_dir( $directory )
    ) {
        return false;
    }

    /*
     * Make sure the resolved directory is still inside
     * the MPM root path. This also blocks symlinks that
     * resolve outside the allowed root.
     */
    $root_prefix = trailingslashit(
        $root_path
    );

    if (
        $directory !== $root_path &&
        ! str_starts_with(
            trailingslashit( $directory ),
            $root_prefix
        )
    ) {
        return false;
    }

    return $directory;
}
