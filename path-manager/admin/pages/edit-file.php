<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// a function
function mpm_edit_file_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$raw_file = filter_input( INPUT_GET, 'file', FILTER_DEFAULT );

	$initial_file = ! empty( $raw_file )
		? mpm_normalize_path( sanitize_text_field( wp_unslash( $raw_file ) ) )
		: '';

	$safe_file = false;
	$file_code = '';
	$file_error = '';

	if ( $initial_file !== '' ) {

		$safe_file = mpm_get_safe_file( $initial_file );

		if ( $safe_file === false ) {

			$file_error = 'Invalid or non-existent file path.';

		} elseif ( ! is_readable( $safe_file ) ) {

			$file_error = 'Unable to read the file.';

		} else {

			$file_code = file_get_contents( $safe_file );

			if ( $file_code === false ) {
				$file_error = 'Unable to read the file.';
			}
		}

	} else {

		$file_error = 'No file was specified in the URL.';
	}

	/*
	 * Load CodeMirror UI.
	 *
	 * The file argument lets WordPress detect the
	 * editor type from the file extension automatically.
	 */
	$code_editor_settings = wp_enqueue_code_editor(
		array(
			'file'       => $initial_file,
			'codemirror' => array(
				'matchBrackets'				=> true,
				'autoCloseTags'				=> true,
				'matchTags'					=> array(
					'bothTags' => true,
				),
            	'styleSelectedText'			=> true,
            	'highlightSelectionMatches' => true,
				'gutters'     				=> array(
                	'CodeMirror-lint-markers',
                	'CodeMirror-foldgutter',
            	),
				'foldGutter'                => true,
			),
		)
	);

	?>

	<style>
		.mpm-edit-file-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			background: #fff;
			padding: 0 20px;
			margin: 20px 0 20px -20px;
			box-sizing: border-box;
		}

		.mpm-edit-file-header-title {
			margin: 0;
			font-weight: 600;
		}

		.mpm-edit-file-header-actions {
			margin: 1em 0;
		}

		.mpm-edit-file-message {
			margin-top: 15px;
		}

		.mpm-edit-file-message.is-hidden {
			display: none;
		}

		.mpm-edit-file-editor {
			margin-top: 20px;
			width: 100%;
			box-sizing: border-box;
			border: 1px solid #dcdcde;
			border-radius: 4px;
			overflow: hidden;
		}

		.mpm-edit-file-editor-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 5px 10px;
			background: #f6f7f7;
			border-bottom: 1px solid #dcdcde;
		}

		.mpm-edit-file-name {
			font-size: 16px;
			font-weight: 500;
			margin: 10px;
		}

		.mpm-edit-file-code {
			display: block;
			width: 100%;
			margin: 0;
			border: 0;
			border-radius: 0;
			box-sizing: border-box;
		}

		.mpm-edit-file-code + .CodeMirror {
			height: 400px;
		}
		
		.cm-matchhighlight {
    		background-color: #e2effd !important;
		}
		
		.CodeMirror-foldgutter-open,
		.CodeMirror-foldgutter-folded {
    		text-align: center;
		}
		
		.mpm-btn-bold {
			font-weight: 600 !important;
		}
		
	</style>

	<div class="mpm-edit-file-header" style="height: 70px;">

		<h1 class="mpm-edit-file-header-title">
			Edit File
		</h1>

		<button
			type="button"
			id="mpm-edit-file-update"
			class="button button-primary mpm-btn-bold"
			onclick="mpm_update_file()"
			<?php disabled( $safe_file === false ); ?>
		>
			Update
		</button>

	</div>

	<div class="wrap">

		<div
			id="mpm-edit-file-message"
			class="mpm-edit-file-message <?php echo $file_error === '' ? 'is-hidden' : 'notice notice-error is-dismissible'; ?>"
		>

			<?php if ( $file_error !== '' ) : ?>

				<p>
					<?php echo esc_html( $file_error ); ?>
				</p>

				<button
					type="button"
					class="notice-dismiss"
					aria-label="Dismiss this notice"
				>
					<span class="screen-reader-text">
						Dismiss this notice.
					</span>
				</button>

			<?php endif; ?>

		</div>

		<div class="mpm-edit-file-editor">

			<div class="mpm-edit-file-editor-header">

				<div class="mpm-edit-file-name">
					File: <?php echo esc_html( $initial_file ); ?>
				</div>

			</div>

			<textarea
				id="mpm_file_code"
				class="mpm-edit-file-code"
				spellcheck="false"
				placeholder="File contents will appear here."
			><?php echo esc_textarea( $file_code ); ?></textarea>

		</div>

	</div>


	<script>

	const mpmEditFileNonce = <?php echo wp_json_encode( wp_create_nonce( 'mpm_edit_file_action' ) ); ?>;

	let mpmCurrentFile = <?php echo wp_json_encode( $initial_file ); ?>;

	let mpmCodeEditor = null;

	/*
	 * Code that was last successfully saved.
	 */
	let mpmSavedFileCode = <?php echo wp_json_encode( $file_code ); ?>;

	/*
	 * Whether the editor currently contains unsaved changes.
	 */
	let mpmHasUnsavedChanges = false;


	/**
	 * Get the current code from CodeMirror / textarea.
	 */
	function mpm_get_current_file_code() {

		if ( mpmCodeEditor && mpmCodeEditor.codemirror ) {

			return mpmCodeEditor.codemirror.getValue();
		}

		const textarea = document.getElementById( 'mpm_file_code' );

		return textarea ? textarea.value : '';
	}


	/**
	 * Check whether the current code differs
	 * from the last successfully saved code.
	 */
	function mpm_update_unsaved_state() {

		const currentCode = mpm_get_current_file_code();

		mpmHasUnsavedChanges = currentCode !== mpmSavedFileCode;
	}


	/**
	 * Show an admin notice.
	 */
	function mpm_show_edit_file_message( message, type = 'error' ) {

		const element = document.getElementById( 'mpm-edit-file-message' );

		element.className =
			'mpm-edit-file-message notice notice-' +
			type +
			' is-dismissible';

		element.innerHTML =
			'<p>' +
			mpmEscapeHtml( message ) +
			'</p>' +
			'<button ' +
				'type="button" ' +
				'class="notice-dismiss" ' +
				'aria-label="Dismiss this notice">' +
				'<span class="screen-reader-text">' +
					'Dismiss this notice.' +
				'</span>' +
			'</button>';

		element.style.display = 'block';

		/*
		 * Add dismiss behavior.
		 */
		const dismissButton = element.querySelector( '.notice-dismiss' );

		if ( dismissButton ) {

			dismissButton.addEventListener(
				'click',
				function () {
					mpm_clear_edit_file_message();
				}
			);
		}
	}


	/**
	 * Clear the admin notice.
	 */
	function mpm_clear_edit_file_message() {

		const element = document.getElementById( 'mpm-edit-file-message' );

		element.innerHTML = '';

		element.style.display = 'none';

		element.className = 'mpm-edit-file-message is-hidden';
	}


	/**
	 * Escape HTML before inserting a message.
	 */
	function mpmEscapeHtml( value ) {

		const div = document.createElement( 'div' );

		div.textContent = String( value );

		return div.innerHTML;
	}


	/**
	 * Update the current file.
	 */
