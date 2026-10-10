<?php
/**
 * Discovers and loads modules.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads module files and bootstraps active features.
 */
class WA_Suit_Module_Loader {

	/**
	 * Settings instance.
	 *
	 * @var WA_Suit_Settings
	 */
	private $settings;

	/**
	 * Module registry instance.
	 *
	 * @var WA_Suit_Module_Registry
	 */
	private $registry;

	/**
	 * Sets up the module loader.
	 *
	 * @param WA_Suit_Settings $settings Global settings instance.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		$this->registry = WA_Suit_Module_Registry::get_instance();
	}

	/**
	 * Discovers module files, registers them, and boots active ones.
	 *
	 * @return void
	 */
	public function load() {
		$this->discover_and_include_modules();

		$all_modules = $this->registry->get_all();

		foreach ( $all_modules as $module ) {
			$module->set_settings_manager( $this->settings );

			if ( $module->is_active() ) {
				$module->init();
			}
		}
	}

	/**
	 * Returns the registry instance.
	 *
	 * @return WA_Suit_Module_Registry
	 */
	public function get_registry() {
		return $this->registry;
	}

	/**
	 * Scans the modules directory and includes entry point files.
	 *
	 * @return void
	 */
	private function discover_and_include_modules() {
		$modules_dir = WA_SUIT_PATH . 'modules';

		if ( ! is_dir( $modules_dir ) ) {
			return;
		}

		// 1. Check for standalone PHP files directly in modules/*.php.
		$direct_files = glob( $modules_dir . '/*.php' );
		if ( is_array( $direct_files ) ) {
			foreach ( $direct_files as $file ) {
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		}

		// 2. Check for subdirectories in modules/*/.
		$directories = glob( $modules_dir . '/*', GLOB_ONLYDIR );
		if ( is_array( $directories ) ) {
			foreach ( $directories as $dir ) {
				$slug = basename( $dir );

				$slug_file  = $dir . '/' . $slug . '.php';
				$index_file = $dir . '/index.php';

				if ( is_readable( $slug_file ) ) {
					require_once $slug_file;
				} elseif ( is_readable( $index_file ) ) {
					require_once $index_file;
				}
			}
		}
	}
}
