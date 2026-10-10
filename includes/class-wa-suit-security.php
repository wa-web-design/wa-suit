<?php
/**
 * Shared security checks.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralizes common permission and nonce checks.
 */
class WA_Suit_Security {

	/**
	 * Nonce action for admin operations.
	 */
	const NONCE_ACTION = 'wa_suit_admin_nonce_action';

	/**
	 * Checks whether the current user can manage toolkit settings.
	 *
	 * @return bool
	 */
	public static function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Creates a standard nonce for the plugin.
	 *
	 * @param string $action Optional custom action.
	 * @return string
	 */
	public static function create_nonce( $action = self::NONCE_ACTION ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Verifies a nonce for a plugin action.
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	public static function verify_nonce( $nonce, $action = self::NONCE_ACTION ) {
		return is_string( $nonce ) && false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), $action );
	}
}
