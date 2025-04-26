<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class GASFrameworkOptionSave extends GASFrameworkOption {

	public $defaultSecondarySettings = array(
		'save' => '',
		'reset' => '',
		'use_reset' => true,
		'reset_question' => '',
		'action' => 'save',
	);

	public function display() {
		if ( ! empty( $this->owner->postID ) ) {
			return;
		}

		if ( empty( $this->settings['save'] ) ) {
			$this->settings['save'] = __( 'Save Changes', 'awesome-support' );
		}
		if ( empty( $this->settings['reset'] ) ) {
			$this->settings['reset'] = __( 'Reset to Defaults', 'awesome-support' );
		}
		if ( empty( $this->settings['reset_question'] ) ) {
			$this->settings['reset_question'] = __( 'Are you sure you want to reset ALL options to their default values?', 'awesome-support' );
		}

		?>
		</tbody>
		</table>

		<p class='submit'>
			<button name="action" value="<?php echo wp_kses_post($this->settings['action']) ?>" class="button button-primary">
				<?php echo wp_kses_post($this->settings['save']) ?>
			</button>

			<?php
			if ( $this->settings['use_reset'] ) :
			?>
			<button name="action" class="button button-secondary"
				onclick="javascript: if ( confirm( '<?php echo wp_kses_post(htmlentities( esc_attr( $this->settings['reset_question'] ) )) ?>' ) ) { jQuery( '#tf-reset-form' ).submit(); } jQuery(this).blur(); return false;">
				<?php echo wp_kses_post($this->settings['reset']) ?>
			</button>
			<?php
			endif;
			?>
		</p>

		<table class='form-table'>
			<tbody>
		<?php
	}
}
