<?php
/**
 * Products synchronization.
 *
 * This class is used to keep the product taxonomy in sync
 * with any given post type. This is especially useful for
 * a site that uses an e-commerce plugin such as WooCommerce
 * or Easy Digital Downloads.
 *
 * This class was largely inspired by the codebase of CPT-onomies
 * (https://wordpress.org/plugins/cpt-onomies/) by Rachel Carden.
 *
 * @package   Awesome Support
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */
class WPAS_Product_Sync {

	/**
	 * Name of the post type to use
	 * for populating the product taxonomy.
	 *
	 * @since  3.0.2
	 * @var    string
	 */
	protected $post_type;

	/**
	 * The name of the taxonomy to synchronize.
	 *
	 * @since  3.0.2
	 * @var string
	 */
	protected $taxonomy;
	
	/**
	 * Constructor method.
	 *
	 * @since  3.0.2
	 * @param  string $post_type Name of the post type that should be used to populate the product taxonomy
	 */
	public function __construct( $post_type = '', $taxonomy = '' ) {

		$this->post_type = sanitize_title( $post_type );
		$this->taxonomy  = empty( $taxonomy ) ? $this->post_type : sanitize_title( $taxonomy );

		/* Only hack into the taxonomies functions if multiple products is enabled and the provided post type exists */
		if ( $this->is_multiple_products() && post_type_exists( $post_type ) ) {
			add_filter( 'get_terms',    array( $this, 'get_terms' ),    1, 3 );
			add_filter( 'get_term',     array( $this, 'get_term' ),     1, 2 );
			add_action( 'trashed_post', array( $this, 'unsync_term' ), 10, 1 );
			add_action( 'delete_post',  array( $this, 'unsync_term' ), 10, 1 );
		}

	}

