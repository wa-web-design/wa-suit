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
		add_action( 'admin_post_wa_suit_save_general_settings', array( $this, 'save_general_settings' ) );
		add_action( 'admin_action_wa_suit_duplicate_post', array( $this, 'duplicate_post' ) );
		add_action( 'admin_head', array( $this, 'hide_rank_math_notice' ) );
		add_filter( 'post_row_actions', array( $this, 'add_duplicate_post_action' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'add_duplicate_post_action' ), 10, 2 );
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

		$was_active = $module->is_active();
		if ( $state ) {
			$this->settings->enable_module( $module_id );
		} else {
			$this->settings->disable_module( $module_id );
		}

		if ( 'hide-login' === $module_id && $was_active !== $state && is_callable( array( $module, 'refresh_rewrite_rules' ) ) ) {
			$module->refresh_rewrite_rules( $state );
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
	 * Saves the general WA Suit options.
	 *
	 * @return void
	 */
	public function save_general_settings() {
		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_die( esc_html__( 'Você não tem permissão para alterar estas configurações.', 'wa-suit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'wa_suit_save_general_settings' );
		$input = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? wp_unslash( $_POST['settings'] )
			: array();
		$this->settings->save_general_settings( $input );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'wa-suit',
					'tab'           => 'settings',
					'settings-saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Hides only the Rank Math license connection notice when requested.
	 *
	 * @return void
	 */
	public function hide_rank_math_notice() {
		$settings = $this->settings->get_general_settings();
		if ( empty( $settings['hide_rank_math_license_notice'] ) ) {
			return;
		}

		echo '<style>.notice.rank-math-admin-license-notice{display:none!important}</style>';
	}

	/**
	 * Adds a duplicate link to supported content rows.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Current post.
	 * @return array
	 */
	public function add_duplicate_post_action( $actions, $post ) {
		$settings = $this->settings->get_general_settings();
		if ( empty( $settings['duplicate_enabled'] ) || ! in_array( $post->post_type, $settings['duplicate_post_types'], true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = add_query_arg(
			array(
				'action' => 'wa_suit_duplicate_post',
				'post'   => $post->ID,
			),
			admin_url( 'admin.php' )
		);
		$url = wp_nonce_url( $url, 'wa_suit_duplicate_post_' . $post->ID );
		$actions['wa_suit_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicar', 'wa-suit' ) . '</a>';

		return $actions;
	}

	/**
	 * Creates a draft copy of a post, page, or configured public post type.
	 *
	 * @return void
	 */
	public function duplicate_post() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'wa_suit_duplicate_post_' . $post_id );

		$source   = get_post( $post_id );
		$settings = $this->settings->get_general_settings();
		if ( ! $source || empty( $settings['duplicate_enabled'] ) || ! in_array( $source->post_type, $settings['duplicate_post_types'], true ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Este conteúdo não pode ser duplicado com suas permissões atuais.', 'wa-suit' ), '', array( 'response' => 403 ) );
		}

		$duplicate_id = wp_insert_post(
			array(
				'post_type'      => $source->post_type,
				'post_status'    => 'draft',
				'post_title'     => sprintf( __( '%s (cópia)', 'wa-suit' ), $source->post_title ),
				'post_content'   => $source->post_content,
				'post_excerpt'   => $source->post_excerpt,
				'post_author'    => get_current_user_id(),
				'post_parent'    => $source->post_parent,
				'menu_order'     => $source->menu_order,
				'comment_status' => $source->comment_status,
				'ping_status'    => $source->ping_status,
			),
			true
		);

		if ( is_wp_error( $duplicate_id ) ) {
			wp_die( esc_html( $duplicate_id->get_error_message() ), '', array( 'response' => 500 ) );
		}

		foreach ( get_object_taxonomies( $source->post_type ) as $taxonomy ) {
			$term_ids = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $term_ids ) ) {
				wp_delete_post( $duplicate_id, true );
				wp_die( esc_html( $term_ids->get_error_message() ), '', array( 'response' => 500 ) );
			}

			$assigned = wp_set_object_terms( $duplicate_id, $term_ids, $taxonomy );
			if ( is_wp_error( $assigned ) ) {
				wp_delete_post( $duplicate_id, true );
				wp_die( esc_html( $assigned->get_error_message() ), '', array( 'response' => 500 ) );
			}
		}

		$meta = get_post_meta( $post_id );
		foreach ( $meta as $meta_key => $values ) {
			if ( in_array( $meta_key, array( '_edit_lock', '_edit_last' ), true ) ) {
				continue;
			}

			foreach ( $values as $value ) {
				if ( ! add_post_meta( $duplicate_id, $meta_key, maybe_unserialize( $value ) ) ) {
					wp_delete_post( $duplicate_id, true );
					wp_die( esc_html__( 'Não foi possível copiar os metadados do conteúdo.', 'wa-suit' ), '', array( 'response' => 500 ) );
				}
			}
		}

		wp_safe_redirect( admin_url( 'post.php?post=' . absint( $duplicate_id ) . '&action=edit' ) );
		exit;
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

		<!-- Rank Math Inspired Header Bar -->
			<div class="wa-rm-header">
				<div class="wa-rm-header__left">
					<div class="wa-rm-brand-info">
						<h1 class="wa-rm-brand-title">
							<?php echo esc_html__( 'WA Web Design', 'wa-suit' ); ?>
						</h1>
						<span class="wa-rm-breadcrumb"><?php echo esc_html__( 'Dashboard / Módulos', 'wa-suit' ); ?></span>
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
					<li class="wa-rm-nav-tab <?php echo 'settings' === $current_tab ? 'is-active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-suit&tab=settings' ) ); ?>">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php echo esc_html__( 'Configurações gerais', 'wa-suit' ); ?>
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
				} elseif ( 'settings' === $current_tab ) {
					$this->render_general_settings_tab();
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
	 * Renders plugin-wide settings.
	 *
	 * @return void
	 */
	private function render_general_settings_tab() {
		$settings = $this->settings->get_general_settings();
		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);
		?>
		<div class="wa-general-settings">
			<header class="wa-general-settings__header">
				<div>
					<p class="wa-general-settings__eyebrow"><?php echo esc_html__( 'PREFERÊNCIAS DO PLUGIN', 'wa-suit' ); ?></p>
					<h2><?php echo esc_html__( 'Configurações gerais', 'wa-suit' ); ?></h2>
					<p><?php echo esc_html__( 'Gerencie ferramentas e avisos do WA Suit em um só lugar.', 'wa-suit' ); ?></p>
				</div>
			</header>

			<?php if ( isset( $_GET['settings-saved'] ) && is_string( $_GET['settings-saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['settings-saved'] ) ) ) : ?>
				<div class="notice notice-success inline"><p><?php echo esc_html__( 'Configurações atualizadas.', 'wa-suit' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wa-general-settings__form">
				<input type="hidden" name="action" value="wa_suit_save_general_settings">
				<?php wp_nonce_field( 'wa_suit_save_general_settings' ); ?>

				<section class="wa-general-settings__card">
					<div class="wa-general-settings__card-heading">
						<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
						<div>
							<h3><?php echo esc_html__( 'Duplicar conteúdo', 'wa-suit' ); ?></h3>
							<p><?php echo esc_html__( 'Crie uma cópia em rascunho diretamente pela lista de conteúdos.', 'wa-suit' ); ?></p>
						</div>
					</div>

					<label class="wa-general-settings__toggle">
						<input type="checkbox" name="settings[duplicate_enabled]" value="1" <?php checked( $settings['duplicate_enabled'] ); ?>>
						<span>
							<strong><?php echo esc_html__( 'Ativar duplicação', 'wa-suit' ); ?></strong>
							<small><?php echo esc_html__( 'Ativado por padrão. As cópias são criadas como rascunho.', 'wa-suit' ); ?></small>
						</span>
					</label>

					<fieldset class="wa-general-settings__types">
						<legend><?php echo esc_html__( 'Tipos de conteúdo disponíveis', 'wa-suit' ); ?></legend>
						<div class="wa-general-settings__type-list">
							<?php foreach ( $post_types as $post_type => $post_type_object ) :
								if ( in_array( $post_type, array( 'attachment', 'revision', 'product' ), true ) || empty( $post_type_object->public ) || empty( $post_type_object->show_ui ) ) {
									continue;
								}
								?>
								<label>
									<input type="checkbox" name="settings[duplicate_post_types][]" value="<?php echo esc_attr( $post_type ); ?>" <?php checked( in_array( $post_type, $settings['duplicate_post_types'], true ) ); ?>>
									<span><?php echo esc_html( $post_type_object->labels->name ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<p class="description"><?php echo esc_html__( 'Produtos WooCommerce usam a opção nativa “Duplicar” da própria lista de produtos.', 'wa-suit' ); ?></p>
					</fieldset>
				</section>

				<section class="wa-general-settings__card">
					<div class="wa-general-settings__card-heading">
						<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
						<div>
							<h3><?php echo esc_html__( 'Avisos administrativos', 'wa-suit' ); ?></h3>
							<p><?php echo esc_html__( 'Escolha quais avisos externos aparecem no painel.', 'wa-suit' ); ?></p>
						</div>
					</div>
					<label class="wa-general-settings__toggle">
						<input type="checkbox" name="settings[hide_rank_math_license_notice]" value="1" <?php checked( $settings['hide_rank_math_license_notice'] ); ?>>
						<span>
							<strong><?php echo esc_html__( 'Ocultar aviso de conexão do Rank Math PRO', 'wa-suit' ); ?></strong>
							<small><?php echo esc_html__( 'Oculta somente o aviso de licença mostrado no painel. Não altera o Rank Math nem sua licença.', 'wa-suit' ); ?></small>
						</span>
					</label>
				</section>

				<div class="wa-general-settings__actions">
					<button type="submit" class="wa-rm-btn wa-rm-btn--primary"><?php echo esc_html__( 'Salvar configurações', 'wa-suit' ); ?></button>
				</div>
			</form>
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
