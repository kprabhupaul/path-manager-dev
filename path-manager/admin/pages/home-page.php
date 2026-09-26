<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mpm_home_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $raw_path = filter_input(
        INPUT_GET,
        'path',
        FILTER_DEFAULT
    );

    $directory = ! empty( $raw_path )
        ? mpm_normalize_path(
            sanitize_text_field(
                wp_unslash( $raw_path )
            )
        )
        : '';

    /*
     * Absolute path displayed in the file manager.
     */
    $display_path = MPM_ROOT_PATH;

    if ( $directory !== '' ) {
        $display_path =
            trailingslashit( MPM_ROOT_PATH ) .
            $directory .
            '/';
    }

    ?>

    <div class="wrap">

        <h1>Path Manager</h1>

        <div
            class="card"
            style="max-width:100%;box-sizing:border-box;"
        >

            <p style="display:flex;gap:8px;flex-wrap:wrap;">

                <button
                    type="button"
                    id="mpm-back"
                    class="button"
                    onclick="mpm_go_back()"
                >
                    Back
                </button>

                <button
                    type="button"
                    id="mpm-new-folder"
                    class="button button-primary"
                    onclick="mpm_new_asset( 'folder' );"
                >
                    New Folder
                </button>

                <button
                    type="button"
                    id="mpm-new-file"
                    class="button button-primary"
                    onclick="mpm_new_asset( 'file' );"
                >
                    New File
                </button>

            </p>

            <hr>

            <h3>
                Contents: 
                <span id="mpm-current-path">
                    <?php echo esc_html( $display_path ); ?>
                </span>
            </h3>

            <div
                id="mpm-path-assets"
                style="border:1px solid #dcdcde;border-radius:4px;"
            ></div>

        </div>

    </div>

    <script>

    const mpmFileManagerNonce =
        <?php echo wp_json_encode(
            wp_create_nonce( 'mpm_file_manager_action' )
        ); ?>;


    const mpmNewAssetNonce =
        <?php echo wp_json_encode(
            wp_create_nonce( 'mpm_new_asset_action' )
        ); ?>;


    const mpmRootPath =
        <?php echo wp_json_encode(
            trailingslashit( MPM_ROOT_PATH )
        ); ?>;


    function mpmEscapeHtml( value ) {

        const div = document.createElement( 'div' );

        div.textContent = String( value );

        return div.innerHTML;
    }


