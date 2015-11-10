<?php
/**
 * WP Editor Ajax.
 *
 * Loads an instance of TinyMCE via Ajax call.
 * This class will only work if an instance of TinyMCE already exists
 * in the page where we want to load the new one.
 *
 * This class is a cleaner and more optimized version of
 * http://wordpress.stackexchange.com/questions/70548/load-tinymce-wp-editor-via-ajax/71256#71256
 *
 * ---------------------------------------------------------
 * Known issues
 * ---------------------------------------------------------
 *
 * QuickTags, even though correctly loaded in the JS object, can't be used
 * because of a bug in WordPress core. For that reason, QuickTags is deactivated
 * all together in wp_editor().
 *
 * @link https://core.trac.wordpress.org/ticket/26183
 */

/**
 * Load the WP Editor Ajax class.
 */
add_action( 'plugins_loaded', array( 'WPAS_Editor_Ajax', 'get_instance' ), 11, 0 );

class WPAS_Editor_Ajax {

	/**
	 * Instance of this class.
	 *
	 * @since  3.1.5
	 * @var    object
	 */
	protected static $instance = null;

	/**
	 * TinyMCE Settings.
	 *
	 * @since  3.1.5
	 * @var    string
	 */
	private $mce_settings = null;

	/**
	 * QuickTags Settings.
	 *
	 * @since  3.1.5
	 * @var    string
	 */
	private $qt_settings = null;

	public function __construct() {

		/**
		 * Get TinyMCE and QuickTags initial settings.
		 */
		add_filter( 'tiny_mce_before_init', array( $this, 'get_tinymce_settings'),    10, 2 );
		add_filter( 'quicktags_settings',   array( $this, 'get_quicktags_settings' ), 10, 2 );

		/**
		 * Add new settings
		 */
		// add_filter( 'wpas_ajax_editor_tinymce_settings', array( $this, 'add_instance_callback' ), 10, 1 );

		/**
		 * Ajax calls to load the editor.
		 */
		add_action( 'wp_ajax_wp_editor_ajax',         array( $this, 'editor_html' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_wp_editor_ajax',  array( $this, 'editor_html' ), 10, 0 );
		add_action( 'wp_ajax_wp_editor_content_ajax', array( $this, 'get_content' ), 10, 0 );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since  3.1.5
	 * @return object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load TinyMCE.
	 *
	 * Loads a new instance of TinyMCE and extend
	 * the JS object that instantiate the editor.
	 *
	 * @since  3.1.5
	 */
	public function editor_html() {

		$post_id   = filter_input( INPUT_POST, 'post_id',         FILTER_SANITIZE_NUMBER_INT );
		$editor_id = filter_input( INPUT_POST, 'editor_id',       FILTER_SANITIZE_STRING );
		$name      = filter_input( INPUT_POST, 'textarea_name',   FILTER_SANITIZE_STRING );
		$settings  = (array) filter_input( INPUT_POST, 'editor_settings', FILTER_UNSAFE_RAW);

		if ( empty( $editor_id ) ) {
			wpas_debug_display( __( 'An editor ID is mandatory to load a new instance of TinyMCE', 'awesome-support' ) );
			die;
		}

		/**
		 * If we got a post id then we gather the rest of the data from here.
		 */
		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
		}

		/**
		 * Get the content and filter it.
		 */
		$content = ( isset( $post ) && ! empty( $post ) ) ? $post->post_content : filter_input( INPUT_POST, 'editor_content', FILTER_SANITIZE_STRING );
		$content = apply_filters( 'the_content', $content );

		/**
		 * Filter the user settings for the editor
		 */
		$settings = $this->get_editor_settings( $settings );

		/**
		 * Force QuickTags to false due to the WordPress bug
		 */
		$settings['quicktags'] = false;

		/**
		 * Make sure we have a textarea name
		 */
		if ( ! isset( $settings['textarea_name'] ) || empty( $settings['textarea_name'] ) ) {
			$settings['textarea_name'] = ! empty( $name ) ? $name : $editor_id;
		}

		/**
		 * Load a new instance of TinyMCE.
		 */
		wp_editor( $content, $editor_id, $settings );

		/**
		 * Update the TinyMCE and QuickTags pre-init objects.
		 */
		$mce_init = $this->get_mce_init( $editor_id );
		$qt_init  = $this->get_qt_init( $editor_id ); ?>

		<script type="text/javascript">
			tinyMCEPreInit.mceInit = jQuery.extend( tinyMCEPreInit.mceInit, <?php echo $mce_init ?>);
			tinyMCEPreInit.qtInit = jQuery.extend( tinyMCEPreInit.qtInit, <?php echo $qt_init ?>);
		</script>

		<?php die();
	}

	/**
	 * Get the content of a post.
	 *
	 * @since  3.1.5
	 * @return void
	 */
	public function get_content() {

		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $post_id ) ) {
			echo '';
			die();
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			echo '';
			die();
		}

		echo apply_filters( 'the_content', $post->post_content );
		die();
	}

