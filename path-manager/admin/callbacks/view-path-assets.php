<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get files and sub-folders inside a directory.
 *
 * This callback only handles the directory-view logic.
 * It does not render any HTML.
 *
 * @param string $directory Relative directory path.
 * @return array|WP_Error Directory contents or error.
 */
function mpm_view_path_assets( $directory = '' ) {

    /*
     * Resolve and validate the requested directory.
     */
    $safe_directory = mpm_get_safe_directory(
        $directory
    );

    if ( $safe_directory === false ) {

        return new WP_Error(
            'invalid_directory',
            'Invalid or non-existent directory path.'
        );
    }

    $entries = scandir(
        $safe_directory
    );

    if ( $entries === false ) {

        return new WP_Error(
            'directory_read_failed',
            'Unable to read the directory.'
        );
    }

    $items = array();

    foreach ( $entries as $entry ) {

        /*
         * Ignore current and parent directory entries.
         */
        if (
            $entry === '.' ||
            $entry === '..'
        ) {
            continue;
        }

        $entry_path =
            $safe_directory .
            DIRECTORY_SEPARATOR .
            $entry;

        /*
         * Return folders and files separately
         * so the page can decide how to display them.
         */
        if ( is_dir( $entry_path ) ) {

            $items[] = array(
                'name' => $entry,
                'type' => 'folder',
            );

        } elseif ( is_file( $entry_path ) ) {

            $items[] = array(
                'name' => $entry,
                'type' => 'file',
            );
        }
    }

    /*
     * Folders first, then files.
     * Within each type, sort naturally by name.
     */
    usort(
        $items,
        static function ( $a, $b ) {

            if (
                $a['type'] !==
                $b['type']
            ) {

                return
                    $a['type'] === 'folder'
                        ? -1
                        : 1;
            }

            return strnatcasecmp(
                $a['name'],
                $b['name']
            );
        }
    );

    return $items;
}

/**
 * AJAX handler for viewing a directory.
 */
function mpm_ajax_view_path_assets() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array( 'message' => 'Permission denied.' ),
            403
        );
    }

    check_ajax_referer( 'mpm_file_manager_action' );

    $path = isset( $_POST['path'] )
        ? mpm_normalize_path(
            sanitize_text_field( wp_unslash( $_POST['path'] ) )
        )
        : '';

    $items = mpm_view_path_assets( $path );

    if ( is_wp_error( $items ) ) {
        wp_send_json_error(
            array(
                'message' => $items->get_error_message(),
            )
        );
    }

    wp_send_json_success(
        array(
            'path'  => $path,
            'items' => $items,
        )
    );
}

add_action('wp_ajax_mpm_view_path_assets', 'mpm_ajax_view_path_assets');

