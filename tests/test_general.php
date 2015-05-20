<?php
class WPAS_Test_Functions_General extends WP_UnitTestCase {

	private $plugin;
 
    function setUp() {
        parent::setUp();     
    }

	function test_get_option() {
		$option = wpas_get_option( 'support_products' );
		$this->assertFalse( $option );
	}
 
}