	/**
	 * Filter the editor settings.
	 *
	 * @since  3.1.5
	 * @param  array $settings Editor user settings
	 * @return array           Filtered settings
	 */
	protected function get_editor_settings( $settings ) {

		$allowed = array(
			'wpautop',
			'media_buttons',
			'textarea_name',
			'textarea_rows',
			'tabindex',
			'editor_css',
			'editor_class',
			'teeny',
			'dfw',
			'quicktags',
			'drag_drop_upload',
		);

		foreach ( $settings as $setting => $value ) {
			if ( ! array_key_exists( $setting, $allowed ) ) {
				unset( $settings[$setting] );
			}
		}

		return $settings;

	}

	/**
	 * Get QuickTags initial settings.
	 *
	 * We won't modify the settings at all here, we just need to grab
	 * the initial settings.
	 *
	 * @since  3.1.5
	 * @param  string $qtInit    Initial QuickTags settings
	 * @param  string $editor_id Editor ID
	 * @return string            Unmodified settings
	 */
	public function get_quicktags_settings( $qtInit, $editor_id ) {
		$this->qt_settings = apply_filters( 'wpas_ajax_editor_quicktags_settings', $qtInit );
		return apply_filters( 'wpas_ajax_editor_quicktags_settings', $qtInit );
	}

	/**
	 * Get TinyMCE initial settings.
	 *
	 * We won't modify the settings at all here, we just need to grab
	 * the initial settings.
	 *
	 * @since  3.1.5
	 * @param  string $qtInit    Initial TinyMCE settings
	 * @param  string $editor_id Editor ID
	 * @return string            Unmodified settings
	 */
	public function get_tinymce_settings( $mceInit, $editor_id ) {
		$this->mce_settings = apply_filters( 'wpas_ajax_editor_tinymce_settings', $mceInit );
		return apply_filters( 'wpas_ajax_editor_tinymce_settings', $mceInit );
	}

	/**
	 * Get the init settings based on the existing ones.
	 *
	 * @since  3.1.5
	 */
	private function get_qt_init( $editor_id ) {

		if ( ! empty( $this->qt_settings ) ) {
			$options = $this->_parse_init( $this->qt_settings );
			$qtInit  = "'$editor_id':{$options},";
			$qtInit  = '{' . trim( $qtInit, ',' ) . '}';
		} else {
			$qtInit = '{}';
		}

		return $qtInit;

	}

	/**
	 * Get the init settings based on the existing ones.
	 *
	 * @since  3.1.5
	 */
	private function get_mce_init( $editor_id ) {

		if ( ! empty( $this->mce_settings ) ) {
			$options = $this->_parse_init( $this->mce_settings );
			$mceInit = "'$editor_id':{$options},";
			$mceInit = '{' . trim($mceInit, ',') . '}';
		} else {
			$mceInit = '{}';
		}

		return $mceInit;

	}

	/**
	 * Parse TinyMCE and QuickTags settings
	 * from the existing array.
	 *
	 * @since  3.1.5
	 * @param  array  $init Existing settings
	 * @return string       Stringified options
	 */
	private function _parse_init( $init ) {

		$options = '';

		foreach ( $init as $k => $v ) {
			if ( is_bool($v) ) {
				$val = $v ? 'true' : 'false';
				$options .= $k . ':' . $val . ',';
				continue;
			} elseif ( !empty($v) && is_string($v) && ( ('{' == $v{0} && '}' == $v{strlen($v) - 1}) || ('[' == $v{0} && ']' == $v{strlen($v) - 1}) || preg_match('/^\(?function ?\(/', $v) ) ) {
				$options .= $k . ':' . $v . ',';
				continue;
			}
			$options .= $k . ':"' . $v . '",';
		}

		return '{' . trim( $options, ' ,' ) . '}';

	}

	/**
	 * Add an init callback to the TinyMCE settings.
	 * http://stackoverflow.com/a/17934723/1414881
	 *
	 * @since  3.1.5
	 * @param  array $settings Original TinyCME settings
	 * @return array           Settings containing our init callback
	 */
	public function add_instance_callback( $settings ) {
		$settings['setup'] = 'function(ed) {
			ed.on("init", getEditorContent(ed));
		}';
		return $settings;
	}

}