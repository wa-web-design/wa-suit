<?php
/**
 * Global plugin settings.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders plugin settings.
 */
class WA_Suit_Settings {
	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'wa_suit_settings';

	/**
	 * Registers the settings API integration.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registers the module settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'wa_suit',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitizes submitted plugin settings.
	 *
	 * @param mixed $input Submitted settings.
	 * @return array
	 */
	public function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return array( 'enabled_modules' => array() );
		}

		$enabled_modules = isset( $input['enabled_modules'] ) && is_array( $input['enabled_modules'] )
			? array_map( 'sanitize_key', $input['enabled_modules'] )
			: array();

		return array(
			'enabled_modules' => array_values( array_unique( $enabled_modules ) ),
		);
	}

	/**
	 * Gets enabled modules, defaulting to all discovered modules.
	 *
	 * @param string[] $available_modules Discovered module slugs.
	 * @return string[]
	 */
	public function get_enabled_modules( $available_modules ) {
		$options = get_option( self::OPTION_NAME, null );

		if ( ! is_array( $options ) || ! isset( $options['enabled_modules'] ) ) {
			return $available_modules;
		}

		return array_values(
			array_intersect(
				$available_modules,
				(array) $options['enabled_modules']
			)
		);
	}

	/**
	 * Renders a checkbox for each available module.
	 *
	 * @param string[] $modules Available module slugs.
	 * @return void
	 */
	public function render_module_fields( $modules ) {
		$enabled_modules = $this->get_enabled_modules( $modules );
		?>
		<p>
			<label for="wa-suit-enable-all">
				<input type="checkbox" id="wa-suit-enable-all">
				<?php echo esc_html__( 'Selecionar ou desmarcar todos', 'wa-suit' ); ?>
			</label>
		</p>
		<div class="wa-suit__modules">
			<?php foreach ( $modules as $module ) : ?>
				<label class="wa-suit__module">
					<input
						type="checkbox"
						name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled_modules][]"
						value="<?php echo esc_attr( $module ); ?>"
						data-wa-suit-module
						<?php checked( in_array( $module, $enabled_modules, true ) ); ?>
					>
					<span><?php echo esc_html( ucwords( str_replace( '-', ' ', $module ) ) ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
