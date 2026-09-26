<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create a new file or folder inside the MPM root path.
 */
function mpm_new_asset() {

    if ( ! current_user_can( 'manage_options' ) ) {

        wp_send_json_error(
            array(
                'message' => 'You do not have permission to perform this action.',
            ),
            403
        );
    }

    check_ajax_referer(
        'mpm_new_asset_action'
    );


    $directory = isset( $_POST['directory'] )
        ? mpm_normalize_path(
            sanitize_text_field(
                wp_unslash( $_POST['directory'] )
            )
        )
        : '';


    $type = isset( $_POST['type'] )
        ? sanitize_key(
            wp_unslash(
                $_POST['type']
            )
        )
        : '';


    $name = isset( $_POST['name'] )
        ? trim(
            sanitize_text_field(
                wp_unslash( $_POST['name'] )
            )
        )
        : '';


    /*
     * Validate directory.
     */
    $safe_directory =
        mpm_get_safe_directory(
            $directory
        );


    if ( $safe_directory === false ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Invalid or non-existent directory path.',
            )
        );
    }


    /*
     * Validate asset type.
     */
    if (
        ! in_array(
            $type,
            array(
                'file',
                'folder',
            ),
            true
        )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Invalid asset type.',
            )
        );
    }


    /*
     * Validate asset name.
     */
    $safe_name =
        mpm_validate_asset_name(
            $name
        );


    if ( $safe_name === false ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Invalid file or folder name.',
            )
        );
    }

	
/*
 * Files must have an extension.
 */
if (
    $type === 'file' &&
    pathinfo(
        $safe_name,
        PATHINFO_EXTENSION
    ) === ''
) {

    wp_send_json_error(
        array(
            'message' =>
                'File name must include an extension.',
        )
    );
}

    /*
     * Build target path.
     */
    $target =
        $safe_directory .
        DIRECTORY_SEPARATOR .
        $safe_name;


    /*
     * Do not overwrite an existing asset.
     */
    if ( file_exists( $target ) ) {

        wp_send_json_error(
            array(
                'message' =>
                    'A file or folder with this name already exists.',
            )
        );
    }


    /*
     * Create folder.
     */
    if ( $type === 'folder' ) {

        if ( ! wp_mkdir_p( $target ) ) {

            wp_send_json_error(
                array(
                    'message' =>
                        'Unable to create folder.',
                )
            );
        }

        wp_send_json_success(
            array(
                'message' =>
                    'Folder created successfully.',
            )
        );
    }


    /*
     * Create file.
     */
    if (
        file_put_contents(
            $target,
            '',
            LOCK_EX
        ) === false
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'Unable to create file.',
            )
        );
    }


    wp_send_json_success(
        array(
            'message' =>
                'File created successfully.',
        )
    );
}


add_action(
    'wp_ajax_mpm_new_asset',
    'mpm_new_asset'
);