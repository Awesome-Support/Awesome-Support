<?php
class WPAS_Test_Custom_Field extends WP_UnitTestCase {

	private $plugin;

	/**
	 * Text custom field.
	 *
	 * @var WPAS_Custom_Field $text_field
	 */
	public $text_field;

	/**
	 * ID of the test ticket.
	 */
	public $ticket_id;

	/**
	 * Array of registered custom fields.
	 *
	 * @var $fields array
	 */
	public $fields;

	function setUp() {

		parent::setUp();

		/**
		 * Add a text field.
		 */
		wpas_add_custom_field( 'my_test_field', array(
			'field_type'    => 'text',
			'capability'    => 'create_ticket',
			'sanitize'      => 'sanitize_text_field',
			'title'         => 'Test Field',
			'html5_pattern' => '',
		) );

		/**
		 * Add a test ticket.
		 */
		$this->ticket_id = wpas_insert_ticket( array(
			'post_title'   => 'Test Ticket',
			'post_name'    => 'Test Ticket',
			'post_author'  => 1,
			'post_content' => 'In hac habitasse platea dictumst. Nulla neque dolor, sagittis eget, iaculis quis, molestie non, velit. Nullam cursus lacinia erat. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Donec vitae orci sed dolor rutrum auctor.'
		), false );

		/**
		 * Get all the custom fields.
		 */
		$this->fields = WPAS()->custom_fields->get_custom_fields();

		/**
		 * Instantiate the custom fields objects.
		 */
		$this->text_field = new WPAS_Custom_Field( 'my_test_field', $this->fields['my_test_field'] );

	}

	public function test_get_class_name() {
		$this->assertEquals( 'WPAS_CF_Text', $this->text_field->get_class_name() );
	}

	public function test_wpas_add_custom_field() {
		$this->assertArrayHasKey( 'my_test_field', $this->fields );
	}

	public function test_get_field_id() {
		$this->assertEquals( 'wpas_my_test_field', $this->text_field->get_field_id() );
	}

	public function test_get_field_id_save() {
		$this->assertEquals( '_wpas_my_test_field', $this->text_field->get_field_id( true ) );
	}

	public function test_update_value() {
		$result = $this->text_field->update_value( 'test', $this->ticket_id );

		global $wpdb;

		$meta_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '%s' AND post_id = '%d'", '_wpas_my_test_field', $this->ticket_id ) );

		$this->assertEquals( 1, $result );
		$this->assertEquals( 'test', $meta_value->meta_value );
	}

	public function test_get_field_value() {
		update_post_meta( $this->ticket_id, '_wpas_my_test_field', 'hello' );
		$this->assertEquals( 'hello', $this->text_field->get_field_value( '', $this->ticket_id ) );
		$this->assertEquals( 'hello', wpas_get_cf_value( 'my_test_field', $this->ticket_id, '' ) );
	}

	public function test_get_field_class() {
		$this->assertEquals( 'wpas-form-control', $this->text_field->get_field_class() );
		$this->assertEquals( 'wpas-form-control test', $this->text_field->get_field_class( array( 'test' ) ) );
	}

}