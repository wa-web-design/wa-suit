<?php
/**
 * Abstract Base Class for WA Suit Modules.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base module class defining the API contract for all plugin functions.
 */
abstract class WA_Suit_Module_Base {

	/**
	 * Settings component reference.
	 *
	 * @var WA_Suit_Settings|null
	 */
	protected $settings_manager = null;

	/**
	 * Unique identifier slug for the module.
	 *
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Human-readable title for the module.
	 *
	 * @return string
	 */
	abstract public function get_title();

	/**
	 * Short description of what the module does.
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Dashicon class or icon identifier.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'dashicons-admin-generic';
	}

	/**
	 * Category badge for grouping modules in the dashboard.
	 *
	 * @return string
	 */
	public function get_category() {
		return 'Geral';
	}

	/**
	 * Priority order for sorting tabs and cards (lower numbers appear first).
	 *
	 * @return int
	 */
	public function get_order() {
		return 10;
	}

	/**
	 * Whether this module provides a dedicated settings tab when active.
	 *
	 * @return bool
	 */
	public function has_settings_tab() {
		return true;
	}

	/**
	 * Injects the settings manager into this module.
	 *
	 * @param WA_Suit_Settings $settings Global settings instance.
	 * @return void
	 */
	public function set_settings_manager( $settings ) {
		$this->settings_manager = $settings;
	}

	/**
	 * Checks whether this module is currently enabled.
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( ! $this->settings_manager ) {
			return false;
		}

		return $this->settings_manager->is_module_enabled( $this->get_id() );
	}

	/**
	 * Returns the wp_options key for this module's individual settings.
	 *
	 * @return string
	 */
	protected function get_option_key() {
		return 'wa_suit_mod_' . $this->get_id();
	}

	/**
	 * Returns default settings array for the module.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array();
	}

	/**
	 * Retrieves the current settings for this module merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$defaults = $this->get_default_settings();
		$saved    = get_option( $this->get_option_key(), array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Retrieves a single setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed
	 */
	public function get_setting( $key, $default = '' ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Updates the module settings in the database.
	 *
	 * @param array $new_settings New settings array.
	 * @return bool
	 */
	public function update_settings( $new_settings ) {
		return update_option( $this->get_option_key(), $new_settings );
	}

	/**
	 * Executes frontend or backend hooks when the module is enabled.
	 *
	 * @return void
	 */
	public function init() {
		// Child classes override this to register actions and filters.
	}

	/**
	 * Renders the HTML content for this module's settings tab.
	 *
	 * @return void
	 */
	public function render_tab() {
		?>
		<div class="wa-suit-tab-empty">
			<p><?php echo esc_html__( 'Este módulo não possui configurações adicionais.', 'wa-suit' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Handles saving tab settings when submitted via POST.
	 *
	 * @param array $post_data Unslashed $_POST data.
	 * @return void
	 */
	public function save_tab_settings( $post_data ) {
		// Child classes override this to process and save submitted data.
	}
}
