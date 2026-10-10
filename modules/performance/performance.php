<?php
/**
 * Performance & Cleanup Module.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module: Otimização & Limpeza do WordPress.
 */
class WA_Suit_Module_Performance extends WA_Suit_Module_Base {

	public function get_id() {
		return 'performance';
	}

	public function get_title() {
		return __( 'Otimização & Limpeza do WP', 'wa-suit' );
	}

	public function get_description() {
		return __( 'Melhore a velocidade e segurança do site desativando recursos nativos desnecessários do core.', 'wa-suit' );
	}

	public function get_icon() {
		return 'dashicons-performance';
	}

	public function get_category() {
		return __( 'Performance', 'wa-suit' );
	}

	public function get_order() {
		return 3;
	}

	public function get_default_settings() {
		return array(
			'disable_emojis'   => '1',
			'remove_version'   => '1',
			'disable_xmlrpc'   => '1',
			'remove_wlw_rsd'   => '1',
			'disable_wp_embed' => '0',
		);
	}

	public function init() {
		$settings = $this->get_settings();

		// 1. Disable Emojis
		if ( '1' === (string) $settings['disable_emojis'] ) {
			add_action( 'init', array( $this, 'disable_emojis_action' ) );
		}

		// 2. Remove WP Version Generator
		if ( '1' === (string) $settings['remove_version'] ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
		}

		// 3. Disable XML-RPC
		if ( '1' === (string) $settings['disable_xmlrpc'] ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			remove_action( 'wp_head', 'rsd_link' );
		}

		// 4. Remove WLW Manifest and RSD
		if ( '1' === (string) $settings['remove_wlw_rsd'] ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
			remove_action( 'wp_head', 'rsd_link' );
		}

		// 5. Disable WP Embeds
		if ( '1' === (string) $settings['disable_wp_embed'] ) {
			add_action( 'wp_footer', array( $this, 'deregister_wp_embed' ) );
		}
	}

	public function disable_emojis_action() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
	}

	public function disable_emojis_tinymce( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	}

	public function deregister_wp_embed() {
		wp_deregister_script( 'wp-embed' );
	}

	public function render_tab() {
		$settings = $this->get_settings();
		?>
		<p class="description" style="margin-bottom: 20px;">
			<?php echo esc_html__( 'Selecione abaixo as otimizações recomendadas pela WA Web Design para acelerar o carregamento do site:', 'wa-suit' ); ?>
		</p>

		<div class="wa-suit-form-group">
			<label class="wa-suit-checkbox-item">
				<input type="checkbox" name="disable_emojis" value="1" <?php checked( '1' === (string) $settings['disable_emojis'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Desativar scripts de Emojis do WordPress', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Remove scripts e folhas de estilo pesadas inseridas nativamente pelo WordPress em todas as páginas.', 'wa-suit' ); ?></span>
				</div>
			</label>

			<label class="wa-suit-checkbox-item">
				<input type="checkbox" name="remove_version" value="1" <?php checked( '1' === (string) $settings['remove_version'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Remover número de versão do WordPress do código HTML', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Evita que invasores e robôs descubram qual versão do WordPress está instalada.', 'wa-suit' ); ?></span>
				</div>
			</label>

			<label class="wa-suit-checkbox-item">
				<input type="checkbox" name="disable_xmlrpc" value="1" <?php checked( '1' === (string) $settings['disable_xmlrpc'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Desativar XML-RPC (Segurança contra força bruta)', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Bloqueia requisições XML-RPC externas, prevenindo um dos maiores vetores de ataques de força bruta no WordPress.', 'wa-suit' ); ?></span>
				</div>
			</label>

			<label class="wa-suit-checkbox-item">
				<input type="checkbox" name="remove_wlw_rsd" value="1" <?php checked( '1' === (string) $settings['remove_wlw_rsd'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Remover links obsoletos RSD e Windows Live Writer', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Limpa tags <link> inúteis do cabeçalho HTML que não são mais utilizadas.', 'wa-suit' ); ?></span>
				</div>
			</label>

			<label class="wa-suit-checkbox-item">
				<input type="checkbox" name="disable_wp_embed" value="1" <?php checked( '1' === (string) $settings['disable_wp_embed'] ); ?>>
				<div>
					<span class="item-title"><?php echo esc_html__( 'Desativar WordPress oEmbed (wp-embed.min.js)', 'wa-suit' ); ?></span>
					<span class="item-desc"><?php echo esc_html__( 'Evita carregar o script de incorporação de postagens externas se você não precisa desse recurso.', 'wa-suit' ); ?></span>
				</div>
			</label>
		</div>
		<?php
	}

	public function save_tab_settings( $post_data ) {
		$sanitized = array(
			'disable_emojis'   => isset( $post_data['disable_emojis'] ) ? '1' : '0',
			'remove_version'   => isset( $post_data['remove_version'] ) ? '1' : '0',
			'disable_xmlrpc'   => isset( $post_data['disable_xmlrpc'] ) ? '1' : '0',
			'remove_wlw_rsd'   => isset( $post_data['remove_wlw_rsd'] ) ? '1' : '0',
			'disable_wp_embed' => isset( $post_data['disable_wp_embed'] ) ? '1' : '0',
		);

		$this->update_settings( $sanitized );
	}
}

wa_suit_register_module( new WA_Suit_Module_Performance() );
