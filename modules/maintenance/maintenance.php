<?php
/**
 * Maintenance & Coming Soon Mode Module.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module: Modo Manutenção & Em Breve.
 */
class WA_Suit_Module_Maintenance extends WA_Suit_Module_Base {

	public function get_id() {
		return 'maintenance';
	}

	public function get_title() {
		return __( 'Modo Manutenção & Em Breve', 'wa-suit' );
	}

	public function get_description() {
		return __( 'Exibe uma página bonita de aviso enquanto o site está em construção. Administradores navegam normalmente.', 'wa-suit' );
	}

	public function get_icon() {
		return 'dashicons-hammer';
	}

	public function get_category() {
		return __( 'Ferramentas', 'wa-suit' );
	}

	public function get_order() {
		return 4;
	}

	public function get_default_settings() {
		return array(
			'headline'    => __( 'Estamos trabalhando em novidades!', 'wa-suit' ),
			'description' => __( 'Nosso site está passando por melhorias e voltará ao ar em breve.', 'wa-suit' ),
			'contact'     => '',
		);
	}

	public function init() {
		add_action( 'template_redirect', array( $this, 'intercept_maintenance_page' ) );
	}

	public function intercept_maintenance_page() {
		// Logged in administrators can browse normally.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Don't intercept login or admin requests.
		if ( is_admin() || in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ), true ) ) {
			return;
		}

		$headline    = $this->get_setting( 'headline', __( 'Site em Manutenção', 'wa-suit' ) );
		$description = $this->get_setting( 'description', __( 'Voltaremos em breve.', 'wa-suit' ) );
		$contact     = $this->get_setting( 'contact', '' );

		status_header( 503 );
		nocache_headers();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html( $headline ); ?> - <?php bloginfo( 'name' ); ?></title>
			<style>
				* { box-sizing: border-box; margin: 0; padding: 0; }
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
					color: #ffffff;
					min-height: 100vh;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 24px;
					text-align: center;
				}
				.card {
					background: rgba(255, 255, 255, 0.05);
					backdrop-filter: blur(10px);
					border: 1px solid rgba(255, 255, 255, 0.12);
					border-radius: 20px;
					padding: 48px 32px;
					max-width: 580px;
					width: 100%;
					box-shadow: 0 20px 40px rgba(0,0,0,0.4);
				}
				.logo-badge {
					display: inline-block;
					background: #2563eb;
					color: #ffffff;
					font-weight: 700;
					font-size: 13px;
					padding: 6px 14px;
					border-radius: 20px;
					letter-spacing: 1px;
					text-transform: uppercase;
					margin-bottom: 24px;
				}
				h1 {
					font-size: 28px;
					font-weight: 800;
					margin-bottom: 16px;
					line-height: 1.3;
				}
				p {
					color: #94a3b8;
					font-size: 16px;
					line-height: 1.6;
					margin-bottom: 28px;
				}
				.contact {
					padding-top: 20px;
					border-top: 1px solid rgba(255, 255, 255, 0.1);
					font-size: 14px;
					color: #cbd5e1;
				}
				.contact a {
					color: #60a5fa;
					text-decoration: none;
					font-weight: 600;
				}
			</style>
		</head>
		<body>
			<div class="card">
				<div class="logo-badge"><?php bloginfo( 'name' ); ?></div>
				<h1><?php echo esc_html( $headline ); ?></h1>
				<p><?php echo nl2br( esc_html( $description ) ); ?></p>
				<?php if ( ! empty( $contact ) ) : ?>
					<div class="contact">
						<?php echo esc_html__( 'Dúvidas ou urgências:', 'wa-suit' ); ?> <strong><?php echo esc_html( $contact ); ?></strong>
					</div>
				<?php endif; ?>
			</div>
		</body>
		</html>
		<?php
		exit;
	}

	public function render_tab() {
		$settings = $this->get_settings();
		?>
		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_maint_headline">
				<?php echo esc_html__( 'Título Principal do Aviso', 'wa-suit' ); ?>
			</label>
			<input type="text"
				id="wa_maint_headline"
				name="headline"
				value="<?php echo esc_attr( $settings['headline'] ); ?>">
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_maint_desc">
				<?php echo esc_html__( 'Mensagem Explicativa', 'wa-suit' ); ?>
			</label>
			<textarea id="wa_maint_desc" name="description" rows="4"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
		</div>

		<div class="wa-suit-form-group">
			<label class="wa-suit-form-label" for="wa_maint_contact">
				<?php echo esc_html__( 'Contato de Suporte / Telefone / E-mail', 'wa-suit' ); ?>
			</label>
			<input type="text"
				id="wa_maint_contact"
				name="contact"
				value="<?php echo esc_attr( $settings['contact'] ); ?>"
				placeholder="Ex: contato@seusite.com.br ou (11) 99999-9999">
			<span class="description">
				<?php echo esc_html__( 'Exibido discretamente no rodapé da página de manutenção.', 'wa-suit' ); ?>
			</span>
		</div>
		<?php
	}

	public function save_tab_settings( $post_data ) {
		$sanitized = array(
			'headline'    => isset( $post_data['headline'] ) ? sanitize_text_field( $post_data['headline'] ) : '',
			'description' => isset( $post_data['description'] ) ? sanitize_textarea_field( $post_data['description'] ) : '',
			'contact'     => isset( $post_data['contact'] ) ? sanitize_text_field( $post_data['contact'] ) : '',
		);

		$this->update_settings( $sanitized );
	}
}

wa_suit_register_module( new WA_Suit_Module_Maintenance() );
