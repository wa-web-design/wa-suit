<?php
/**
 * Cookie consent module.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a configurable cookie consent banner.
 */
class WA_Suit_Module_Cookies extends WA_Suit_Module_Base {

	/**
	 * Registers the settings save handler even while the module is disabled.
	 */
	public function __construct() {
		add_action( 'admin_post_wa_suit_save_cookies', array( $this, 'save_settings' ) );
	}

	/**
	 * Returns the module identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'cookies';
	}

	/**
	 * Returns the module title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Cookies', 'wa-suit' );
	}

	/**
	 * Returns the module description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Exiba um aviso de cookies, permita a escolha de preferências e configure o link da política de privacidade.', 'wa-suit' );
	}

	/**
	 * Returns the module icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'dashicons-shield';
	}

	/**
	 * Returns the module category.
	 *
	 * @return string
	 */
	public function get_category() {
		return __( 'Privacidade', 'wa-suit' );
	}

	/**
	 * Returns default module settings.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			'site_name'          => get_bloginfo( 'name' ),
			'message'            => __( 'Usamos cookies essenciais para o funcionamento do site. Você pode escolher se permite as categorias opcionais disponíveis, aceitar todas ou permitir apenas as essenciais.', 'wa-suit' ),
			'privacy_policy_url' => function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '',
			'use_analytics'      => true,
			'use_adsense'        => false,
			'position'           => 'bottom-left',
			'consent_days'       => 180,
			'background_color'   => '#ffffff',
			'text_color'         => '#222222',
			'button_color'       => '#171717',
			'accept_button_color'    => '#171717',
			'reject_button_color'    => '#f3f3f3',
			'customize_button_color' => '#ffffff',
			'save_button_color'      => '#171717',
		);
	}

	/**
	 * Returns validated settings merged with their defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = parent::get_settings();
		$defaults = $this->get_default_settings();

		$settings['site_name'] = isset( $settings['site_name'] ) && is_string( $settings['site_name'] )
			? sanitize_text_field( $settings['site_name'] )
			: $defaults['site_name'];
		$settings['message'] = isset( $settings['message'] ) && is_string( $settings['message'] )
			? sanitize_textarea_field( $settings['message'] )
			: $defaults['message'];
		$settings['privacy_policy_url'] = isset( $settings['privacy_policy_url'] ) && is_string( $settings['privacy_policy_url'] )
			? esc_url_raw( $settings['privacy_policy_url'] )
			: '';
		$settings['use_analytics'] = isset( $settings['use_analytics'] ) && in_array( $settings['use_analytics'], array( true, 1, '1' ), true );
		$settings['use_adsense']   = isset( $settings['use_adsense'] ) && in_array( $settings['use_adsense'], array( true, 1, '1' ), true );
		$settings['position']      = isset( $settings['position'] ) && is_string( $settings['position'] ) && in_array( $settings['position'], array( 'bottom-left', 'bottom-right' ), true )
			? $settings['position']
			: $defaults['position'];
		$days = isset( $settings['consent_days'] ) && is_scalar( $settings['consent_days'] )
			? absint( $settings['consent_days'] )
			: $defaults['consent_days'];
		$settings['consent_days'] = min( 365, max( 1, $days ) );

		foreach ( array( 'background_color', 'text_color', 'button_color', 'accept_button_color', 'reject_button_color', 'customize_button_color', 'save_button_color' ) as $color_key ) {
			$color = isset( $settings[ $color_key ] ) && is_string( $settings[ $color_key ] )
				? sanitize_hex_color( $settings[ $color_key ] )
				: false;
			$settings[ $color_key ] = $color ? $color : $defaults[ $color_key ];
		}

		$saved_settings = get_option( $this->get_option_key(), array() );
		$legacy_color   = is_array( $saved_settings ) && isset( $saved_settings['button_color'] ) && is_string( $saved_settings['button_color'] )
			? sanitize_hex_color( $saved_settings['button_color'] )
			: false;
		if ( $legacy_color ) {
			foreach ( array( 'accept_button_color', 'reject_button_color', 'customize_button_color', 'save_button_color' ) as $color_key ) {
				if ( ! array_key_exists( $color_key, $saved_settings ) ) {
					$settings[ $color_key ] = $legacy_color;
				}
			}
		}

		if ( '' === $settings['site_name'] ) {
			$settings['site_name'] = $defaults['site_name'];
		}
		if ( '' === $settings['message'] ) {
			$settings['message'] = $defaults['message'];
		}

		return $settings;
	}

	/**
	 * Registers frontend assets and the settings save handler.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_banner' ) );
	}

	/**
	 * Enqueues the consent banner assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'wa-suit-cookies',
			WA_SUIT_URL . 'assets/css/cookies.css',
			array(),
			filemtime( WA_SUIT_PATH . 'assets/css/cookies.css' )
		);

		wp_enqueue_script(
			'wa-suit-cookies',
			WA_SUIT_URL . 'assets/js/cookies.js',
			array(),
			filemtime( WA_SUIT_PATH . 'assets/js/cookies.js' ),
			true
		);
	}

	/**
	 * Validates and saves submitted module settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! WA_Suit_Security::can_manage_settings() ) {
			wp_die( esc_html__( 'Você não tem permissão para alterar estas configurações.', 'wa-suit' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'wa_suit_save_cookies' );

		$input    = isset( $_POST['cookies'] ) && is_array( $_POST['cookies'] )
			? wp_unslash( $_POST['cookies'] )
			: array();
		$defaults = $this->get_default_settings();
		$position = isset( $input['position'] ) && is_string( $input['position'] )
			? sanitize_key( $input['position'] )
			: '';
		$days     = isset( $input['consent_days'] ) && is_scalar( $input['consent_days'] )
			? absint( $input['consent_days'] )
			: $defaults['consent_days'];

		$settings = array(
			'site_name'          => isset( $input['site_name'] ) && is_string( $input['site_name'] )
				? sanitize_text_field( $input['site_name'] )
				: $defaults['site_name'],
			'message'            => isset( $input['message'] ) && is_string( $input['message'] )
				? sanitize_textarea_field( $input['message'] )
				: $defaults['message'],
			'privacy_policy_url' => isset( $input['privacy_policy_url'] ) && is_string( $input['privacy_policy_url'] )
				? esc_url_raw( $input['privacy_policy_url'] )
				: '',
			'use_analytics'      => isset( $input['use_analytics'] ) && is_scalar( $input['use_analytics'] ) && '1' === (string) $input['use_analytics'],
			'use_adsense'        => isset( $input['use_adsense'] ) && is_scalar( $input['use_adsense'] ) && '1' === (string) $input['use_adsense'],
			'position'           => in_array( $position, array( 'bottom-left', 'bottom-right' ), true )
				? $position
				: $defaults['position'],
			'consent_days'       => min( 365, max( 1, $days ) ),
			'background_color'   => isset( $input['background_color'] ) && is_string( $input['background_color'] )
				? sanitize_hex_color( $input['background_color'] )
				: $defaults['background_color'],
			'text_color'         => isset( $input['text_color'] ) && is_string( $input['text_color'] )
				? sanitize_hex_color( $input['text_color'] )
				: $defaults['text_color'],
			'button_color'       => isset( $input['button_color'] ) && is_string( $input['button_color'] )
				? sanitize_hex_color( $input['button_color'] )
				: $defaults['button_color'],
			'accept_button_color'    => isset( $input['accept_button_color'] ) && is_string( $input['accept_button_color'] )
				? sanitize_hex_color( $input['accept_button_color'] )
				: $defaults['accept_button_color'],
			'reject_button_color'    => isset( $input['reject_button_color'] ) && is_string( $input['reject_button_color'] )
				? sanitize_hex_color( $input['reject_button_color'] )
				: $defaults['reject_button_color'],
			'customize_button_color' => isset( $input['customize_button_color'] ) && is_string( $input['customize_button_color'] )
				? sanitize_hex_color( $input['customize_button_color'] )
				: $defaults['customize_button_color'],
			'save_button_color'      => isset( $input['save_button_color'] ) && is_string( $input['save_button_color'] )
				? sanitize_hex_color( $input['save_button_color'] )
				: $defaults['save_button_color'],
		);

		if ( '' === $settings['site_name'] ) {
			$settings['site_name'] = $defaults['site_name'];
		}
		if ( '' === $settings['message'] ) {
			$settings['message'] = $defaults['message'];
		}
		foreach ( array( 'background_color', 'text_color', 'button_color', 'accept_button_color', 'reject_button_color', 'customize_button_color', 'save_button_color' ) as $color_key ) {
			if ( ! $settings[ $color_key ] ) {
				$settings[ $color_key ] = $defaults[ $color_key ];
			}
		}

		$this->update_settings( $settings );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'wa-suit',
					'tab'            => $this->get_id(),
					'cookies-saved'  => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders the module settings form.
	 *
	 * @return void
	 */
	public function render_tab() {
		$settings = $this->get_settings();
		?>
		<div class="wa-general-settings">
			<header class="wa-general-settings__header">
				<div>
					<h2><?php echo esc_html__( 'Aviso de cookies', 'wa-suit' ); ?></h2>
					<p><?php echo esc_html__( 'Configure como o aviso aparece e quais preferências os visitantes podem escolher.', 'wa-suit' ); ?></p>
				</div>
			</header>

			<?php if ( isset( $_GET['cookies-saved'] ) && is_string( $_GET['cookies-saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cookies-saved'] ) ) ) : ?>
				<div class="notice notice-success inline">
					<p><?php echo esc_html__( 'Configurações de cookies salvas.', 'wa-suit' ); ?></p>
				</div>
			<?php endif; ?>

