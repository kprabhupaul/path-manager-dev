<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Delete a file or an empty folder inside the MPM root path.
 *
 * Non-empty folders are intentionally not deleted recursively.
 *
 * @param string $target_relative_path Relative path of the item.
 * @return true|WP_Error
 */
function mpm_delete_path_asset(
    $target_relative_path
) {

    /*
     * Resolve the target as a file first.
     */
    $target = mpm_get_safe_file(
        $target_relative_path
    );

    /*
     * If it is not a file, try resolving it as a directory.
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
     * Never allow the MPM root path to be deleted.
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
            'The MPM root path cannot be deleted.'
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
     * Delete a file.
     */
    if ( is_file( $target ) ) {

        if ( ! $wp_filesystem->delete( $target, false ) ) {

            return new WP_Error(
                'delete_failed',
                'Unable to delete file.'
            );
        }

        return true;
    }

    /*
     * Delete a folder only when it is empty.
     *
     * Recursive deletion is intentionally avoided
     * to prevent accidental deletion of plugin contents.
     */
    if ( is_dir( $target ) ) {

        $items = scandir(
            $target
        );

        if ( $items === false ) {

            return new WP_Error(
                'directory_read_failed',
                'Unable to read the folder.'
            );
        }

        if ( count( $items ) > 2 ) {

            return new WP_Error(
                'folder_not_empty',
                'Folder is not empty. Delete its contents first.'
            );
        }

        if ( ! $wp_filesystem->delete( $target, false ) ) {

            return new WP_Error(
                'delete_failed',
                'Unable to delete folder.'
            );
        }

        return true;
    }

    return new WP_Error(
        'unsupported_target',
        'Unsupported file system item.'
    );
}

/**
 * AJAX handler for deleting a file or folder.
 */
function mpm_ajax_delete_path_asset() {

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

    $result = mpm_delete_path_asset(
        $target
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
            'message' => 'Item deleted successfully.',
        )
    );
}

add_action(
    'wp_ajax_mpm_delete_path_asset',
    'mpm_ajax_delete_path_asset'
);
