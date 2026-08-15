<?php
/**
 * Term Order Meta Field
 *
 * Adds a numeric "Order" meta field to all Awesome Support taxonomy term
 * add/edit forms, allowing admins to control the display order of terms
 * in dropdown selects. Terms with lower order values appear first.
 * Terms without an order value fall back to default sorting logic.
 *
 * @since 6.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all registered Awesome Support taxonomy names.
 *
 * @return array List of taxonomy slugs.
 */
function wpas_get_custom_taxonomy_names() {

	$taxonomies = array();

	if ( ! class_exists( 'WPAS' ) || ! method_exists( WPAS(), 'custom_fields' ) ) {
		return $taxonomies;
	}

	$fields = WPAS()->custom_fields->get_custom_fields();

	if ( empty( $fields ) ) {
		return $taxonomies;
	}

	foreach ( $fields as $field ) {
		if ( isset( $field['args']['field_type'] ) && 'taxonomy' === $field['args']['field_type'] ) {
			$taxonomies[] = $field['name'];
		}
	}

	return $taxonomies;
}

/**
 * Register term order hooks for all Awesome Support taxonomies.
 *
 * Hooks into 'init' at priority 20 to ensure custom fields are registered first.
 */
add_action( 'init', 'wpas_register_term_order_hooks', 20 );

function wpas_register_term_order_hooks() {

	$taxonomies = wpas_get_custom_taxonomy_names();

	foreach ( $taxonomies as $taxonomy ) {
		add_action( "{$taxonomy}_add_form_fields",  'wpas_term_order_add_field' );
		add_action( "{$taxonomy}_edit_form_fields",  'wpas_term_order_edit_field', 10, 2 );
		add_action( "created_{$taxonomy}",           'wpas_term_order_save', 10, 2 );
		add_action( "edited_{$taxonomy}",            'wpas_term_order_save', 10, 2 );
	}
}

/**
 * Display the "Order" field on the "Add New Term" form.
 *
 * @param string $taxonomy Current taxonomy slug.
 */
function wpas_term_order_add_field( $taxonomy ) {
	?>
	<div class="form-field term-order-wrap">
		<label for="term-order"><?php esc_html_e( 'Order', 'awesome-support' ); ?></label>
		<input type="number" name="term-order" id="term-order" value="" min="0" step="1" />
		<p class="description"><?php esc_html_e( 'Custom display order. Lower numbers appear first. Leave empty for default sorting.', 'awesome-support' ); ?></p>
	</div>
	<?php
}

/**
 * Display the "Order" field on the "Edit Term" form.
 *
 * @param WP_Term $term     Current term object.
 * @param string  $taxonomy Current taxonomy slug.
 */
function wpas_term_order_edit_field( $term, $taxonomy ) {

	$order = get_term_meta( $term->term_id, 'term_order', true );
	?>

	<tr class="form-field term-order-wrap">
		<th scope="row" valign="top">
			<label for="term-order"><?php esc_html_e( 'Order', 'awesome-support' ); ?></label>
		</th>
		<td>
			<input type="number" name="term-order" id="term-order" value="<?php echo esc_attr( $order ); ?>" min="0" step="1" />
			<p class="description"><?php esc_html_e( 'Custom display order. Lower numbers appear first. Leave empty for default sorting.', 'awesome-support' ); ?></p>
		</td>
	</tr>

	<?php
}

/**
 * Save the term order meta value.
 *
 * @param int $term_id Term ID.
 * @param int $tt_id   Term taxonomy ID.
 */
function wpas_term_order_save( $term_id, $tt_id ) {

	if ( isset( $_POST['term-order'] ) && $_POST['term-order'] !== '' ) {
		$order = absint( $_POST['term-order'] );
		update_term_meta( $term_id, 'term_order', $order );
	} else {
		delete_term_meta( $term_id, 'term_order' );
	}
}

/**
 * Add "Order" column to taxonomy list table.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function wpas_term_order_column( $columns ) {
	$columns['term_order'] = __( 'Order', 'awesome-support' );
	return $columns;
}

/**
 * Render the "Order" column content in taxonomy list table.
 *
 * @param string $content     Column content.
 * @param string $column_name Column name.
 * @param int    $term_id     Term ID.
 * @return string Modified column content.
 */
function wpas_term_order_column_content( $content, $column_name, $term_id ) {
	if ( 'term_order' === $column_name ) {
		$order = get_term_meta( $term_id, 'term_order', true );
		$content = ( $order !== '' && $order !== false ) ? esc_html( $order ) : '&mdash;';
	}
	return $content;
}

/**
 * Make the "Order" column sortable.
 *
 * @param array $columns Sortable columns.
 * @return array Modified sortable columns.
 */
function wpas_term_order_sortable_column( $columns ) {
	$columns['term_order'] = 'term_order';
	return $columns;
}

/**
 * Register list table column hooks for all taxonomies.
 */
add_action( 'init', 'wpas_register_term_order_column_hooks', 20 );

function wpas_register_term_order_column_hooks() {

	$taxonomies = wpas_get_custom_taxonomy_names();

	foreach ( $taxonomies as $taxonomy ) {
		add_filter( "manage_edit-{$taxonomy}_columns",          'wpas_term_order_column' );
		add_filter( "manage_{$taxonomy}_custom_column",         'wpas_term_order_column_content', 10, 3 );
		add_filter( "manage_edit-{$taxonomy}_sortable_columns", 'wpas_term_order_sortable_column' );
	}
}
