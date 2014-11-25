<?php
class WPAS_Tickets_Tests extends WP_UnitTestCase {

	private $plugin;
 
    function setUp() {
         
        parent::setUp();
       
        $this->ticket_id = null;
     
    }

    function test_wpas_insert_ticket() {

    	$data = array(
			'post_title'   => 'Test Ticket',
			'post_name'    => 'Test Ticket',
			'post_author'  => 1,
			'post_content' => 'In hac habitasse platea dictumst. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Nullam cursus lacinia erat. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Donec vitae orci sed dolor rutrum auctor.',
    	);

		$this->ticket_id = wpas_insert_ticket( $data, false );

    	$this->assertInternalType( "int", $this->ticket_id );
	}
 
}