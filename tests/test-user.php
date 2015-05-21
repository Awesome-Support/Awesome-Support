<?php

class WPAS_Test_Functions_User extends WP_UnitTestCase {

	function setUp() {

		parent::setUp();

		$agent = wp_insert_user( array(
			'first_name' => 'Agent',
			'last_name'  => 'Demo',
			'user_email' => 'agent@n2clic.com',
			'user_login' => 'demoagent',
			'user_pass'  => rand( 1000, 9999 ),
			'role'       => 'wpas_agent'
		) );

		$client = wp_insert_user( array(
			'first_name' => 'User',
			'last_name'  => 'Demo',
			'user_email' => 'user@n2clic.com',
			'user_login' => 'democlient',
			'user_pass'  => rand( 1000, 9999 ),
			'role'       => 'wpas_user'
		) );
	}

	function test_wpas_get_user_nice_role() {
		$this->assertEquals( 'Agent', wpas_get_user_nice_role( 'wpas_agent' ) );
	}

	function test_get_users() {

		$users = wpas_get_users( array( 'cap' => 'edit_ticket' ) );

		$this->assertInternalType( 'array', $users );
		$this->assertCount( 2, $users );

	}

	function test_get_users_reply_ticket() {

		$users = wpas_get_users( array( 'cap' => 'reply_ticket' ) );

		$this->assertInternalType( 'array', $users );
		$this->assertCount( 3, $users );

	}

	function test_get_users_clients() {

		$args = array(
			'exclude'     => array(),
			'cap'         => 'create_ticket',
			'cap_exclude' => 'edit_ticket',
		);

		$users = wpas_get_users( $args );
		$hash  = substr( md5( serialize( $args ) ), 0, 10 );

		delete_transient( "wpas_list_users_$hash" );

		$this->assertInternalType( 'array', $users );
		$this->assertCount( 1, $users );

	}

	function test_get_users_cache() {

		$args = array(
			'exclude'     => array(),
			'cap'         => 'edit_ticket',
			'cap_exclude' => '',
		);

		$hash      = substr( md5( serialize( $args ) ), 0, 10 );
		$users     = wpas_get_users( $args );
		$transient = get_transient( "wpas_list_users_$hash" );

		delete_transient( "wpas_list_users_$hash" );

		$this->assertInternalType( 'array', $transient );
		$this->assertCount( 2, $transient );

	}

	function test_wpas_list_users_edit_ticket() {

		$users = wpas_list_users( 'edit_ticket' );

		$this->assertInternalType( 'array', $users );
		$this->assertCount( 2, $users );

	}

	function test_wpas_list_users_create_ticket() {

		$users  = wpas_list_users( 'create_ticket' );

		$this->assertInternalType( 'array', $users );
		$this->assertCount( 3, $users );

	}

}