	/**
	 * Check if the site uses multiple products.
	 *
	 * @since  3.0.2
	 * @return boolean True if multiple products is enabled, false otherwise
	 */
	protected function is_multiple_products() {

		/* Get Awesome Support options */
		$options = maybe_unserialize( get_option( 'wpas_options', array() ) );

		if ( isset( $options['support_products'] ) && true === boolval( $options['support_products'] ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if a given taxonomy is ours.
	 *
	 * @since  3.0.2
	 * @param  string  $taxonomy A taxonomy name
	 * @return boolean           True if this is our taxonomy, false otherwise
	 */
	protected function is_product_tax( $taxonomy ) {
		return $this->taxonomy === $taxonomy ? true : false;
	}

	/**
	 * Map the taxonomy arguments to the WP_Query arguments.
	 *
	 * Take all of the arguments available for get_terms and convert
	 * them to their equivalent for WP_Query. If there is no equivalent
	 * for an argument (at least for our case), we simply ignore it.
	 *
	 * @since  3.0.2
	 * @param  array $args Taxonomy arguments
	 * @return array       WP_Query arguments
	 */
	public function map_args( $args ) {

		$clean_args = array();

		/* Then we need to map the taxonomy args to the WP_Query args */
		foreach ( $args as $arg => $value ) {

			switch ( $arg ) {

				/**
				 * These are the arguments that have absolutely no
				 * equivalent or use for us in the WP_Query.
				 */
				case 'pad_counts':
				case 'hide_empty':
				case 'hierarchical':
				case 'cache_domain':
					continue;
					break;

				case 'exclude':
					$clean_args['post__not_in'] = (array) $value;
					break;

				case 'exclude_tree':
					$clean_args['post_parent__not_in'] = (array) $value;
					break;

				case 'include':
					$clean_args['post__in'] = (array) $value;
					break;

				case 'number':
					$clean_args['posts_per_page'] = $value;
					break;

				case 'fields':

					/* @TODO Supporting more 'fields' types could be done with post processing by filtering the result manually */

					/* If the user only wants the terms count it is a special case. Set a trigger var and continue */
					if ( 'count' === $value ) {
						$clean_args['fields']              = 'ids';
						$clean_args['wpas_get_post_count'] = true; // We set wpas_get_post_count in order to know that we just need the post count
						continue;
					}

					/* Use the given arg if supported by WP_Query */
					if ( in_array( $value, array( 'all', 'ids', 'id=>parent' ) ) ) {
						$clean_args['fields'] = $value;
					} else {
						$clean_args['fields'] = 'all';
					}

					break;

				case 'slug':
				case 'name':
					$clean_args['name'] = sanitize_title( $value );
					break;

				case 'parent':
				case 'child_of':

					/* Overwrite the child_of argument */
					if ( isset( $args['get'] ) && 'all' === $args['get'] ) {
						continue;
					}

					$clean_args['post_parent'] = $value;
					break;

				case 'search':
				case 'name__like':
				case 'description__like':
					$clean_args['s'] = $value;
					break;

				case 'offset':
					$clean_args['offset'] = $value;
					break;

				case 'order':
					$clean_args['order'] = $value;
					break;

				case 'orderby':

					/* Convert the orderby value in a value that's compatible with WP_Query */
					switch ( $value ) {

						case '':
						case 'name':
						case 'count':
						case 'term_group':
							$value = 'title';
							break;

						case 'id':
							$value = 'ID';
							break;

						case 'slug':
							$value = 'name';
							break;

						case 'none':
							$value = 'none';
							break;

						default:
							$value = 'title';
							break;

					}

					$clean_args['orderby'] = $value;

					break;

			}

		}

		return apply_filters( 'wpas_product_sync_mapped_args', $clean_args );

	}

	/**
	 * Create a term object.
	 *
	 * Take a WordPress post and adapt its content to a regular
	 * taxonomy term object.
	 *
	 * @since  3.0.2
	 * @param  object $post Post
	 * @return object       Taxonomy term object
	 */
	protected function create_term_object( $post ) {

		/* Try to get the term data from the post meta */
		$term_data = get_post_meta( $post->ID, '_wpas_product_term', true );

		/* If this post doesn't have a corresponding term we create it now */
		if ( empty( $term_data ) ) {

			/* Make sure this term is not currently being inserted */
			if ( $this->is_insert_protected( $post->ID ) ) {
				return false;
			}

			$term_data = $this->insert_term( $post );

			/* If the term couldn't be insterted we return false, which will result in skipping this post */
			if ( false === $term_data ) {
				return false;
			}

		}

		/* Get the term and term taxonomy IDs */
		$term_id          = $term_data['term_id'];
		$term_taxonomy_id = $term_data['term_taxonomy_id'];

		$term = array(
			'term_id'          => $term_id,
			'name'             => $post->post_title,
			'slug'             => $post->post_name,
			'term_group'       => 0,
			'term_taxonomy_id' => $term_taxonomy_id,
			'taxonomy'         => $this->taxonomy,
			'description'      => wp_trim_words( $post->post_content, 55, ' [...]' ),
			'parent'           => $post->post_parent,
			'count'            => 0,
		);

		return (object) $term;

	}

	/**
	 * Insert a new term.
	 *
	 * Insert a term corresponding to the given post.
	 * This term is a placeholder for the post and no detail
	 * will be saved here. Instead, we use the post ID as the term
	 * name in order to retrieve the post data when necessary.
	 *
	 * @since  3.0.2
	 * @param  object        $post  Post that requires a term placeholder
	 * @return array|boolean        Array containing the term ID and term taxonomy ID on success, false on failure
	 */
	public function insert_term( $post ) {

		/* Protect from nested insertions causing an infinite loop */
		$this->protect_insert( $post->ID );

		/**
		 * Insert a new term with the post ID as the name.
		 * This will allow us to retrieve the post data.
		 * 
		 * @var array|WP_Error
		 */
		$term = wp_insert_term( $post->ID, $this->taxonomy );

		$this->unprotect_insert( $post->ID );

		if ( is_wp_error( $term ) ) {
			return false;
		}

		/* Save the term data as a post meta in order to be able to play with it from the post */
		add_post_meta( $post->ID, '_wpas_product_term', $term, true );

		return $term;

	}

	/**
	 * Prevent a term from being insterted.
	 *
	 * The wp_insert_term() function calls get_terms()
	 * which created an infinite loop. To prevent this,
	 * we add a transient while a term is begin insterted
	 * and remove it after the term was inserted.
	 *
	 * If this transient is present while a new instance of get_posts()
	 * is running we do not trigger the term insertion method.
	 *
	 * @since  3.0.2
	 * @param  integer $post_id ID of the post for wich a placeholder term is being insterted
	 * @return void
	 */
	public function protect_insert( $post_id ) {
		set_transient( 'wpas_product_term_' . $post_id, 1, 5*60 );
	}

	/**
	 * Remove the protection transient.
	 *
	 * @since  3.0.2
	 * @param  integer $post_id ID of the post for wich a placeholder term was insterted
	 * @return void
	 */
	public function unprotect_insert( $post_id ) {
		delete_transient( 'wpas_product_term_' . $post_id );
	}

	/**
	 * Checks if a term's insertion is protected.
	 *
	 * If a transient is set for this term we consider it
	 * protected for nested insertion.
	 *
	 * @since  3.0.2
	 * @param  integer  $post_id ID of the post for wich a placeholder term is being insterted
	 * @return boolean           True if the term is protected, false otherwise
	 */
	public function is_insert_protected( $post_id ) {

		if ( false === get_transient( 'wpas_product_term_' . $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get taxonomy terms.
	 *
	 * Hooked on the get_terms() function and returns the post type
	 * posts instead of the actual taxonomy terms.
	 *
	 * @since  3.0.2
	 * @param  array         $terms      Taxonomy terms
	 * @param  array|string  $taxonomies Taxonomies for wich to retrieve the terms
	 * @param  array         $args       Additional arguments
	 * @return array                     Array of term objects
	 */
	public function get_terms( $terms, $taxonomies, $args ) {

		/* If taxonomy name is string, convert to array */
		if ( ! is_array( $taxonomies ) ) {
			$taxonomies = array( $taxonomies );
		}

		foreach ( $taxonomies as $taxonomy ) {

			if ( !$this->is_product_tax( $taxonomy ) ) {
				return $terms;
			}

			/* Map the tax arge to the WP_Query args */
			$query_args = $this->map_args( $args );

			$query_defaults = array(
				'post_type'              => $this->post_type,
				'post_status'            => 'any',
				'order'                  => 'ASC',
				'orderby'                => 'title',
				'ignore_sticky_posts'    => false,
				'posts_per_page'         => -1,
				'perm'                   => 'readable',
				'no_found_rows'          => false,
				'cache_results'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			);

			$query_args = wp_parse_args( $query_args, $query_defaults );
			$query      = new WP_Query( $query_args );

			if ( isset( $query_args['wpas_get_post_count'] ) && $query_args['wpas_get_post_count'] ) {
				return $query->post_count;
			}

			if ( empty( $query->posts ) ) {
				return array();
			}

			/* This is the terms object array */
			$terms = array();

			/* Create the term object for each post */
			foreach ( $query->posts as $key => $post ) {

				/* Create the term object */
				$term = $this->create_term_object( $post );

				/* If an error occured during the insertion of the placeholder term we do not display this one */
				if ( false === $term ) {
					continue;
				}

				$terms[] = $term;
			}

			return $terms;

		}

		return $terms;

	}

	/**
	 * Get a taxonomy term.
	 *
	 * Filters the term returned by get_term() and modifies it
	 * if it belongs to the taxonomy we're keeping in sync.
	 * 
	 * @param  int|object $term     A term object
	 * @param  string     $taxonomy The taxonomy this term belongs to
	 * @return object               The original term object if the taxonomy it belongs to isn't ours, an updated object otherwise
	 */
	public function get_term( $term, $taxonomy ) {

		if ( $taxonomy !== $this->taxonomy ) {
			return $term;
		}

		/* Get the post ID */
		$post_id = intval( $term->name );

		/* Check that the post exists and that it is of the required post type */
		if ( get_post_type( $post_id ) !== $this->post_type ) {
			return $term;
		}

		/* Get the post data */
		$post = get_post( $post_id );

		/* Set the new term array */
		$new_term = (array) $term;

		/* Set the new values */
		$new_term['name']        = $post->post_title;
		$new_term['slug']        = $post->post_name;
		$new_term['description'] = wp_trim_words( $post->post_content, 55, ' [...]' );

		return (object) $new_term;

	}

	/**
	 * Delete a placeholder term.
	 *
	 * This function is used to delete a placeholder taxonomy term.
	 * It is hooked on the delete_post action in order to keep the post type
	 * and the taxonomy in sync.
	 * 
	 * @param  integer        $post_id ID of the post that's being deleted
	 * @return boolean|object          True if term was deleted, false is nothing happened and WP_Error if an error occured
	 */
	public function unsync_term( $post_id ) {

		if ( get_post_type( $post_id ) === $this->post_type ) {

			/* Get the term data from the post meta */
			$term = get_post_meta( $post_id, '_wpas_product_term', true );

			/* Delete the term */
			$delete = wp_delete_term( intval( $term['term_id'] ), $this->taxonomy, $args );

			return $delete;
		}

		return false;

	}

}