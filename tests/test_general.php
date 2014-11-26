<?php
class WPAS_Test_Functions_General extends WP_UnitTestCase {

	private $plugin;
 
    function setUp() {
        parent::setUp();     
    }

    function test_wpas_redirect() {
        $redirect = wpas_redirect( 'test_case', 'http://google.com' );
    	$this->assertTrue( $redirect );
    }

    function test_wpas_redirect_fail() {
    	$redirect = wpas_redirect( 'test_case' );
    	$this->assertFalse( $redirect );
    }
 
}