<?php
/**
 * Custom Scripts & Pixels Module.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module: Códigos & Scripts Customizados (Header, Body, Footer).
 */
class WA_Suit_Module_Custom_Scripts extends WA_Suit_Module_Base {

	public function get_id() {
		return 'custom-scripts';
	}

	public function get_title() {
		return __( 'Scripts & Códigos Customizados', 'wa-suit' );
	}

	public function get_description() {
		return __( 'Insira facilmente Google Analytics, GTM, Pixel da Meta e códigos no cabeçalho ou rodapé.', 'wa-suit' );
	}

	public function get_icon() {
		return 'dashicons-editor-code';
	}

	public function get_category() {
		return __( 'Ferramentas', 'wa-suit' );
	}

	public function get_order() {
		return 2;
	}

	public function get_default_settings() {
		return array(
			'head_code'   => '',
			'body_code'   => '',
			'footer_code' => '',
			'custom_css'  => '',
		);
	}

	public function init() {
		add_action( 'wp_head', array( $this, 'output_head_code' ), 99 );
		add_action( 'wp_body_open', array( $this, 'output_body_code' ), 1 );
		add_action( 'wp_footer', array( $this, 'output_footer_code' ), 99 );
		add_action( 'wp_head', array( $this, 'output_custom_css' ), 100 );
	}

	public function output_head_code() {
		$code = $this->get_setting( 'head_code', '' );
		if ( ! empty( $code ) ) {
			echo "\n<!-- WA Suit: Head Scripts -->\n" . $code . "\n<!-- /WA Suit: Head Scripts -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function output_body_code() {
		$code = $this->get_setting( 'body_code', '' );
		if ( ! empty( $code ) ) {
			echo "\n<!-- WA Suit: Body Open Scripts -->\n" . $code . "\n<!-- /WA Suit: Body Open Scripts -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function output_footer_code() {
		$code = $this->get_setting( 'footer_code', '' );
		if ( ! empty( $code ) ) {
			echo "\n<!-- WA Suit: Footer Scripts -->\n" . $code . "\n<!-- /WA Suit: Footer Scripts -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function output_custom_css() {
		$css = $this->get_setting( 'custom_css', '' );
		if ( ! empty( $css ) ) {
			echo "\n<style id=\"wa-suit-custom-css\">\n" . wp_strip_all_tags( $css ) . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function render_tab() {
		$settings = $this->get_settings();
		?>
		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_head_code">
				<?php echo esc_html__( 'Scripts no Cabeçalho (<head>)', 'wa-suit' ); ?>
			</label>
			<textarea id="wa_head_code" name="head_code" class="code-editor" rows="6"><?php echo esc_textarea( $settings['head_code'] ); ?></textarea>
			<span class="description">
				<?php echo esc_html__( 'Ideal para Google Tag Manager (<head>), Google Analytics (GA4), Pixel do Meta / Facebook, tags de verificação, etc.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_body_code">
				<?php echo esc_html__( 'Scripts no Início do <body> (wp_body_open)', 'wa-suit' ); ?>
			</label>
			<textarea id="wa_body_code" name="body_code" class="code-editor" rows="5"><?php echo esc_textarea( $settings['body_code'] ); ?></textarea>
			<span class="description">
				<?php echo esc_html__( 'Ideal para o código <body> do Google Tag Manager (<noscript>) e scripts executados logo na abertura da página.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_footer_code">
				<?php echo esc_html__( 'Scripts no Rodapé (antes de </body>)', 'wa-suit' ); ?>
			</label>
			<textarea id="wa_footer_code" name="footer_code" class="code-editor" rows="6"><?php echo esc_textarea( $settings['footer_code'] ); ?></textarea>
			<span class="description">
				<?php echo esc_html__( 'Ideal para scripts de chat, rastreadores pesados, widgets externos ou JavaScript adicional.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_custom_css">
				<?php echo esc_html__( 'CSS Customizado Adicional', 'wa-suit' ); ?>
			</label>
			<textarea id="wa_custom_css" name="custom_css" class="code-editor" rows="5"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
			<span class="description">
				<?php echo esc_html__( 'Digite apenas regras CSS (sem a tag <style>). Será impresso no cabeçalho do site.', 'wa-suit' ); ?>
			</span>
		</div>
		<?php
	}

	public function save_tab_settings( $post_data ) {
		// Scripts may contain HTML tags, so we preserve them for admins who manage options.
		$sanitized = array(
			'head_code'   => isset( $post_data['head_code'] ) ? wp_unslash( $post_data['head_code'] ) : '',
			'body_code'   => isset( $post_data['body_code'] ) ? wp_unslash( $post_data['body_code'] ) : '',
			'footer_code' => isset( $post_data['footer_code'] ) ? wp_unslash( $post_data['footer_code'] ) : '',
			'custom_css'  => isset( $post_data['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $post_data['custom_css'] ) ) : '',
		);

		$this->update_settings( $sanitized );
	}
}

wa_suit_register_module( new WA_Suit_Module_Custom_Scripts() );
