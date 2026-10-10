<?php
/**
 * WhatsApp Floating Button Module.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module: Botão Flutuante de WhatsApp.
 */
class WA_Suit_Module_Whatsapp extends WA_Suit_Module_Base {

	public function get_id() {
		return 'whatsapp';
	}

	public function get_title() {
		return __( 'Botão WhatsApp Flutuante', 'wa-suit' );
	}

	public function get_description() {
		return __( 'Adiciona um botão flutuante de WhatsApp de alta conversão em todas as páginas do site.', 'wa-suit' );
	}

	public function get_icon() {
		return 'dashicons-format-chat';
	}

	public function get_category() {
		return __( 'Conversão', 'wa-suit' );
	}

	public function get_order() {
		return 1;
	}

	public function get_default_settings() {
		return array(
			'phone_number'    => '',
			'default_message' => __( 'Olá! Vim pelo site e gostaria de mais informações.', 'wa-suit' ),
			'button_text'     => __( 'Fale Conosco', 'wa-suit' ),
			'position'        => 'bottom-right',
			'button_color'    => '#25D366',
			'open_new_tab'    => '1',
		);
	}

	public function init() {
		add_action( 'wp_footer', array( $this, 'render_floating_button' ) );
	}

	public function render_floating_button() {
		$phone = trim( (string) $this->get_setting( 'phone_number', '' ) );
		if ( empty( $phone ) ) {
			return;
		}

		// Sanitize phone number (digits only).
		$clean_phone = preg_replace( '/[^0-9]/', '', $phone );
		if ( empty( $clean_phone ) ) {
			return;
		}

		$message      = $this->get_setting( 'default_message', '' );
		$button_text  = $this->get_setting( 'button_text', '' );
		$position     = $this->get_setting( 'position', 'bottom-right' );
		$button_color = $this->get_setting( 'button_color', '#25D366' );
		$open_new_tab = '1' === (string) $this->get_setting( 'open_new_tab', '1' );

		$whatsapp_url = 'https://api.whatsapp.com/send?phone=' . rawurlencode( $clean_phone );
		if ( ! empty( $message ) ) {
			$whatsapp_url .= '&text=' . rawurlencode( $message );
		}

		$pos_css = ( 'bottom-left' === $position ) ? 'left: 20px;' : 'right: 20px;';
		$target  = $open_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		?>
		<style>
			.wa-suit-floating-wa {
				position: fixed;
				bottom: 20px;
				<?php echo esc_attr( $pos_css ); ?>
				z-index: 999990;
				display: inline-flex;
				align-items: center;
				gap: 10px;
				text-decoration: none;
				background-color: <?php echo esc_attr( $button_color ); ?>;
				color: #ffffff !important;
				padding: 10px 18px 10px 14px;
				border-radius: 50px;
				box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
				transition: transform 0.25s ease, box-shadow 0.25s ease;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
			}
			.wa-suit-floating-wa:hover {
				transform: translateY(-3px) scale(1.02);
				box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
				color: #ffffff !important;
			}
			.wa-suit-floating-wa svg {
				width: 26px;
				height: 26px;
				fill: #ffffff;
				flex-shrink: 0;
			}
			@media (max-width: 480px) {
				.wa-suit-floating-wa span {
					display: none;
				}
				.wa-suit-floating-wa {
					padding: 12px;
					border-radius: 50%;
				}
			}
		</style>
		<a href="<?php echo esc_url( $whatsapp_url ); ?>" class="wa-suit-floating-wa"<?php echo $target; // phpcs:ignore ?> aria-label="<?php esc_attr_e( 'Conversar no WhatsApp', 'wa-suit' ); ?>">
			<svg viewBox="0 0 24 24" width="24" height="24">
				<path d="M12.031 2C6.511 2 2.024 6.488 2.024 12.008c0 1.83.498 3.551 1.365 5.039L2 22l5.127-1.344a9.98 9.98 0 0 0 4.904 1.282c5.521 0 10.008-4.488 10.008-10.008C22.039 6.488 17.552 2 12.031 2zm0 18.232c-1.583 0-3.082-.42-4.39-1.154l-.315-.178-3.042.798.812-2.965-.205-.327a8.214 8.214 0 0 1-1.261-4.398c0-4.545 3.697-8.242 8.242-8.242 4.546 0 8.243 3.697 8.243 8.242 0 4.546-3.697 8.243-8.242 8.243zm4.52-6.177c-.247-.124-1.463-.722-1.69-.805-.227-.082-.392-.124-.557.124-.165.247-.64 1.04-.784 1.205-.144.165-.289.186-.536.062-.248-.124-1.045-.385-1.99-1.228-.737-.657-1.234-1.47-1.379-1.717-.144-.247-.015-.381.108-.504.111-.111.248-.289.371-.433.124-.144.165-.247.247-.412.083-.165.041-.309-.02-.433-.062-.124-.557-1.341-.763-1.836-.2-.483-.404-.417-.557-.425l-.474-.009c-.165 0-.433.062-.66.309-.227.247-.866.846-.866 2.063s.887 2.393 1.01 2.558c.124.165 1.745 2.665 4.228 3.738.591.256 1.052.409 1.411.523.593.188 1.134.162 1.561.098.477-.071 1.463-.598 1.669-1.176.206-.577.206-1.072.144-1.175-.062-.103-.227-.165-.474-.289z"/>
			</svg>
			<?php if ( ! empty( $button_text ) ) : ?>
				<span><?php echo esc_html( $button_text ); ?></span>
			<?php endif; ?>
		</a>
		<?php
	}

