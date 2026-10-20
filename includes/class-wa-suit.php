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
require_once WA_SUIT_PATH . 'includes/class-wa-suit-debug.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-settings.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-module-base.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-module-registry.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-module-loader.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-admin.php';
require_once WA_SUIT_PATH . 'includes/class-wa-suit-elementor-widgets.php';

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
	 * Elementor widgets integration.
	 *
	 * @var WA_Suit_Elementor_Widgets
	 */
	private $elementor_widgets;

	/**
	 * Registers plugin hooks and loads enabled components.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		WA_Suit_Debug::init();

		$this->settings      = new WA_Suit_Settings();
		$this->module_loader = new WA_Suit_Module_Loader( $this->settings );
		$this->module_loader->load();

		$this->elementor_widgets = new WA_Suit_Elementor_Widgets();
		$this->elementor_widgets->register_hooks();

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
