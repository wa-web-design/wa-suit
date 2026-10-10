<?php
/**
 * Main plugin class.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WA_SUIT_PATH . 'includes/class-wa-suit-security.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-settings.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-module-loader.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-admin.php';

/**
 * Coordinates plugin components.
 */
class WA_Suit {
	/**
	 * Registers plugin hooks and loads enabled components.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		$settings      = new WA_Suit_Settings();
		$module_loader = new WA_Suit_Module_Loader( $settings );
		$module_loader->load();

		if ( is_admin() ) {
			$admin = new WA_Suit_Admin( $settings, $module_loader );
			$admin->register_hooks();
		}
	}

	/**
	 * Loads translations for the plugin.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wa-suit',
			false,
			dirname( plugin_basename( WA_SUIT_FILE ) ) . '/languages'
		);
	}
}
