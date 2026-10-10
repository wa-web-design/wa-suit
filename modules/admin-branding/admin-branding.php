<?php
/**
 * Admin Branding & Agency Customization Module.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module: Personalização do Painel & Marca da Agência.
 */
class WA_Suit_Module_Admin_Branding extends WA_Suit_Module_Base {

	public function get_id() {
		return 'admin-branding';
	}

	public function get_title() {
		return __( 'Personalização do Admin (WA Branding)', 'wa-suit' );
	}

	public function get_description() {
		return __( 'Personalize o rodapé do painel e remova avisos e menus nativos do WP para os clientes da agência.', 'wa-suit' );
	}

	public function get_icon() {
		return 'dashicons-admin-appearance';
	}

	public function get_category() {
		return __( 'Aparência', 'wa-suit' );
	}

	public function get_order() {
		return 5;
	}

	public function get_default_settings() {
		return array(
			'footer_text'      => __( 'Desenvolvido com carinho por <a href="https://wawebdesign.com.br" target="_blank" rel="noopener">WA Web Design</a>.', 'wa-suit' ),
			'remove_wp_logo'   => '1',
			'hide_update_nags' => '1',
		);
	}

	public function init() {
		if ( ! is_admin() ) {
			return;
		}

		$settings = $this->get_settings();

		// Custom footer text
		if ( ! empty( $settings['footer_text'] ) ) {
			add_filter( 'admin_footer_text', array( $this, 'custom_admin_footer' ) );
		}

		// Remove WordPress logo from admin bar
		if ( '1' === (string) $settings['remove_wp_logo'] ) {
			add_action( 'wp_before_admin_bar_render', array( $this, 'remove_wp_logo_from_bar' ), 0 );
		}

		// Hide update notices for non-admins
		if ( '1' === (string) $settings['hide_update_nags'] && ! current_user_can( 'update_core' ) ) {
			add_action( 'admin_head', array( $this, 'hide_update_notices_css' ), 1 );
		}
	}

	public function custom_admin_footer() {
		$text = $this->get_setting( 'footer_text', '' );
		return wp_kses_post( $text );
	}

	public function remove_wp_logo_from_bar() {
		global $wp_admin_bar;
		if ( is_object( $wp_admin_bar ) ) {
			$wp_admin_bar->remove_menu( 'wp-logo' );
		}
	}

	public function hide_update_notices_css() {
		echo '<style>.update-nag, .notice-warning.update-nag { display: none !important; }</style>';
	}

	public function render_tab() {
		$settings = $this->get_settings();
		?>
		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_branding_footer">
				<?php echo esc_html__( 'Texto Personalizado no Rodapé do Painel', 'wa-suit' ); ?>
			</label>
			<input type="text"
				id="wa_branding_footer"
				name="footer_text"
				value="<?php echo esc_attr( $settings['footer_text'] ); ?>"
				style="max-width: 600px;">
			<span class="description">
				<?php echo esc_html__( 'Suporta links HTML para colocar a assinatura da WA Web Design no rodapé de todo o painel.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-checkbox-item">
				<input type="checkbox" name="remove_wp_logo" value="1" <?php checked( '1' === (string) $settings['remove_wp_logo'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Remover o logotipo do WordPress da barra superior', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Remove o menu suspenso do WordPress na barra de ferramentas superior para uma aparência mais limpa.', 'wa-suit' ); ?></span>
				</div>
			</label>

			<label class="wa-suit-checkbox-item">
				<input type="checkbox" name="hide_update_nags" value="1" <?php checked( '1' === (string) $settings['hide_update_nags'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Ocultar avisos de atualização para clientes (não-administradores)', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Evita que usuários sem privilégios vejam caixas amarelas de aviso de atualização do WordPress.', 'wa-suit' ); ?></span>
				</div>
			</label>
		</div>
		<?php
	}

	public function save_tab_settings( $post_data ) {
		$sanitized = array(
			'footer_text'      => isset( $post_data['footer_text'] ) ? wp_kses_post( $post_data['footer_text'] ) : '',
			'remove_wp_logo'   => isset( $post_data['remove_wp_logo'] ) ? '1' : '0',
			'hide_update_nags' => isset( $post_data['hide_update_nags'] ) ? '1' : '0',
		);

		$this->update_settings( $sanitized );
	}
}

wa_suit_register_module( new WA_Suit_Module_Admin_Branding() );
