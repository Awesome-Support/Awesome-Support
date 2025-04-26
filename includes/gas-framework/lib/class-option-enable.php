<?php
/**
 * Enable option
 *
 * @package GAS Framework
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

/**
 * Enable Option
 *
 * A heading for separating your options in an admin page or meta box
 *
 * <strong>Creating a heading option with a description:</strong>
 * <pre>$adminPage->createOption( array(
 *     'name' => __( 'Enable Feature', 'default' ),
 *     'type' => 'enable',
 *     'default' => true,
 *     'desc' => __( 'You can disable this feature if you do not like it', 'default' ),
 * ) );</pre>
 *
 * @since 1.0
 * @type enable
 * @availability Admin Pages|Meta Boxes|Customizer
 */
class GASFrameworkOptionEnable extends GASFrameworkOption {

	private static $firstLoad = true;

	/**
	 * Default settings specific for this option
	 * @var array
	 */
	public $defaultSecondarySettings = array(

		/**
		 * (Optional) The label to display in the enable portion of the buttons
		 *
		 * @since 1.0
		 * @var string
		 */
		'enabled' => '',

		/**
		 * (Optional) The label to display in the disable portion of the buttons
		 *
		 * @since 1.0
		 * @var string
		 */
		'disabled' => '',
	);

	/*
	 * Display for options and meta
	 */
	public function display() {
		$this->echoOptionHeader();

		if ( empty( $this->settings['enabled'] ) ) {
			$this->settings['enabled'] = __( 'Enabled', 'awesome-support' );
		}
		if ( empty( $this->settings['disabled'] ) ) {
			$this->settings['disabled'] = __( 'Disabled', 'awesome-support' );
		}

		?>
		<input name="<?php echo esc_attr($this->getID()) ?>" type="checkbox" id="<?php echo esc_attr($this->getID()) ?>" value="1" <?php checked( $this->getValue(), 1 ) ?>>
		<span class="button button-<?php echo checked( $this->getValue(), 1, false ) ? 'primary' : 'secondary' ?>"><?php echo wp_kses_post($this->settings['enabled'] )?></span><span class="button button-<?php echo checked( $this->getValue(), 1, false ) ? 'secondary' : 'primary' ?>"><?php echo wp_kses_post($this->settings['disabled']) ?></span>
		<?php

		// load the javascript to init the colorpicker
		if ( self::$firstLoad ) :
			?>
			<script>
			jQuery(document).ready(function($) {
				"use strict";
				$('body').on('click', '.tf-enable .button-secondary', function() {
					$(this).parent().find('.button').toggleClass('button-primary button-secondary');
					var checkBox = $(this).parents('.tf-enable').find('input');
					if ( checkBox.is(':checked') ) {
						checkBox.removeAttr('checked');
					} else {
						checkBox.attr('checked', 'checked');
					}
					checkBox.trigger('change');
				});
			});
			</script>
			<?php
		endif;

		$this->echoOptionFooter();

		self::$firstLoad = false;
	}

	public function cleanValueForSaving( $value ) {
		return $value != '1' ? '0' : '1';
	}

	public function cleanValueForGetting( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return $value == '1' ? true : false;
	}

	/*
	 * Display for theme customizer
	 */
	public function registerCustomizerControl( $wp_customize, $section, $priority = 1 ) {
		$wp_customize->add_control( new GASFrameworkOptionEnableControl( $wp_customize, $this->getID(), array(
			'label' => $this->settings['name'],
			'section' => $section->settings['id'],
			'settings' => $this->getID(),
			'description' => $this->settings['desc'],
			'priority' => $priority,
			'options' => $this->settings,
		) ) );
	}
}


/*
 * We create a new control for the theme customizer
 */
add_action( 'customize_register', 'registerGASFrameworkOptionEnableControl', 1 );
function registerGASFrameworkOptionEnableControl() {
	class GASFrameworkOptionEnableControl extends WP_Customize_Control {
		public $description;
		public $options;

		private static $firstLoad = true;

		public function render_content() {

			if ( empty( $this->options['enabled'] ) ) {
				$this->options['enabled'] = __( 'Enabled', 'awesome-support' );
			}
			if ( empty( $this->options['disabled'] ) ) {
				$this->options['disabled'] = __( 'Disabled', 'awesome-support' );
			}
			?>
			<div class='tf-enable'>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<input type="checkbox" value="1" <?php $this->link(); ?>>
				<span class="button button-<?php echo checked( $this->value(), 1, false ) ? 'primary' : 'secondary' ?>"><?php echo wp_kses_post($this->options['enabled']) ?></span><span class="button button-<?php echo checked( $this->value(), 1, false ) ? 'secondary' : 'primary' ?>"><?php echo wp_kses_post($this->options['disabled']) ?></span>
			</div>
			<?php

			echo "<p class='description'>" . wp_kses_post($this->description) . "</p>";

			// load the javascript to init the colorpicker
			if ( self::$firstLoad ) :
				?>
				<script>
				jQuery(document).ready(function($) {
					"use strict";
					$('body').on('click', '.tf-enable .button-secondary', function() {
						$(this).parent().find('.button').toggleClass('button-primary button-secondary');
						var checkBox = $(this).parents('.tf-enable').find('input');
						if ( checkBox.is(':checked') ) {
							checkBox.removeAttr('checked');
						} else {
							checkBox.attr('checked', 'checked');
						}
						checkBox.trigger('change');
					});
				});
				</script>
				<?php
			endif;

			self::$firstLoad = false;
		}
	}
}