function mpmRenderPathAssets( items, directory ) {

    const container =
        document.getElementById( 'mpm-path-assets' );

    container.innerHTML = '';

    if ( ! Array.isArray( items ) || items.length === 0 ) {

        container.innerHTML =
            '<p style="padding:12px;"><em>This directory is empty.</em></p>';

        return;
    }

    items.forEach( function ( item ) {

        const relativePath =
            directory !== ''
                ? directory + '/' + item.name
                : item.name;

        const row = document.createElement( 'div' );

        row.style.cssText =
            'display:flex;' +
            'align-items:center;' +
            'justify-content:space-between;' +
            'gap:15px;' +
            'padding:10px 12px;' +
            'border-bottom:1px solid #dcdcde;';

        const left = document.createElement( 'div' );
        const right = document.createElement( 'div' );

right.style.cssText =
    'display:flex;' +
    'align-items:center;';

        /*
         * Folder
         */
        if ( item.type === 'folder' ) {

            const folderButton =
                document.createElement( 'button' );

            folderButton.type = 'button';
            folderButton.className = 'button-link';

            folderButton.style.cssText =
                'font-weight:600;' +
                'font-size:15px;' +
                'color:#2271b1;' +
                'text-decoration:none;' +
                'padding:0;' +
                'border:0;' +
                'background:none;' +
                'cursor:pointer;';

            folderButton.innerHTML =
                '<span style="' +
                    'display:inline-block;' +
                    'width:28px;' +
                    'font-size:20px;' +
                    'line-height:1;' +
                    'text-decoration:none;' +
                '">📁</span>' +
                '<span style="' +
                    'font-size:15px;' +
                    'text-decoration:none;' +
                '">' +
                    mpmEscapeHtml( item.name ) +
                '</span>';

            folderButton.addEventListener(
                'click',
                function () {

                    view_path_assets(
                        relativePath,
                        true
                    );

                }
            );

            left.appendChild(
                folderButton
            );


        /*
         * File
         */
        } else {

            const fileLabel =
                document.createElement( 'button' );

            fileLabel.type = 'button';
            fileLabel.className = 'button-link';

            fileLabel.style.cssText =
                'font-weight:600;' +
                'font-size:15px;' +
                'color:#50575e;' +
                'text-decoration:none;' +
                'padding:0;' +
                'border:0;' +
                'background:none;' +
                'cursor:pointer;';

            fileLabel.innerHTML =
                '<span style="' +
                    'display:inline-block;' +
                    'width:28px;' +
                    'font-size:20px;' +
                    'line-height:1;' +
                    'text-decoration:none;' +
                '">📄</span>' +
                '<span style="' +
                    'font-size:15px;' +
                    'text-decoration:none;' +
                '">' +
                    mpmEscapeHtml( item.name ) +
                '</span>';

            fileLabel.dataset.target =
                relativePath;

            fileLabel.setAttribute(
                'onclick',
                'edit_path_file(this)'
            );

            left.appendChild(
                fileLabel
            );
        }


const actionSelect =
    document.createElement( 'select' );

actionSelect.style.cssText =
    'min-width:100px;' +
    'max-width:120px;' +
    'height:32px;' +
    'padding:0 8px;' +
    'border:1px solid #8c8f94;' +
    'border-radius:3px;' +
    'background-color:#fff;' +
    'cursor:pointer;';
		
const defaultOption =
    document.createElement( 'option' );

defaultOption.value = '';
defaultOption.textContent = 'Action';
defaultOption.selected = true;

actionSelect.appendChild(
    defaultOption
);


/*
 * Download
 */
const downloadOption =
    document.createElement( 'option' );

downloadOption.value = 'download';
downloadOption.textContent = 'Download';

actionSelect.appendChild(
    downloadOption
);


/*
 * Rename
 */
const renameOption =
    document.createElement( 'option' );

renameOption.value = 'rename';
renameOption.textContent = 'Rename';

actionSelect.appendChild(
    renameOption
);


/*
 * Delete
 */
const deleteOption =
    document.createElement( 'option' );

deleteOption.value = 'delete';
deleteOption.textContent = 'Delete';

actionSelect.appendChild(
    deleteOption
);


/*
 * Store target path.
 */
actionSelect.dataset.target =
    relativePath;

actionSelect.dataset.name =
    item.name;


/*
 * Handle action.
 */
actionSelect.addEventListener(
    'change',
    function () {

        const action =
            this.value;

        if ( ! action ) {
            return;
        }

        if ( action === 'download' ) {

            download_path_asset( this );

        } else if ( action === 'rename' ) {

            rename_path_asset( this );

        } else if ( action === 'delete' ) {

            delete_path_asset( this );

        }

        /*
         * Reset back to Action.
         */
        this.value = '';

    }
);

right.appendChild(
    actionSelect
);

        row.appendChild( left );
        row.appendChild( right );

        container.appendChild( row );

    } );
}
		
    let directory =
        <?php echo wp_json_encode( $directory ); ?>;


    function mpm_update_back_button() {

        const backButton =
            document.getElementById(
                'mpm-back'
            );

        if ( ! backButton ) {
            return;
        }

        backButton.disabled =
            directory === '';

    }


    function mpm_go_back() {

        if ( directory === '' ) {
            return;
        }

        if (
            window.history.state &&
            window.history.state.mpmPath !== undefined
        ) {

            window.history.back();

            return;
        }

        const parts =
            directory.split( '/' ).filter(
                function ( part ) {
                    return part !== '';
                }
            );

        parts.pop();

        const parentPath =
            parts.join( '/' );

        view_path_assets(
            parentPath,
            true
        );
    }


    function mpm_set_history( path ) {

        const url =
            new URL(
                window.location.href
            );

        url.searchParams.set(
            'page',
            'mpm'
        );

        if ( path !== '' ) {

            url.searchParams.set(
                'path',
                path
            );

        } else {

            url.searchParams.delete(
                'path'
            );
        }

        window.history.pushState(
            {
                mpmPath: path
            },
            '',
            url.toString()
        );
    }


    function mpm_new_asset( assetType ) {

        const assetName =
            window.prompt(
                assetType === 'folder'
                    ? 'Enter the new folder name:'
                    : 'Enter the new file name:'
            );

        if (
            assetName === null ||
            assetName.trim() === ''
        ) {
            return;
        }

        const formData =
            new FormData();

        formData.append(
            'action',
            'mpm_new_asset'
        );

        formData.append(
            'directory',
            directory
        );

        formData.append(
            'type',
            assetType
        );

        formData.append(
            'name',
            assetName.trim()
        );

        formData.append(
            '_ajax_nonce',
            mpmNewAssetNonce
        );

        fexios.post(
            '',
            formData
        )
        .then( function ( response ) {

            const result =
                response.data;

            if (
                ! result ||
                ! result.success
            ) {

                throw new Error(
                    result &&
                    result.data &&
                    result.data.message
                        ? result.data.message
                        : (
                            assetType === 'folder'
                                ? 'Unable to create folder.'
                                : 'Unable to create file.'
                        )
                );
            }

            view_path_assets();

        } )
        .catch( function ( error ) {

            alert(
                error.message ||
                (
                    assetType === 'folder'
                        ? 'Unable to create folder.'
                        : 'Unable to create file.'
                )
            );

        } );
    }


