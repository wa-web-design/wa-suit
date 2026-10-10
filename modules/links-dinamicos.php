<?php
/**
 * Módulo: Links Dinâmicos para Elementor.
 * Gerenciador centralizado de links com shortcodes dinâmicos.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe do módulo de Links Dinâmicos.
 */
class WA_Suit_Module_Dynamic_Links extends WA_Suit_Module_Base {

	/**
	 * Nome da opção no banco de dados.
	 */
	const OPTION_LINKS = 'wa_suit_dynamic_links';

	public function get_id() {
		return 'dynamic-links';
	}

	public function get_title() {
		return __( 'Links Dinâmicos', 'wa-suit' );
	}

	public function get_description() {
		return __( 'Crie links centralizados com shortcodes para botões do Elementor. Altere o link aqui e todos os botões do site mudam instantaneamente.', 'wa-suit' );
	}

	public function get_icon() {
		return 'dashicons-admin-links';
	}

	public function get_category() {
		return __( 'Elementor', 'wa-suit' );
	}

	public function get_order() {
		return 1;
	}

	/**
	 * Inicializa hooks do WordPress quando o módulo está ATIVO.
	 */
	public function init() {
		// Registra o shortcode oficial [wa_link id="..."]
		add_shortcode( 'wa_link', array( $this, 'render_shortcode' ) );

		// Compatibilidade com Elementor (substituição em atributos href)
		add_filter( 'the_content', array( $this, 'filter_elementor_links' ), 20 );
		add_filter( 'widget_text', array( $this, 'filter_elementor_links' ), 20 );

		// Redirecionamento amigável opcional: se acessar ?wa_go=slug
		add_action( 'template_redirect', array( $this, 'handle_direct_redirect' ) );

		// Handlers AJAX para adicionar, editar e excluir links
		if ( is_admin() ) {
			add_action( 'wp_ajax_wa_suit_save_link', array( $this, 'ajax_save_link' ) );
			add_action( 'wp_ajax_wa_suit_delete_link', array( $this, 'ajax_delete_link' ) );
		}
	}

	/**
	 * Retorna todos os links cadastrados.
	 *
	 * @return array
	 */
	public function get_all_links() {
		$links = get_option( self::OPTION_LINKS, array() );
		return is_array( $links ) ? $links : array();
	}

	/**
	 * Busca a URL de um link pelo ID/Slug.
	 *
	 * @param string $id Identificador do link.
	 * @return string URL ou string vazia se não encontrar.
	 */
	public function get_link_url( $id ) {
		$clean_id = sanitize_key( $id );
		$links    = $this->get_all_links();

		if ( isset( $links[ $clean_id ]['url'] ) ) {
			return $links[ $clean_id ]['url'];
		}

		return '';
	}

