<?php

/**
 * Class WPLib_View_Base
 *
 * @property WPLib_Entity_Base $entity
 * @property WPLib_Entity_Base $owner
 * @property WPLib_Model_Base $model
 * @mixin WPLib_Model_Base
 */
abstract class WPLib_View_Base extends WPLib_Base {

	/**
	 * Use this to ease refactoring from $entity to $owner
	 *
	 * @return WPLib_Entity_Base
	 */
	function entity() {

		return $this->owner;

	}

	/**
	 * Use this to ease refactoring from $entity to $owner
	 *
	 * @param WPLib_Entity_Base $entity
	 */
	function set_entity( $entity ) {

		$this->owner = $entity;

	}

	/**
	 * Get model
	 *
	 * @return WPLib_Model_Base
	 */
	function model() {

		return $this->owner->model;

	}

	/**
	 * @param string $template
	 * @param array $_template_vars
	 * @return string
	 */
	function get_template_html( $template, $_template_vars = array() ) {
		ob_start();
		$this->the_template( $template, $_template_vars );
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * @param string $template
	 * @param array $_template_vars
	 */
	function the_template( $template, $_template_vars = array() ) {

		WPLib::the_template( $template, $_template_vars, $this->owner );

	}


	/**
	 * Do a context controlled version of get_header()
	 *
	 * @param string $name The name of the specialised header.
	 */
	function the_header_html( $name = null ) {
		/**
		 * @future  Add context save and set
		 *          (current code does not need it, but future code will be more robust)
		 */
		get_header();
		/**
		 * @future  Add context reset
		 */
	}

	/**
	 * Do a context controlled version of get_footer()
	 *
	 * @param string $name The name of the specialised header.
	 */
	function the_footer_html( $name = null ) {
		/**
		 * @future  Add context save and set
		 *          (current code does not need it, but future code will be more robust)
		 */
		get_footer();
		/**
		 * @future  Add context reset
		 */
	}

	/**
	 * Magic method for getting inaccessible properties
	 * Examples:
	 *  $this->ID       Return ID
	 *  $this->the_ID   Output ID
	 *
	 * @param string $property_name
	 *
	 * @return mixed|null
	 */
	function __get( $property_name ) {

		$value = null;

		if ( is_callable( $property_callable = array( $this, $property_name ) ) ) {

			$value = call_user_func( $property_callable );

		} else {

			$value = $this->model()->$property_name;

		}

		return $value;
	}

	/**
	 * Magic method for setting inaccessible properties
	 *
	 * @param string $property_name
	 * @param mixed  $value
	 *
	 * @return void
	 */
	function __set( $property_name, $value ) {

		if ( is_callable( $property_setter = array( $this, "set_{$property_name}" ) ) ) {

			call_user_func( $property_setter, $value );

		} else {

			$this->owner->model->$property_name = $value;

		}

	}

	/**
	 * Magic method for calling inaccessible methods
	 * Examples:
	 *  $this->date             Return original ISO 8601 date format from model
	 *  $this->get_date()       Return custom formatted date
	 *  $this->get_date_html()  Return custom formatted date HTML
	 *  $this->the_date()       Output custom formatted date
	 *  $this->the_date_html()  Output custom formatted date HTML
	 *
	 * @param string $method_name
	 * @param array  $args
	 *
	 * @return mixed|null
	 */
	function __call( $method_name, $args = array() ) {

		$value = null;

		if ( 0 !== strpos( $method_name, 'the_' ) ) {

			$value = call_user_func_array( array( $this->owner->model, $method_name ), $args );

		} else {

		   	$value = WPLib::do_the_methods( $this, $this->model(), $method_name, $args );
		}

		return $value;

	}




}
