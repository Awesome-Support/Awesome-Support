<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
class GASFrameworkAdminPage {

	private $defaultSettings = array(
		'name' => '', // Name of the menu item
		'menu_title' => '', // Title of the menu item
		'title' => '', // Title displayed on the top of the admin panel
		'parent' => null, // id of parent, if blank, then this is a top level menu
		'id' => '', // Unique ID of the menu item
		'capability' => 'manage_options', // User role
		'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only http://melchoyce.github.io/dashicons/
		'position' => null, // Menu position. Can be used for both top and sub level menus
		'use_form' => true, // If false, options will not be wrapped in a form
		'desc' => '', // Description displayed below the title
	);

	public $settings = array();
	public $options = array();
	public $tabs = array();
	public $owner;

	public $panelID;

	private $activeTab = null;
	private static $idsUsed = array();

	function __construct( $settings, $owner ) {
		$this->owner = $owner;

		if ( ! is_admin() ) {
			return;
		}

		$this->settings = array_merge( $this->defaultSettings, $settings );
		// $this->options = $options;
		if ( empty( $this->settings['name'] ) ) {
			return;
		}

		if ( empty( $this->settings['title'] ) ) {
			$this->settings['title'] = $this->settings['name'];
		}

		if ( empty( $this->settings['menu_title'] ) ) {
			$this->settings['menu_title'] = $this->settings['title'];
		}

		if ( empty( $this->settings['id'] ) ) {
			$prefix = '';
			if ( ! empty( $this->settings['parent'] ) ) {
				$prefix = str_replace( ' ', '-', trim( strtolower( $this->settings['parent'] ) ) ) . '-';
			}
			$this->settings['id'] = $prefix . str_replace( ' ', '-', trim( strtolower( $this->settings['name'] ) ) );
			$this->settings['id'] = str_replace( '&', '-', $this->settings['id'] );
		}

		// make sure all our IDs are unique
		$suffix = '';
		while ( in_array( $this->settings['id'] . $suffix, self::$idsUsed ) ) {
			if ( $suffix == '' ) {
				$suffix = 2;
			} else {
				$suffix++;
			}
		}
		$this->settings['id'] .= $suffix;

		// keep track of all IDs used
		self::$idsUsed[] = $this->settings['id'];

		$priority = -1;
		if ( $this->settings['parent'] ) {
			$priority = intval( $this->settings['position'] );
		}

		add_action( 'admin_menu', array( $this, 'register' ), $priority );
	}

	public function createAdminPanel( $settings ) {
		$settings['parent'] = $this->settings['id'] ?? null;
		return $this->owner->createAdminPanel( $settings );
	}

	public function createSampleContentPage( $settings ) {
		$settings['parent'] = $this->settings['id'] ?? null;
		return $this->owner->createSampleContentPage( $settings );
	}

	public function register() {

		// Parent menu
		if ( empty( $this->settings['parent'] ) ) {
			$this->panelID = add_menu_page( $this->settings['name'],
				$this->settings['menu_title'],
				$this->settings['capability'],
				$this->settings['id'],
				array( $this, 'createAdminPage' ),
				$this->settings['icon'],
			$this->settings['position'] );
			// Sub menu
		} else {
			$this->panelID = add_submenu_page( $this->settings['parent'],
				$this->settings['name'],
				$this->settings['menu_title'],
				$this->settings['capability'],
				$this->settings['id'],
			array( $this, 'createAdminPage' ) );
		}

		add_action( 'load-' . $this->panelID, array( $this, 'saveOptions' ) );

		add_action( 'load-' . $this->panelID, array( $this, 'addGasCredit' ) );
	}


	public function addGasCredit() {
		add_filter( 'admin_footer_text', array( $this, 'addGasCreditText' ) );
	}


	public function addGasCreditText() {
		return __( "<em>Options Page Created with <a href='#'>Gas Framework</a></em>", 'awesome-support' );
	}


	public function getOptionNamespace() {
		return $this->owner->optionNamespace;
	}

