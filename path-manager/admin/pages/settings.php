<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Path Manager settings page.
 */
function mpm_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$message      = '';
	$message_type = 'success';
	
	$default_directory = WP_CONTENT_DIR;

	$root_path = get_option( 'mpm_root_path', $default_directory );

	if ( isset( $_POST['mpm_settings_submit'] ) ) {

		if ( ! isset( $_POST['mpm_settings_nonce'] ) || ! wp_verify_nonce(sanitize_text_field(wp_unslash( $_POST['mpm_settings_nonce'] )), 'mpm_settings_action') ) {
			wp_die( 'Security check failed.' );
		}

		$new_root_path = isset( $_POST['mpm_root_path'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['mpm_root_path'] ) ) ) : '';
		
		// Empty path or "/" means WordPress content directory.
		
		if ( $new_root_path === '' || $new_root_path === '/' ) {
			$new_root_path = $default_directory;
		}

		$real_path = realpath( $new_root_path );

		if ( $real_path === false || ! is_dir( $real_path ) ) {

			$message      = 'Invalid or non-existent directory path.';
			$message_type = 'error';

		} else {

			$real_path = untrailingslashit( $real_path );

			update_option( 'mpm_root_path', $real_path );

			$root_path = $real_path;

			$message = 'Root path updated successfully.';
		}
	}

	?>

	<div class="wrap">

		<h1>Path Manager</h1>

		<?php if ( $message !== '' ) : ?>

			<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
				<p>
					<?php echo esc_html( $message ); ?>
				</p>
			</div>

		<?php endif; ?>

		<div class="card" style="max-width:700px; box-sizing:border-box;">

			<h2>Settings</h2>

			<form method="post">

				<?php
					wp_nonce_field( 'mpm_settings_action', 'mpm_settings_nonce' );
				?>

				<table class="form-table">

					<tr>
						<th scope="row">
							<label for="mpm_root_path">
								Root Path
							</label>
						</th>

						<td>

							<input type="text" id="mpm_root_path" name="mpm_root_path" value="<?php echo esc_attr( $root_path ); ?>" class="large-text" placeholder="<?php echo esc_attr( $default_directory ); ?>">

							<p class="description" style="margin-top:10px;">
								Leave empty to use the WordPress content directory
							</p>

						</td>

					</tr>

				</table>

				<p class="submit">

					<button type="submit" name="mpm_settings_submit" value="1" class="button button-primary">
						Save Changes
					</button>

				</p>

			</form>

		</div>
		
	</div>

	<?php
}