	/**
	 * Executa o shortcode [wa_link id="xyz"].
	 * Retorna apenas a URL limpa para ser usada em links e botões.
	 *
	 * @param array $atts Atributos do shortcode.
	 * @return string URL de destino.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'      => '',
				'slug'    => '',
				'default' => '#',
			),
			$atts,
			'wa_link'
		);

		$link_id = ! empty( $atts['id'] ) ? $atts['id'] : $atts['slug'];

		if ( empty( $link_id ) ) {
			return esc_url( $atts['default'] );
		}

		$url = $this->get_link_url( $link_id );

		return ! empty( $url ) ? esc_url( $url ) : esc_url( $atts['default'] );
	}

	/**
	 * Filtra o conteúdo para processar shortcodes dentro de atributos href="[wa_link id=...]"
	 * garantindo compatibilidade total com botões do Elementor.
	 *
	 * @param string $content Conteúdo HTML renderizado.
	 * @return string Conteúdo processado.
	 */
	public function filter_elementor_links( $content ) {
		if ( false === strpos( $content, '[wa_link' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/href=([\'"])(?:%5B|\[)wa_link\s+id=(?:&quot;|&#34;|[\'"])?([a-zA-Z0-9_\-]+)(?:&quot;|&#34;|[\'"])?(?:%5D|\])\1/i',
			function ( $matches ) {
				$quote   = $matches[1];
				$link_id = $matches[2];
				$url     = $this->get_link_url( $link_id );
				$target  = ! empty( $url ) ? $url : '#';
				return 'href=' . $quote . esc_url( $target ) . $quote;
			},
			$content
		);
	}

	/**
	 * Permite redirecionamento rápido caso o visitante acesse ?wa_go=slug.
	 */
	public function handle_direct_redirect() {
		if ( ! empty( $_GET['wa_go'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$slug = sanitize_key( $_GET['wa_go'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$url  = $this->get_link_url( $slug );

			if ( ! empty( $url ) ) {
				wp_redirect( esc_url_raw( $url ), 302 );
				exit;
			}
		}
	}

	/**
	 * Salva ou atualiza um link via AJAX.
	 */
	public function ajax_save_link() {
		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'wa-suit' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! WA_Suit_Security::verify_nonce( $nonce ) ) {
			wp_send_json_error( array( 'message' => __( 'Sessão expirada. Recarregue a página.', 'wa-suit' ) ) );
		}

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$id    = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		$url   = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( empty( $title ) || empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'Preencha o Nome e a URL do link.', 'wa-suit' ) ) );
		}

		if ( empty( $id ) ) {
			$id = sanitize_key( sanitize_title( $title ) );
		}

		if ( empty( $id ) ) {
			$id = 'link-' . time();
		}

		$links = $this->get_all_links();

		$links[ $id ] = array(
			'id'         => $id,
			'title'      => $title,
			'url'        => $url,
			'updated_at' => current_time( 'mysql' ),
		);

		update_option( self::OPTION_LINKS, $links );

		wp_send_json_success(
			array(
				'message'   => __( 'Link salvo com sucesso!', 'wa-suit' ),
				'link'      => $links[ $id ],
				'shortcode' => '[wa_link id="' . $id . '"]',
			)
		);
	}

	/**
	 * Exclui um link via AJAX.
	 */
	public function ajax_delete_link() {
		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'wa-suit' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! WA_Suit_Security::verify_nonce( $nonce ) ) {
			wp_send_json_error( array( 'message' => __( 'Sessão expirada. Recarregue a página.', 'wa-suit' ) ) );
		}

		$id    = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
		$links = $this->get_all_links();

		if ( isset( $links[ $id ] ) ) {
			unset( $links[ $id ] );
			update_option( self::OPTION_LINKS, $links );
			wp_send_json_success( array( 'message' => __( 'Link excluído com sucesso.', 'wa-suit' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Link não encontrado.', 'wa-suit' ) ) );
	}

	/**
	 * Renderiza a interface minimalista nível Apple dentro da aba do módulo.
	 */
	public function render_tab() {
		$links = $this->get_all_links();
		?>
		<div class="wa-apple-links-manager" id="wa-links-manager">

			<!-- Top Actions Row -->
			<div class="wa-apple-panel-top">
				<div>
					<h3 class="wa-apple-panel-title"><?php echo esc_html__( 'Gerenciador de Links Dinâmicos', 'wa-suit' ); ?></h3>
					<p class="wa-apple-panel-subtitle">
						<?php echo esc_html__( 'Cadastre URLs abaixo, copie os shortcodes e use nos botões do Elementor. Altere o link aqui para atualizar o site inteiro.', 'wa-suit' ); ?>
					</p>
				</div>
				<button type="button" class="wa-apple-btn wa-apple-btn--primary" id="wa-btn-toggle-add-link">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php echo esc_html__( 'Novo Link', 'wa-suit' ); ?>
				</button>
			</div>

			<!-- Apple-style Clean Card: Form for Add/Edit Link (hidden by default) -->
			<div class="wa-apple-link-drawer" id="wa-link-form-drawer" style="display: none;">
				<div class="wa-apple-drawer-header">
					<h4 id="wa-drawer-title"><?php echo esc_html__( 'Criar Novo Link Dinâmico', 'wa-suit' ); ?></h4>
					<button type="button" class="wa-apple-btn-close" id="wa-btn-close-drawer" aria-label="<?php esc_attr_e( 'Fechar', 'wa-suit' ); ?>">&times;</button>
				</div>

				<form id="wa-link-form" class="wa-apple-link-form">
					<div class="wa-apple-form-grid">
						<div class="wa-apple-field">
							<label for="wa_link_title"><?php echo esc_html__( 'Nome de Identificação', 'wa-suit' ); ?></label>
							<input type="text" id="wa_link_title" name="title" placeholder="Ex: WhatsApp Suporte, Checkout Kiwify, Agendamento" required>
							<span class="wa-apple-hint"><?php echo esc_html__( 'Um nome claro para você identificar este link.', 'wa-suit' ); ?></span>
						</div>

						<div class="wa-apple-field">
							<label for="wa_link_id"><?php echo esc_html__( 'ID / Slug do Shortcode', 'wa-suit' ); ?></label>
							<input type="text" id="wa_link_id" name="id" placeholder="Ex: whatsapp, checkout-pro, agendamento">
							<span class="wa-apple-hint"><?php echo esc_html__( 'Identificador único usado no shortcode. Gerado automaticamente se deixar em branco.', 'wa-suit' ); ?></span>
						</div>

						<div class="wa-apple-field wa-apple-field--full">
							<label for="wa_link_url"><?php echo esc_html__( 'URL de Destino Completa', 'wa-suit' ); ?></label>
							<input type="url" id="wa_link_url" name="url" placeholder="https://wa.me/5511999998888 ou https://pay.kiwify.com.br/..." required>
							<span class="wa-apple-hint"><?php echo esc_html__( 'O link para onde o visitante será direcionado ao clicar no botão.', 'wa-suit' ); ?></span>
						</div>
					</div>

					<div class="wa-apple-drawer-actions">
						<button type="button" class="wa-apple-btn wa-apple-btn--secondary" id="wa-btn-cancel-drawer">
							<?php echo esc_html__( 'Cancelar', 'wa-suit' ); ?>
						</button>
						<button type="submit" class="wa-apple-btn wa-apple-btn--primary" id="wa-btn-save-link">
							<span class="dashicons dashicons-saved"></span>
							<?php echo esc_html__( 'Salvar Link', 'wa-suit' ); ?>
						</button>
					</div>
				</form>
			</div>

			<!-- Links Table / List -->
			<div class="wa-apple-table-wrap">
				<table class="wa-apple-table">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Nome & ID', 'wa-suit' ); ?></th>
							<th><?php echo esc_html__( 'URL de Destino', 'wa-suit' ); ?></th>
							<th><?php echo esc_html__( 'Shortcode para Elementor', 'wa-suit' ); ?></th>
							<th class="wa-apple-text-right"><?php echo esc_html__( 'Ações', 'wa-suit' ); ?></th>
						</tr>
					</thead>
					<tbody id="wa-links-tbody">
						<?php if ( empty( $links ) ) : ?>
							<tr class="wa-apple-empty-row" id="wa-empty-row">
								<td colspan="4">
									<div class="wa-apple-empty-state">
										<div class="wa-apple-empty-icon">
											<span class="dashicons dashicons-admin-links"></span>
										</div>
										<h4><?php echo esc_html__( 'Nenhum link cadastrado ainda', 'wa-suit' ); ?></h4>
										<p><?php echo esc_html__( 'Clique no botão acima "Novo Link" para criar seu primeiro link dinâmico.', 'wa-suit' ); ?></p>
									</div>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $links as $link ) :
								$shortcode = '[wa_link id="' . esc_attr( $link['id'] ) . '"]';
								?>
								<tr data-link-id="<?php echo esc_attr( $link['id'] ); ?>">
									<td>
										<strong class="wa-apple-link-title"><?php echo esc_html( $link['title'] ); ?></strong>
										<span class="wa-apple-link-id">ID: <code><?php echo esc_html( $link['id'] ); ?></code></span>
									</td>
									<td>
										<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="wa-apple-url-preview" title="<?php echo esc_attr( $link['url'] ); ?>">
											<?php echo esc_html( $link['url'] ); ?>
										</a>
									</td>
									<td>
										<div class="wa-apple-copy-box">
											<code class="wa-apple-code-tag"><?php echo esc_html( $shortcode ); ?></code>
											<button type="button" class="wa-apple-btn-copy" data-copy="<?php echo esc_attr( $shortcode ); ?>" title="<?php esc_attr_e( 'Copiar shortcode', 'wa-suit' ); ?>">
												<span class="dashicons dashicons-admin-page"></span>
												<span class="copy-text"><?php echo esc_html__( 'Copiar', 'wa-suit' ); ?></span>
											</button>
										</div>
									</td>
									<td class="wa-apple-text-right">
										<button type="button"
											class="wa-apple-icon-btn wa-btn-edit-link"
											data-id="<?php echo esc_attr( $link['id'] ); ?>"
											data-title="<?php echo esc_attr( $link['title'] ); ?>"
											data-url="<?php echo esc_attr( $link['url'] ); ?>"
											title="<?php esc_attr_e( 'Editar', 'wa-suit' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</button>
										<button type="button"
											class="wa-apple-icon-btn wa-apple-icon-btn--danger wa-btn-delete-link"
											data-id="<?php echo esc_attr( $link['id'] ); ?>"
											data-title="<?php echo esc_attr( $link['title'] ); ?>"
											title="<?php esc_attr_e( 'Excluir', 'wa-suit' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Clean Step-by-Step Guide for Elementor -->
			<div class="wa-apple-card-guide">
				<div class="wa-apple-guide-badge">
					<span class="wa-apple-guide-dot"></span>
					<?php echo esc_html__( 'Como usar no Elementor', 'wa-suit' ); ?>
				</div>
				<div class="wa-apple-steps-grid">
					<div class="wa-apple-step">
						<span class="wa-apple-step-num">1</span>
						<h5><?php echo esc_html__( 'Copie o Shortcode', 'wa-suit' ); ?></h5>
						<p><?php echo esc_html__( 'Clique no botão "Copiar" ao lado do link desejado acima (ex: [wa_link id="whatsapp"]).', 'wa-suit' ); ?></p>
					</div>
					<div class="wa-apple-step">
						<span class="wa-apple-step-num">2</span>
						<h5><?php echo esc_html__( 'Cole no Botão', 'wa-suit' ); ?></h5>
						<p><?php echo esc_html__( 'No Elementor, selecione seu botão e cole o shortcode diretamente no campo "Link" ou use Tags Dinâmicas > Shortcode.', 'wa-suit' ); ?></p>
					</div>
					<div class="wa-apple-step">
						<span class="wa-apple-step-num">3</span>
						<h5><?php echo esc_html__( 'Mude Tudo de Uma Vez', 'wa-suit' ); ?></h5>
						<p><?php echo esc_html__( 'Quando precisar trocar o link, altere apenas a URL aqui no painel. Todos os botões do site atualizarão na hora!', 'wa-suit' ); ?></p>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}

wa_suit_register_module( new WA_Suit_Module_Dynamic_Links() );
