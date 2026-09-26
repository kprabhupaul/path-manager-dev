<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rename a file or folder inside the MPM root path.
 *
 * @param string $target_relative_path Relative path of the item.
 * @param string $new_name             New file/folder name.
 * @return true|WP_Error
 */
function mpm_rename_path_asset(
    $target_relative_path,
    $new_name
) {

    /*
     * Resolve and validate the existing target.
     *
     * This works for both files and folders.
     */
    $target = mpm_get_safe_file(
        $target_relative_path
    );

    /*
     * get_safe_file() only accepts files, so if it is not
     * a file, resolve it as a directory instead.
     */
    if ( $target === false ) {

        $target = mpm_get_safe_directory(
            $target_relative_path
        );
    }

    if ( $target === false ) {

        return new WP_Error(
            'invalid_target',
            'Invalid or non-existent file or folder.'
        );
    }

    /*
     * Validate the new name.
     */
    $safe_name = mpm_validate_asset_name(
        $new_name
    );

    if ( $safe_name === false ) {

        return new WP_Error(
            'invalid_name',
            'Invalid new name.'
        );
    }

    /*
     * Do not allow the MPM root path to be renamed.
     */
    $plugin_root = realpath(
        MPM_ROOT_PATH
    );

    if (
        $plugin_root !== false &&
        untrailingslashit( $plugin_root ) ===
        untrailingslashit( $target )
    ) {

        return new WP_Error(
            'protected_root',
            'The MPM root path cannot be renamed.'
        );
    }

    /*
     * Build the new target path in the same parent directory.
     */
    $parent_directory = dirname(
        $target
    );

    $new_target =
        $parent_directory .
        DIRECTORY_SEPARATOR .
        $safe_name;

    /*
     * Prevent renaming to an existing file/folder.
     */
    if ( file_exists( $new_target ) ) {

        return new WP_Error(
            'already_exists',
            'A file or folder with this name already exists.'
        );
    }

    /*
     * Load the WordPress filesystem API.
     */
    require_once ABSPATH . 'wp-admin/includes/file.php';

    if ( ! WP_Filesystem() ) {

        return new WP_Error(
            'filesystem_unavailable',
            'Unable to initialize the WordPress filesystem.'
        );
    }

    global $wp_filesystem;

    /*
     * Rename the item.
     */
    if ( ! $wp_filesystem->move( $target, $new_target, false ) ) {

        return new WP_Error(
            'rename_failed',
            'Unable to rename the file or folder.'
        );
    }

    return true;
}

/**
 * AJAX handler for renaming a file or folder.
 */
function mpm_ajax_rename_path_asset() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array( 'message' => 'Permission denied.' ),
            403
        );
    }

    check_ajax_referer( 'mpm_file_manager_action' );

    $target = isset( $_POST['target'] )
        ? sanitize_text_field( wp_unslash( $_POST['target'] ) )
        : '';

    $name = isset( $_POST['name'] )
        ? sanitize_text_field( wp_unslash( $_POST['name'] ) )
        : '';

    $result = mpm_rename_path_asset(
        $target,
        $name
    );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error(
            array(
                'message' => $result->get_error_message(),
            )
        );
    }

    wp_send_json_success(
        array(
            'message' => 'Item renamed successfully.',
        )
    );
}

add_action(
    'wp_ajax_mpm_rename_path_asset',
    'mpm_ajax_rename_path_asset'
);
