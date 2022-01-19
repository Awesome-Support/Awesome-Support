<?php
/**
 * Main plugin file
 *
 * @package GAS Framework
 *
 * @see lib/class-gas-framework.php
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

// Used for tracking the version used.
defined( 'GASF_VERSION' ) or define( 'GASF_VERSION', '2.0.1' );
// Used for text domains.
defined( 'GASF_I18NDOMAIN' ) or define( 'GASF_I18NDOMAIN', 'gas-framework' );
// Used for general naming, e.g. nonces.
defined( 'GASF' ) or define( 'GASF', 'gas-framework' );
// Used for general naming.
defined( 'GASF_NAME' ) or define( 'GASF_NAME', 'GAS Framework' );
// Used for file includes.
defined( 'GASF_PATH' ) or define( 'GASF_PATH', trailingslashit( dirname( __FILE__ ) ) );
// Used for testing and checking plugin slug name.
defined( 'GASF_PLUGIN_BASENAME' ) or define( 'GASF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once( GASF_PATH . 'lib/class-admin-notification.php' );
require_once( GASF_PATH . 'lib/class-admin-page.php' );
require_once( GASF_PATH . 'lib/class-admin-tab.php' );
require_once( GASF_PATH . 'lib/class-customizer.php' );
require_once( GASF_PATH . 'lib/class-meta-box.php' );
require_once( GASF_PATH . 'lib/class-option.php' );
require_once( GASF_PATH . 'lib/class-option-ajax-button.php' );
require_once( GASF_PATH . 'lib/class-option-checkbox.php' );
require_once( GASF_PATH . 'lib/class-option-code.php' );
require_once( GASF_PATH . 'lib/class-option-color.php' );
require_once( GASF_PATH . 'lib/class-option-custom.php' );
require_once( GASF_PATH . 'lib/class-option-edd-license.php' );
require_once( GASF_PATH . 'lib/class-option-date.php' );
require_once( GASF_PATH . 'lib/class-option-enable.php' );
require_once( GASF_PATH . 'lib/class-option-editor.php' );
require_once( GASF_PATH . 'lib/class-option-font.php' );
require_once( GASF_PATH . 'lib/class-option-gallery.php' );
require_once( GASF_PATH . 'lib/class-option-group.php' );
require_once( GASF_PATH . 'lib/class-option-heading.php' );
require_once( GASF_PATH . 'lib/class-option-iframe.php' );
require_once( GASF_PATH . 'lib/class-option-multicheck.php' );
require_once( GASF_PATH . 'lib/class-option-multicheck-categories.php' );
require_once( GASF_PATH . 'lib/class-option-multicheck-pages.php' );
require_once( GASF_PATH . 'lib/class-option-multicheck-posts.php' );
require_once( GASF_PATH . 'lib/class-option-multicheck-post-types.php' );
require_once( GASF_PATH . 'lib/class-option-note.php' );
require_once( GASF_PATH . 'lib/class-option-number.php' );
require_once( GASF_PATH . 'lib/class-option-radio.php' );
require_once( GASF_PATH . 'lib/class-option-radio-image.php' );
require_once( GASF_PATH . 'lib/class-option-radio-palette.php' );
require_once( GASF_PATH . 'lib/class-option-save.php' );
require_once( GASF_PATH . 'lib/class-option-select.php' );
require_once( GASF_PATH . 'lib/class-option-select-categories.php' );
require_once( GASF_PATH . 'lib/class-option-select-pages.php' );
require_once( GASF_PATH . 'lib/class-option-select-posts.php' );
require_once( GASF_PATH . 'lib/class-option-select-post-types.php' );
require_once( GASF_PATH . 'lib/class-option-select-users.php' );
require_once( GASF_PATH . 'lib/class-option-separator.php' );
require_once( GASF_PATH . 'lib/class-option-sortable.php' );
require_once( GASF_PATH . 'lib/class-option-text.php' );
require_once( GASF_PATH . 'lib/class-option-textarea.php' );
require_once( GASF_PATH . 'lib/class-option-upload.php' );
require_once( GASF_PATH . 'lib/class-option-file.php' );
require_once( GASF_PATH . 'lib/class-gas-css.php' );
require_once( GASF_PATH . 'lib/class-gas-framework.php' );
require_once( GASF_PATH . 'lib/class-wp-customize-control.php' );
require_once( GASF_PATH . 'lib/functions-googlefonts.php' );
require_once( GASF_PATH . 'lib/functions-utils.php' );

/**
 * GAS Framework Plugin Class
 *
 * @since 1.0
 */
class GASFrameworkPlugin {


	/**
	 * Constructor, add hooks
	 *
	 * @since 1.0
	 */
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );
		add_action( 'plugins_loaded', array( $this, 'force_load_first' ), 10, 1 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 2 );

		// Create the options.
		add_action( 'init', array( $this, 'trigger_option_creation' ), 1 );
	}


	/**
	 * Trigger the creation of the options
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return void
	 */
	public function trigger_option_creation() {

		/**
		 * Triggers the creation of options. Hook into this action and use the various create methods.
		 *
		 * @since 1.0
		 */
		do_action( 'tf_create_options' );

		/**
		 * Fires immediately after options are created.
		 *
		 * @since 1.8
		 */
		do_action( 'tf_done' );
	}


	/**
	 * Load plugin translations
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( GASF_I18NDOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Forces our plugin to be loaded first. This is to ensure that plugins that use the framework have access to
	 * this class from almost anywhere
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param array $plugins List of plugins loaded.
	 *
	 * @return array Modified list of plugins.
	 *
	 * @see	initially based on http://snippets.khromov.se/modify-wordpress-plugin-load-order/
	 */
	public function force_load_first( $plugins = null ) {
		$plugins = null === $plugins ? (array) get_option( 'active_plugins' ) : $plugins;

		if ( ! empty( $plugins ) ) {
			$index = array_search( GASF_PLUGIN_BASENAME, $plugins );
			if ( false !== $index && 0 !== $index ) {
				array_splice( $plugins, $index, 1 );
				array_unshift( $plugins, GASF_PLUGIN_BASENAME );
				update_option( 'active_plugins', $plugins );
			}
		}

		return $plugins;
	}


	/**
	 * Adds links to the docs and GitHub
	 *
	 * @since 1.1.1
	 * @access public
	 *
	 * @param	array  $plugin_meta The current array of links.
	 * @param	string $plugin_file The plugin file.
	 * @return	array  The current array of links together with our additions
	 **/
	public function plugin_links( $plugin_meta, $plugin_file ) {
		if ( GASF_PLUGIN_BASENAME === $plugin_file ) {
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				'#',
				__( 'Documentation', GASF_I18NDOMAIN )
			);
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				'https://github.com/tednh/GAS-Framework',
				__( 'GitHub Repo', GASF_I18NDOMAIN )
			);
			$plugin_meta[] = sprintf( "<a href='%s' target='_blank'>%s</a>",
				'https://github.com/tednh/GAS-Framework/issues',
				__( 'Issue Tracker', GASF_I18NDOMAIN )
			);
		}
		return $plugin_meta;
	}
}


new GASFrameworkPlugin();
