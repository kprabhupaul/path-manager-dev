<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Path Manager logs.
 */
function mpm_logs_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$log_file = WP_CONTENT_DIR . '/debug.log';

	/*
	 * Delete logs.
	 */
	if ( isset( $_POST['mpm_delete_logs'] ) && isset( $_POST['mpm_delete_logs_nonce'] ) ) {

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpm_delete_logs_nonce'] ) ), 'mpm_delete_logs_action' ) ) {
			wp_die( 'Security check failed.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( WP_Filesystem() ) {

			global $wp_filesystem;

			if ( $wp_filesystem->exists( $log_file ) ) {

				$wp_filesystem->put_contents(
					$log_file,
					'',
					FS_CHMOD_FILE
				);
			}
		}
	}

	?>

	<style>
		.mpm-notice-error {
			background: #fcf0f0;
			border-left: 4px solid #cc1818;
			padding: 12px 15px;
			margin-bottom: 20px;
			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		}
	</style>

	<div class="wrap">

		<!-- Header: Page Title & Delete Button -->
		<div
			style="
				display:flex;
				align-items:center;
				justify-content:space-between;
				gap:15px;
				margin-bottom:20px;
			"
		>
			<h1 style="margin:0;">
				Logs
			</h1>

			<form method="post">
				<?php
				wp_nonce_field(
					'mpm_delete_logs_action',
					'mpm_delete_logs_nonce'
				);
				?>

				<button
					type="submit"
					name="mpm_delete_logs"
					value="1"
					class="button button-primary"
					onclick="return confirm('Are you sure you want to delete all logs?');"
				>
					Delete Logs
				</button>
			</form>
		</div>

		<?php
		// WordPress debug settings.

		$wp_debug						= defined( 'WP_DEBUG' ) ? WP_DEBUG : false;

		$wp_debug_log					= defined( 'WP_DEBUG_LOG' ) ? WP_DEBUG_LOG : false;

		$wp_debug_display				= defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : true;

		$debug_settings_need_attention	= ! $wp_debug || ! $wp_debug_log || $wp_debug_display;

		// Show warning notice when WordPress debug logging settings are not configured correctly.
		if ( $debug_settings_need_attention ) :
			?>

			<div class="mpm-notice-error">

				<p style="margin:0 0 10px;">
					<strong>Debug logging is not fully enabled.</strong>
					Add the following code to <code>wp-config.php</code> to fix it.
				</p>

				<?php
				$html_code = '';

				if ( ! $wp_debug ) {
					$html_code .= "define( 'WP_DEBUG', true );           // To enable debug mode\n";
				}

				if ( ! $wp_debug_log ) {
					$html_code .= "define( 'WP_DEBUG_LOG', true );       // To record logs/errors to debug file\n";
				}

				if ( $wp_debug_display ) {
					$html_code .= "define( 'WP_DEBUG_DISPLAY', false );  // To hide errors from the screen";
				}

				// Extra newlines remove cheyadaniki trim vaadadam manchidi
				$html_code = trim( $html_code );
				?>

				<?php if ( ! empty( $html_code ) ) : ?>
					<code style="display:inline-block; white-space:pre-wrap;"><?php echo esc_html( $html_code ); ?></code>
				<?php endif; ?>

			</div>

		<?php endif; ?>

		<?php
		/*
		 * Display log contents.
		 */
		if ( ! file_exists( $log_file ) ) :
			?>

			<div class="notice notice-info">
				<p>
					No debug log file was found.
				</p>
			</div>

		<?php else : ?>

			<?php
			$log_contents = file_get_contents( $log_file );
			?>

			<pre
				style="
					margin:0;
					padding:15px;
					min-height:500px;
					max-height:700px;
					overflow:auto;
					box-sizing:border-box;
					color:#000;
					font-family:Consolas,Monaco,'Courier New',monospace;
					font-size:13px;
					line-height:1.6;
					white-space:pre-wrap;
					word-break:break-word;
				"
			><?php

				if ( $log_contents === false ) {
					echo esc_html( 'Unable to read the log file.' );
				} elseif ( $log_contents === '' ) {
					echo esc_html( 'No logs found.' );
				} else {
					echo esc_html( $log_contents );
				}

			?></pre>

		<?php endif; ?>

	</div>

	<?php
}