function view_path_assets(
    path = null,
    updateHistory = false,
    scrollTop = true
) {

    const requestedPath =
        path !== null
            ? path
            : directory;

    const formData =
        new FormData();

    formData.append(
        'action',
        'mpm_view_path_assets'
    );

    formData.append(
        'path',
        requestedPath
    );

    formData.append(
        '_ajax_nonce',
        mpmFileManagerNonce
    );

    fexios.post(
        '',
        formData
    )
    .then( function ( response ) {

        const result =
            response.data;

        if (
            ! result ||
            ! result.success
        ) {

            throw new Error(
                result &&
                result.data &&
                result.data.message
                    ? result.data.message
                    : 'Unable to load directory.'
            );
        }

        directory =
            result.data.path;

        if ( updateHistory ) {

            mpm_set_history(
                directory
            );
        }

        /*
         * Update current absolute path.
         */
        document.getElementById(
            'mpm-current-path'
        ).textContent =
            mpmRootPath +
            directory +
            (
                directory !== ''
                    ? '/'
                    : ''
            );

        mpm_update_back_button();

        /*
         * Render directory contents.
         */
        mpmRenderPathAssets(
            result.data.items,
            directory
        );

        /*
         * Scroll the complete document
         * back to the top.
         */
        if ( scrollTop ) {

            window.scrollTo(
                {
                    top: 0,
                    behavior: 'smooth'
                }
            );

        }

    } )
    .catch( function ( error ) {

        alert(
            error.message ||
            'Unable to load directory.'
        );

    } );
}
    function edit_path_file( button ) {

        const file =
            button.dataset.target;

        const url =
            new URL(
                <?php echo wp_json_encode(
                    admin_url(
                        'admin.php?page=mpm-edit-file'
                    )
                ); ?>,
                window.location.origin
            );

        url.searchParams.set(
            'file',
            file
        );

        window.location.href =
            url.toString();
    }


    function download_path_asset( button ) {

        const target =
            button.dataset.target;

        const url =
            new URL(
                <?php echo wp_json_encode(
                    admin_url(
                        'admin-post.php?action=mpm_download_path_asset'
                    )
                ); ?>,
                window.location.origin
            );

        url.searchParams.set(
            'path',
            target
        );

        url.searchParams.set(
            '_wpnonce',
            <?php echo wp_json_encode(
                wp_create_nonce(
                    'mpm_download_path_asset'
                )
            ); ?>
        );

        window.location.href =
            url.toString();
    }


    function rename_path_asset( button ) {

        const newName =
            window.prompt(
                'Enter the new name:',
                button.dataset.name
            );

        if (
            newName === null ||
            newName.trim() === ''
        ) {
            return;
        }

        const formData =
            new FormData();

        formData.append(
            'action',
            'mpm_rename_path_asset'
        );

        formData.append(
            'target',
            button.dataset.target
        );

        formData.append(
            'name',
            newName
        );

        formData.append(
            '_ajax_nonce',
            mpmFileManagerNonce
        );

        fexios.post(
            '',
            formData
        )
        .then( function ( response ) {

            const result =
                response.data;

            if (
                ! result ||
                ! result.success
            ) {

                throw new Error(
                    result &&
                    result.data &&
                    result.data.message
                        ? result.data.message
                        : 'Unable to rename item.'
                );
            }

            view_path_assets(
                directory,
                false,
                false
            );

        } )
        .catch( function ( error ) {

            alert(
                error.message ||
                'Unable to rename item.'
            );

        } );
    }


    function delete_path_asset( button ) {

        const assetName =
            button.dataset.name ||
            button.dataset.target;

        if (
            ! window.confirm(
                'Are you sure you want to delete ' +
                assetName +
                '?'
            )
        ) {
            return;
        }

        const formData =
            new FormData();

        formData.append(
            'action',
            'mpm_delete_path_asset'
        );

        formData.append(
            'target',
            button.dataset.target
        );

        formData.append(
            '_ajax_nonce',
            mpmFileManagerNonce
        );

        fexios.post(
            '',
            formData
        )
        .then( function ( response ) {

            const result =
                response.data;

            if (
                ! result ||
                ! result.success
            ) {

                throw new Error(
                    result &&
                    result.data &&
                    result.data.message
                        ? result.data.message
                        : 'Unable to delete item.'
                );
            }

            view_path_assets(
                directory,
                false,
                false
            );

        } )
        .catch( function ( error ) {

            alert(
                error.message ||
                'Unable to delete item.'
            );

        } );
    }


    document.addEventListener(
        'DOMContentLoaded',
        function () {

            window.history.replaceState(
                {
                    mpmPath: directory
                },
                '',
                window.location.href
            );

            mpm_update_back_button();

            view_path_assets(
                directory,
                false
            );

        }
    );


    window.addEventListener(
        'popstate',
        function ( event ) {

            const state =
                event.state;

            if (
                state &&
                state.mpmPath !== undefined
            ) {

                view_path_assets(
                    state.mpmPath,
                    false
                );
            }

        }
    );

    </script>

    <?php
}