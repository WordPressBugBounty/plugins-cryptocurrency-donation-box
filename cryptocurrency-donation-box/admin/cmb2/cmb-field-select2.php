<?php

/**
 * Class PW_CMB2_Field_Select2
 */
class PW_CMB2_Field_Select2 {

	/**
	 * Current version number
	 */
	const VERSION = '3.0.3';

	/**
	 * Initialize the plugin by hooking into CMB2
	 */
	public function __construct() {
		add_filter( 'cmb2_render_pw_select', array( $this, 'render_pw_select' ), 10, 5 );
		add_filter( 'cmb2_render_pw_multiselect', array( $this, 'render_pw_multiselect' ), 10, 5 );
		add_filter( 'cmb2_sanitize_pw_multiselect', array( $this, 'pw_multiselect_sanitize' ), 10, 4 );
		add_filter( 'cmb2_types_esc_pw_multiselect', array( $this, 'pw_multiselect_escaped_value' ), 10, 3 );
		add_filter( 'cmb2_repeat_table_row_types', array( $this, 'pw_multiselect_table_row_class' ), 10, 1 );
	}

	/**
	 * Render select box field
	 */
	public function render_pw_select( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->setup_admin_scripts();

		if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
			$field_type_object->type = new CMB2_Type_Select( $field_type_object );
		}

		echo wp_kses_post( $field_type_object->select( array(
			'class'            => 'pw_select2 pw_select',
			'desc'             => esc_html( $field_type_object->_desc( true ) ),
			'options'          => '<option></option>' . wp_kses_post( $field_type_object->concat_items() ),
			'data-placeholder' => esc_attr( $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ) ),
		) ) );
		
	}

	/**
	 * Render multi-value select input field
	 */
	public function render_pw_multiselect( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->setup_admin_scripts();

		if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
			$field_type_object->type = new CMB2_Type_Select( $field_type_object );
		}

		$a = $field_type_object->parse_args( 'pw_multiselect', array(
			'multiple'         => 'multiple',
			'style'            => 'width: 99%',
			'class'            => 'pw_select2 pw_multiselect',
			'name'             => $field_type_object->_name() . '[]',
			'id'               => $field_type_object->_id(),
			'desc'             => $field_type_object->_desc( true ),
			'options'          => $this->get_pw_multiselect_options( $field_escaped_value, $field_type_object ),
			'data-placeholder' => $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ),
		) );

		$attrs = $field_type_object->concat_attrs( $a, array( 'desc', 'options' ) );
		echo sprintf(
			'<select%s>%s</select>%s',
			esc_attr( $attrs ),                // Escape attributes
			wp_kses_post( $a['options'] ),      // Allow only safe HTML
			esc_html( $a['desc'] )              // Escape text
		);

	}

	/**
	 * Return list of options for pw_multiselect
	 *
	 * Return the list of options, with selected options at the top preserving their order. This also handles the
	 * removal of selected options which no longer exist in the options array.
	 */
	public function get_pw_multiselect_options( $field_escaped_value = array(), $field_type_object ) {
		$options = (array) $field_type_object->field->options();

		// If we have selected items, we need to preserve their order
		if ( ! empty( $field_escaped_value ) ) {
			$options = $this->sort_array_by_array( $options, $field_escaped_value );
		}

		$selected_items = '';
		$other_items = '';

		foreach ( $options as $option_value => $option_label ) {

			// Clone args & modify for just this item
			$option = array(
				'value' => $option_value,
				'label' => $option_label,
			);

			// Split options into those which are selected and the rest
			if ( in_array( $option_value, (array) $field_escaped_value ) ) {
				$option['checked'] = true;
				$selected_items .= $field_type_object->select_option( $option );
			} else {
				$other_items .= $field_type_object->select_option( $option );
			}
		}

		return $selected_items . $other_items;
	}

	/**
	 * Sort an array by the keys of another array
	 *
	 * @author Eran Galperin
	 * @link http://link.from.pw/1Waji4l
	 */
	public function sort_array_by_array( array $array, array $orderArray ) {
		$ordered = array();

		foreach ( $orderArray as $key ) {
			if ( array_key_exists( $key, $array ) ) {
				$ordered[ $key ] = $array[ $key ];
				unset( $array[ $key ] );
			}
		}

		return $ordered + $array;
	}

	/**
	 * Handle sanitization for repeatable fields
	 */
	public function pw_multiselect_sanitize( $check, $meta_value, $object_id, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}

		foreach ( $meta_value as $key => $val ) {
			$meta_value[$key] = array_map( 'sanitize_text_field', $val );
		}

		return $meta_value;
	}

	/**
	 * Handle escaping for repeatable fields
	 */
	public function pw_multiselect_escaped_value( $check, $meta_value, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}

		foreach ( $meta_value as $key => $val ) {
			$meta_value[$key] = array_map( 'esc_attr', $val );
		}

		return $meta_value;
	}

	/**
	 * Add 'table-layout' class to multi-value select field
	 */
	public function pw_multiselect_table_row_class( $check ) {
		$check[] = 'pw_multiselect';

		return $check;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function setup_admin_scripts() {
		$asset_path = apply_filters( 'pw_cmb2_field_select2_asset_path', plugins_url( '', __FILE__  ) );

		wp_register_script( 'pw-select2', $asset_path . '/js/select2.min.js', array( 'jquery-ui-sortable' ), '4.0.3' );
		wp_enqueue_script( 'pw-select2-init', $asset_path . '/js/script.js', array( 'cmb2-scripts', 'pw-select2' ), self::VERSION );
		wp_register_style( 'pw-select2', $asset_path . '/css/select2.min.css', array(), '4.0.3' );
		wp_enqueue_style( 'pw-select2-tweaks', $asset_path . '/css/style.css', array( 'pw-select2' ), self::VERSION );
	}
}
$pw_cmb2_field_select2 = new PW_CMB2_Field_Select2();
