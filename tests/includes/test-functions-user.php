<?php

class WPAS_Test_Functions_User extends WP_UnitTestCase {

	private $plugin;
	private $first_name = 'John';
	private $last_name  = 'Doe';
	private $pwd        = 'supersecret';
	private $email      = 'mail@example.com';
	private $user_id;

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

	function test_wpas_insert_user_valid() {

		$user_id = wpas_insert_user( array(
			'email'      => $this->email,
			'first_name' => $this->first_name,
			'last_name'  => $this->last_name,
			'pwd'        => $this->pwd,
		), false );
		$this->assertInternalType( 'int', $user_id );
		$this->user_id = $user_id;

		// Check that the user data is correct
		$user     = get_user_by( 'id', $user_id );
		$username = sanitize_user( strtolower( $this->first_name ) . strtolower( $this->last_name ) );

		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertEquals( $username, $user->data->user_login );
		$this->assertEquals( "{$this->first_name} {$this->last_name}", $user->data->display_name );
		$this->assertEquals( $this->email, $user->data->user_email );
		$this->assertEquals( 'wpas_user', $user->roles[0] );

	}

	function test_wpas_insert_user_invalid() {
		$user_id = wpas_insert_user( array(
			'email'      => '',
			'first_name' => $this->first_name,
			'last_name'  => $this->last_name,
			'pwd'        => $this->pwd,
		), false );
		$this->assertInstanceOf( 'WP_Error', $user_id );
	}

	function test_wpas_get_user_nice_role() {
		$this->assertEquals( 'Agent', wpas_get_user_nice_role( 'wpas_agent' ) );
	}

	function test_get_users() {

		$users = wpas_get_users( array( 'cap' => 'edit_ticket' ) );

		$this->assertInternalType( 'array', $users->members );
		$this->assertCount( 2, $users->members );

	}

	function test_get_users_reply_ticket() {

		$users = wpas_get_users( array( 'cap' => 'reply_ticket' ) );

		$this->assertInternalType( 'array', $users->members );
		$this->assertCount( 3, $users->members );

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

		$this->assertInternalType( 'array', $users->members );
		$this->assertCount( 1, $users->members );

	}

	function test_get_users_cache() {

		$args = array(
			'exclude'     => array(),
			'cap'         => 'edit_ticket',
			'cap_exclude' => '',
			'search'      => array(),
		);

		$hash  = md5( serialize( $args ) );
		$users = wpas_get_users( $args );
		$cache = wp_cache_get( 'users_' . $hash, 'wpas' );

		delete_transient( "wpas_list_users_$hash" );

		$this->assertInternalType( 'array', $cache );
		$this->assertCount( 2, $cache );

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