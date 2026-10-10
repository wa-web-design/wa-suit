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
require_once WA_SUIT_PATH . 'includes/class-wa-suit-module-base.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-module-registry.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-module-loader.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-admin.php';

/**
 * Coordinates plugin components.
 */
class WA_Suit {

	/**
	 * Settings instance.
	 *
	 * @var WA_Suit_Settings
	 */
	private $settings;

	/**
	 * Module loader instance.
	 *
	 * @var WA_Suit_Module_Loader
	 */
	private $module_loader;

	/**
	 * Admin controller instance.
	 *
	 * @var WA_Suit_Admin|null
	 */
	private $admin = null;

	/**
	 * Registers plugin hooks and loads enabled components.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		$this->settings      = new WA_Suit_Settings();
		$this->module_loader = new WA_Suit_Module_Loader( $this->settings );
		$this->module_loader->load();

		if ( is_admin() ) {
			$this->admin = new WA_Suit_Admin( $this->settings, $this->module_loader );
			$this->admin->register_hooks();
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
