<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler for loading an existing file.
 */
function mpm_ajax_load_file() {

    if ( ! current_user_can( 'manage_options' ) ) {

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


    if (
        ! is_file( $safe_file ) ||
        ! is_readable( $safe_file )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Unable to read the file.',
            )
        );
    }


    $file_code =
        file_get_contents(
            $safe_file
        );


    if ( $file_code === false ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Unable to read the file.',
            )
        );
    }


    /*
     * Detect editor type here.
     *
     * Use the same editor-type detection logic
     * that was previously inside mpm_load_file().
     */
    $extension =
        strtolower(
            pathinfo(
                $safe_file,
                PATHINFO_EXTENSION
            )
        );


    $editor_type =
        'text/plain';


    switch ( $extension ) {

        case 'php':
            $editor_type = 'application/x-httpd-php';
            break;

        case 'js':
            $editor_type = 'text/javascript';
            break;

        case 'css':
            $editor_type = 'text/css';
            break;

        case 'html':
        case 'htm':
            $editor_type = 'text/html';
            break;

        case 'json':
            $editor_type = 'application/json';
            break;

        case 'xml':
            $editor_type = 'application/xml';
            break;

        case 'md':
            $editor_type = 'text/markdown';
            break;

        case 'sql':
            $editor_type = 'text/x-sql';
            break;
    }


    wp_send_json_success(
        array(
            'path' =>
                $file_path,

            'code' =>
                $file_code,

            'editor_type' =>
                $editor_type,
        )
    );
}


add_action(
    'wp_ajax_mpm_load_file',
    'mpm_ajax_load_file'
);