			<form class="wa-general-settings__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wa_suit_save_cookies">
				<?php wp_nonce_field( 'wa_suit_save_cookies' ); ?>

				<section class="wa-general-settings__card">
					<div class="wa-general-settings__card-heading">
						<span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span>
						<div>
							<h3><?php echo esc_html__( 'Conteúdo e preferências', 'wa-suit' ); ?></h3>
							<p><?php echo esc_html__( 'Informe os dados do site e as categorias opcionais que ele utiliza.', 'wa-suit' ); ?></p>
						</div>
					</div>

					<p>
						<label for="wa-cookies-site-name"><strong><?php echo esc_html__( 'Nome do site', 'wa-suit' ); ?></strong></label><br>
						<input class="regular-text" id="wa-cookies-site-name" name="cookies[site_name]" type="text" value="<?php echo esc_attr( $settings['site_name'] ); ?>" required>
					</p>

					<p>
						<label for="wa-cookies-message"><strong><?php echo esc_html__( 'Mensagem do aviso', 'wa-suit' ); ?></strong></label><br>
						<textarea class="large-text" id="wa-cookies-message" name="cookies[message]" rows="4" required><?php echo esc_textarea( $settings['message'] ); ?></textarea>
					</p>

					<p>
						<label for="wa-cookies-policy-url"><strong><?php echo esc_html__( 'Link da política de privacidade', 'wa-suit' ); ?></strong></label><br>
						<input class="regular-text" id="wa-cookies-policy-url" name="cookies[privacy_policy_url]" type="url" value="<?php echo esc_attr( $settings['privacy_policy_url'] ); ?>" placeholder="https://">
						<span class="description"><?php echo esc_html__( 'Se estiver vazio, o link não será exibido. Você pode criar ou escolher uma página em Configurações > Privacidade do WordPress.', 'wa-suit' ); ?></span>
					</p>

