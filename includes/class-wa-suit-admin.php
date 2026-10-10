<?php
/**
 * Administration menu and dashboard - Rank Math inspired layout.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the admin interface, tabs, and module switching in Rank Math style.
 */
class WA_Suit_Admin {

	/**
	 * Settings instance.
	 *
	 * @var WA_Suit_Settings
	 */
	private $settings;

	/**
	 * Module loader instance.
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
		add_action( 'wp_ajax_wa_suit_toggle_module', array( $this, 'ajax_toggle_module' ) );
	}

	/**
	 * Adds the admin menu right under Dashboard (Painel) at the highest order (position 3).
	 *
	 * @return void
	 */
	public function add_menu() {
		// Position 3 places the menu immediately below Dashboard (2), above Posts (5).
		add_menu_page(
			__( 'WA Web Design', 'wa-suit' ),
			__( 'WA Web Design', 'wa-suit' ),
			'manage_options',
			'wa-suit',
			array( $this, 'render_page' ),
			'dashicons-admin-links',
			3
		);

		add_submenu_page(
			'wa-suit',
			__( 'Visão Geral - WA Web Design', 'wa-suit' ),
			__( 'Painel Geral', 'wa-suit' ),
			'manage_options',
			'wa-suit',
			array( $this, 'render_page' )
		);

		// Dynamic submenu for active modules (Rank Math style)
		$registry       = $this->module_loader->get_registry();
		$active_modules = $registry->get_active();

		foreach ( $active_modules as $module ) {
			if ( ! $module->has_settings_tab() ) {
				continue;
			}

			global $submenu;
			if ( isset( $submenu['wa-suit'] ) ) {
				$tab_url = admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $module->get_id() ) );
				$submenu['wa-suit'][] = array(
					esc_html( $module->get_title() ),
					'manage_options',
					$tab_url,
				);
			}
		}
	}

	/**
	 * Enqueues assets only on plugin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'toplevel_page_wa-suit' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'wa-suit-admin',
			WA_SUIT_URL . 'assets/css/admin.css',
			array( 'dashicons' ),
			WA_SUIT_VERSION
		);

		wp_enqueue_script(
			'wa-suit-admin',
			WA_SUIT_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WA_SUIT_VERSION,
			true
		);

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

		wp_localize_script(
			'wa-suit-admin',
			'waSuitAdminData',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => WA_Suit_Security::create_nonce(),
				'adminUrl'   => admin_url( 'admin.php?page=wa-suit' ),
				'currentTab' => $current_tab,
				'strings'    => array(
					'copied'     => __( 'Copiado!', 'wa-suit' ),
					'copy'       => __( 'Copiar', 'wa-suit' ),
					'confirmDel' => __( 'Tem certeza que deseja excluir este link?', 'wa-suit' ),
					'saving'     => __( 'Salvando...', 'wa-suit' ),
					'save'       => __( 'Salvar Link', 'wa-suit' ),
					'active'     => __( 'Ativo', 'wa-suit' ),
					'inactive'   => __( 'Inativo', 'wa-suit' ),
				),
			)
		);
	}

	/**
	 * Handles AJAX module toggle.
	 *
	 * @return void
	 */
	public function ajax_toggle_module() {
		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'wa-suit' ) ) );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! WA_Suit_Security::verify_nonce( $nonce ) ) {
			wp_send_json_error( array( 'message' => __( 'Sessão expirada. Recarregue a página.', 'wa-suit' ) ) );
		}

		$module_id = isset( $_POST['module_id'] ) ? sanitize_key( $_POST['module_id'] ) : '';
		$state     = isset( $_POST['state'] ) && '1' === (string) $_POST['state'];

		$module = $this->module_loader->get_registry()->get_module( $module_id );
		if ( ! $module ) {
			wp_send_json_error( array( 'message' => __( 'Módulo não encontrado.', 'wa-suit' ) ) );
		}

		if ( $state ) {
			$this->settings->enable_module( $module_id );
		} else {
			$this->settings->disable_module( $module_id );
		}

		$active_count = count( $this->module_loader->get_registry()->get_active() );

		wp_send_json_success(
			array(
				'module_id'    => $module_id,
				'state'        => $state,
				'active_count' => $active_count,
				'title'        => $module->get_title(),
				'tab_url'      => admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $module_id ) ),
				'message'      => $state
					? sprintf( __( '"%s" foi ativado.', 'wa-suit' ), $module->get_title() )
					: sprintf( __( '"%s" foi desativado.', 'wa-suit' ), $module->get_title() ),
			)
		);
	}

	/**
	 * Renders main administrative interface.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'wa-suit' ) );
		}

		$registry       = $this->module_loader->get_registry();
		$all_modules    = $registry->get_all();
		$active_modules = $registry->get_active();

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
		?>
		<div class="wrap wa-rm-wrap">
			<!-- Subtle Top Gradient Detail Bar -->
			<div class="wa-rm-gradient-bar"></div>

			<!-- Rank Math Inspired Header Bar -->
			<div class="wa-rm-header">
				<div class="wa-rm-header__left">
					<div class="wa-rm-brand-logo">WA</div>
					<div class="wa-rm-brand-info">
						<h1 class="wa-rm-brand-title">
							<?php echo esc_html__( 'WA Web Design', 'wa-suit' ); ?>
							<span class="wa-rm-badge-version">SUITE</span>
						</h1>
						<span class="wa-rm-breadcrumb"><?php echo esc_html__( 'Dashboard / Módulos', 'wa-suit' ); ?></span>
					</div>
				</div>

				<div class="wa-rm-header__right">
					<div class="wa-rm-status-pill">
						<span class="wa-rm-dot-live"></span>
						<span><?php echo esc_html__( 'Sistema Ativo', 'wa-suit' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Rank Math Sub-Navigation Tab Bar -->
			<div class="wa-rm-nav-bar">
				<ul class="wa-rm-nav-tabs">
					<li class="wa-rm-nav-tab <?php echo 'dashboard' === $current_tab ? 'is-active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=dashboard' ) ); ?>">
							<span class="dashicons dashicons-screenoptions"></span>
							<?php echo esc_html__( 'Módulos', 'wa-suit' ); ?>
						</a>
					</li>

					<?php foreach ( $all_modules as $mod ) :
						$is_active = $mod->is_active();
						if ( ! $mod->has_settings_tab() ) {
							continue;
						}
						$is_current = ( $current_tab === $mod->get_id() );
						?>
						<li class="wa-rm-nav-tab wa-rm-nav-tab--module <?php echo $is_current ? 'is-active' : ''; ?>"
							data-nav-module="<?php echo esc_attr( $mod->get_id() ); ?>"
							style="<?php echo $is_active ? '' : 'display: none;'; ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $mod->get_id() ) ) ); ?>">
								<span class="dashicons <?php echo esc_attr( $mod->get_icon() ); ?>"></span>
								<?php echo esc_html( $mod->get_title() ); ?>
							</a>
						</li>
					<?php endforeach; ?>

					<li class="wa-rm-nav-tab <?php echo 'help' === $current_tab ? 'is-active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=help' ) ); ?>">
							<span class="dashicons dashicons-editor-help"></span>
							<?php echo esc_html__( 'Ajuda', 'wa-suit' ); ?>
						</a>
					</li>
				</ul>
			</div>

			<!-- Main Tab Content Area -->
			<div class="wa-rm-content">
				<?php
				if ( 'dashboard' === $current_tab ) {
					$this->render_dashboard_tab( $all_modules );
				} elseif ( 'help' === $current_tab ) {
					$this->render_help_tab();
				} else {
					$active_module = $registry->get_module( $current_tab );
					if ( $active_module ) {
						$this->render_module_tab( $active_module );
					} else {
						?>
						<div class="wa-rm-card">
							<h3><?php echo esc_html__( 'Módulo não encontrado', 'wa-suit' ); ?></h3>
							<p><?php echo esc_html__( 'O módulo selecionado não está disponível.', 'wa-suit' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=dashboard' ) ); ?>" class="wa-rm-btn wa-rm-btn--primary">
								<?php echo esc_html__( 'Voltar aos Módulos', 'wa-suit' ); ?>
							</a>
						</div>
						<?php
					}
				}
				?>
			</div>

			<!-- Rank Math Footer Note -->
			<div class="wa-rm-footer">
				<p>
					<?php echo esc_html__( 'Desenvolvido por', 'wa-suit' ); ?>
					<strong>WA Web Design</strong> &bull; <?php echo esc_html__( 'Versão', 'wa-suit' ); ?> <?php echo esc_html( WA_SUIT_VERSION ); ?>
				</p>
			</div>

			<!-- Floating Toast Notification -->
			<div id="wa-suit-toast" class="wa-rm-toast" aria-live="polite"></div>
		</div>
		<?php
	}

	/**
	 * Renders Rank Math style Modules Dashboard.
	 *
	 * @param array $all_modules List of registered modules.
	 * @return void
	 */
	private function render_dashboard_tab( $all_modules ) {
		?>
		<div class="wa-rm-dashboard-wrap">
			<!-- Rank Math Grid of Modules -->
			<div class="wa-rm-modules-grid">
				<?php foreach ( $all_modules as $mod ) :
					$is_active = $mod->is_active();
					$mod_id    = $mod->get_id();
					?>
					<div class="wa-rm-box wa-rm-module-box <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>" data-module-card="<?php echo esc_attr( $mod_id ); ?>">
						<!-- Card Header: Title + Badges -->
						<header class="wa-rm-box-header">
							<div class="wa-rm-box-title-wrap">
								<span class="dashicons <?php echo esc_attr( $mod->get_icon() ); ?> wa-rm-module-icon"></span>
								<h3 class="wa-rm-box-title"><?php echo esc_html( $mod->get_title() ); ?></h3>
							</div>
							<span class="wa-rm-badge-tag"><?php echo esc_html( $mod->get_category() ); ?></span>
						</header>

						<!-- Card Content: Description -->
						<div class="wa-rm-box-content">
							<p><?php echo esc_html( $mod->get_description() ); ?></p>
						</div>

						<!-- Card Footer: Settings Link on Left, Switch on Right -->
						<footer class="wa-rm-box-footer">
							<div class="wa-rm-box-status">
								<?php if ( $mod->has_settings_tab() ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $mod_id ) ) ); ?>"
										class="wa-rm-settings-link"
										data-action-area="<?php echo esc_attr( $mod_id ); ?>"
										style="<?php echo $is_active ? '' : 'display: none;'; ?>">
										<span class="dashicons dashicons-admin-generic"></span>
										<?php echo esc_html__( 'Configurações', 'wa-suit' ); ?>
										<span class="dashicons dashicons-arrow-right-alt2"></span>
									</a>
								<?php endif; ?>
							</div>

							<div class="wa-rm-box-actions">
								<label class="wa-rm-switch" title="<?php esc_attr_e( 'Ativar ou desativar módulo', 'wa-suit' ); ?>">
									<input type="checkbox"
										class="wa-rm-module-toggle"
										data-module-id="<?php echo esc_attr( $mod_id ); ?>"
										<?php checked( $is_active ); ?>>
									<span class="wa-rm-slider"></span>
								</label>
							</div>
						</footer>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders module settings tab view (Rank Math Style).
	 *
	 * @param WA_Suit_Module_Base $module Module object.
	 * @return void
	 */
	private function render_module_tab( $module ) {
		$is_active = $module->is_active();
		?>
		<div class="wa-rm-module-view">
			<div class="wa-rm-module-view-header">
				<div class="wa-rm-module-view-nav">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=dashboard' ) ); ?>" class="wa-rm-back-link">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<?php echo esc_html__( 'Módulos', 'wa-suit' ); ?>
					</a>
					<span class="wa-rm-sep">/</span>
					<span class="wa-rm-current"><?php echo esc_html( $module->get_title() ); ?></span>
				</div>

				<div class="wa-rm-module-view-actions">
					<label class="wa-rm-switch">
						<input type="checkbox"
							class="wa-rm-module-toggle"
							data-module-id="<?php echo esc_attr( $module->get_id() ); ?>"
							<?php checked( $is_active ); ?>>
						<span class="wa-rm-slider"></span>
					</label>
					<span class="wa-rm-toggle-status" data-status-label="<?php echo esc_attr( $module->get_id() ); ?>">
						<?php echo $is_active ? esc_html__( 'Ativo', 'wa-suit' ) : esc_html__( 'Inativo', 'wa-suit' ); ?>
					</span>
				</div>
			</div>

			<div class="wa-rm-module-view-body">
				<?php $module->render_tab(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders Help tab.
	 *
	 * @return void
	 */
	private function render_help_tab() {
		?>
		<div class="wa-rm-box wa-rm-help-box">
			<header class="wa-rm-box-header">
				<h3 class="wa-rm-box-title">
					<span class="dashicons dashicons-welcome-learn-more"></span>
					<?php echo esc_html__( 'Guia de Uso: Links Dinâmicos no Elementor', 'wa-suit' ); ?>
				</h3>
			</header>

			<div class="wa-rm-box-content wa-rm-help-content">
				<p>
					<?php echo esc_html__( 'O módulo de Links Dinâmicos permite que você cadastre links estratégicos (como WhatsApp, Checkout, Agendamento) em um único lugar e utilize shortcodes em todos os botões do Elementor.', 'wa-suit' ); ?>
				</p>

				<div class="wa-rm-help-grid">
					<div class="wa-rm-help-col">
						<h4>1. <?php echo esc_html__( 'Cadastrar o Link', 'wa-suit' ); ?></h4>
						<p><?php echo esc_html__( 'Na aba "Links Dinâmicos", clique em "+ Novo Link", defina um nome (ex: WhatsApp Geral) e a URL completa.', 'wa-suit' ); ?></p>
					</div>
					<div class="wa-rm-help-col">
						<h4>2. <?php echo esc_html__( 'Copiar o Shortcode', 'wa-suit' ); ?></h4>
						<p><?php echo esc_html__( 'Clique no botão "Copiar" ao lado do link para copiar [wa_link id="slug"].', 'wa-suit' ); ?></p>
					</div>
					<div class="wa-rm-help-col">
						<h4>3. <?php echo esc_html__( 'Usar no Elementor', 'wa-suit' ); ?></h4>
						<p><?php echo esc_html__( 'No Elementor, cole o shortcode diretamente no campo de Link do botão ou selecione a Tag Dinâmica > Shortcode.', 'wa-suit' ); ?></p>
					</div>
					<div class="wa-rm-help-col">
						<h4>4. <?php echo esc_html__( 'Alteração Global Instantânea', 'wa-suit' ); ?></h4>
						<p><?php echo esc_html__( 'Quando o número ou a URL mudar, edite uma única vez no painel. Todos os botões do site serão atualizados na hora!', 'wa-suit' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
