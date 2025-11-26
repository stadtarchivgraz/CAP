<?php

class Starg_Email_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'starg_email_shortcode_widget';
	}

	public function get_title() {
		return esc_html__( 'Email address', 'sip' );
	}

	public function get_icon() {
		// return 'eicon-shortcode';
		return 'eicon-envelope';
	}

	public function get_categories() {
		return array('general',);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$class    = ( $settings['class'] ) ? ' class="' . esc_attr( $settings['class'] ) . '" ' : '';
		echo do_shortcode( '[starg_email' . $class . ']'  . esc_attr( $settings['content'] ) . '[/starg_email]');
	}

	protected function content_template() {}

	protected function _register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => esc_html__('Content', 'sip'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'content',
			array(
				'label'       => esc_html__('Content', 'sip'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'example@your-domain.tld',
				'description' => esc_attr__( 'The email address you want to protect from bots.', 'sip' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'option_section',
			array(
				'label' => esc_html__('Options', 'sip'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'class',
			array(
				'label'       => esc_html__('CSS Class', 'sip'),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'button',
				'description' => esc_attr__( 'One or more classes for the element separated by whitespace.', 'sip' ),
			)
		);

		$this->end_controls_section();
	}
}