					<fieldset class="wa-general-settings__types">
						<legend><?php echo esc_html__( 'Categorias de cookies utilizadas', 'wa-suit' ); ?></legend>
						<div class="wa-general-settings__type-list">
							<label>
								<input type="checkbox" checked disabled>
								<span><?php echo esc_html__( 'Essenciais (sempre ativos)', 'wa-suit' ); ?></span>
							</label>
							<label>
								<input type="checkbox" name="cookies[use_analytics]" value="1" <?php checked( $settings['use_analytics'] ); ?>>
								<span><?php echo esc_html__( 'Análise e desempenho', 'wa-suit' ); ?></span>
							</label>
							<label>
								<input type="checkbox" name="cookies[use_adsense]" value="1" <?php checked( $settings['use_adsense'] ); ?>>
								<span><?php echo esc_html__( 'Publicidade (incluindo AdSense)', 'wa-suit' ); ?></span>
							</label>
						</div>
						<p class="description"><?php echo esc_html__( 'As categorias escolhidas aparecem como opções no aviso. A ferramenta registra a escolha do visitante, mas não bloqueia automaticamente scripts ou cookies de serviços externos; configure cada integração separadamente.', 'wa-suit' ); ?></p>
					</fieldset>
				</section>

				<section class="wa-general-settings__card">
					<div class="wa-general-settings__card-heading">
						<span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
						<div>
							<h3><?php echo esc_html__( 'Aparência e duração', 'wa-suit' ); ?></h3>
							<p><?php echo esc_html__( 'Ajuste a posição, as cores e por quanto tempo a escolha será lembrada neste navegador.', 'wa-suit' ); ?></p>
						</div>
					</div>

