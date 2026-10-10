<?php
/**
 * Module Registry for WA Suit.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages all discovered and registered plugin modules.
 */
class WA_Suit_Module_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var WA_Suit_Module_Registry|null
	 */
	private static $instance = null;

	/**
	 * Registered modules list indexed by ID.
	 *
	 * @var array<string, WA_Suit_Module_Base>
	 */
	private $modules = array();

	/**
	 * Returns singleton instance.
	 *
	 * @return WA_Suit_Module_Registry
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton pattern.
	 */
	private function __construct() {}

	/**
	 * Registers a module instance.
	 *
	 * @param WA_Suit_Module_Base $module Module instance.
	 * @return void
	 */
	public function register( WA_Suit_Module_Base $module ) {
		$this->modules[ $module->get_id() ] = $module;
	}

	/**
	 * Retrieves a registered module by its ID.
	 *
	 * @param string $id Module slug ID.
	 * @return WA_Suit_Module_Base|null
	 */
	public function get_module( $id ) {
		return isset( $this->modules[ $id ] ) ? $this->modules[ $id ] : null;
	}

	/**
	 * Returns all registered modules sorted by order and title.
	 *
	 * @return array<string, WA_Suit_Module_Base>
	 */
	public function get_all() {
		$modules = $this->modules;

		uasort(
			$modules,
			function ( $a, $b ) {
				$order_a = $a->get_order();
				$order_b = $b->get_order();

				if ( $order_a === $order_b ) {
					return strcasecmp( $a->get_title(), $b->get_title() );
				}

				return ( $order_a < $order_b ) ? -1 : 1;
			}
		);

		return $modules;
	}

	/**
	 * Returns only active registered modules.
	 *
	 * @return array<string, WA_Suit_Module_Base>
	 */
	public function get_active() {
		$active = array();

		foreach ( $this->get_all() as $id => $module ) {
			if ( $module->is_active() ) {
				$active[ $id ] = $module;
			}
		}

		return $active;
	}

	/**
	 * Clears the registry (useful for testing).
	 *
	 * @return void
	 */
	public function reset() {
		$this->modules = array();
	}
}

/**
 * Global helper function to easily register a module from any file.
 *
 * @param WA_Suit_Module_Base $module Module instance.
 * @return void
 */
function wa_suit_register_module( WA_Suit_Module_Base $module ) {
	WA_Suit_Module_Registry::get_instance()->register( $module );
}
