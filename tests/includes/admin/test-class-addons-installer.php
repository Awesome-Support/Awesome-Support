<?php

class WPAS_Test_Addons_Installer extends WP_UnitTestCase {

	/**
	 * @var WPAS_Addons_Installer
	 */
	public $addons_installer;

	function setUp() {

		parent::setUp();
		require( dirname( __FILE__ ) . '/../../../includes/admin/class-addons-installer.php' );
		$this->addons_installer = new WPAS_Addons_Installer();
	}

	function test_class_wpas_addon_installer() {
		$this->assertTrue( class_exists( 'WPAS_Addons_Installer' ) );
	}

	function test_load_api_credentials() {
		$this->assertTrue( $this->addons_installer->load_api_credentials() );
	}

	function test_get_purchased_addons() {
		$purchase = $this->addons_installer->get_purchased_addons();
		$this->assertTrue( is_array( $purchase ) );
		$this->assertContains( 'products', $purchase );
		$this->assertSame( 1, count( $purchase['products'] ) ); // The test account has exactly 1 purchase.
	}

}