	private function gas_sanitize_array($array) {
	    // Check if the input is an array
	    if (is_array($array)) {
	        // Loop through each element of the array
	        foreach ($array as $key => $value) {
	            // If the element is an array, recursively sanitize it
	            if (is_array($value)) {
	                $array[$key] = $this->gas_sanitize_array($value);
	            } else {
	                // Otherwise, sanitize the text field
	                $array[$key] = sanitize_text_field( wp_unslash( $value) );
	            }
	        }
	    }
	    return $array;
	}

	public function save_single_option( $option ) {
		
		if ( empty( $option->settings['id'] ) ) {
			return;
		}

		$value = '';	
		if ( isset( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) ) {

			if ( !empty( $option->settings['type'] ) ) {	
				
				switch ( $option->settings['type'] ) {

					case 'heading':	
					case 'note':
					case 'text':
					case 'checkbox':
					case 'radio':
					case 'number':
					case 'upload':
					case 'color':
					case 'custom':
					case 'warningmessage':
					case 'enable': //awesome-support-tasks-and-todos , awesome-support-service-level-agreements
					case 'date':  //awesome-support-service-level-agreements
					case 'email-test-config': //awesome-support-email-support
						$value = sanitize_text_field( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) );
					    break;

					case 'select':
					    if( !is_array( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) )
						{
							$value = sanitize_text_field( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) );
						}
						else
						{
							$value = array_map('sanitize_text_field' , wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ]  ) );
						}					    
					    break;
					
