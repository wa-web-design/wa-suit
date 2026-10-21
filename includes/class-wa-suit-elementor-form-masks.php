<?php
/**
 * Input masks for Elementor Pro form fields.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds configurable masks to Elementor Pro form fields.
 */
class WA_Suit_Elementor_Form_Masks {

	/**
	 * Settings option name.
	 */
	const OPTION_NAME = 'wa_suit_elementor_form_mask_settings';

	/**
	 * Registers Elementor controls and frontend rendering hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'elementor/element/form/section_form_fields/before_section_end', array( $this, 'register_field_controls' ) );
		add_filter( 'elementor/widget/render_content', array( $this, 'add_mask_attributes' ), PHP_INT_MAX, 2 );

		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Returns saved settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_NAME, array() );
		$saved = is_array( $saved ) ? $saved : array();

		$settings = wp_parse_args(
			$saved,
			array(
				'phone_country_selector' => false,
				'phone_country_mode'     => 'exclude',
				'phone_countries'        => array(),
				'phone_default_country'  => 'br',
			)
		);
		$settings['phone_country_mode']    = in_array( $settings['phone_country_mode'], array( 'only', 'exclude' ), true ) ? $settings['phone_country_mode'] : 'exclude';
		$settings['phone_countries']       = self::sanitize_country_codes( $settings['phone_countries'] );
		$settings['phone_countries']       = self::normalize_country_filter( $settings['phone_country_mode'], $settings['phone_countries'] );
		$settings['phone_default_country'] = self::sanitize_default_country( $settings['phone_default_country'], $settings['phone_country_mode'], $settings['phone_countries'] );
		$widgets = isset( $saved['widgets'] ) && is_array( $saved['widgets'] )
			? $saved['widgets']
			: array();

		$settings['widgets'] = wp_parse_args(
			$widgets,
			array(
				'popup' => true,
			)
		);

		return $settings;
	}

	/**
	 * Saves sanitized settings.
	 *
	 * @param mixed $settings Submitted settings.
	 * @return bool
	 */
	public static function save_settings( $settings ) {
		$settings = is_array( $settings ) ? $settings : array();
		$widgets  = isset( $settings['widgets'] ) && is_array( $settings['widgets'] )
			? $settings['widgets']
			: null;
		$country_mode = isset( $settings['phone_country_mode'] ) && 'only' === $settings['phone_country_mode'] ? 'only' : 'exclude';
		$countries    = self::sanitize_country_codes( isset( $settings['phone_countries'] ) ? $settings['phone_countries'] : array() );
		$countries    = self::normalize_country_filter( $country_mode, $countries );

		return update_option(
			self::OPTION_NAME,
			array(
				'phone_country_selector' => ! empty( $settings['phone_country_selector'] ),
				'phone_country_mode'     => $country_mode,
				'phone_countries'        => $countries,
				'phone_default_country'  => self::sanitize_default_country(
					isset( $settings['phone_default_country'] ) ? $settings['phone_default_country'] : 'br',
					$country_mode,
					$countries
				),
				'widgets'                => array(
					'popup' => null === $widgets || ! empty( $widgets['popup'] ),
				),
			)
		);
	}

	/**
	 * Returns the countries supported by the international phone input.
	 *
	 * @return array
	 */
	public static function get_phone_countries() {
		$country_codes = 'af ax al dz as ad ao ai ag ar am aw ac au at az bs bh bd bb by be bz bj bm bt bo ba bw br io vg bn bg bf bi kh cm ca cv bq ky cf td cl cn cx cc co km cg cd ck cr ci hr cu cw cy cz dk dj dm do ec eg sv gq er ee sz et fk fo fj fi fr gf pf ga gm ge de gh gi gr gl gd gp gu gt gg gn gw gy ht hn hk hu is in id il ir ie im it jm jp je jo kz ke ki xk kw kg la lv lb ls lr ly li lt lu mo mg mw my mv ml mt mh mq mr mu yt mx fm md mc mn me ms ma mz mm na nr np nl nc nz ni ne ng nu nf kp mk mp no om pk pw ps pa pg py pe ph pl pt pr qa re ro ru rw ws sm st sa sn rs sc sl sg sx sk si sb so za kr ss es lk bl sh kn lc mf pm vc sd sr sj se ch sy tw tj tz th tl tg tk to tt tn tr tm tc tv vi ug ua ae gb us uy uz vu va ve vn wf eh ye zm zw';
		$country_codes .= ' iq';
		$countries     = array();

		foreach ( explode( ' ', $country_codes ) as $country_code ) {
			$countries[ $country_code ] = $country_code;
		}
		ksort( $countries );

		return $countries;
	}

