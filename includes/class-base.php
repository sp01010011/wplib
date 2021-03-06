<?php

/**
 * Class WPLib_Base
 */
abstract class WPLib_Base {

	/**
	 * @var array Capture any extra $args passed for which there are no properties.
	 */
	var $extra_args = array();

	/**
	 * @var WPLib_Base|null If not null contains reference to containing object.
	 */
	var $owner;

	/**
	 * @var bool If true will trigger an error if a non-existent method or property is accessed.
	 */
	private $_trigger_error = true;

	/**
	 * @param array|string|object $args
	 */
	function __construct( $args = array() ) {

		$this->set_state( $args );

	}

	/**
	 * Set the object state given an array of $args with elements that match property names.
	 *
	 * @not And array elements not found as properties will be assigned to the property array $this->extra_args.
	 *
	 * @param array|object $args
	 */
	function set_state( $args ) {

		$args = wp_parse_args( $args );

		foreach ( $args as $name => $value ) {

			if ( 'extra_args' != $name && property_exists( $this, $name ) ) {

				$this->{$name} = $value;

			} else if ( property_exists( $this, $protected_name = "_{$name}" ) ) {

				$this->{$protected_name} = $value;

			} else {

				$this->extra_args[ $name ] = $value;

			}

		}

	}

	/**
	 * Return a class constant for the called instance.
	 *
	 * @param string $constant_name
	 * @param string $class_name
	 *
	 * @return mixed|null
	 */
	function constant( $constant_name, $class_name = null ) {

		if ( is_null( $class_name ) ) {

			$class_name = get_class( $this );

		}

		return defined( $constant_ref = "{$class_name}::{$constant_name}" ) ? constant( $constant_ref ) : null;

	}

	/**
	 * @param string $action
	 * @param int $priority
	 */
	static function add_class_action( $action, $priority = 10 ) {

		$hook = "_{$action}" . ( 10 != $priority ? "_{$priority}" : '' );
		add_action( $action, array( get_called_class(), $hook ), $priority, 99 );

	}

	/**
	 * @param string $filter
	 * @param int $priority
	 */
	static function add_class_filter( $filter, $priority = 10 ) {

		$hook = "_{$filter}" . ( 10 != $priority ? "_{$priority}" : '' );
		add_filter( $filter, array( get_called_class(), $hook ), $priority, 99 );

	}

	/**
	 * @param string $action
	 * @param int $priority
	 */
	static function remove_class_action( $action, $priority = 10 ) {

		$hook = "_{$action}" . ( 10 != $priority ? "_{$priority}" : '' );
		remove_action( $action, array( get_called_class(), $hook ), $priority, 99 );

	}

	/**
	 * @param string $filter
	 * @param int $priority
	 */
	static function remove_class_filter( $filter, $priority = 10 ) {

		$hook = "_{$filter}" . ( 10 != $priority ? "_{$priority}" : '' );
		remove_filter( $filter, array( get_called_class(), $hook ), $priority, 99 );

	}

	/**
	 * @param string $property_name
	 *
	 * @return bool
	 */
	function __isset( $property_name ) {

		$save_trigger_error = $this->_trigger_error;
		$this->_trigger_error = false;

		do { // Use do{}while(false) to allow 'break'ing out of a code sequence.


			if ( ! is_callable( array( $this, $property_name ) ) ) {

				$isset = false;
				break;

			}

			if ( null === $this->$property_name() ) {

				$isset = false;
				break;

			}

			$isset = true;

		} while ( false );

		$this->_trigger_error = $save_trigger_error;

		return $isset;

	}

	/**
	 *
	 * Call same named method to access virtial properties defined as methods.
	 *
	 * Generates debugging error message for attempts to get a non-existent property.
	 *
	 * @example
	 *
	 *  $name = $this->name; //  If no 'name' property, calls `name()` if that method exists.
	 *
	 * @param string $property_name
	 *
	 * @return null
	 */
	function __get( $property_name ) {

		$value = null;

		if ( is_callable( $callable = array( $this, $property_name ) ) ) {

			$value = call_user_func( $callable );

		} else if ( $this->_trigger_error ) {

			$message = __( "Cannot access property '%s' in class '%s'.", 'wplib' );

			WPLib::trigger_error( sprintf( $message, $property_name, get_class( $this ) ) );

			$value = null;

		}

		return $value;

	}

	/**
	 * Generate debugging error message for attempts to set a non-existent property.
	 *
	 * @param string $property_name
	 * @param mixed $value
	 *
	 * @return void
	 */
	function __set( $property_name, $value ) {

		if ( is_callable( $callable = array( $this, "set_{$property_name}" ) ) ) {

			call_user_func( $callable, $value );

		} else if ( $this->_trigger_error ) {

			$message = __( "Cannot set property '%s' in class '%s'.", 'wplib' );

			WPLib::trigger_error( sprintf( $message, $property_name, get_class( $this ) ) );

		}

	}

	/**
	 * Generate debugging error message for attempts to call a non-existent method.
	 *
	 * @param string $method_name
	 * @param array $args
	 *
	 * @return null
	 */
	function __call( $method_name, $args ) {

		$value = null;

		if ( $this->_trigger_error ) {

			$message = __( "Cannot call method '%s' in class '%s'.", 'wplib' );

			WPLib::trigger_error( sprintf( $message, $method_name, get_class( $this ) ) );

		}

		return $value;

	}

}
