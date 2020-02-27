<?php
/**
 * Plugin Name:       WP Runtime Profiler
 * Version:           1.0.1
 * Author:            Novembit
 * Text Domain:       novembit
 */

register_activation_hook( __FILE__, function () {

	$source = __DIR__ . '/mu-plugin/mu.php';
	$target = WPMU_PLUGIN_DIR . '/wp-runtime-profiler.php';

	if ( ! file_exists( WPMU_PLUGIN_DIR ) || ! is_dir( WPMU_PLUGIN_DIR ) ) {
		mkdir( WPMU_PLUGIN_DIR );
	}

	if ( ! copy( $source, $target ) ) {
		add_action(
			'admin_notices',
			function () {
				?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e( 'Can\'t install mu-plugin file!', 'novembit' ); ?></p>
                </div>
				<?php
			}
		);
	}
} );

register_deactivation_hook( __FILE__, function () {
	$target = WPMU_PLUGIN_DIR . '/wp-runtime-profiler.php';

	if ( unlink( $target ) ) {
		return true;
	}

	return false;
} );


