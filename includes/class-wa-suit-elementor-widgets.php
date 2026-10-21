<?php
/**
 * Elementor widgets integration.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers WA Suit's Elementor category and widgets.
 */
class WA_Suit_Elementor_Widgets {

	/**
	 * Registers Elementor hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
	}

	/**
	 * Registers the WA Widgets category in Elementor.
	 *
	 * @param Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		if ( ! WA_Suit_Elementor_Form_Masks::is_widget_enabled( 'popup' ) ) {
			return;
		}

		$elements_manager->add_category(
			'wa-widgets',
			array(
				'title' => __( 'WA Widgets', 'wa-suit' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Registers WA Suit Elementor widgets.
	 *
	 * @param Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		if ( WA_Suit_Elementor_Form_Masks::is_widget_enabled( 'popup' ) ) {
			require_once WA_SUIT_PATH . 'includes/elementor/class-wa-suit-elementor-popup-widget.php';
			$widgets_manager->register( new WA_Suit_Elementor_Popup_Widget() );
		}
	}

	/**
	 * Registers front-end scripts used by the widgets.
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script(
			'wa-suit-elementor-widgets',
			WA_SUIT_URL . 'assets/js/elementor-widgets.js',
			array(),
			WA_SUIT_VERSION,
			true
		);
	}

	/**
	 * Registers front-end styles used by the widgets.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'wa-suit-elementor-widgets',
			WA_SUIT_URL . 'assets/css/elementor-widgets.css',
			array(),
			WA_SUIT_VERSION
		);
	}
}