					<p>
						<label for="wa-cookies-position"><strong><?php echo esc_html__( 'Posição do aviso', 'wa-suit' ); ?></strong></label><br>
						<select id="wa-cookies-position" name="cookies[position]">
							<option value="bottom-left" <?php selected( $settings['position'], 'bottom-left' ); ?>><?php echo esc_html__( 'Inferior esquerdo', 'wa-suit' ); ?></option>
							<option value="bottom-right" <?php selected( $settings['position'], 'bottom-right' ); ?>><?php echo esc_html__( 'Inferior direito', 'wa-suit' ); ?></option>
						</select>
					</p>

					<p>
						<label for="wa-cookies-days"><strong><?php echo esc_html__( 'Duração da escolha (dias)', 'wa-suit' ); ?></strong></label><br>
						<input id="wa-cookies-days" name="cookies[consent_days]" type="number" min="1" max="365" value="<?php echo esc_attr( $settings['consent_days'] ); ?>" required>
					</p>

					<div class="wa-general-settings__type-list wa-cookie-settings__colors">
						<p>
							<label for="wa-cookies-background"><strong><?php echo esc_html__( 'Fundo do aviso', 'wa-suit' ); ?></strong></label><br>
							<input id="wa-cookies-background" name="cookies[background_color]" type="color" value="<?php echo esc_attr( $settings['background_color'] ); ?>">
						</p>
						<p>
							<label for="wa-cookies-text"><strong><?php echo esc_html__( 'Texto do aviso', 'wa-suit' ); ?></strong></label><br>
							<input id="wa-cookies-text" name="cookies[text_color]" type="color" value="<?php echo esc_attr( $settings['text_color'] ); ?>">
						</p>
					</div>