	/**
	 * Sanitizes a list of supported country codes.
	 *
	 * @param mixed $country_codes Submitted country codes.
	 * @return array
	 */
	private static function sanitize_country_codes( $country_codes ) {
		if ( ! is_array( $country_codes ) ) {
			return array();
		}

		$valid_codes = self::get_phone_countries();
		$sanitized   = array();

		foreach ( $country_codes as $country_code ) {
			if ( ! is_string( $country_code ) ) {
				continue;
			}

			$country_code = strtolower( sanitize_key( $country_code ) );
			if ( isset( $valid_codes[ $country_code ] ) ) {
				$sanitized[] = $country_code;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Prevents a country filter from excluding every available country.
	 *
	 * @param string $country_mode Country filter mode.
	 * @param array  $countries Country codes.
	 * @return array
	 */
	private static function normalize_country_filter( $country_mode, $countries ) {
		if (
			'exclude' === $country_mode
			&& count( $countries ) >= count( self::get_phone_countries() )
		) {
			return array_values( array_diff( $countries, array( 'br' ) ) );
		}

		return $countries;
	}

	/**
	 * Sanitizes the default country against the configured country filter.
	 *
	 * @param mixed  $country_code Default country code.
	 * @param string $country_mode Country filter mode.
	 * @param array  $countries Configured country codes.
	 * @return string
	 */
	private static function sanitize_default_country( $country_code, $country_mode, $countries ) {
		$valid_codes  = self::get_phone_countries();
		$country_code = is_string( $country_code ) ? strtolower( sanitize_key( $country_code ) ) : '';

		if ( ! isset( $valid_codes[ $country_code ] ) ) {
			$country_code = 'br';
		}

		$is_unavailable = ( 'only' === $country_mode && ! empty( $countries ) && ! in_array( $country_code, $countries, true ) )
			|| ( 'exclude' === $country_mode && in_array( $country_code, $countries, true ) );

		if ( $is_unavailable ) {
			foreach ( array_keys( $valid_codes ) as $candidate ) {
				if (
					( 'only' === $country_mode && in_array( $candidate, $countries, true ) )
					|| ( 'exclude' === $country_mode && ! in_array( $candidate, $countries, true ) )
				) {
					return $candidate;
				}
			}
		}

		return $country_code;
	}

	/**
	 * Checks whether a WA Suit Elementor widget is enabled.
	 *
	 * @param string $widget_id Widget ID.
	 * @return bool
	 */
	public static function is_widget_enabled( $widget_id ) {
		$settings = self::get_settings();
		return ! isset( $settings['widgets'][ $widget_id ] ) || ! empty( $settings['widgets'][ $widget_id ] );
	}

	/**
	 * Adds mask controls to the Elementor Pro form-fields repeater.
	 *
	 * @param Elementor\Element_Base $element Form widget.
	 * @return void
	 */
	public function register_field_controls( $element ) {
		if ( ! method_exists( $element, 'get_controls' ) || ! method_exists( $element, 'update_control' ) ) {
			return;
		}

		$form_fields_control = $element->get_controls( 'form_fields' );
		if ( ! is_array( $form_fields_control ) || empty( $form_fields_control['fields'] ) || ! is_array( $form_fields_control['fields'] ) ) {
			return;
		}

		$existing_controls = array();
		foreach ( $form_fields_control['fields'] as $field_control ) {
			if ( isset( $field_control['name'] ) ) {
				$existing_controls[ $field_control['name'] ] = true;
			}
		}

		$country_options = array();
		foreach ( self::get_phone_countries() as $country_code ) {
			$country_options[ $country_code ] = self::get_country_label( $country_code );
		}

		$text_mask_options = array(
			'none'              => __( 'Sem máscara', 'wa-suit' ),
			'preset'            => __( 'CPF, CNPJ ou CEP', 'wa-suit' ),
			'phone_br_mobile'   => __( 'Celular Brasil (00)00000-0000', 'wa-suit' ),
			'phone_br_landline' => __( 'Telefone Brasil (00)0000-0000', 'wa-suit' ),
		);
		if ( ! empty( self::get_settings()['phone_country_selector'] ) ) {
			$text_mask_options['phone'] = __( 'Telefone internacional', 'wa-suit' );
		}
		$text_mask_options['custom'] = __( 'Máscara personalizada', 'wa-suit' );

		$controls = array(
			array(
				'name'        => 'wa_suit_mask_type',
				'label'       => __( 'Máscara para texto', 'wa-suit' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $text_mask_options,
				'default'     => 'none',
				'label_block' => true,
				'condition'   => array(
					'field_type' => 'text',
				),
			),
			array(
				'name'        => 'wa_suit_mask_preset',
				'label'       => __( 'Formato pronto', 'wa-suit' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => array(
					'cpf'  => 'CPF (000.000.000-00)',
					'cnpj' => 'CNPJ (00.000.000/0000-00)',
					'cep'  => 'CEP (00000-000)',
				),
				'default'     => 'cpf',
				'condition'   => array(
					'wa_suit_mask_type' => 'preset',
					'field_type'        => 'text',
				),
				'label_block' => true,
			),
			array(
				'name'        => 'wa_suit_mask_pattern',
				'label'       => __( 'Formato da máscara', 'wa-suit' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '000.000.000-00',
				'description' => __( 'Use 0 para números, A para letras e * para números ou letras. Os demais caracteres são exibidos como separadores.', 'wa-suit' ),
				'condition'   => array(
					'wa_suit_mask_type' => 'custom',
					'field_type'        => 'text',
				),
				'label_block' => true,
			),
		);

		$controls = array_merge(
			$controls,
			array(
				array(
					'name'        => 'wa_suit_phone_country_mode',
					'label'       => __( 'Países disponíveis', 'wa-suit' ),
					'type'        => \Elementor\Controls_Manager::SELECT,
					'options'     => array(
						'global'  => __( 'Usar configuração global', 'wa-suit' ),
						'only'    => __( 'Mostrar somente os países selecionados', 'wa-suit' ),
						'exclude' => __( 'Mostrar todos, exceto os selecionados', 'wa-suit' ),
					),
					'default'     => 'global',
					'description' => __( 'Configure a lista específica deste campo ou herde a lista global do WA Suit.', 'wa-suit' ),
					'label_block' => true,
					'condition'   => array(
						'wa_suit_mask_type' => 'phone',
						'field_type'        => 'text',
					),
				),
				array(
					'name'        => 'wa_suit_phone_countries',
					'label'       => __( 'Selecionar países', 'wa-suit' ),
					'type'        => \Elementor\Controls_Manager::SELECT2,
					'options'     => $country_options,
					'multiple'    => true,
					'default'     => array(),
					'description' => __( 'Use a pesquisa para encontrar países. A lista será permitida ou excluída conforme a opção acima.', 'wa-suit' ),
					'label_block' => true,
					'condition'   => array(
						'wa_suit_mask_type'             => 'phone',
						'wa_suit_phone_country_mode!' => 'global',
						'field_type'                    => 'text',
					),
				),
				array(
					'name'        => 'wa_suit_phone_default_country',
					'label'       => __( 'País inicial deste campo', 'wa-suit' ),
					'type'        => \Elementor\Controls_Manager::SELECT,
					'options'     => array_merge(
						array( '' => __( 'Usar país padrão global', 'wa-suit' ) ),
						$country_options
					),
					'default'     => '',
					'label_block' => true,
					'condition'   => array(
						'wa_suit_mask_type' => 'phone',
						'field_type'        => 'text',
					),
				),
			)
		);

		foreach ( $controls as $control ) {
			if ( ! isset( $existing_controls[ $control['name'] ] ) ) {
				$form_fields_control['fields'][] = $control;
			}
		}

		$element->update_control( 'form_fields', $form_fields_control );
	}

	/**
	 * Returns a country label for Elementor control options.
	 *
	 * @param string $country_code ISO 3166-1 alpha-2 country code.
	 * @return string
	 */
	private static function get_country_label( $country_code ) {
		$country_name = class_exists( '\Locale' )
			? \Locale::getDisplayRegion( 'und_' . strtoupper( $country_code ), 'pt_BR' )
			: '';

		return ( $country_name ? $country_name : strtoupper( $country_code ) ) . ' (' . strtoupper( $country_code ) . ')';
	}

	/**
	 * Adds mask metadata to rendered Elementor Pro form inputs.
	 *
	 * @param string                    $content Rendered widget HTML.
	 * @param Elementor\Widget_Base|object $widget  Elementor widget instance.
	 * @return string
	 */
	public function add_mask_attributes( $content, $widget ) {
		if ( ! is_string( $content ) || ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) || 'form' !== $widget->get_name() ) {
			return $content;
		}

		$settings = method_exists( $widget, 'get_settings_for_display' )
			? $widget->get_settings_for_display()
			: array();
		$fields   = isset( $settings['form_fields'] ) && is_array( $settings['form_fields'] )
			? $settings['form_fields']
			: array();
		$raw_field_sources = array();
		if ( method_exists( $widget, 'get_settings' ) ) {
			$raw_settings = $widget->get_settings();
			if ( is_array( $raw_settings ) && isset( $raw_settings['form_fields'] ) && is_array( $raw_settings['form_fields'] ) ) {
				$raw_field_sources[] = $raw_settings['form_fields'];
			}
		}

		if ( method_exists( $widget, 'get_data' ) ) {
			$element_data = $widget->get_data();
			if (
				is_array( $element_data )
				&& isset( $element_data['settings']['form_fields'] )
				&& is_array( $element_data['settings']['form_fields'] )
			) {
				$raw_field_sources[] = $element_data['settings']['form_fields'];
			}
		}

		if ( empty( $fields ) && ! empty( $raw_field_sources ) ) {
			$fields = end( $raw_field_sources );
		} elseif ( ! empty( $raw_field_sources ) ) {

			foreach ( $fields as $index => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				foreach ( $raw_field_sources as $raw_fields ) {
					$raw_field = isset( $raw_fields[ $index ] ) && is_array( $raw_fields[ $index ] )
						? $raw_fields[ $index ]
						: array();

					foreach ( array( 'wa_suit_mask_type', 'wa_suit_phone_mask_type' ) as $setting_key ) {
						if ( ! empty( $raw_field[ $setting_key ] ) && 'none' !== $raw_field[ $setting_key ] ) {
							$fields[ $index ][ $setting_key ] = $raw_field[ $setting_key ];
						} elseif ( ! isset( $fields[ $index ][ $setting_key ] ) && isset( $raw_field[ $setting_key ] ) ) {
							$fields[ $index ][ $setting_key ] = $raw_field[ $setting_key ];
						}
					}

					foreach ( array( 'wa_suit_mask_preset', 'wa_suit_mask_pattern', 'wa_suit_phone_mask_pattern', 'wa_suit_phone_country_mode', 'wa_suit_phone_countries', 'wa_suit_phone_default_country' ) as $setting_key ) {
						if ( isset( $raw_field[ $setting_key ] ) && ( ! isset( $fields[ $index ][ $setting_key ] ) || empty( $fields[ $index ][ $setting_key ] ) ) ) {
							$fields[ $index ][ $setting_key ] = $raw_field[ $setting_key ];
						}
					}
				}
			}
		}
		$phone_settings = self::get_settings();
		$field_masks   = array(
			'text' => array(),
			'tel'  => array(),
		);
		$has_phone     = false;
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$field_type = isset( $field['field_type'] ) ? sanitize_key( $field['field_type'] ) : '';
			if ( ! isset( $field_masks[ $field_type ] ) ) {
				continue;
			}

			$mask_config = $this->get_field_mask_config( $field, $phone_settings );
			if ( empty( $mask_config ) ) {
				$field_masks[ $field_type ][] = array();
				continue;
			}

			$field_masks[ $field_type ][] = array(
				'type'           => $mask_config['type'],
				'pattern'        => $mask_config['pattern'],
				'remove_pattern' => ! empty( $mask_config['remove_pattern'] ),
				'phone_options'  => isset( $mask_config['phone_options'] ) ? $mask_config['phone_options'] : array(),
			);
			$has_phone = $has_phone || 'phone' === $mask_config['type'];
		}

		if ( ! array_filter( array_merge( $field_masks['text'], $field_masks['tel'] ) ) ) {
			return $content;
		}

		if ( $has_phone ) {
			wp_enqueue_script( 'wa-suit-intl-tel-input' );
			$scripts = wp_scripts();
			if (
				isset( $scripts->registered['wa-suit-elementor-form-masks'] )
				&& ! in_array( 'wa-suit-intl-tel-input', $scripts->registered['wa-suit-elementor-form-masks']->deps, true )
			) {
				$scripts->registered['wa-suit-elementor-form-masks']->deps[] = 'wa-suit-intl-tel-input';
			}
		}

		wp_enqueue_script( 'wa-suit-elementor-form-masks' );
		wp_enqueue_style( 'wa-suit-elementor-form-masks' );

		return $this->add_mask_attributes_to_inputs( $content, $field_masks );
	}

	/**
	 * Registers local scripts and styles used by masked fields.
	 *
	 * @return void
	 */
	public function register_assets() {
		$settings      = self::get_settings();
		$phone_enabled = ! empty( $settings['phone_country_selector'] );

		wp_register_script(
			'wa-suit-intl-tel-input',
			WA_SUIT_URL . 'assets/vendor/intl-tel-input/dist/js/intlTelInputWithUtils.min.js',
			array(),
			'29.5.6',
			true
		);
		wp_register_script(
			'wa-suit-elementor-form-masks',
			WA_SUIT_URL . 'assets/js/elementor-form-masks.js',
			$phone_enabled
				? array( 'jquery', 'wa-suit-intl-tel-input' )
				: array( 'jquery' ),
			filemtime( WA_SUIT_PATH . 'assets/js/elementor-form-masks.js' ),
			true
		);
		wp_localize_script(
			'wa-suit-elementor-form-masks',
			'waSuitFormMasks',
			array(
				'phoneCountrySelector' => ! empty( $settings['phone_country_selector'] ),
				'phoneCountryMode'     => $settings['phone_country_mode'],
				'phoneCountries'       => $settings['phone_countries'],
				'phoneDefaultCountry'  => $settings['phone_default_country'],
			)
		);
		wp_register_style(
			'wa-suit-intl-tel-input',
			WA_SUIT_URL . 'assets/vendor/intl-tel-input/dist/css/intlTelInput.min.css',
			array(),
			'29.5.6'
		);
		wp_register_style(
			'wa-suit-elementor-form-masks',
			WA_SUIT_URL . 'assets/css/elementor-form-masks.css',
			$phone_enabled
				? array( 'wa-suit-intl-tel-input' )
				: array(),
			filemtime( WA_SUIT_PATH . 'assets/css/elementor-form-masks.css' )
		);
	}

	/**
	 * Enqueues frontend assets before WordPress prints styles and scripts.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_script( 'wa-suit-elementor-form-masks' );

		if ( ! empty( self::get_settings()['phone_country_selector'] ) ) {
			wp_enqueue_script( 'wa-suit-intl-tel-input' );
			wp_enqueue_style( 'wa-suit-intl-tel-input' );
		}

		wp_enqueue_style( 'wa-suit-elementor-form-masks' );
	}

	/**
	 * Adds mask attributes to text and telephone inputs in Elementor field order.
	 *
	 * @param string $content Rendered widget HTML.
	 * @param array  $field_masks Mask configurations grouped by Elementor field type.
	 * @return string
	 */
	private function add_mask_attributes_to_inputs( $content, $field_masks ) {
		$input_indexes = array(
			'text' => 0,
			'tel'  => 0,
		);
		return preg_replace_callback(
			'/<input\b[^>]*>/i',
			function ( $matches ) use ( $field_masks, &$input_indexes ) {
				$tag = $matches[0];
				if ( ! preg_match( '/\btype\s*=\s*(["\'])(text|tel)\1/i', $tag, $type_match ) ) {
					return $tag;
				}

				$field_type = strtolower( $type_match[2] );
				$field_index = $input_indexes[ $field_type ]++;
				if ( ! isset( $field_masks[ $field_type ][ $field_index ] ) || empty( $field_masks[ $field_type ][ $field_index ] ) ) {
					return $tag;
				}

				if ( false !== stripos( $tag, 'data-wa-mask-type=' ) ) {
					return $tag;
				}

				$mask = $field_masks[ $field_type ][ $field_index ];
				$mask_type = $mask['type'];
				$pattern = $mask['pattern'];
				$remove_pattern = $mask['remove_pattern'];
				$phone_options = $mask['phone_options'];
				$suffix     = '/>' === substr( $tag, -2 ) ? '/>' : '>';
				$tag_start  = substr( $tag, 0, -strlen( $suffix ) );
				if ( $remove_pattern ) {
					$tag_start = preg_replace( '/\s+pattern\s*=\s*(["\']).*?\1/i', '', $tag_start );
					$tag_start = preg_replace( '/\s+title\s*=\s*(["\'])Apenas números e caracteres de telefone[^"\']*\1/i', '', $tag_start );
				}
				$attributes = ' data-wa-mask-type="' . esc_attr( $mask_type ) . '"';

				if ( '' !== $pattern ) {
					$attributes .= ' data-wa-mask-pattern="' . esc_attr( $pattern ) . '"';
				}
				if ( $remove_pattern ) {
					$attributes .= ' data-wa-phone-mask-active="true"';
				}
				if ( 'phone' === $mask_type ) {
					$attributes .= ' data-wa-phone-country-mode="' . esc_attr( $phone_options['mode'] ) . '"';
					$attributes .= ' data-wa-phone-countries="' . esc_attr( implode( ',', $phone_options['countries'] ) ) . '"';
					$attributes .= ' data-wa-phone-default-country="' . esc_attr( $phone_options['default_country'] ) . '"';
				}

				return $tag_start . $attributes . $suffix;
			},
			$content
		);
	}

	/**
	 * Resolves the enabled mask for a supported Elementor field type.
	 *
	 * @param array $field Elementor form field settings.
	 * @param array $phone_settings Global phone settings.
	 * @return array
	 */
	private function get_field_mask_config( $field, $phone_settings ) {
		$field_type = isset( $field['field_type'] ) ? sanitize_key( $field['field_type'] ) : '';
		if ( 'text' === $field_type ) {
			$mask_type = isset( $field['wa_suit_mask_type'] ) ? sanitize_key( $field['wa_suit_mask_type'] ) : '';
			if ( 'phone' === $mask_type ) {
				return $this->get_international_phone_config( $field, $phone_settings );
			} elseif ( in_array( $mask_type, array( 'phone_br_mobile', 'phone_br_landline' ), true ) ) {
				$pattern = $this->get_phone_preset_pattern( $mask_type );
			} elseif ( 'preset' === $mask_type ) {
				$pattern = $this->get_preset_pattern( $field );
			} elseif ( 'custom' === $mask_type ) {
				$pattern = $this->get_custom_mask_pattern( $field );
			} else {
				return array();
			}
		} else {
			return array();
		}

		if ( '' === $pattern ) {
			return array();
		}

		return array(
			'type'           => 'pattern',
			'pattern'        => $pattern,
			'remove_pattern' => false,
		);
	}

	/**
	 * Builds the international phone mask settings for a text field.
	 *
	 * @param array $field Elementor form field settings.
	 * @param array $phone_settings Global phone settings.
	 * @return array
	 */
	private function get_international_phone_config( $field, $phone_settings ) {
		if ( empty( $phone_settings['phone_country_selector'] ) ) {
			return array();
		}

		$field_mode = isset( $field['wa_suit_phone_country_mode'] )
			? sanitize_key( $field['wa_suit_phone_country_mode'] )
			: 'global';
		$mode = in_array( $field_mode, array( 'only', 'exclude' ), true )
			? $field_mode
			: $phone_settings['phone_country_mode'];
		$countries = in_array( $field_mode, array( 'only', 'exclude' ), true )
			? self::sanitize_country_codes( isset( $field['wa_suit_phone_countries'] ) ? $field['wa_suit_phone_countries'] : array() )
			: $phone_settings['phone_countries'];
		$default_country = ! empty( $field['wa_suit_phone_default_country'] )
			? $field['wa_suit_phone_default_country']
			: $phone_settings['phone_default_country'];
		$countries = self::normalize_country_filter( $mode, $countries );

		return array(
			'type'           => 'phone',
			'pattern'        => '',
			'remove_pattern' => false,
			'phone_options'  => array(
				'mode'            => $mode,
				'countries'       => $countries,
				'default_country' => self::sanitize_default_country( $default_country, $mode, $countries ),
			),
		);
	}

	/**
	 * Returns a valid custom mask pattern, or an empty string.
	 *
	 * @param array  $field Elementor form field settings.
	 * @param string $key Field setting containing the pattern.
	 * @return string
	 */
	private function get_custom_mask_pattern( $field, $key = 'wa_suit_mask_pattern' ) {
		if ( ! isset( $field[ $key ] ) || ! is_string( $field[ $key ] ) ) {
			return '';
		}

		$pattern = sanitize_text_field( $field[ $key ] );

		return preg_match( '/[0A*]/', $pattern ) ? $pattern : '';
	}

	/**
	 * Resolves a built-in mask preset to its pattern.
	 *
	 * @param array $field Elementor form field settings.
	 * @return string
	 */
	private function get_preset_pattern( $field ) {
		if ( empty( $field['wa_suit_mask_preset'] ) || ! is_string( $field['wa_suit_mask_preset'] ) ) {
			return '';
		}

		$patterns = array(
			'cpf'  => '000.000.000-00',
			'cnpj' => '00.000.000/0000-00',
			'cep'  => '00000-000',
		);
		$preset  = sanitize_key( $field['wa_suit_mask_preset'] );

		return isset( $patterns[ $preset ] ) ? $patterns[ $preset ] : '';
	}

	/**
	 * Returns a supported Brazilian phone mask pattern.
	 *
	 * @param string $preset Preset identifier.
	 * @return string
	 */
	private function get_phone_preset_pattern( $preset ) {
		$patterns = array(
			'phone_br_mobile'   => '(00)00000-0000',
			'phone_br_landline' => '(00)0000-0000',
		);

		return isset( $patterns[ $preset ] ) ? $patterns[ $preset ] : '';
	}
}