	public function render_tab() {
		$settings = $this->get_settings();
		?>
		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_whatsapp_phone">
				<?php echo esc_html__( 'Número do WhatsApp (com DDI e DDD)', 'wa-suit' ); ?>
			</label>
			<input type="text"
				id="wa_whatsapp_phone"
				name="phone_number"
				value="<?php echo esc_attr( $settings['phone_number'] ); ?>"
				placeholder="Ex: 5511999998888">
			<span class="description">
				<?php echo esc_html__( 'Digite apenas números incluindo código do país (55 para Brasil) e DDD. Exemplo: 5511999998888.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_whatsapp_msg">
				<?php echo esc_html__( 'Mensagem Inicial Padrão', 'wa-suit' ); ?>
			</label>
			<textarea id="wa_whatsapp_msg" name="default_message" rows="3"><?php echo esc_textarea( $settings['default_message'] ); ?></textarea>
			<span class="description">
				<?php echo esc_html__( 'Texto que já virá pré-digitado no WhatsApp do cliente ao clicar no botão.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_whatsapp_text">
				<?php echo esc_html__( 'Texto de Apoio no Botão (Call to Action)', 'wa-suit' ); ?>
			</label>
			<input type="text"
				id="wa_whatsapp_text"
				name="button_text"
				value="<?php echo esc_attr( $settings['button_text'] ); ?>"
				placeholder="Ex: Fale Conosco">
			<span class="description">
				<?php echo esc_html__( 'Texto exibido ao lado do ícone no desktop. Deixe em branco se quiser apenas o ícone redondo.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_whatsapp_pos">
				<?php echo esc_html__( 'Posição do Botão na Tela', 'wa-suit' ); ?>
			</label>
			<select id="wa_whatsapp_pos" name="position">
				<option value="bottom-right" <?php selected( $settings['position'], 'bottom-right' ); ?>>
					<?php echo esc_html__( 'Canto Inferior Direito (Recomendado)', 'wa-suit' ); ?>
				</option>
				<option value="bottom-left" <?php selected( $settings['position'], 'bottom-left' ); ?>>
					<?php echo esc_html__( 'Canto Inferior Esquerdo', 'wa-suit' ); ?>
				</option>
			</select>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_whatsapp_color">
				<?php echo esc_html__( 'Cor de Fundo do Botão', 'wa-suit' ); ?>
			</label>
			<input type="text"
				id="wa_whatsapp_color"
				name="button_color"
				value="<?php echo esc_attr( $settings['button_color'] ); ?>"
				style="max-width: 140px;"
				placeholder="#25D366">
			<span class="description">
				<?php echo esc_html__( 'Cor em formato HEX. Padrão do WhatsApp é #25D366.', 'wa-suit' ); ?>
			</span>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-checkbox-item">
				<input type="checkbox"
					name="open_new_tab"
					value="1"
					<?php checked( '1' === (string) $settings['open_new_tab'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Abrir conversa em uma nova aba', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Mantém a página do seu site aberta no navegador do cliente ao clicar.', 'wa-suit' ); ?></span>
				</div>
			</label>
		</div>
		<?php
	}

	public function save_tab_settings( $post_data ) {
		$sanitized = array(
			'phone_number'    => isset( $post_data['phone_number'] ) ? sanitize_text_field( $post_data['phone_number'] ) : '',
			'default_message' => isset( $post_data['default_message'] ) ? sanitize_textarea_field( $post_data['default_message'] ) : '',
			'button_text'     => isset( $post_data['button_text'] ) ? sanitize_text_field( $post_data['button_text'] ) : '',
			'position'        => isset( $post_data['position'] ) && 'bottom-left' === $post_data['position'] ? 'bottom-left' : 'bottom-right',
			'button_color'    => isset( $post_data['button_color'] ) ? sanitize_hex_color( $post_data['button_color'] ) : '#25D366',
			'open_new_tab'    => isset( $post_data['open_new_tab'] ) ? '1' : '0',
		);

		$this->update_settings( $sanitized );
	}
}

wa_suit_register_module( new WA_Suit_Module_Whatsapp() );
