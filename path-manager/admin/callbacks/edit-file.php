<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler for updating an existing file.
 */
function mpm_ajax_edit_file() {

    if (
        ! current_user_can(
            'manage_options'
        )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'You do not have permission to perform this action.',
            ),
            403
        );
    }


    check_ajax_referer(
        'mpm_edit_file_action'
    );


    $file_path =
        isset( $_POST['file'] )
            ? mpm_normalize_path(
                sanitize_text_field(
                    wp_unslash( $_POST['file'] )
                )
            )
            : '';


    $file_code =
        isset( $_POST['code'] )
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File contents must be preserved verbatim.
            ? wp_unslash( $_POST['code'] )
            : '';


    /*
     * Get the safe file path.
     */
    $safe_file =
        mpm_get_safe_file(
            $file_path
        );


    if ( $safe_file === false ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Invalid or non-existent file path.',
            )
        );
    }


    /*
     * Make sure it is an actual file.
     */
    if ( ! is_file( $safe_file ) ) {

        wp_send_json_error(
            array(
                'message' =>
                    'File cannot be updated.',
            )
        );
    }


    /*
     * Load the WordPress filesystem API.
     */
    require_once ABSPATH . 'wp-admin/includes/file.php';

    if ( ! WP_Filesystem() ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Unable to initialize the WordPress filesystem.',
            ),
            500
        );
    }

    global $wp_filesystem;


    /*
     * Update the file.
     */
    if ( ! $wp_filesystem->put_contents( $safe_file, $file_code, FS_CHMOD_FILE ) ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Unable to update the file.',
            )
        );
    }


    /*
     * Return success.
     */
    wp_send_json_success(
        array(
            'path' =>
                $file_path,

            'message' =>
                'File updated successfully.',
        )
    );
}


add_action('wp_ajax_mpm_edit_file', 'mpm_ajax_edit_file');
