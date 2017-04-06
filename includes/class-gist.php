<?php
/**
 * Gist oEmbed Support.
 *
 * This class is will add oEmbed support for Gist, which can prove
 * very useful for providing digital products support.
 *
 * This class was inspired by the codebase of oEmbed Gist
 * (https://github.com/miya0001/oembed-gist) by Takayuki Miyauchi.
 *
 * @package   Awesome Support
 * @author    AwesomeSupport <contact@getawesomesupport.com>
 * @license   GPL-2.0+
 * @link      https://getawesomesupport.com
 * @copyright 2014-2017 AwesomeSupport
 * @since     3.1.3
 */

// Register Gist support
add_action( 'plugins_loaded', array( 'WPAS_Gist', 'get_instance' ), 11, 0 );

class WPAS_Gist {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Gist regex.
	 *
	 * @var  string The regex used to convert a Gist URL
	 */
	private $regex = '#(https://gist.github.com/([^\/]+\/)?([a-zA-Z0-9]+)(\/[a-zA-Z0-9]+)?)(\#file(\-|_)(.+))?$#i';

	public function __construct() {
		$this->register();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
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
	 * Get Gist regex.
	 *
	 * @since  3.1.3
	 * @return string Regex
	 */
	public function get_regex() {
		return $this->regex;
	}

	/**
	 * Register the Gist handler.
	 *
	 * @since   3.1.3
	 * @return  void
	 */
	protected function register() {
		wp_embed_register_handler( 'gist', $this->get_regex(), array( $this, 'handler' ), 10 );
	}

	/**
	 * oEmbed handler.
	 * 
	 * @since  3.1.3
	 * @param  array  $matches Matches from the regex
	 * @param  array  $attr    oEmbed attributes
	 * @param  string $url     Parsed URL
	 * @param  array  $rawattr Raw attributes
	 * @return string          Embed code
	 */
	public function handler( $matches, $attr, $url, $rawattr ) {

		/**
		 * Check if a file is specified. If not we set this match as null.
		 */
		if ( ! isset( $matches[7] ) || ! $matches[7] ) {
			$matches[7] = null;
		}

		$url  = $matches[1];  // Gist full URL
		$file = $matches[7];  // Gist file
		$url  = $url . '.js'; // Append the .js extension

		/* Possibly add the file name within the Gist */
		if ( ! empty( $file ) ) {
			$file = preg_replace( '/[\-\.]([a-z]+)$/', '.\1', $file );
			$url = $url . '?file=' . $file;
		}

		$noscript = sprintf( __( 'View the code on <a href="%s">Gist</a>.', 'awesome-support' ), esc_url( $url ) );
		$embed = sprintf( '<div class="oembed-gist"><script src="%s"></script><noscript>%s</noscript></div>', $url, $noscript );

		return apply_filters( 'embed_gist', $embed, $matches, $attr, $url, $rawattr );

	}

}