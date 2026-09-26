<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Download a file or a folder as a ZIP archive.
 */
function mpm_download_path_asset() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            esc_html( 'You do not have permission to perform this action.' ),
            403
        );
    }

    if (
        ! isset( $_GET['_wpnonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
            'mpm_download_path_asset'
        )
    ) {
        wp_die( esc_html( 'Security check failed.' ), 403 );
    }

    $path = isset( $_GET['path'] )
        ? mpm_normalize_path(
            sanitize_text_field( wp_unslash( $_GET['path'] ) )
        )
        : '';

    if ( $path === '' ) {
        wp_die( esc_html( 'Invalid asset path.' ), 400 );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    if ( ! WP_Filesystem() ) {
        wp_die( esc_html( 'Unable to initialize the WordPress filesystem.' ), 500 );
    }

    global $wp_filesystem;

    $safe_file = mpm_get_safe_file( $path );

    if ( $safe_file !== false && is_file( $safe_file ) && is_readable( $safe_file ) ) {

        $filename = basename( $safe_file );

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();

        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename="' . str_replace( '"', '', $filename ) . '"' );
        header( 'Content-Length: ' . (string) filesize( $safe_file ) );

        $file_contents = $wp_filesystem->get_contents( $safe_file );

        if ( $file_contents === false ) {
            wp_die( esc_html( 'Unable to read the file.' ), 500 );
        }

        // Raw file bytes must be sent unchanged for the download response.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary/file content must not be escaped.
        echo $file_contents;
        exit;
    }

    $safe_directory = mpm_get_safe_directory( $path );

    if ( $safe_directory !== false && is_dir( $safe_directory ) ) {

        if ( ! class_exists( 'ZipArchive' ) ) {
            wp_die( esc_html( 'ZIP support is not available on this server.' ), 500 );
        }

        $root_name = basename( rtrim( $safe_directory, DIRECTORY_SEPARATOR ) );
        $zip_path  = wp_tempnam( $root_name . '.zip' );

        if ( ! $zip_path ) {
            wp_die( esc_html( 'Unable to create temporary ZIP file.' ), 500 );
        }

        $zip = new ZipArchive();

        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            $wp_filesystem->delete( $zip_path, false );
            wp_die( esc_html( 'Unable to create ZIP archive.' ), 500 );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $safe_directory,
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $iterator as $item ) {

            $absolute_path = $item->getPathname();
            $relative_path = substr(
                $absolute_path,
                strlen( rtrim( $safe_directory, DIRECTORY_SEPARATOR ) ) + 1
            );

            $archive_path = $root_name . '/' . str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $relative_path
            );

            if ( $item->isDir() ) {
                $zip->addEmptyDir( $archive_path );
            } elseif ( $item->isFile() && $item->isReadable() ) {
                $zip->addFile( $absolute_path, $archive_path );
            }
        }

        $zip->close();

        if ( ! is_readable( $zip_path ) ) {
            $wp_filesystem->delete( $zip_path, false );
            wp_die( esc_html( 'Unable to read ZIP archive.' ), 500 );
        }

        $download_name = $root_name . '.zip';

        while ( ob_get_level() ) {
            ob_end_clean();
        }

        nocache_headers();

        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . str_replace( '"', '', $download_name ) . '"' );
        header( 'Content-Length: ' . (string) filesize( $zip_path ) );

        $zip_contents = $wp_filesystem->get_contents( $zip_path );

        if ( $zip_contents === false ) {
            $wp_filesystem->delete( $zip_path, false );
            wp_die( esc_html( 'Unable to read ZIP archive.' ), 500 );
        }

        // Raw ZIP bytes must be sent unchanged for the download response.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary/ZIP content must not be escaped.
        echo $zip_contents;
        $wp_filesystem->delete( $zip_path, false );
        exit;
    }

    wp_die( esc_html( 'Asset not found.' ), 404 );
}

add_action(
    'admin_post_mpm_download_path_asset',
    'mpm_download_path_asset'
);
