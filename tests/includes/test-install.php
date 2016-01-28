<?php
class WPAS_Test_Functions_Install extends WP_UnitTestCase {

	private $plugin;

	function test_default_options() {

		if ( ! function_exists( 'get_settings_defaults' ) ) {
			require( WPAS_PATH . 'includes/admin/settings/functions-settings.php' );
		}

		$options  = get_option( 'wpas_options' );
		$defaults = serialize( get_settings_defaults() );
		$this->assertEquals( $defaults, $options );
	}

	function test_setup_status() {
		$this->assertEquals( 'pending', get_option( 'wpas_setup' ) );
	}

	function test_redirect_about() {
		$this->assertEquals( true, get_option( 'wpas_redirect_about' ) );
	}

	function test_products_setup() {
		$this->assertEquals( 'pending', get_option( 'wpas_support_products' ) );
	}

	function test_plugin_version() {
		$this->assertEquals( WPAS_VERSION, get_option( 'wpas_version' ) );
	}

	function test_db_version() {
		$this->assertEquals( WPAS_DB_VERSION, get_option( 'wpas_db_version' ) );
	}

}