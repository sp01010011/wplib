<?php

/**
 * Class WPLib_Term_Model_Base
 *
 * The Model Base Class for Terms
 *
 * @property WPLib_Term_Base $owner
 */
abstract class WPLib_Term_Model_Base extends WPLib_Model_Base {

	/**
	 * @var WP_Term|object|null
	 */
	private $_term;

	/**
	 * Child class should define a valid value for TAXONOMY
	 */
	const TAXONOMY = null;

	/**
	 * @param object|int|string|null $term
	 * @param array $args
	 *
	 */
	function __construct( $term, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'lookup_type' => 'term_id'
		));

		switch ( gettype( $term ) ) {

			case 'object':
				$args[ 'term' ] = $term;
				break;

			case 'integer':

				if ( empty( $args[ 'taxonomy' ] ) ) {
					break;
				}
				$args['term'] = get_term_by( $args[ 'lookup_type' ], $term, $args['taxonomy'] );
				break;

			case 'string':
				if ( empty( $args[ 'taxonomy' ] ) ) {
					break;
				}
				if( ! ( $args[ 'term' ] = get_term_by( 'slug', $term, $args[ 'taxonomy' ] ) ) ) {

					if ( !( $args[ 'term' ] = get_term_by( 'name', $term, $args[ 'taxonomy' ] ) ) ) {

						$args['term'] = $term;

					}

				}
				break;

		}

		parent::__construct( $args );

	}

	/**
	 * @param array $args
	 *
	 * @return WPLib_Term_Model_Base
	 */
	static function make_new( $args ) {

		if ( isset( $args['term']->term_id ) && is_numeric( $args['term']->term_id ) ) {

			$term = $args['term'];

			$term = get_term( static::TAXONOMY, $term->term_id );

			unset( $args['term'] );

		} else if ( isset( $args['term'] ) && is_numeric( $args['term'] ) ) {

			$term = get_term( static::TAXONOMY, $args['term'] );

			unset( $args['term'] );

		} else {

			$term = null;

		}

		return new static( $term, $args );

	}

	/**
	 * @return object|null
	 */
	function term() {

		return $this->_term;

	}

	/**
	 * @param object $term
	 * @return mixed|object
	 */
	function set_term( $term ) {

		if ( WPLib_Terms::is_term( $term ) ) {

			$this->_term = $term;

		}

	}

	/**
	 * Check to see if this instance has a valid term object in $_term property.
	 *
	 * @return bool
	 */
	function has_term() {

		return WPLib_Terms::is_term( $this->_term );

	}

	function taxonomy() {

		return $this->constant( 'TAXONOMY' );

	}


	/**
	 * @return int
	 */
	function term_id() {

		return $this->has_term() ? intval( $this->_term->term_id ) : 0;

	}

	/**
	 * @return null|string
	 */
	function term_name() {

		return $this->has_term() ? $this->_term->name : null;

	}

	/**
	 * @return null|string
	 */
	function term_slug() {

		return $this->has_term() ? $this->_term->slug : null;

	}

	/**
	 * @return string|WP_Error
	 */
	function permalink() {

		switch ( static::taxonomy() ) {

			case WPLib_Category::TAXONOMY:

	 	        $permalink = get_category_link( $this->term_id() );

				break;

			case WPLib_Post_Tag::TAXONOMY:

				$permalink = get_tag_link( $this->term_id() );

				break;

			default:

				$permalink = get_term_link( $this->term_id(), $this->taxonomy() );

				break;

		}

		return $permalink;

	}

	/**
	 * Return array of object IDs associate with this taxonomy term.
	 *
	 * @return array|WP_Error
	 */
	function object_ids() {

		$object_ids = get_objects_in_term( $this->term_id(), $this->taxonomy() );
		return $object_ids;

	}

	/**
	 * Is the passed Object ID associated with this taxonomy term.
	 *
	 * @todo Decide if "assign" is the best term for this.
	 *
	 * @param int $object_id Likely a Post ID but can be other things
	 *
	 * @return bool|WP_Error
	 */
	function is_assigned( $object_id ) {

		return $this->has_term() ? is_object_in_term( $object_id, $this->taxonomy(), $this->term()->term_id ) : false;

	}

	/**
	 * Associate the passed Object ID with this taxonomy term.
	 *
	 * @param int $object_id Likely a Post ID but can be other things
	 *
	 * @return int[]|WP_Error
	 */
	function assign( $object_id ) {

		return $this->has_term() ? wp_add_object_terms( $object_id, $this->term()->term_id, $this->taxonomy() ) : false;

	}

	/**
	 * Associate the passed Object ID with this taxonomy term.
	 *
	 * @param int $object_id Likely a Post ID but can be other things
	 *
	 * @return bool|WP_Error
	 */
	function unassign( $object_id ) {

		return $this->has_term() ? wp_remove_object_terms( $object_id, $this->term()->term_id, $this->taxonomy() ) : false;

	}


	/**
	 * @param string $new_slug
	 * @param bool|WP_Error $wp_error
	 */
	function update_slug( $new_slug, $wp_error = false ) {

		$this->update_field( 'slug', $new_slug );

	}

	/**
	 * @param string $new_description
	 * @param bool|WP_Error $wp_error
	 */
	function update_description( $new_description, $wp_error = false ) {

		$this->update_field( 'description', $new_description );

	}

	/**
	 * @param string $field_name
	 * @param string $new_value
	 * @param bool|WP_Error $wp_error
	 */
	function update_field( $field_name, $new_value, $wp_error = false ) {
		if ( $this->has_wp_term() ) {

			/**
			 * @var wpdb $wpdb
			 */
			global $wpdb;

			$args         = (array) $this->term();
			$args[ $field_name ] = sanitize_title_with_dashes( $new_value );
			$term         = wp_update_term( $this->term()->term_id, $taxonomy = $this->taxonomy(), $args );
			if ( ! is_wp_error( $term ) ) {
				$term = get_term( $term[ 'term_id' ], $taxonomy );
				if ( isset( $term->$field_name ) ) {
					$this->term()->$field_name = $term->$field_name;
				}
			}
		}
	}


}
