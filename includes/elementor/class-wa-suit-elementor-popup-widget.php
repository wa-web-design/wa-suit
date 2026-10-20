<?php
/**
 * Click-to-open popup widget for Elementor.
 *
 * @package WASuit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a button that opens a hidden popup.
 */
class WA_Suit_Elementor_Popup_Widget extends \Elementor\Widget_Base {

	/**
	 * Returns the widget identifier.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wa-suit-popup';
	}

	/**
	 * Returns the widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Popup', 'wa-suit' );
	}

	/**
	 * Returns the widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-click';
	}

	/**
	 * Returns the widget category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'wa-widgets' );
	}

	/**
	 * Returns scripts required by this widget.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'wa-suit-elementor-widgets' );
	}

	/**
	 * Returns styles required by this widget.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'wa-suit-elementor-widgets' );
	}

	/**
	 * Registers the widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Conteúdo', 'wa-suit' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'       => __( 'Texto do botão', 'wa-suit' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Abrir popup', 'wa-suit' ),
				'placeholder' => __( 'Digite o texto do botão', 'wa-suit' ),
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label'   => __( 'Ícone', 'wa-suit' ),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => array(),
			)
		);

		$this->add_control(
			'button_icon_position',
			array(
				'label'   => __( 'Posição do ícone', 'wa-suit' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'options' => array(
					'row'         => array(
						'title' => __( 'Ícone antes do texto', 'wa-suit' ),
						'icon'  => 'eicon-arrow-left',
					),
					'row-reverse' => array(
						'title' => __( 'Ícone depois do texto', 'wa-suit' ),
						'icon'  => 'eicon-arrow-right',
					),
				),
				'default'   => 'row',
				'toggle'    => false,
				'condition' => array(
					'button_icon[value]!' => '',
				),
			)
		);

		$this->add_control(
			'popup_title',
			array(
				'label'       => __( 'Título do popup', 'wa-suit' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Título do popup', 'wa-suit' ),
				'placeholder' => __( 'Opcional', 'wa-suit' ),
			)
		);

		$this->add_control(
			'popup_content',
			array(
				'label'   => __( 'Conteúdo do popup', 'wa-suit' ),
				'type'    => \Elementor\Controls_Manager::WYSIWYG,
				'default' => __( 'Adicione aqui o conteúdo que será exibido no popup.', 'wa-suit' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			array(
				'label' => __( 'Botão', 'wa-suit' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Cor do texto', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-trigger' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .wa-suit-popup-trigger',
			)
		);

		$this->add_control(
			'button_background_color',
			array(
				'label'     => __( 'Cor de fundo', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-trigger' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_alignment',
			array(
				'label'     => __( 'Alinhamento', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'flex-start' => __( 'Esquerda', 'wa-suit' ),
					'center'     => __( 'Centro', 'wa-suit' ),
					'flex-end'   => __( 'Direita', 'wa-suit' ),
					'space-between' => __( 'Largura total', 'wa-suit' ),
				),
				'default'   => 'flex-start',
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-widget' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_padding',
			array(
				'label'      => __( 'Espaçamento interno', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .wa-suit-popup-trigger',
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => __( 'Arredondamento', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-trigger' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .wa-suit-popup-trigger',
			)
		);

		$this->add_control(
			'button_icon_spacing',
			array(
				'label'      => __( 'Espaço entre ícone e texto', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-trigger .elementor-button-content-wrapper' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_transition_duration',
			array(
				'label'      => __( 'Duração da transição', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 1000,
						'step' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-trigger' => 'transition: all {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_hover_heading',
			array(
				'label'     => __( 'Ao passar o mouse', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'button_hover_text_color',
			array(
				'label'     => __( 'Cor do texto', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-trigger:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_background_color',
			array(
				'label'     => __( 'Cor de fundo', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-trigger:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_hover_border_color',
			array(
				'label'     => __( 'Cor da borda', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-trigger:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_hover_box_shadow',
				'selector' => '{{WRAPPER}} .wa-suit-popup-trigger:hover',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_popup_style',
			array(
				'label' => __( 'Popup', 'wa-suit' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'popup_width',
			array(
				'label'      => __( 'Largura do popup', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min'  => 320,
						'max'  => 1200,
						'step' => 10,
					),
					'%'  => array(
						'min'  => 50,
						'max'  => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 640,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-panel' => 'width: min(100%, {{SIZE}}{{UNIT}});',
				),
			)
		);

		$this->add_control(
			'popup_close_color',
			array(
				'label'     => __( 'Cor do botão de fechar', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-close' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_close_background_color',
			array(
				'label'     => __( 'Cor de fundo do botão de fechar', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-close' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_close_hover_heading',
			array(
				'label'     => __( 'Ao passar o mouse no botão de fechar', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'popup_close_hover_color',
			array(
				'label'     => __( 'Cor do ícone', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-close:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_close_hover_background_color',
			array(
				'label'     => __( 'Cor de fundo', 'wa-suit' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wa-suit-popup-close:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'popup_close_size',
			array(
				'label'      => __( 'Tamanho do ícone', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 48,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-close' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'popup_close_padding',
			array(
				'label'      => __( 'Espaçamento interno', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-close' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'popup_close_border',
				'selector' => '{{WRAPPER}} .wa-suit-popup-close',
			)
		);

		$this->add_control(
			'popup_close_border_radius',
			array(
				'label'      => __( 'Arredondamento', 'wa-suit' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wa-suit-popup-close' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Renders the trigger button and hidden popup.
	 *
	 * @return void
	 */
	protected function render() {
		$settings  = $this->get_settings_for_display();
		$popup_id  = 'wa-suit-popup-' . sanitize_html_class( $this->get_id() );
		$title_id  = $popup_id . '-title';
		$has_title = ! empty( $settings['popup_title'] );
		$has_icon  = ! empty( $settings['button_icon']['value'] );
		$alignment = isset( $settings['button_alignment'] ) ? $settings['button_alignment'] : 'flex-start';
		$button_wrapper_style = isset( $settings['button_icon_position'] ) ? $settings['button_icon_position'] : 'row';
		?>
		<div class="wa-suit-popup-widget<?php echo 'space-between' === $alignment ? ' wa-suit-popup-widget--full-width' : ''; ?>">
			<button
				type="button"
				class="wa-suit-popup-trigger elementor-button"
				data-wa-popup-open
				aria-haspopup="dialog"
				aria-controls="<?php echo esc_attr( $popup_id ); ?>"
			>
				<span class="elementor-button-content-wrapper" style="flex-direction: <?php echo esc_attr( $button_wrapper_style ); ?>">
					<?php if ( $has_icon ) : ?>
						<span class="wa-suit-popup-button-icon">
							<?php \Elementor\Icons_Manager::render_icon( $settings['button_icon'], array( 'aria-hidden' => 'true' ) ); ?>
						</span>
					<?php endif; ?>
					<span class="elementor-button-text"><?php echo esc_html( $settings['button_text'] ); ?></span>
				</span>
			</button>

			<div
				id="<?php echo esc_attr( $popup_id ); ?>"
				class="wa-suit-popup"
				data-wa-popup-dialog
				role="dialog"
				aria-modal="true"
				<?php if ( $has_title ) : ?>
					aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
				<?php else : ?>
					aria-label="<?php esc_attr_e( 'Popup', 'wa-suit' ); ?>"
				<?php endif; ?>
				hidden
			>
				<div class="wa-suit-popup-backdrop">
					<div class="wa-suit-popup-panel" data-wa-popup-panel tabindex="-1">
						<button
							type="button"
							class="wa-suit-popup-close"
							data-wa-popup-close
							aria-label="<?php esc_attr_e( 'Fechar popup', 'wa-suit' ); ?>"
						>&times;</button>
						<?php if ( $has_title ) : ?>
							<h2 id="<?php echo esc_attr( $title_id ); ?>" class="wa-suit-popup-title">
								<?php echo esc_html( $settings['popup_title'] ); ?>
							</h2>
						<?php endif; ?>
						<div class="wa-suit-popup-content">
							<?php echo wp_kses_post( $this->parse_text_editor( $settings['popup_content'] ) ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
