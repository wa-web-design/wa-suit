<?php
/**
 * Administration menu and dashboard.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the plugin administration page.
 */
class WA_Suit_Admin {
	/**
	 * Global settings component.
	 *
	 * @var WA_Suit_Settings
	 */
	private $settings;

	/**
	 * Module discovery component.
	 *
	 * @var WA_Suit_Module_Loader
	 */
	private $module_loader;

	/**
	 * Sets up the administration controller.
	 *
	 * @param WA_Suit_Settings      $settings      Global settings.
	 * @param WA_Suit_Module_Loader $module_loader Module loader.
	 */
	public function __construct( $settings, $module_loader ) {
		$this->settings      = $settings;
		$this->module_loader = $module_loader;
	}

	/**
	 * Registers administration hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Adds the toolkit menu page.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'WA Suit', 'wa-suit' ),
			__( 'WA Suit', 'wa-suit' ),
			'manage_options',
			'wa-suit',
			array( $this, 'render_page' ),
			'dashicons-admin-tools'
		);
	}

	/**
	 * Loads dashboard assets only on the toolkit page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'toplevel_page_wa-suit' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'wa-suit-admin',
			WA_SUIT_URL . 'assets/css/admin.css',
			array(),
			WA_SUIT_VERSION
		);
		wp_enqueue_script(
			'wa-suit-admin',
			WA_SUIT_URL . 'assets/js/admin.js',
			array(),
			WA_SUIT_VERSION,
			true
		);
	}

	/**
	 * Renders the global settings and available modules.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'wa-suit' ) );
		}

		$modules = $this->module_loader->get_available_modules();
		?>
		<div class="wrap wa-suit">
			<h1><?php echo esc_html__( 'WA Suit', 'wa-suit' ); ?></h1>
			<p><?php echo esc_html__( 'Gerencie as configurações globais e os módulos do plugin.', 'wa-suit' ); ?></p>

			<form action="options.php" method="post">
				<?php settings_fields( 'wa_suit' ); ?>
				<h2><?php echo esc_html__( 'Módulos', 'wa-suit' ); ?></h2>
				<p><?php echo esc_html__( 'Habilite apenas os recursos que deseja carregar neste site.', 'wa-suit' ); ?></p>
				<?php $this->settings->render_module_fields( $modules ); ?>
				<?php submit_button( __( 'Salvar alterações', 'wa-suit' ) ); ?>
			</form>
		</div>
		<?php
	}
}
