<?php
/**
 * Automatic plugin updates setting.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the global automatic plugin updates setting.
 */
class WA_Suit_Setting_Auto_Plugin_Updates {

	/**
	 * Registers the WordPress automatic plugin update filter.
	 */
	public function __construct() {
		add_filter( 'auto_update_plugin', array( $this, 'filter_auto_update_plugin' ), 10, 2 );
		add_filter( 'file_mod_allowed', array( $this, 'filter_file_mod_allowed' ), 99, 2 );
		add_filter( 'automatic_updater_disabled', array( $this, 'filter_automatic_updater_disabled' ), 99 );
		add_filter( 'plugins_auto_update_enabled', array( $this, 'filter_plugins_auto_update_enabled' ), 99 );
		add_action( 'admin_init', array( $this, 'initialize_missing_preference' ) );
		add_action( 'add_option_' . WA_Suit_Settings::GENERAL_OPTION_NAME, array( $this, 'sync_after_option_added' ), 10, 2 );
		add_action( 'update_option_' . WA_Suit_Settings::GENERAL_OPTION_NAME, array( $this, 'sync_after_option_updated' ), 10, 3 );
	}

	/**
	 * Gets the saved setting, enabled by default for existing and new installs.
	 *
	 * @param array $saved Saved general settings.
	 * @return array
	 */
	public function get_settings( $saved ) {
		return array(
			'auto_update_plugins' => isset( $saved['auto_update_plugins'] ) ? (bool) $saved['auto_update_plugins'] : true,
		);
	}

	/**
	 * Validates the submitted setting.
	 *
	 * @param array $input Submitted general settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		return array(
			'auto_update_plugins' => ! empty( $input['auto_update_plugins'] ),
		);
	}

	/**
	 * Applies the configured preference to automatic updates for all plugins.
	 *
	 * @param bool|null $update Whether WordPress should automatically update the plugin.
	 * @param object    $item   Plugin update data.
	 * @return bool|null
	 */
	public function filter_auto_update_plugin( $update, $item ) {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		return $update;
	}

	/**
	 * Makes the native plugin auto-update column available when allowed by WordPress.
	 *
	 * @param bool $enabled Whether WordPress allows plugin automatic updates.
	 * @return bool
	 */
	public function filter_plugins_auto_update_enabled( $enabled ) {
		return $this->is_enabled();
	}

	/**
	 * Allows file changes only for WordPress's automatic updater when enabled.
	 *
	 * This does not enable manual plugin/theme installation or editing.
	 *
	 * @param bool   $allowed Whether file modifications are allowed.
	 * @param string $context The file modification context.
	 * @return bool
	 */
	public function filter_file_mod_allowed( $allowed, $context ) {
		if ( 'automatic_updater' === $context && $this->is_enabled() ) {
			return true;
		}

		return $allowed;
	}

	/**
	 * Allows the automatic updater to run when this plugin's setting is enabled.
	 *
	 * @param bool $disabled Whether the automatic updater is disabled.
	 * @return bool
	 */
	public function filter_automatic_updater_disabled( $disabled ) {
		return $this->is_enabled() ? false : $disabled;
	}

	/**
	 * Initializes the default-on preference once for existing installations.
	 *
	 * @return void
	 */
	public function initialize_missing_preference() {
		$settings = get_option( WA_Suit_Settings::GENERAL_OPTION_NAME, array() );
		if ( is_array( $settings ) && array_key_exists( 'auto_update_plugins', $settings ) ) {
			return;
		}

		$this->sync_wordpress_auto_update_plugins();
		$settings = is_array( $settings ) ? $settings : array();
		$settings['auto_update_plugins'] = true;
		update_option( WA_Suit_Settings::GENERAL_OPTION_NAME, $settings );
	}

	/**
	 * Synchronizes native plugin selections when the general settings are first saved.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  New option value.
	 * @return void
	 */
	public function sync_after_option_added( $option, $value ) {
		$this->sync_wordpress_auto_update_plugins();
	}

	/**
	 * Synchronizes native plugin selections only when the global preference changes.
	 *
	 * @param mixed  $old_value Previous option value.
	 * @param mixed  $new_value New option value.
	 * @param string $option    Option name.
	 * @return void
	 */
	public function sync_after_option_updated( $old_value, $new_value, $option ) {
		$old_enabled = is_array( $old_value ) && array_key_exists( 'auto_update_plugins', $old_value )
			? (bool) $old_value['auto_update_plugins']
			: true;
		$new_enabled = is_array( $new_value ) && array_key_exists( 'auto_update_plugins', $new_value )
			? (bool) $new_value['auto_update_plugins']
			: true;

		if ( $old_enabled !== $new_enabled ) {
			$this->sync_wordpress_auto_update_plugins();
		}
	}

	/**
	 * Synchronizes the setting with WordPress's native per-plugin update list.
	 *
	 * This makes the enabled state visible in the Plugins screen as well as in
	 * the automatic updater.
	 *
	 * @return void
	 */
	public function sync_wordpress_auto_update_plugins() {
		$enabled = $this->is_enabled();

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = array_keys( get_plugins() );
		$current_plugins   = array_values( array_unique( (array) get_site_option( 'auto_update_plugins', array() ) ) );
		$auto_update       = $enabled ? $installed_plugins : array();
		sort( $current_plugins );
		sort( $auto_update );

		if ( $auto_update !== $current_plugins ) {
			update_site_option( 'auto_update_plugins', $auto_update );
		}
	}

	/**
	 * Gets the saved global preference, defaulting to enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		$settings = get_option( WA_Suit_Settings::GENERAL_OPTION_NAME, array() );

		return ! is_array( $settings ) || ! array_key_exists( 'auto_update_plugins', $settings )
			? true
			: (bool) $settings['auto_update_plugins'];
	}

	/**
	 * Renders the automatic plugin updates settings card.
	 *
	 * @param array $settings All general settings.
	 * @return void
	 */
	public function render_settings( $settings ) {
		?>
		<section class="wa-general-settings__card">
			<div class="wa-general-settings__card-heading">
				<span class="dashicons dashicons-update" aria-hidden="true"></span>
				<div>
					<h3><?php echo esc_html__( 'Atualização automática dos plugins', 'wa-suit' ); ?></h3>
					<p><?php echo esc_html__( 'Permita que o WordPress instale automaticamente as atualizações disponíveis para os plugins.', 'wa-suit' ); ?></p>
				</div>
			</div>

			<label class="wa-general-settings__toggle">
				<input type="checkbox" name="settings[auto_update_plugins]" value="1" <?php checked( $settings['auto_update_plugins'] ); ?>>
				<span>
					<strong><?php echo esc_html__( 'Ativar atualização automática dos plugins', 'wa-suit' ); ?></strong>
					<small><?php echo esc_html__( 'Ativado por padrão. Desmarque para impedir atualizações automáticas de plugins.', 'wa-suit' ); ?></small>
				</span>
			</label>
		</section>
		<?php
	}
}
