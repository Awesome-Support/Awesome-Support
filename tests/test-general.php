<?php
class MUMEI_AYUDA_Test_Functions_General extends WP_UnitTestCase {

	private $plugin;
 
    function setUp() {
        parent::setUp();     
    }

	function test_get_option() {
		$option = mumei_ayuda_get_option( 'support_products' );
		$this->assertFalse( $option );
	}
 
}