					<fieldset class="wa-cookie-settings__button-colors">
						<legend><?php echo esc_html__( 'Personalizar cada botão', 'wa-suit' ); ?></legend>
						<div class="wa-general-settings__type-list wa-cookie-settings__colors">
							<p>
								<label for="wa-cookies-accept-color"><strong><?php echo esc_html__( 'Aceitar todos', 'wa-suit' ); ?></strong></label><br>
								<input id="wa-cookies-accept-color" name="cookies[accept_button_color]" type="color" value="<?php echo esc_attr( $settings['accept_button_color'] ); ?>">
							</p>
							<p>
								<label for="wa-cookies-reject-color"><strong><?php echo esc_html__( 'Somente necessários', 'wa-suit' ); ?></strong></label><br>
								<input id="wa-cookies-reject-color" name="cookies[reject_button_color]" type="color" value="<?php echo esc_attr( $settings['reject_button_color'] ); ?>">
							</p>
							<p>
								<label for="wa-cookies-customize-color"><strong><?php echo esc_html__( 'Personalizar', 'wa-suit' ); ?></strong></label><br>
								<input id="wa-cookies-customize-color" name="cookies[customize_button_color]" type="color" value="<?php echo esc_attr( $settings['customize_button_color'] ); ?>">
							</p>
							<p>
								<label for="wa-cookies-save-color"><strong><?php echo esc_html__( 'Salvar preferências', 'wa-suit' ); ?></strong></label><br>
								<input id="wa-cookies-save-color" name="cookies[save_button_color]" type="color" value="<?php echo esc_attr( $settings['save_button_color'] ); ?>">
							</p>
						</div>
					</fieldset>
				</section>

