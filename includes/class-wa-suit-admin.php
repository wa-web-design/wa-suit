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
 * Handles the admin interface, tabs, and module switching.
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
	 * Status message for notifications.
	 *
	 * @var string
	 */
	private $notice_message = '';

	/**
	 * Status type for notifications.
	 *
	 * @var string
	 */
	private $notice_type = 'success';

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
		add_action( 'admin_init', array( $this, 'handle_save_tab_settings' ) );
	}

	/**
	 * Adds the admin menu and dynamic submenus.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'WA Web Design', 'wa-suit' ),
			__( 'WA Web Design', 'wa-suit' ),
			'manage_options',
			'wa-suit',
			array( $this, 'render_page' ),
			'dashicons-art',
			62
		);

		add_submenu_page(
			'wa-suit',
			__( 'Painel Geral - WA Web Design', 'wa-suit' ),
			__( 'Painel Geral', 'wa-suit' ),
			'manage_options',
			'wa-suit',
			array( $this, 'render_page' )
		);

		// Add dynamic submenus for each active module.
		$registry       = $this->module_loader->get_registry();
		$active_modules = $registry->get_active();

		foreach ( $active_modules as $module ) {
			if ( ! $module->has_settings_tab() ) {
				continue;
			}

			// Add direct link submenu under WA Web Design.
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
	 * Enqueues assets only on the plugin pages.
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
					'moduleEnabled'  => __( 'Função ativada com sucesso!', 'wa-suit' ),
					'moduleDisabled' => __( 'Função desativada.', 'wa-suit' ),
					'error'          => __( 'Erro ao atualizar a função.', 'wa-suit' ),
					'configure'      => __( 'Configurar', 'wa-suit' ),
					'active'         => __( 'Ativo', 'wa-suit' ),
					'inactive'       => __( 'Inativo', 'wa-suit' ),
				),
			)
		);
	}

	/**
	 * Handles AJAX request to toggle module activation.
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
				'module_id'        => $module_id,
				'state'            => $state,
				'active_count'     => $active_count,
				'title'            => $module->get_title(),
				'icon'             => $module->get_icon(),
				'has_settings_tab' => $module->has_settings_tab(),
				'tab_url'          => admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $module_id ) ),
				'message'          => $state
					? sprintf( __( '"%s" foi ativado.', 'wa-suit' ), $module->get_title() )
					: sprintf( __( '"%s" foi desativado.', 'wa-suit' ), $module->get_title() ),
			)
		);
	}

	/**
	 * Handles POST submissions for saving tab settings.
	 *
	 * @return void
	 */
	public function handle_save_tab_settings() {
		if ( ! isset( $_POST['wa_suit_save_action'] ) ) {
			return;
		}

		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_die( esc_html__( 'Você não tem permissão para realizar esta ação.', 'wa-suit' ) );
		}

		$nonce = isset( $_POST['wa_suit_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wa_suit_nonce'] ) ) : '';
		if ( ! WA_Suit_Security::verify_nonce( $nonce ) ) {
			$this->notice_message = __( 'Erro de segurança: token inválido. Tente novamente.', 'wa-suit' );
			$this->notice_type    = 'error';
			return;
		}

		$module_id = isset( $_POST['module_id'] ) ? sanitize_key( $_POST['module_id'] ) : '';
		$module    = $this->module_loader->get_registry()->get_module( $module_id );

		if ( $module ) {
			$module->save_tab_settings( wp_unslash( $_POST ) );
			$this->notice_message = sprintf( __( 'Configurações de "%s" salvas com sucesso!', 'wa-suit' ), $module->get_title() );
			$this->notice_type    = 'success';
		}
	}

	/**
	 * Renders the main administration interface.
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

		// Extract unique categories for filter.
		$categories = array();
		foreach ( $all_modules as $mod ) {
			$cat = $mod->get_category();
			if ( ! in_array( $cat, $categories, true ) ) {
				$categories[] = $cat;
			}
		}
		sort( $categories );
		?>
		<div class="wrap wa-suit-wrap">
			<!-- Header Banner -->
			<header class="wa-suit-header">
				<div class="wa-suit-header__brand">
					<div class="wa-suit-header__logo">WA</div>
					<div class="wa-suit-header__info">
						<h1><?php echo esc_html__( 'WA Web Design', 'wa-suit' ); ?> <span class="wa-suit-header__badge">Suite v<?php echo esc_html( WA_SUIT_VERSION ); ?></span></h1>
						<p><?php echo esc_html__( 'Painel de Recursos, Ferramentas & Otimizações para Clientes', 'wa-suit' ); ?></p>
					</div>
				</div>
				<div class="wa-suit-header__stats">
					<div class="wa-suit-stat-box">
						<span class="wa-suit-stat-label"><?php echo esc_html__( 'Total de Funções', 'wa-suit' ); ?></span>
						<span class="wa-suit-stat-value"><?php echo esc_html( count( $all_modules ) ); ?></span>
					</div>
					<div class="wa-suit-stat-box">
						<span class="wa-suit-stat-label"><?php echo esc_html__( 'Funções Ativas', 'wa-suit' ); ?></span>
						<span class="wa-suit-stat-value wa-suit-stat-active-count"><?php echo esc_html( count( $active_modules ) ); ?></span>
					</div>
					<div class="wa-suit-stat-box wa-suit-stat-status">
						<span class="wa-suit-stat-label"><?php echo esc_html__( 'Status Geral', 'wa-suit' ); ?></span>
						<span class="wa-suit-stat-value wa-suit-text-success"><span class="wa-suit-status-dot"></span> <?php echo esc_html__( 'Ativo', 'wa-suit' ); ?></span>
					</div>
				</div>
			</header>

			<?php if ( ! empty( $this->notice_message ) ) : ?>
				<div class="wa-suit-notice wa-suit-notice--<?php echo esc_attr( $this->notice_type ); ?>">
					<p><?php echo esc_html( $this->notice_message ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Main Container with Sidebar Tabs and Content -->
			<div class="wa-suit-layout">
				<!-- Left Sidebar: Tabs Navigation -->
				<aside class="wa-suit-sidebar">
					<nav class="wa-suit-nav" aria-label="<?php esc_attr_e( 'Navegação de Funções', 'wa-suit' ); ?>">
						<!-- Main Dashboard Tab -->
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=dashboard' ) ); ?>"
							class="wa-suit-nav__item <?php echo 'dashboard' === $current_tab ? 'is-active' : ''; ?>">
							<span class="dashicons dashicons-admin-generic"></span>
							<span class="wa-suit-nav__title"><?php echo esc_html__( 'Painel de Funções', 'wa-suit' ); ?></span>
							<span class="wa-suit-nav__badge"><?php echo esc_html( count( $all_modules ) ); ?></span>
						</a>

						<!-- Section Heading: Active Modules -->
						<div class="wa-suit-nav__heading">
							<span><?php echo esc_html__( 'Abas de Funções Ativas', 'wa-suit' ); ?></span>
						</div>

						<div class="wa-suit-nav__modules-list" id="wa-suit-active-nav-list">
							<?php
							$has_active_tabs = false;
							foreach ( $all_modules as $mod ) :
								$is_active = $mod->is_active();
								if ( ! $mod->has_settings_tab() ) {
									continue;
								}
								if ( $is_active ) {
									$has_active_tabs = true;
								}
								$is_current = ( $current_tab === $mod->get_id() );
								?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $mod->get_id() ) ) ); ?>"
									class="wa-suit-nav__item wa-suit-nav__item--module <?php echo $is_current ? 'is-active' : ''; ?>"
									data-nav-module="<?php echo esc_attr( $mod->get_id() ); ?>"
									style="<?php echo $is_active ? '' : 'display: none;'; ?>">
									<span class="dashicons <?php echo esc_attr( $mod->get_icon() ); ?>"></span>
									<span class="wa-suit-nav__title"><?php echo esc_html( $mod->get_title() ); ?></span>
									<span class="wa-suit-status-dot wa-suit-status-dot--green" title="<?php esc_attr_e( 'Módulo Ativo', 'wa-suit' ); ?>"></span>
								</a>
							<?php endforeach; ?>

							<div class="wa-suit-nav__empty" id="wa-suit-nav-empty" style="<?php echo $has_active_tabs ? 'display: none;' : ''; ?>">
								<small><?php echo esc_html__( 'Nenhuma função ativa. Ative uma função ao lado para acessar sua aba de configurações.', 'wa-suit' ); ?></small>
							</div>
						</div>

						<!-- Section Heading: About -->
						<div class="wa-suit-nav__heading">
							<span><?php echo esc_html__( 'Informações', 'wa-suit' ); ?></span>
						</div>

						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=about' ) ); ?>"
							class="wa-suit-nav__item <?php echo 'about' === $current_tab ? 'is-active' : ''; ?>">
							<span class="dashicons dashicons-info"></span>
							<span class="wa-suit-nav__title"><?php echo esc_html__( 'Sobre a WA Web Design', 'wa-suit' ); ?></span>
						</a>
					</nav>
				</aside>

				<!-- Right Area: Tab Content -->
				<main class="wa-suit-content">
					<?php
					if ( 'dashboard' === $current_tab ) {
						$this->render_dashboard_tab( $all_modules, $categories );
					} elseif ( 'about' === $current_tab ) {
						$this->render_about_tab();
					} else {
						$current_module = $registry->get_module( $current_tab );
						if ( $current_module ) {
							$this->render_module_tab( $current_module );
						} else {
							?>
							<div class="wa-suit-card">
								<h2><?php echo esc_html__( 'Aba não encontrada', 'wa-suit' ); ?></h2>
								<p><?php echo esc_html__( 'A função solicitada não foi encontrada ou foi removida.', 'wa-suit' ); ?></p>
								<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=dashboard' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Voltar ao Painel Geral', 'wa-suit' ); ?></a></p>
							</div>
							<?php
						}
					}
					?>
				</main>
			</div>

			<!-- Toast Notification Floating Box -->
			<div id="wa-suit-toast" class="wa-suit-toast" aria-live="polite"></div>
		</div>
		<?php
	}

	/**
	 * Renders the main Dashboard tab with modules list and switches.
	 *
	 * @param array $all_modules List of all registered modules.
	 * @param array $categories List of categories.
	 * @return void
	 */
	private function render_dashboard_tab( $all_modules, $categories ) {
		?>
		<div class="wa-suit-dashboard">
			<!-- Toolbar: Search & Filters -->
			<div class="wa-suit-toolbar">
				<div class="wa-suit-toolbar__filters">
					<button type="button" class="wa-suit-filter-btn is-active" data-filter="all">
						<?php echo esc_html__( 'Todas', 'wa-suit' ); ?> (<?php echo esc_html( count( $all_modules ) ); ?>)
					</button>
					<?php foreach ( $categories as $category ) : ?>
						<button type="button" class="wa-suit-filter-btn" data-filter="<?php echo esc_attr( sanitize_title( $category ) ); ?>">
							<?php echo esc_html( $category ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="wa-suit-toolbar__search">
					<span class="dashicons dashicons-search"></span>
					<input type="text" id="wa-suit-search-input" placeholder="<?php esc_attr_e( 'Buscar funções...', 'wa-suit' ); ?>">
				</div>
			</div>

			<!-- Grid of Module Cards -->
			<div class="wa-suit-grid" id="wa-suit-modules-grid">
				<?php foreach ( $all_modules as $module ) :
					$is_active = $module->is_active();
					$mod_id    = $module->get_id();
					$category  = $module->get_category();
					$cat_slug  = sanitize_title( $category );
					?>
					<div class="wa-suit-card wa-suit-card--module <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>"
						data-module-card="<?php echo esc_attr( $mod_id ); ?>"
						data-category="<?php echo esc_attr( $cat_slug ); ?>"
						data-title="<?php echo esc_attr( strtolower( $module->get_title() ) ); ?>"
						data-description="<?php echo esc_attr( strtolower( $module->get_description() ) ); ?>">

						<div class="wa-suit-card__header">
							<div class="wa-suit-card__icon-wrapper">
								<span class="dashicons <?php echo esc_attr( $module->get_icon() ); ?>"></span>
							</div>
							<span class="wa-suit-category-badge"><?php echo esc_html( $category ); ?></span>
						</div>

						<div class="wa-suit-card__body">
							<h3 class="wa-suit-card__title"><?php echo esc_html( $module->get_title() ); ?></h3>
							<p class="wa-suit-card__description"><?php echo esc_html( $module->get_description() ); ?></p>
						</div>

						<div class="wa-suit-card__footer">
							<div class="wa-suit-card__switch-area">
								<label class="wa-suit-switch" title="<?php esc_attr_e( 'Ativar ou desativar esta função', 'wa-suit' ); ?>">
									<input type="checkbox"
										class="wa-suit-module-toggle"
										data-module-id="<?php echo esc_attr( $mod_id ); ?>"
										<?php checked( $is_active ); ?>>
									<span class="wa-suit-slider"></span>
								</label>
								<span class="wa-suit-status-pill <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>" data-status-label="<?php echo esc_attr( $mod_id ); ?>">
									<?php echo $is_active ? esc_html__( 'Ativo', 'wa-suit' ) : esc_html__( 'Inativo', 'wa-suit' ); ?>
								</span>
							</div>

							<?php if ( $module->has_settings_tab() ) : ?>
								<div class="wa-suit-card__action-area" data-action-area="<?php echo esc_attr( $mod_id ); ?>" style="<?php echo $is_active ? '' : 'display: none;'; ?>">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $mod_id ) ) ); ?>" class="wa-suit-btn-config">
										<?php echo esc_html__( 'Configurar', 'wa-suit' ); ?>
										<span class="dashicons dashicons-arrow-right-alt2"></span>
									</a>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="wa-suit-empty-search" id="wa-suit-empty-search" style="display: none;">
				<span class="dashicons dashicons-warning"></span>
				<p><?php echo esc_html__( 'Nenhuma função encontrada com os critérios pesquisados.', 'wa-suit' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a specific module's settings tab.
	 *
	 * @param WA_Suit_Module_Base $module Module instance.
	 * @return void
	 */
	private function render_module_tab( $module ) {
		$is_active = $module->is_active();
		?>
		<div class="wa-suit-module-view">
			<!-- Breadcrumb & Top Bar -->
			<div class="wa-suit-module-view__header">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=dashboard' ) ); ?>" class="wa-suit-back-link">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<?php echo esc_html__( 'Voltar ao Painel Geral', 'wa-suit' ); ?>
				</a>

				<div class="wa-suit-module-view__title-row">
					<div class="wa-suit-module-view__identity">
						<div class="wa-suit-module-view__icon">
							<span class="dashicons <?php echo esc_attr( $module->get_icon() ); ?>"></span>
						</div>
						<div>
							<h2><?php echo esc_html( $module->get_title() ); ?></h2>
							<p class="wa-suit-module-view__desc"><?php echo esc_html( $module->get_description() ); ?></p>
						</div>
					</div>

					<div class="wa-suit-module-view__toggle">
						<label class="wa-suit-switch" title="<?php esc_attr_e( 'Ativar/Desativar', 'wa-suit' ); ?>">
							<input type="checkbox"
								class="wa-suit-module-toggle"
								data-module-id="<?php echo esc_attr( $module->get_id() ); ?>"
								<?php checked( $is_active ); ?>>
							<span class="wa-suit-slider"></span>
						</label>
						<span class="wa-suit-status-pill <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>" data-status-label="<?php echo esc_attr( $module->get_id() ); ?>">
							<?php echo $is_active ? esc_html__( 'Ativo', 'wa-suit' ) : esc_html__( 'Inativo', 'wa-suit' ); ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Notice if module is currently disabled -->
			<div class="wa-suit-module-inactive-notice" data-inactive-notice="<?php echo esc_attr( $module->get_id() ); ?>" style="<?php echo $is_active ? 'display: none;' : ''; ?>">
				<span class="dashicons dashicons-info"></span>
				<p><?php echo esc_html__( 'Aviso: Esta função está atualmente DESATIVADA. Você pode ajustar as opções abaixo, mas elas só terão efeito no site quando a função for ativada no switch acima.', 'wa-suit' ); ?></p>
			</div>

			<!-- Module Settings Form -->
			<div class="wa-suit-card wa-suit-form-card">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=' . rawurlencode( $module->get_id() ) ) ); ?>">
					<input type="hidden" name="wa_suit_save_action" value="1">
					<input type="hidden" name="module_id" value="<?php echo esc_attr( $module->get_id() ); ?>">
					<input type="hidden" name="wa_suit_nonce" value="<?php echo esc_attr( WA_Suit_Security::create_nonce() ); ?>">

					<div class="wa-suit-tab-fields">
						<?php $module->render_tab(); ?>
					</div>

					<div class="wa-suit-form-actions">
						<button type="submit" class="button button-primary button-large wa-suit-submit-btn">
							<span class="dashicons dashicons-saved"></span>
							<?php echo esc_html__( 'Salvar Alterações', 'wa-suit' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the "About WA Web Design" tab with developer guides.
	 *
	 * @return void
	 */
	private function render_about_tab() {
		?>
		<div class="wa-suit-about">
			<div class="wa-suit-card wa-suit-about-hero">
				<div class="wa-suit-about-hero__badge">WA WEB DESIGN</div>
				<h2><?php echo esc_html__( 'Transformamos ideias em experiências digitais de alto impacto', 'wa-suit' ); ?></h2>
				<p>
					<?php echo esc_html__( 'Este plugin foi desenvolvido com tecnologia modular pela WA Web Design para equipar sites WordPress com ferramentas essenciais de conversão, performance, segurança e usabilidade.', 'wa-suit' ); ?>
				</p>
				<div class="wa-suit-about-hero__actions">
					<a href="https://wawebdesign.com.br" target="_blank" rel="noopener noreferrer" class="button button-primary button-large">
						<span class="dashicons dashicons-external"></span>
						<?php echo esc_html__( 'Visitar WA Web Design', 'wa-suit' ); ?>
					</a>
				</div>
			</div>

			<!-- Developer Guide: How to add a new function -->
			<div class="wa-suit-card wa-suit-dev-guide">
				<h3><span class="dashicons dashicons-code-standards"></span> <?php echo esc_html__( 'Como adicionar uma nova função no plugin?', 'wa-suit' ); ?></h3>
				<p>
					<?php echo esc_html__( 'Para criar uma nova funcionalidade, basta criar um novo arquivo PHP dentro da pasta', 'wa-suit' ); ?>
					<code>modules/</code> (por exemplo, <code>modules/minha-funcao.php</code> ou <code>modules/minha-funcao/minha-funcao.php</code>)
					<?php echo esc_html__( 'estendendo a classe base:', 'wa-suit' ); ?>
				</p>

				<pre class="wa-suit-code-block"><code>&lt;?php
class WA_Suit_Module_Minha_Funcao extends WA_Suit_Module_Base {
    public function get_id()          { return 'minha-funcao'; }
    public function get_title()       { return 'Minha Nova Função'; }
    public function get_description() { return 'Descrição clara do recurso.'; }
    public function get_icon()        { return 'dashicons-star-filled'; }
    public function get_category()    { return 'Ferramentas'; }

    // Roda quando a função for ativada pelo usuário:
    public function init() {
        add_action( 'wp_footer', array( $this, 'meu_codigo' ) );
    }

    // Formulário de configurações na aba lateral:
    public function render_tab() {
        echo '&lt;p&gt;Campos de configuração aqui...&lt;/p&gt;';
    }
}
wa_suit_register_module( new WA_Suit_Module_Minha_Funcao() );</code></pre>

				<p class="description">
					<?php echo esc_html__( 'O painel detectará a nova função automaticamente, criará o switch de ativação/desativação e exibirá sua aba lateral na navegação quando ativada!', 'wa-suit' ); ?>
				</p>
			</div>
		</div>
		<?php
	}
}
