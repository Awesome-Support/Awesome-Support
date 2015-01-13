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
 * Actually, big chuncks of her code was reused as-is in this class.
 * Thanks Rachel!
 *
 * @package   Awesome Support
 * @author    ThemeAvenue <web@themeavenue.net>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2014 ThemeAvenue
 */
class WPAS_Product_Sync {

	/**
	 * Instance of this class.
	 *
	 * @since    3.0.2
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Name of the post type to use
	 * for populating the product taxonomy.
	 *
	 * @since  3.0.2
	 * @var    string
	 */
	protected $post_type;
	
	/**
	 * Constructor method.
	 *
	 * @since  3.0.2
	 * @param  string $post_type Name of the post type that should be used to populate the product taxonomy
	 */
	public function __construct( $post_type = '' ) {

		$this->post_type = sanitize_title( $post_type );

		/* Only hack into the taxonomies functions if multiple products is enabled and the provided post type exists */
		if ( $this->is_multiple_products() && post_type_exists( $post_type ) ) {
			add_filter( 'get_terms',           array( $this, 'get_terms' ),             1, 3 );
			// add_filter( 'wp_get_object_terms', array( $this, 'wp_get_object_terms' ),   1, 4 );
			// add_filter( 'get_terms_args',      array( $this, 'adjust_get_terms_args' ), 1, 2 );
		}

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     3.0.2
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
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
		return 'product' === $taxonomy ? true : false;
	}

	public function get_terms( $terms, $taxonomies, $args ) {

		/* If taxonomy name is string, convert to array */
		if ( ! is_array( $taxonomies ) ) {
			$taxonomies = array( $taxonomies );
		}

		foreach ( $taxonomies as $taxonomy ) {

			if ( !$this->is_product_tax( $taxonomy ) ) {
				return $terms;
			}

			$exclude = array(
				'hide_empty',
				'exclude_tree',
			);

			$defaults = array(
				'orderby'           => 'name', 
				'order'             => 'ASC', 
				'exclude'           => array(), 
				''      => array(), 
				'include'           => array(),
				'number'            => -1, 
				'fields'            => 'all', 
				'slug'              => '',
				'name'              => '',
				'parent'            => '',
				'hierarchical'      => true, 
				'child_of'          => 0, 
				'get'               => '', 
				'name__like'        => '',
				'description__like' => '',
				'pad_counts'        => false, 
				'offset'            => '', 
				'search'            => '', 
				'cache_domain'      => 'core'
			);

			$query_args = array(
				//Post & Page Parameters
				'p'             => 1,
				'name'          => 'hello-world',
				'page_id'       => 1,
				'pagename'     => 'sample-page',
				'post_parent'  => 1,
				'post__in'     => array(1,2,3),
				'post__not_in' => array(1,2,3),
				
				//Author Parameters
				'author'      => '1,2,3,',
				'author_name' => 'admin',
				
				//Category Parameters
				'cat'              => 1,
				'category_name'    => 'blog',
				'category__and'    => array( 1, 2),
				'category__in'     => array(1, 2),
				'category__not_in' => array( 1, 2 ),
				
				//Type & Status Parameters
				'post_type'   => 'any',
				'post_status' => 'any',
				
				//Choose ^ 'any' or from below, since 'any' cannot be in an array
				'post_type' => array(
					'post',
					'page',
					'revision',
					'attachment',
					'my-post-type',
					),
				
				'post_status' => array(
					'publish',
					'pending',
					'draft',
					'auto-draft',
					'future',
					'private',
					'inherit',
					'trash'
					),
				
				//Order & Orderby Parameters
				'order'               => 'DESC',
				'orderby'             => 'date',
				'ignore_sticky_posts' => false,
				'year'                => 2012,
				'monthnum'            => 1,
				'w'                   => 1,
				'day'                 => 1,
				'hour'                => 12,
				'minute'              => 5,
				'second'              => 30,
				
				//Tag Parameters
				'tag'           => 'cooking',
				'tag_id'        => 5,
				'tag__and'      => array( 1, 2),
				'tag__in'       => array( 1, 2),
				'tag__not_in'   => array( 1, 2),
				'tag_slug__and' => array( 'red', 'blue'),
				'tag_slug__in'  => array( 'red', 'blue'),
				
				//Pagination Parameters
				'posts_per_page'         => 10,
				'posts_per_archive_page' => 10,
				'nopaging'               => false,
				'paged'                  => get_query_var('paged'),
				'offset'                 => 3,
				
				//Custom Field Parameters
				'meta_key'       => 'key',
				'meta_value'     => 'value',
				'meta_value_num' => 10,
				'meta_compare'   => '=',
				'meta_query'     => array(
					array(
						'key' => 'color',
						'value' => 'blue',
						'type' => 'CHAR',
						'compare' => '='
					),
					array(
						'key' => 'price',
						'value' => array( 1,200 ),
						'compare' => 'NOT LIKE'
					),
				
				//Taxonomy Parameters
				'tax_query' => array(
				'relation'  => 'AND',
					array(
						'taxonomy'         => 'color',
						'field'            => 'slug',
						'terms'            => array( 'red', 'blue' ),
						'include_children' => true,
						'operator'         => 'IN'
					),
					array(
						'taxonomy'         => 'actor',
						'field'            => 'id',
						'terms'            => array( 1, 2, 3 ),
						'include_children' => false,
						'operator'         => 'NOT IN'
					)
				),
				
				//Permission Parameters -
				'perm' => 'readable',
				
				//Parameters relating to caching
				'no_found_rows'          => false,
				'cache_results'          => true,
				'update_post_term_cache' => true,
				'update_post_meta_cache' => true,
			);
			
			$query = new WP_Query( $args );
			

			return array();

		}

		print_r( $taxonomies );

		return $terms;

	}

	public function wp_get_object_terms() {

	}

	public function adjust_get_terms_args() {

	}	

}