/**
	 * Update the current file.
	 */
	function mpm_update_file() {

		if ( mpmCurrentFile === '' ) {

			mpm_show_edit_file_message(
				'Please load a file first.'
			);

			return;
		}


		let code = '';

		if ( mpmCodeEditor && mpmCodeEditor.codemirror ) {

			code = mpmCodeEditor.codemirror.getValue();

		} else {

			code = document.getElementById( 'mpm_file_code' ).value;
		}


		const formData = new FormData();

		formData.append(
			'action',
			'mpm_edit_file'
		);

		formData.append(
			'file',
			mpmCurrentFile
		);

		formData.append(
			'code',
			code
		);

		formData.append(
			'_ajax_nonce',
			mpmEditFileNonce
		);


		const updateButton =
			document.getElementById( 'mpm-edit-file-update' );

		// 1. Disable button & add WordPress core running state class
		updateButton.disabled = true;
		updateButton.classList.add( 'updating-message' );


		mpm_clear_edit_file_message();


		fexios.post( '', formData )

			.then(
				function ( response ) {

					const result = response.data;


					if (
						! result ||
						! result.success
					) {

						throw new Error(
							result &&
							result.data &&
							result.data.message
								? result.data.message
								: 'Unable to update file.'
						);
					}


					mpmCurrentFile = result.data.path;


					/*
					 * The current editor contents are now
					 * the successfully saved version.
					 */
					mpmSavedFileCode = code;

					mpmHasUnsavedChanges = false;


					mpm_show_edit_file_message(
						'File updated successfully.',
						'success'
					);

				}
			)

			.catch(
				function ( error ) {

					mpm_show_edit_file_message(
						error.message ||
						'Unable to update file.'
					);

				}
			)

			.finally(
				function () {

					// 2. Re-enable button & remove WordPress running state class
					updateButton.disabled = false;
					updateButton.classList.remove( 'updating-message' );

				}
			);
	}

	/**
	 * Warn the user when leaving the page
	 * with unsaved changes.
	 */
	window.addEventListener(
		'beforeunload',
		function ( event ) {

			if ( ! mpmHasUnsavedChanges ) {
				return;
			}

			event.preventDefault();

			/*
			 * Required by modern browsers to trigger
			 * the native unsaved-changes confirmation.
			 */
			event.returnValue = '';
		}
	);


	/**
	 * Initialize CodeMirror.
	 */
	document.addEventListener(
		'DOMContentLoaded',
		function () {

			if (
				typeof wp !== 'undefined' &&
				wp.codeEditor &&
				typeof wp.codeEditor.initialize === 'function'
			) {

				mpmCodeEditor = wp.codeEditor.initialize(
					'mpm_file_code',
					<?php echo wp_json_encode( $code_editor_settings ); ?>
				);


				if (
					mpmCodeEditor &&
					mpmCodeEditor.codemirror
				) {

					/*
					 * Track every editor change.
					 */
					mpmCodeEditor.codemirror.on(
						'change',
						function () {

							mpm_update_unsaved_state();
						}
					);


					/*
					 * Make sure the initial editor contents
					 * are considered saved.
					 */
					mpmSavedFileCode =
						mpmCodeEditor.codemirror.getValue();

					mpmHasUnsavedChanges = false;
				}
			}

		}
	);
		
	

	/**
	 * Global keydown listener to catch Ctrl+S / Cmd+S outside of CodeMirror focus.
	 */
	window.addEventListener('keydown', function(event) {
		// Check for Ctrl+S or Cmd+S (Mac)
		if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
			event.preventDefault(); // Stop default browser "Save Page As" dialog

			const updateButton = document.getElementById('mpm-edit-file-update');
			
			// Trigger save only if the button is active/enabled
			if (updateButton && !updateButton.disabled) {
				mpm_update_file();
			}
		}
	});
		
	</script>

	<?php
}