				<div class="wa-general-settings__actions">
					<button class="wa-admin-btn wa-admin-btn--primary" type="submit"><?php echo esc_html__( 'Salvar configurações', 'wa-suit' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Outputs the cookie consent banner and preference controls.
	 *
	 * @return void
	 */
	public function render_banner() {
		if ( is_admin() ) {
			return;
		}

		$settings  = $this->get_settings();
		$position  = in_array( $settings['position'], array( 'bottom-left', 'bottom-right' ), true )
			? $settings['position']
			: 'bottom-left';
		$style     = sprintf(
			'--wa-cookie-background:%1$s;--wa-cookie-text:%2$s;--wa-cookie-button:%3$s;--wa-cookie-accept:%4$s;--wa-cookie-reject:%5$s;--wa-cookie-customize:%6$s;--wa-cookie-save:%7$s;',
			$settings['background_color'],
			$settings['text_color'],
			$settings['button_color'],
			$settings['accept_button_color'],
			$settings['reject_button_color'],
			$settings['customize_button_color'],
			$settings['save_button_color']
		);
		?>
		<div class="wa-cookie-consent wa-cookie-consent--<?php echo esc_attr( $position ); ?>" data-wa-cookie-consent data-consent-days="<?php echo esc_attr( $settings['consent_days'] ); ?>" style="<?php echo esc_attr( $style ); ?>" hidden>
			<section class="wa-cookie-consent__panel" aria-labelledby="wa-cookie-consent-title">
				<header class="wa-cookie-consent__header">
					<div class="wa-cookie-consent__identity">
						<span class="wa-cookie-consent__cookie-icon" aria-hidden="true">
							<svg viewBox="0 0 48 48" focusable="false">
								<circle class="wa-cookie-consent__cookie-body" cx="24" cy="24" r="19"/>
								<circle class="wa-cookie-consent__cookie-bite" cx="39" cy="10" r="9"/>
								<circle class="wa-cookie-consent__cookie-chip" cx="18" cy="18" r="2.2"/>
								<circle class="wa-cookie-consent__cookie-chip" cx="14" cy="29" r="2.5"/>
								<circle class="wa-cookie-consent__cookie-chip" cx="27" cy="35" r="2"/>
								<circle class="wa-cookie-consent__cookie-chip" cx="31" cy="24" r="1.8"/>
							</svg>
						</span>
						<div>
							<span class="wa-cookie-consent__eyebrow"><?php echo esc_html__( 'SUA PRIVACIDADE', 'wa-suit' ); ?></span>
							<h2 id="wa-cookie-consent-title"><?php echo esc_html( sprintf( __( 'Cookies em %s', 'wa-suit' ), $settings['site_name'] ) ); ?></h2>
						</div>
					</div>
					<div class="wa-cookie-consent__brand">
						<img src="<?php echo esc_url( WA_SUIT_URL . 'assets/images/logo-wa-preto.svg' ); ?>" alt="" width="34" height="34">
						<span><?php echo esc_html__( 'WA WEB DESIGN', 'wa-suit' ); ?></span>
					</div>
				</header>
				<p><?php echo esc_html( $settings['message'] ); ?></p>
				<?php if ( ! empty( $settings['privacy_policy_url'] ) ) : ?>
					<p class="wa-cookie-consent__policy"><a href="<?php echo esc_url( $settings['privacy_policy_url'] ); ?>"><?php echo esc_html__( 'Leia nossa política de privacidade', 'wa-suit' ); ?></a></p>
				<?php endif; ?>

				<div id="wa-cookie-consent-preferences" class="wa-cookie-consent__preferences" data-wa-cookie-preferences hidden>
					<label>
						<input type="checkbox" checked disabled>
						<span><?php echo esc_html__( 'Cookies essenciais (sempre ativos)', 'wa-suit' ); ?></span>
					</label>
					<?php if ( $settings['use_analytics'] ) : ?>
						<label>
							<input type="checkbox" data-wa-cookie-category="analytics">
							<span><?php echo esc_html__( 'Análise e desempenho', 'wa-suit' ); ?></span>
						</label>
					<?php endif; ?>
					<?php if ( $settings['use_adsense'] ) : ?>
						<label>
							<input type="checkbox" data-wa-cookie-category="advertising">
							<span><?php echo esc_html__( 'Publicidade (incluindo AdSense)', 'wa-suit' ); ?></span>
						</label>
					<?php endif; ?>
					<button class="wa-cookie-consent__button wa-cookie-consent__button--save" type="button" data-wa-cookie-save><?php echo esc_html__( 'Salvar preferências', 'wa-suit' ); ?></button>
				</div>

				<div class="wa-cookie-consent__actions">
					<button class="wa-cookie-consent__button wa-cookie-consent__button--accept" type="button" data-wa-cookie-accept><?php echo esc_html__( 'Aceitar todos', 'wa-suit' ); ?></button>
					<button class="wa-cookie-consent__button wa-cookie-consent__button--reject" type="button" data-wa-cookie-reject><?php echo esc_html__( 'Somente as essenciais', 'wa-suit' ); ?></button>
					<button class="wa-cookie-consent__button wa-cookie-consent__button--customize" type="button" data-wa-cookie-customize aria-controls="wa-cookie-consent-preferences" aria-expanded="false"><?php echo esc_html__( 'Personalizar', 'wa-suit' ); ?></button>
				</div>
			</section>
		</div>
		<button class="wa-cookie-consent__reopen wa-cookie-consent--<?php echo esc_attr( $position ); ?>" type="button" data-wa-cookie-reopen hidden>
			<span class="wa-cookie-consent__reopen-icon" aria-hidden="true">
				<svg viewBox="0 0 48 48" focusable="false"><circle class="wa-cookie-consent__cookie-body" cx="24" cy="24" r="19"/><circle class="wa-cookie-consent__cookie-bite" cx="39" cy="10" r="9"/><circle class="wa-cookie-consent__cookie-chip" cx="18" cy="18" r="2.2"/><circle class="wa-cookie-consent__cookie-chip" cx="14" cy="29" r="2.5"/><circle class="wa-cookie-consent__cookie-chip" cx="27" cy="35" r="2"/><circle class="wa-cookie-consent__cookie-chip" cx="31" cy="24" r="1.8"/></svg>
			</span>
			<?php echo esc_html__( 'Preferências de cookies', 'wa-suit' ); ?>
		</button>
		<?php
	}
}

wa_suit_register_module( new WA_Suit_Module_Cookies() );
