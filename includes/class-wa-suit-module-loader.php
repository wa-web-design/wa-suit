<?php
/**
 * Discovers and loads enabled modules.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads module entry points from the modules directory.
 */
class WA_Suit_Module_Loader {
	/**
	 * Global settings component.
	 *
	 * @var WA_Suit_Settings
	 */
	private $settings;

	/**
	 * Discovered module slugs.
	 *
	 * @var string[]
	 */
	private $available_modules = array();

	/**
	 * Sets up the module loader.
	 *
	 * @param WA_Suit_Settings $settings Global settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Loads enabled module entry points.
	 *
	 * @return void
	 */
	public function load() {
		$this->available_modules = $this->discover_modules();
		$enabled_modules          = $this->settings->get_enabled_modules( $this->available_modules );

		foreach ( $enabled_modules as $module ) {
			$module_file = WA_SUIT_PATH . 'modules/' . $module . '/' . $module . '.php';

			if ( is_readable( $module_file ) ) {
				require_once $module_file;
			}

			if ( is_admin() ) {
				$admin_file = WA_SUIT_PATH . 'modules/' . $module . '/admin.php';

				if ( is_readable( $admin_file ) ) {
					require_once $admin_file;
				}
			}
		}
	}

	/**
	 * Returns the discovered module slugs.
	 *
	 * @return string[]
	 */
	public function get_available_modules() {
		if ( empty( $this->available_modules ) ) {
			$this->available_modules = $this->discover_modules();
		}

		return $this->available_modules;
	}

	/**
	 * Finds module directories that have a matching entry point.
	 *
	 * @return string[]
	 */
	private function discover_modules() {
		$directories = glob( WA_SUIT_PATH . 'modules/*', GLOB_ONLYDIR );
		$modules     = array();

		if ( false === $directories ) {
			return $modules;
		}

		foreach ( $directories as $directory ) {
			$module = basename( $directory );

			if ( is_readable( $directory . '/' . $module . '.php' ) ) {
				$modules[] = sanitize_key( $module );
			}
		}

		sort( $modules );

		return $modules;
	}
}
