<?php
class WPAS_Test_Functions_Post extends WP_UnitTestCase {

	private $plugin;
 
    function setUp() {
         
        parent::setUp();

        $this->ticket_data = array(
            'post_title'   => 'Test Ticket',
            'post_name'    => 'Test Ticket',
            'post_author'  => 1,
            'post_content' => 'In hac habitasse platea dictumst. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Nullam cursus lacinia erat. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Donec vitae orci sed dolor rutrum auctor.'
        );

        $this->reply_data = array(
            'post_content' => 'Vivamus aliquet elit ac nisl. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. Nullam dictum felis eu pede mollis pretium. Nullam vel sem. Praesent nonummy mi in odio.'
        );
     
    }

    function test_wpas_open_ticket() {
        $data = array(
            'title'   => 'Test Ticket',
            'message' => 'In hac habitasse platea dictumst. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Nullam cursus lacinia erat. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Donec vitae orci sed dolor rutrum auctor.'
        );
        $ticket_id = wpas_open_ticket( $data );
        $this->assertInternalType( 'int', $ticket_id );
    }

    function test_wpas_insert_ticket() {
		$ticket_id = wpas_insert_ticket( $this->ticket_data, false );
    	$this->assertInternalType( 'int', $ticket_id );
	}

    function test_wpas_add_reply() {
        $ticket_id = wpas_insert_ticket( $this->ticket_data, false );
        $reply_id  = wpas_add_reply( $this->reply_data, $ticket_id );
        $this->assertInternalType( 'int', $reply_id );
    }

    function test_wpas_insert_reply() {
        $ticket_id = wpas_insert_ticket( $this->ticket_data, false );
        $reply_id  = wpas_insert_reply( $this->reply_data, $ticket_id );
        $this->assertInternalType( 'int', $reply_id );
    }

    function test_wpas_insert_reply_fail() {
        $ticket_id = wpas_insert_ticket( $this->ticket_data, false );
        $reply_id  = wpas_insert_reply( $this->reply_data );
        $this->assertFalse( $reply_id );
    }

    function test_wpas_update_ticket_status() {
        $ticket_id = wpas_insert_ticket( $this->ticket_data, false );
        $updated   = wpas_update_ticket_status( $ticket_id, 'processing' );
        $this->assertInternalType( 'int', $updated );
        $this->assertNotEquals( 0, $updated );
    }

    function test_wpas_update_ticket_status_fail() {
        $ticket_id = wpas_insert_ticket( $this->ticket_data, false );
        $updated   = wpas_update_ticket_status( $ticket_id, 'unknown' );
        $this->assertEquals( 0, $updated );
    }

    function test_wpas_edit_reply() {
        $ticket_id = wpas_insert_ticket( $this->ticket_data, false );
        $reply_id  = wpas_insert_reply( $this->reply_data, $ticket_id );
        $edited    = wpas_edit_reply( $reply_id, 'Vivamus aliquet elit ac nisl.' );
        $this->assertInternalType( 'int', $edited );
    }

    function test_wpas_mark_reply_read() {
        $ticket_id = wpas_insert_ticket( $this->ticket_data, false );
        $reply_id  = wpas_insert_reply( $this->reply_data, $ticket_id );
        $edited    = wpas_mark_reply_read( $reply_id );
        $this->assertInternalType( 'int', $edited );
    }

    function test_wpas_get_replies() {
        $ticket_id  = wpas_insert_ticket( $this->ticket_data, false );
        $reply_id   = wpas_insert_reply( $this->reply_data, $ticket_id );
        $reply_id_2 = wpas_insert_reply( $this->reply_data, $ticket_id );
        $replies    = wpas_get_replies( $ticket_id );
        $this->assertNotEmpty( $replies );
        $this->assertCount( 2, $replies );
    }

    function test_wpas_find_agent() {
        $agent = wpas_find_agent();
        $this->assertInternalType( 'int', $agent );
    }
 
}