					case 'textarea':
					    $value = sanitize_textarea_field( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) );
					    break;					
					  
					case 'editor':					    
						$value = wp_kses_post( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ));		
					    break;						    
					
					case 'multicheck':
					    //reports-and-statistics
						if( !is_array( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) )
						{
							$value = sanitize_text_field( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) );
						}
						else
						{
							$value = array_map('sanitize_text_field' , wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ]  ) );
						}					    
					    break;
					case 'multi-checkbox-options':
					    //agent-front-end
						if( !is_array( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) )
						{
							$value = sanitize_text_field( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) );
						}
						else
						{
							$value = $this->gas_sanitize_array( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ]);	
						}
						break;
					default:
					    $value = sanitize_text_field( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) );
				}

			}
			else
			{
				if( !is_array( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) )
				{
					$value = sanitize_text_field( wp_unslash( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ] ) );
					
				}
				else
				{
					$value = $this->gas_sanitize_array( $_POST[ $this->getOptionNamespace() . '_' . $option->settings['id'] ]);	
				}
			}
		}

		$option->setValue( $value );
	}

	public function saveOptions() {
		if ( ! $this->verifySecurity() ) {
			return;
		}

		$message = '';
		$activeTab = $this->getActiveTab();

		/*
		 *  Save
		 */

		if ( isset($_POST['action']) && $_POST['action'] == 'save' ) {

			// we are in a tab
			if ( ! empty( $activeTab ) ) {
				foreach ( $activeTab->options as $option ) {
					
					$this->save_single_option( $option );

					if ( ! empty( $option->options ) ) {
						foreach ( $option->options as $group_option ) {												
							$this->save_single_option( $group_option );
						}
					}
				}
			}

			foreach ( $this->options as $option ) {
				$this->save_single_option( $option );

				if ( ! empty( $option->options ) ) {
					foreach ( $option->options as $group_option ) {
						$this->save_single_option( $group_option );
					}
				}
			}

			// Hook 'tf_pre_save_options_{namespace}' - action pre-saving
			/**
			 * Fired right before options are saved.
			 *
			 * @since 1.0
			 *
			 * @param GasFrameworkAdminPage|GasFrameworkCustomizer|GasFrameworkMetaBox $this The container currently being saved.
			 */
			$namespace = $this->getOptionNamespace();
			do_action( "tf_pre_save_options_{$namespace}", $this );
			do_action( "tf_pre_save_admin_{$namespace}", $this, $activeTab, $this->options );

			$this->owner->saveInternalAdminPageOptions();

			do_action( 'tf_save_admin_' . $this->getOptionNamespace(), $this, $activeTab, $this->options );

			$message = 'saved';

			/*
			* Reset
			*/

		} else if ( isset( $_POST['action'] ) && $_POST['action'] == 'reset' ) {

			// we are in a tab
			if ( ! empty( $activeTab ) ) {
				foreach ( $activeTab->options as $option ) {

					if ( ! empty( $option->options ) ) {
						foreach ( $option->options as $group_option ) {

							if ( ! empty( $group_option->settings['id'] ) ) {
								$group_option->setValue( $group_option->settings['default'] );
							}
						}
					}

					if ( empty( $option->settings['id'] ) ) {
						continue;
					}

					$option->setValue( $option->settings['default'] );
				}
			}

			foreach ( $this->options as $option ) {

				if ( ! empty( $option->options ) ) {
					foreach ( $option->options as $group_option ) {

						if ( ! empty( $group_option->settings['id'] ) ) {
							$group_option->setValue( $group_option->settings['default'] );
						}
					}
				}

				if ( empty( $option->settings['id'] ) ) {
					continue;
				}

				$option->setValue( $option->settings['default'] );
			}

			// Hook 'tf_pre_reset_options_{namespace}' - action pre-saving
			do_action( 'tf_pre_reset_options_' . $this->getOptionNamespace(), $this );
			do_action( 'tf_pre_reset_admin_' . $this->getOptionNamespace(), $this, $activeTab, $this->options );

			$this->owner->saveInternalAdminPageOptions();

			do_action( 'tf_reset_admin_' . $this->getOptionNamespace(), $this, $activeTab, $this->options );

			$message = 'reset';
		}

		/*
		 * Redirect to prevent refresh saving
		 */

		// urlencode to allow special characters in the url
		$url = wp_get_referer();
		$activeTab = $this->getActiveTab();
		$url = add_query_arg( 'page', urlencode( $this->settings['id'] ), $url );
		if ( ! empty( $activeTab ) ) {
			$url = add_query_arg( 'tab', urlencode( $activeTab->settings['id'] ), $url );
		}
		if ( ! empty( $message ) ) {
			$url = add_query_arg( 'message', $message, $url );
		}

		do_action( 'tf_admin_options_saved_' . $this->getOptionNamespace() );
		wp_redirect( esc_url_raw( $url ) );		
	}

	private function verifySecurity() {
		if ( empty( $_POST ) || empty( $_POST['action'] ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( $screen->id != $this->panelID ) {
			return false;
		}

		if ( ! current_user_can( $this->settings['capability'] ) ) {
			return false;
		}

		if ( ! check_admin_referer( $this->settings['id'], GASF . '_nonce' ) ) {
			return false;
		}

		return true;
	}

	public function getActiveTab() {
		if ( ! count( $this->tabs ) ) {
			return '';
		}
		if ( ! empty( $this->activeTab ) ) {
			return $this->activeTab;
		}

		if ( empty( $_GET['tab'] ) ) {
			$this->activeTab = $this->tabs[0];
			return $this->activeTab;
		}

		foreach ( $this->tabs as $tab ) {
			if ( $tab->settings['id'] == $_GET['tab'] ) {
				$this->activeTab = $tab;
				return $this->activeTab;
			}
		}

		$this->activeTab = $this->tabs[0];
		return $this->activeTab;
	}

	public function createAdminPage() {
		do_action( 'tf_admin_page_before' );
		do_action( 'tf_admin_page_before_' . $this->getOptionNamespace() );

		?>
		<div class="wrap">
		<h2><?php echo wp_kses_post($this->settings['title']) ?></h2>
		<?php
		if ( ! empty( $this->settings['desc'] ) ) {
			?><p class='description'><?php echo wp_kses_post($this->settings['desc']) ?></p><?php
		}
		?>

		<div class='gas-framework-panel-wrap'>
		<?php

		do_action( 'tf_admin_page_start' );
		do_action( 'tf_admin_page_start_' . $this->getOptionNamespace() );

		if ( count( $this->tabs ) ) :
			?>
			<h2 class="nav-tab-wrapper">
			<?php

			do_action( 'tf_admin_page_tab_start' );
			do_action( 'tf_admin_page_tab_start_' . $this->getOptionNamespace() );

			foreach ( $this->tabs as $tab ) {
				$tab->displayTab();
			}

			do_action( 'tf_admin_page_tab_end' );
			do_action( 'tf_admin_page_tab_end_' . $this->getOptionNamespace() );

			?>
			</h2>
			<?php
		endif;

		?>
		<div class='options-container'>
		<?php

		// Display notification if we did something
		if ( ! empty( $_GET['message'] ) ) {
			if ( $_GET['message'] == 'saved' ) {
				echo wp_kses_post(GASFrameworkAdminNotification::formNotification( __( 'Settings saved.', 'awesome-support' ) ), esc_html( isset($_GET['message']) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '' ) );
			} else if ( $_GET['message'] == 'reset' ) {
				echo wp_kses_post(GASFrameworkAdminNotification::formNotification( __( 'Settings reset to default.', 'awesome-support' ) ), esc_html( isset($_GET['message']) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '' ) );
			}
		}

		if ( $this->settings['use_form'] ) :
			?>
			<form method='post'>
			<?php
		endif;

		if ( $this->settings['use_form'] ) {
			// security
			wp_nonce_field( $this->settings['id'], GASF . '_nonce' );
		}

		?>
		<table class='form-table'>
			<tbody>
		<?php

		do_action( 'tf_admin_page_table_start' );
		do_action( 'tf_admin_page_table_start_' . $this->getOptionNamespace() );

		$activeTab = $this->getActiveTab();
		if ( ! empty( $activeTab ) ) {

			if ( ! empty( $activeTab->settings['desc'] ) ) {
				?><p class='description'><?php echo wp_kses_post($activeTab->settings['desc']) ?></p><?php
			}

			$activeTab->displayOptions();
		}

		foreach ( $this->options as $option ) {
			$option->display();
		}

		do_action( 'tf_admin_page_table_end' );
		do_action( 'tf_admin_page_table_end_' . $this->getOptionNamespace() );

		?>
			</tbody>
		</table>
		<?php

		if ( $this->settings['use_form'] ) :
			?>
			</form>
			<?php
		endif;

		// Reset form. We use JS to trigger a reset from other buttons within the main form
		// This is used by class-option-save.php
		if ( $this->settings['use_form'] ) :
			?>
			<form method='post' id='tf-reset-form'>
				<?php
				// security
				wp_nonce_field( $this->settings['id'], GASF . '_nonce' );
				?>
				<input type='hidden' name='action' value='reset'/>
			</form>
			<?php
		endif;

		do_action( 'tf_admin_page_end' );
		do_action( 'tf_admin_page_end_' . $this->getOptionNamespace() );

		?>
		<div class='options-container'>
		</div>
		</div>
		</div>
		</div>
		<?php

		do_action( 'tf_admin_page_after' );
		do_action( 'tf_admin_page_after_' . $this->getOptionNamespace() );
	}

	public function createTab( $settings ) {
		$obj = new GASFrameworkAdminTab( $settings, $this );
		$this->tabs[] = $obj;

		do_action( 'tf_admin_tab_created_' . $this->getOptionNamespace(), $obj );

		return $obj;
	}

	public function createOption( $settings ) {
		if ( ! apply_filters( 'tf_create_option_continue_' . $this->getOptionNamespace(), true, $settings ) ) {
			return null;
		}

		$obj = GASFrameworkOption::factory( $settings, $this );
		$this->options[] = $obj;

		do_action( 'tf_create_option_' . $this->getOptionNamespace(), $obj );

		return $obj;
	}
}
