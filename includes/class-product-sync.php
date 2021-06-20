<?php
/**
 * Products synchronization.
 *
 * This class is used to keep the product taxonomy in sync
 * with any given post type. This is especially useful for
 * a site that uses an e-commerce plugin such as WooCommerce
 * or Easy Digital Downloads.
 *
 * Two synchronization modes are available: replace or append.
 * When nothing is specified, the "replace" mode is used and all
 * possibly existing terms in the synced taxonomy will be replaced
 * by the post type posts.
 *
 * If the append mode is used, existing terms of the synced taxonomy
 * will be displayed along the post type posts.
 *
 * In both cases, only the actual taxonomy terms (not the synced posts)
 * can be edited through the term edit screen. Synced terms will trigger
 * a wp_die() asking the user to modify the post directly.
 *
 * This class was inspired by the codebase of CPT-onomies
 * (https://wordpress.org/plugins/cpt-onomies/) by Rachel Carden.
 *
 * ---------------------------------------------------
 * Known issues
 * ---------------------------------------------------
 *
 * get_term_by()
 * -------------
 * This class will work with get_term_by() only if the $field used is the term ID.
 * In all other cases, get_term_by() queries the database directly and there is no filter
 * to alter the results.
 *
 * get_the_terms()
 * ---------------
 * When using get_the_terms() the synchronized terms returned are raw, meaning that the term
 * name and slug are the post type ID. It is mandatory to run the terms returned by get_the_terms()
 * through get_term() in order to correctly apply the filters to the synced terms.
 *
 * @package   Awesome Support
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
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
	 * Defines if this class completely replaces the taxonomy terms
	 * or just appends new ones to it.
	 *
	 * @since  3.0.2
	 * @var boolean
	 */
	protected $append;
	
	/**
	 * Constructor method.
	 *
	 * @since  3.0.2
	 * @param  string  $post_type Name of the post type that should be used to populate the product taxonomy
	 * @param  string  $taxonomy  The name of the taxonomy to keep in sync with the $post_type
	 * @param  boolean $append    Defines if the taxonomy terms should be replaced or if synced terms should just be append to existing terms
	 */
	public function __construct( $post_type = '', $taxonomy = '', $append = false ) {

		$this->post_type = sanitize_title( $post_type );
		$this->taxonomy  = empty( $taxonomy ) ? $this->post_type : sanitize_title( $taxonomy );
		$this->append    = $append;

		/* Only hack into the taxonomies functions if multiple products is enabled and the provided post type exists */
		if ( $this->is_multiple_products() && post_type_exists( $post_type ) ) {

			/**
			 * We need to run an initial synchronization of products
			 * for large products lists. The get_terms used in the taxonomy page
			 * only queries 10 terms per page, which means that only the first 10 items
			 * will be synced
			 */
			$sync_init = get_option( "wpas_sync_$this->post_type" );

			if ( false === $sync_init ) {
				$this->run_initial_sync();
			}

			add_filter( 'get_terms',                        array( $this, 'get_terms' ),                       1, 3 );
			add_filter( 'get_term',                         array( $this, 'get_term' ),                        1, 2 );
			add_filter( 'get_the_terms',                    array( $this, 'get_the_terms' ),                   1, 3 );
			add_action( 'init',                             array( $this, 'lock_taxonomy' ),                  12, 0 );
			add_action( 'admin_notices',                    array( $this, 'notice_locked_tax' ),              10, 0 );

			add_action( 'wp_insert_post',                   array( $this, 'sync_term' ),                      10, 3 );
			add_action( 'trashed_post',                     array( $this, 'unsync_term' ),                    10, 1 );
			add_action( 'delete_post',                      array( $this, 'unsync_term' ),                    10, 1 );

			add_action( 'wpas_system_tools_table_after',    array( $this, 'add_resync_tool' ),                11, 0 );
			add_action( 'wpas_system_tools_table_after',    array( $this, 'add_delete_tool' ),                12, 0 );
			add_action( 'wpas_system_tools_table_after',    array( $this, 'add_delete_unused_terms_tool' ),   13, 0 );

		}

	}

	/**
	 * Set the post type after instantiating the class.
	 *
	 * @param $post_type string Post type ID to set
	 *
	 * @since 3.1.7
	 */
	public function set_post_type( $post_type ) {
		if ( post_type_exists( $post_type ) ) {
			$this->post_type = $post_type;
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
					// Comment out `continue` and replaced with `break` because of a fix in PHP version 7.3
					// continue;
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
					$clean_args['posts_per_page'] = 0 === $value ? -1 : $value;
					break;

				case 'fields':

					/* @TODO Supporting more 'fields' types could be done with post processing by filtering the result manually */

					/* If the user only wants the terms count it is a special case. Set a trigger var and continue */
					if ( 'count' === $value ) {
						$clean_args['fields']              = 'ids';
						$clean_args['wpas_get_post_count'] = true; // We set wpas_get_post_count in order to know that we just need the post count
						// Comment out `continue` and replaced with `break` because of a fix in PHP version 7.3
						// continue;
						break;
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
						// Comment out `continue` and replaced with `break` because of a fix in PHP version 7.3
						// continue;
						break;
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
	 *
	 * @param  object $post Post
	 *
	 * @return boolean|object Taxonomy term object
	 */
	protected function create_term_object( $post ) {

		/* If the $post is not an object we return false to avoid triggering PHP errors */
		if ( ! is_object( $post ) ) {
			return false;
		}
		
		/* If $post is not set to one of the approved statuses return false as well */
		$statuses = $this->get_valid_post_statuses();
		if ( ! in_array( get_post_status( $post->ID ), $statuses, true  ) ) {
			return false ;
		}
		

		/* Try to get the term data from the post meta */
		$term_data = get_post_meta( $post->ID, '_wpas_product_term', true );

		/* If this post doesn't have a corresponding term we create it now */
		if ( ! $term_data ) {

			/* Make sure this term is not currently being inserted */
			if ( $this->is_insert_protected( $post->ID ) ) {
				return false;
			}


			$term_data = $this->insert_term( $post );

            /* If the term couldn't be inserted we return false, which will result in skipping this post */
			if ( false === $term_data ) {
				return false;
			}

		}

		/* Get the term and term taxonomy IDs */
        if( ! is_array($term_data) && is_a( $term_data, 'WP_Term' )) {
            $term_id          = $term_data->term_id;
            $term_taxonomy_id = $term_data->term_taxonomy_id;
        }
        else {
            $term_id          = $term_data['term_id'];
            $term_taxonomy_id = $term_data['term_taxonomy_id'];
        }

		$term = array(
			'term_id'          => $term_id,
			'post_id'          => $post->ID, // Could be handy to still have access to the post ID
			'name'             => $post->post_title,
			'slug'             => $post->post_name,
			'term_group'       => 0,
			'term_taxonomy_id' => $term_taxonomy_id,
			'taxonomy'         => $this->taxonomy,
			'description'      => wp_trim_words( $post->post_content, 55, ' [...]' ),
			'parent'           => $post->post_parent,
			'count'            => get_term_by('id', $term_id, $this->taxonomy )->count,   //0,
			'object_id'        => $post->ID, // Could be handy to still have access to the post ID
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
		update_post_meta( $post->ID, '_wpas_product_term', $term );

		return $term;

	}

	/**
	 * Prevent a term from being inserted.
	 *
	 * The wp_insert_term() function calls get_terms()
	 * which created an infinite loop. To prevent this,
	 * we add a transient while a term is begin inserted
	 * and remove it after the term was inserted.
	 *
	 * If this transient is present while a new instance of get_posts()
	 * is running we do not trigger the term insertion method.
	 *
	 * @since  3.0.2
	 * @param  integer $post_id ID of the post for which a placeholder term is being inserted
	 * @return void
	 */
	public function protect_insert( $post_id ) {
		set_transient( 'wpas_product_term_' . $post_id, 1, 5*60 );
	}

	/**
	 * Remove the protection transient.
	 *
	 * @since  3.0.2
	 * @param  integer $post_id ID of the post for which a placeholder term was inserted
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
	 * @param  integer  $post_id ID of the post for which a placeholder term is being inserted
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
	 *
	 * @param  array        $terms      Taxonomy terms
	 * @param  array|string $taxonomies Taxonomies for which to retrieve the terms
	 * @param  array        $args       Additional arguments
	 *
	 * @return array                     Array of term objects
	 */
	public function get_terms( $terms, $taxonomies, $args ) {

		/* If taxonomy name is string, convert to array */
		if ( ! is_array( $taxonomies ) ) {
			$taxonomies = array( $taxonomies );
		}

		// Check if the product taxonomy is one of the taxonomies being queried in this instance. If not, then we immediately return the unchanged terms array
		if ( ! in_array( $this->taxonomy, $taxonomies ) ) {
			return $terms;
		}

		$slug    = WPAS_eCommerce_Integration::get_instance()->plugin;
		
		// Get the list of products to include/exclude
		$raw_include =  wpas_get_option( 'support_products_' . $slug . '_include', array() ) ;
		$raw_exclude =  wpas_get_option( 'support_products_' . $slug . '_exclude', array() ) ;
		
		// Initialize empty arrays just in case the if statements below turn out to be true.
		// $raw_exclude/include in the if statements below can be empty if the user did not click SAVE on the PRODUCTS configuration tab. 
		$include = array();
		$exclude = array();
		
		if ( ! empty( $raw_include ) ) {
			$include = array_filter( $raw_include ); // Because of the "None" option, the option returns an array with an empty value if none is selected. We need to filter that
		}
		
		if ( ! empty( $raw_exclude ) ) {
			$exclude = array_filter( $raw_exclude );  // Because of the "None" option, the option returns an array with an empty value if none is selected. We need to filter that
		}

		/* Map the tax args to the WP_Query args */
		$query_args = $this->map_args( $args );

		$query_defaults = array(
			'post_type'              => $this->post_type,
			'post_status'            => $this->get_valid_post_statuses(),
			'order'                  => 'ASC',
			'orderby'                => 'title',
			'ignore_sticky_posts'    => false,
			'posts_per_page'         => - 1,
			'perm'                   => 'readable',
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		$query_args = wp_parse_args( $query_args, $query_defaults );

		if ( ! empty( $include ) ) {
			$query_args['post__in'] = $include;
		}

		if ( ! empty( $exclude ) ) {
			$query_args['post__not_in'] = $exclude;
		}

		$query = new WP_Query( $query_args );

		if ( false === get_option( "wpas_sync_$this->post_type", false ) ) {
			$this->run_initial_sync();
		}

		if ( isset( $query_args['wpas_get_post_count'] ) && $query_args['wpas_get_post_count'] ) {
			return $this->append ? $query->post_count + count( $terms ) : $query->post_count;
		}

		if ( empty( $query->posts ) ) {
			return $terms;
		}

		$index = array();
		$sort  = array(); // Used to store the orderby field from the term object.

		// Set the array_multisort() arg flags based on the supplied orderby and order args.
		if ( 'id' === $args['orderby'] ) {

			$sort_flag = SORT_NUMERIC;

		} else {

			$sort_flag = SORT_REGULAR;
		}

		if ( 'DESC' === $args['order'] ) {

			$sort_order = SORT_DESC;

		} else {

			$sort_order = SORT_ASC;
		}

		foreach ( $query->posts as $post ) {
			if( isset( $post->ID ) ) {
				$index[ $post->ID ] = $post;
			} else {
				$index[ $post ] = $post;
			}
		}

		// We will store the new terms in this array
		$new_terms = array();

		// Now go go through each term, maybe update it, and add it to the final terms array
		foreach ( $terms as $term ) {

		    // If the term is a synchronized product we build the custom term object
		    if ( $this->is_synced_term( $term ) ) {

			    $tid = is_a( $term, 'WP_Term' ) ? (int) $term->name : (int) $term;

                // Create the custom term object
                if( array_key_exists( $tid, $index ) ) {
	                $term = $this->create_term_object( $index[ $tid ] );
                }

		    }

			if ( false !== $term ) {

				$new_terms[] = apply_filters( 'wpas_get_terms_term', $term, $this->taxonomy );

				if ( 'id' === $args['orderby'] ) {

					$sort[] = (int) $term->{$args['orderby']};

				} else {

					$sort[] = strtolower( $term->{$args['orderby']} ); // Make lower case to get a natural sort since mixed case yields undesired results.
				}

			}

		}

		// Ensure terms are sorted according to the supplied args.
		array_multisort( $sort, $sort_order, $sort_flag, $new_terms );

		return apply_filters( 'wpas_get_terms', $new_terms );

	}

	/**
	 * Get a taxonomy term.
	 *
	 * Filters the term returned by get_term() and modifies it
	 * if it belongs to the taxonomy we're keeping in sync.
	 *
	 * @since  3.0.2
	 * @param  int|object $term     A term object
	 * @param  string     $taxonomy The taxonomy this term belongs to
	 * @return object               The original term object if the taxonomy it belongs to isn't ours, an updated object otherwise
	 */
	public function get_term( $term, $taxonomy = '' ) {

		if ( $taxonomy !== $this->taxonomy ) {
			return $term;
		}

		if ( false === $this->is_synced_term( $term->term_id ) ) {
			return $term;
		}

		/* Get the post ID */
		$post_id = intval( $term->name );

		/* Check that the post exists and that it is of the required post type */
		if ( get_post_type( $post_id ) !== $this->post_type ) {
			return $term;
		}

		/* Lets cache real term data */
		
		$term_data = array(
			'name'        => $term->name,     
			'slug'        => $term->slug,      
			'description' => $term->description,
			'post_id'     => $term->post_id,
		);
		
		/* Get the post data */
		$post = get_post( $post_id );

		/* Set the new values */
		$term->name        = $post->post_title;
		$term->slug        = $post->post_name;
		$term->description = wp_trim_words( $post->post_content, 55, ' [...]' );
		$term->post_id     = $post_id;
		$term->term_data   = $term_data;

		//$x = wp_cache_get( $post->ID, $term->term_id, $this->taxonomy . '_relationships' );

		//$x = wp_cache_add( $post->ID, $term->term_id, $this->taxonomy . '_relationships' );
		
		return $term;

	}

	/**
	 * Retrieve the terms of the taxonomy that are attached to the post.
	 *
	 * Hooked on get_the_terms this function will convert the placeholder terms
	 * into their actual values.
	 * 
	 * @param  array   $terms    Terms attached to this post
	 * @param  integer $post_id  Post ID
	 * @param  string  $taxonomy Taxonomy ID
	 * @return array             Updated terms
	 */
	public function get_the_terms( $terms, $post_id, $taxonomy ) {

		if ( ! $this->is_product_tax( $taxonomy ) ) {
			return $terms;
		}

		if( empty($terms) ) {
            $post_terms = wp_get_post_terms( $post_id, $taxonomy );
	       	if( empty( $post_terms ) ) {
	       	    return $terms;
            }
            $terms = array_merge( $terms, $post_terms );
        }

		foreach ( $terms as $key => $term ) {

			if ( true == $this->is_synced_term( $term->term_id ) ) {
				$terms[$key] = get_term( $term, $taxonomy );
			}

		}

		return $terms;
	}

	/**
	 * Add an AS Product taxonomy term
	 *
	 * @param $post_id
	 *
	 * @param $post
	 *
	 * @param $update
	 *
	 */
	public function sync_term( $post_id, $post, $update ) {

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		if ( get_post_type( $post_id ) !== $this->post_type ) {
		    return;
        }

		$slug    = WPAS_eCommerce_Integration::get_instance()->plugin;

		// If syncing enabled
		if( (bool) wpas_get_option( 'support_products_' . $slug, array() ) ) {

			// Get currently synced products
			$include = array_filter( wpas_get_option( 'support_products_' . $slug . '_include', array() ) ); // Because of the "None" option, the option returns an array with an empty value if none is selected. We need to filter that

            if( ! empty( $include ) ) {

                // If include list configured add this term if it doesn't exist
	            if( !in_array( (string) $post_id, $include ) ) {
		            $include[] = (string) $post_id;
		            wpas_update_option( 'support_products_' . $slug . '_include', $include );
	            }

            }

            // Create the AS Product term
            $term = $this->create_term_object( $post );

		}

	}

	/**
	 * Delete a placeholder term.
	 *
	 * This function is used to delete a placeholder taxonomy term.
	 * It is hooked on the delete_post action in order to keep the post type
	 * and the taxonomy in sync.
	 * @since  3.0.2
	 * @param  integer        $post_id ID of the post that's being deleted
	 * @return boolean|object          True if term was deleted, false is nothing happened and WP_Error if an error occurred
	 */
	public function unsync_term( $post_id ) {

		if ( get_post_type( $post_id ) === $this->post_type ) {

			/* Get the term data from the post meta */
			$term = get_post_meta( $post_id, '_wpas_product_term', true );

			/* Delete the term */
			if( ! empty( $term ) ) {
				$delete = wp_delete_term( (int) $term['term_id'], $this->taxonomy );

				if ( true === $delete ) {
					delete_post_meta( $post_id, '_wpas_product_term' );
				}

				return $delete;
			}

		}

		return false;

	}

	/**
	 * Check if the current screen displays a term belonging to our taxonomy.
	 *
	 * @since  3.0.2
	 * @return boolean True if the term belong to our tax, false otherwise
	 */
	public function is_tax_screen() {

		global $pagenow, $wpdb;

		if ( 'term.php' !== $pagenow ) {
			return false;
		}

		if ( ! isset( $_GET['tag_ID'] ) ) {
			return false;
		}

		$term_id       = intval( $_GET['tag_ID'] );
		$query         = $wpdb->prepare( "SELECT * FROM $wpdb->term_taxonomy WHERE term_id = '%d'", $term_id );
		$term_taxonomy = $wpdb->get_col( $query, 2 );

		if ( ! is_array( $term_taxonomy ) || ! isset( $term_taxonomy[0] ) ) {
			return false;
		}

		$taxonomy_name = $term_taxonomy[0];

		if ( $taxonomy_name !== $this->taxonomy ) {
			return true;
		}

		return true;
	}

	/**
	 * Check if a given term is a placeholder for a post.
	 *
	 * @since  3.0.2
	 *
	 * @param  string|int|WP_Term $term Term ID or term object
	 *
	 * @return int|boolean          True if this is a placeholder term, false otherwise
	 */
	public function is_synced_term( $term = '' ) {

		if ( ! is_a( $term, 'WP_Term' ) ) {

			global $wpdb;

			if ( ! is_numeric( $term ) ) {
				if ( isset( $_GET['tag_ID'] ) ) {
					$term = intval( $_GET['tag_ID'] );
				} else {
					return false;
				}
			}

			/* We use a SQL query because get_term() would give us a filtered result */
			$query     = $wpdb->prepare( "SELECT * FROM $wpdb->terms WHERE term_id = '%d'", $term );
			$term_name = $wpdb->get_col( $query, 1 );

			if ( ! is_array( $term_name ) || ! isset( $term_name[0] ) ) {
				return false;
			}

			$term_name = $term_name[0];

		} else {
			$term_name = $term->name;
		}

		if ( ! is_numeric( $term_name ) ) {
			return false;
		}

		$post_id = intval( $term_name );

		if ( get_post_type( $post_id ) !== $this->post_type ) {
			return false;
		}

		return $post_id;

	}

	/**
	 * Retrieve a synced term by its slug.
	 *
	 * @since  3.1.5
	 * @param  string $slug Term slug
	 * @return object       Term object
	 */
	public function get_synced_term_by_slug( $slug ) {

		$args = array(
			'name'                   => $slug,
			'post_type'              => $this->post_type,
			'post_status'            => $this->get_valid_post_statuses(),
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);
		
		$query = new WP_Query( $args );
		
		if ( ! empty( $query->post ) ) {
			$term = (object) $this->create_term_object( $query->post );
		} else {
			$term = false;
		}

		return $term;

	}

	/**
	 * Display a notice on the edit tag screen.
	 *
	 * This notice explains to the user why he can't modify it, because
	 * it is not a real term and the post should be modified instead.
	 * This is used as a fallback. Normally the screen won't even load
	 * because lock_taxonomy() will prevent it.
	 *
	 * @since  3.0.2
	 * @return void
	 */
	public function notice_locked_tax() {

		$message = apply_filters( 'wpas_taxonomy_locked_msg', sprintf( __( 'You cannot edit this term from here because it is linked to a post (of the %s post type). Please edit the post directly instead.', 'awesome-support' ), "<code>$this->post_type</code>" ) );

		if ( $this->is_tax_screen() && true == $this->is_synced_term() ) { ?>
			<div class="error">
				<p><?php echo $message; ?></p>
			</div>
		<?php }

	}

	/**
	 * Lock the term edit screen.
	 *
	 * Display a wp_die() screen if the user is trying to edit
	 * a term that is in sync with a post. This is because all modifications
	 * should be done in the post directly.
	 * 
	 * @since  3.0.2
	 * @return void
	 */
	public function lock_taxonomy() {

		$message = apply_filters( 'wpas_taxonomy_locked_msg', sprintf( __( 'You cannot edit this term from here because it is linked to a post (of the %s post type). Please edit the post directly instead.', 'awesome-support' ), "<code>$this->post_type</code>" ) );

		if ( $this->is_tax_screen() && true == $this->is_synced_term() ) {
			wp_die( $message, __( 'Term Locked', 'awesome-support' ), array( 'back_link' => true ) );
		}

	}

	/**
	 * Runs the initial synchronization of products.
	 *
	 * @since 3.1.7
	 * @return integer The number of terms synchronized
	 */
	public function run_initial_sync() {

		$slug = WPAS_eCommerce_Integration::get_instance()->plugin;

		// Get the list of products to include/exclude
		$raw_include = wpas_get_option( 'support_products_' . $slug . '_include', array() );
		$raw_exclude = wpas_get_option( 'support_products_' . $slug . '_exclude', array() );

		// Initialize empty arrays just in case the if statements below turn out to be true.
		// $raw_exclude/include in the if statements below can be empty if the user did not click SAVE on the PRODUCTS configuration tab.
		$include = array();
		$exclude = array();

		if ( ! empty( $raw_include ) ) {
			$include = array_filter( $raw_include ); // Because of the "None" option, the option returns an array with an empty value if none is selected. We need to filter that
		}

		if ( ! empty( $raw_exclude ) ) {
			$exclude = array_filter( $raw_exclude );  // Because of the "None" option, the option returns an array with an empty value if none is selected. We need to filter that
		}

		$args = array(
			'post_type'              => $this->post_type,
			'post_status'            => $this->get_valid_post_statuses(),
			'order'                  => 'ASC',
			'orderby'                => 'title',
			'ignore_sticky_posts'    => false,
			'posts_per_page'         => -1,
			'perm'                   => 'readable',
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		if ( ! empty( $include ) ) {
			$args['post__in'] = $include;
		}

		if ( ! empty( $exclude ) ) {
			$args['post__not_in'] = $exclude;
		}

		$query = new WP_Query( $args );
		$count = 0;

		/* Create the term object for each post */
		foreach ( $query->posts as $post ) {

			if ( ! is_a( $post, 'WP_Post' ) ) {
				continue;
			}

			/* Create the term object */
			$term = $this->create_term_object( $post );

			/* If the term was successfully created we increment our counter */
			if ( false !== $term ) {
				$count = get_option( "wpas_sync_$this->post_type", 0 );
				//++$count;
				update_option( "wpas_sync_$this->post_type", ++$count );
			}

		}

		// add_option( "wpas_sync_$this->post_type", $count );

		return $count;

	}

	/**
	 * Adds a button to re-sync the products in the system tools.
	 *
	 * @since 3.1.7
	 */
	public function add_resync_tool() { ?>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Re-Synchronize Products', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'resync_products', array( 'pt' => $this->post_type ) ); ?>"
				   class="button-secondary"><?php _e( 'Resync', 'awesome-support' ); ?></a>
				<span
					class="wpas-system-tools-desc"><?php _e( 'Re-synchronize all products from your e-commerce plugin. Any product not attached to an existing ticket and not matched to a product in your e-commerce system will be deleted.', 'awesome-support' ); ?></span>
			</td>
		</tr>
	<?php }

	/**
	 * Adds a button to delete the products in the system tools.
	 *
	 * @since 3.1.7
	 */
	public function add_delete_tool() { ?>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Delete Products', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'delete_products', array( 'pt' => $this->post_type ) ); ?>"
				   class="button-secondary"><?php _e( 'Delete', 'awesome-support' ); ?></a>
				<span
					class="wpas-system-tools-desc"><?php _e( 'Delete all products synchronized from your e-commerce plugin.', 'awesome-support' ); ?></span>
			</td>
		</tr>
	<?php }

	/**
	 * Adds a button to delete unused Product Terms system tools.
	 *
	 * @since 3.1.7
	 */
	public function add_delete_unused_terms_tool() { ?>
		<tr>
			<td class="row-title"><label for="tablecell"><?php _e( 'Delete unused Product Terms', 'awesome-support' ); ?></label></td>
			<td>
				<a href="<?php echo wpas_tool_link( 'delete_unused_terms', array( 'pt' => $this->post_type ) ); ?>"
				   class="button-secondary"><?php _e( 'Delete', 'awesome-support' ); ?></a>
				<span
					class="wpas-system-tools-desc"><?php _e( 'Delete all Product Terms not used in any AS ticket.', 'awesome-support' ); ?></span>
			</td>
		</tr>
	<?php }
	

	/**
	 * Gets a list of valid post statuses to sync.
	 *
	 * @since 5.8.1
	 *
	 * @return array 
	 */	
	public function get_valid_post_statuses() {
		return explode( ',' , wpas_get_option( 'support_products_statuses', 'publish' ) );
	}

}