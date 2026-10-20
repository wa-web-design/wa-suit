<?php
/**
 * Private fatal-error logging for WA Suit.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Captures fatal PHP errors in a protected WA Suit log.
 */
class WA_Suit_Debug {

	/**
	 * Developer settings option.
	 */
	const OPTION_NAME = 'wa_suit_developer_settings';

	/**
	 * Registers fatal-error logging when enabled.
	 *
	 * @return void
	 */
	public static function init() {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) || empty( $settings['debug_enabled'] ) ) {
			return;
		}

		register_shutdown_function( array( __CLASS__, 'capture_fatal_error' ) );
	}

	/**
	 * Writes the last fatal PHP error to the private WA Suit log.
	 *
	 * @return void
	 */
	public static function capture_fatal_error() {
		$error = error_get_last();
		$fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR );

		if ( ! is_array( $error ) || ! isset( $error['type'], $error['message'], $error['file'], $error['line'] ) || ! in_array( $error['type'], $fatal_types, true ) ) {
			return;
		}

		$entry = sprintf(
			"[%s UTC] PHP fatal error (%d): %s in %s:%d\n",
			gmdate( 'Y-m-d H:i:s' ),
			$error['type'],
			$error['message'],
			$error['file'],
			$error['line']
		);

		if ( ! self::append_log( $entry ) ) {
			error_log( 'WA Suit debug logger could not write its private fatal-error log. ' . trim( $entry ) );
		}
	}

	/**
	 * Returns the protected log file path.
	 *
	 * @return string
	 */
	public static function get_log_path() {
		$document_root = isset( $_SERVER['DOCUMENT_ROOT'] ) ? realpath( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : false;
		$base_directory = realpath( WP_CONTENT_DIR );

		if ( false === $document_root || self::is_path_inside( $base_directory, $document_root ) ) {
			$base_directory = realpath( sys_get_temp_dir() );

			if ( false !== $document_root && self::is_path_inside( $base_directory, $document_root ) ) {
				$base_directory = realpath( dirname( untrailingslashit( ABSPATH ) ) );
			}

			if ( false === $base_directory || ( false !== $document_root && self::is_path_inside( $base_directory, $document_root ) ) ) {
				return '';
			}
		}

		if ( false === $base_directory ) {
			return '';
		}

		return trailingslashit( $base_directory ) . 'wa-suit-' . substr( hash( 'sha256', ABSPATH ), 0, 16 ) . '/wa-suit-debug.log';
	}

	/**
	 * Returns the tail of the private log file.
	 *
	 * @param int $max_bytes Maximum bytes to return.
	 * @return string|false
	 */
	public static function get_log_contents( $max_bytes = 65536 ) {
		$path = self::get_log_path();

		if ( '' === $path ) {
			return false;
		}

		if ( ! file_exists( $path ) ) {
			return '';
		}

		if ( ! is_readable( $path ) ) {
			return false;
		}

		$file = fopen( $path, 'rb' );
		if ( false === $file ) {
			return false;
		}

		$file_size = filesize( $path );
		if ( false === $file_size ) {
			fclose( $file );
			return false;
		}

		$start = max( 0, $file_size - absint( $max_bytes ) );
		if ( 0 !== $start && -1 === fseek( $file, $start ) ) {
			fclose( $file );
			return false;
		}

		$contents = stream_get_contents( $file );
		fclose( $file );

		if ( false === $contents ) {
			return false;
		}

		if ( 0 !== $start ) {
			$first_newline = strpos( $contents, "\n" );
			$contents      = false === $first_newline ? '' : substr( $contents, $first_newline + 1 );
		}

		return $contents;
	}

	/**
	 * Clears the private log.
	 *
	 * @return bool
	 */
	public static function clear_log() {
		$path = self::get_log_path();

		if ( '' === $path ) {
			return false;
		}

		if ( ! file_exists( $path ) ) {
			return true;
		}

		$cleared = false !== file_put_contents( $path, '', LOCK_EX );
		$rotated_path = $path . '.1';

		if ( file_exists( $rotated_path ) ) {
			$cleared = $cleared && unlink( $rotated_path );
		}

		return $cleared;
	}

	/**
	 * Appends a log entry and ensures the directory cannot be browsed.
	 *
	 * @param string $entry Log entry.
	 * @return bool
	 */
	private static function append_log( $entry ) {
		$log_path = self::get_log_path();
		if ( '' === $log_path ) {
			return false;
		}

		$directory = dirname( $log_path );
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			return false;
		}
		if ( DIRECTORY_SEPARATOR === '/' && ! chmod( $directory, 0700 ) ) {
			return false;
		}

		$index_file = $directory . '/index.php';
		if ( ! file_exists( $index_file ) && false === file_put_contents( $index_file, "<?php\n// Silence is golden.\n", LOCK_EX ) ) {
			return false;
		}

		$htaccess_file = $directory . '/.htaccess';
		$htaccess_rules = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
		if ( ! file_exists( $htaccess_file ) && false === file_put_contents( $htaccess_file, $htaccess_rules, LOCK_EX ) ) {
			return false;
		}

		$web_config_file = $directory . '/web.config';
		$web_config_rules = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>\n";
		if ( ! file_exists( $web_config_file ) && false === file_put_contents( $web_config_file, $web_config_rules, LOCK_EX ) ) {
			return false;
		}

		if ( file_exists( $log_path ) && filesize( $log_path ) > 1048576 ) {
			if ( false === file_put_contents( $log_path, '', LOCK_EX ) ) {
				return false;
			}
		}

		$written = file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
		if ( false !== $written && DIRECTORY_SEPARATOR === '/' && ! chmod( $log_path, 0600 ) ) {
			return false;
		}

		return false !== $written;
	}

	/**
	 * Checks whether a path is equal to or nested beneath another path.
	 *
	 * @param string|false $path        Path to check.
	 * @param string|false $parent_path Parent path.
	 * @return bool
	 */
	private static function is_path_inside( $path, $parent_path ) {
		if ( false === $path || false === $parent_path ) {
			return false;
		}

		$path        = untrailingslashit( wp_normalize_path( $path ) );
		$parent_path = untrailingslashit( wp_normalize_path( $parent_path ) );

		if ( '\\' === DIRECTORY_SEPARATOR ) {
			$path        = strtolower( $path );
			$parent_path = strtolower( $parent_path );
		}

		return $path === $parent_path || 0 === strpos( $path, trailingslashit( $parent_path ) );
	}
}
