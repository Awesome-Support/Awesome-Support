<?php
global $post;

$status = get_post_meta( $post->ID, '_wpas_status', true );
?>


<?php 
/**
 * Use this hook to insert items at the beginning of the toolbar.
 * Generally, just call the wpas_add_ticket_detail_toolbar_item() 
 * function at the bottom of this file to add a new toolbar item.
 */
do_action( 'wpas_backend_middle_toolbar_before', $post ); 
?>

<!-- Link to collapse replies -->
<?php /*
<span data-hint="<?php esc_html_e( 'Toggle Replies (Hide All Replies Except The Last 3)', 'awesome-support' ); ?>" class="wpas-replies-middle-toolbar-item hint-bottom hint-anim"> 
	<img name="wpas_collapse_replies_top" id="wpas-collapse-replies-top" class="link-primary wpas-link-reply wpas-middle-toolbar-links" value="collapse_replies" src="<?php echo WPAS_URL; ?>assets/admin/images/icons/toggle-replies.png"></img> 
</span>
*/ ?>

<!-- Link to collapse replies -->
<?php wpas_add_ticket_detail_toolbar_item( 'img', 'wpas-collapse-replies-top', __( 'Toggle Replies (Hide All Replies Except The Last 3)', 'awesome-support' ), WPAS_URL . "assets/admin/images/icons/toggle-replies.png" ); ?>

<?php 
/**
 * Use this hook to insert items at the end of the toolbar.
 * Generally, just call the wpas_add_ticket_detail_toolbar_item() 
 * function at the bottom of this file to add a new toolbar item. 
 */
do_action( 'wpas_backend_middle_toolbar_after', $post ); 
?>

<?php
/**
 * Adds an item to the tool-bar in the ticket detail in wp-admin
 * 
 * Note that the menu item is echoed directly to the screen so this
 * function should be called using a do_action hook if called
 * from any file other than this one!
 *
 * @since 4.4.0
 *
 * @param 	string $html_element_type   img or a (anchor)
 *          string $item_css_id 		The CSS ID of the toolbar item
 *		  	string $tool_tip_text 		The tool tip that will be displayed for the new toolbar item
 *			string $image_url			the URL for the toolbar item image. 
 *
 * @return 	void (basically nothing is returned)
 */
function wpas_add_ticket_detail_toolbar_item( $html_element_type, $item_css_id, $tool_tip_text, $image_url, $target_url='' ) {
	$name = str_replace( '-', '_', $item_css_id );
?>
	<span data-hint="<?php echo $tool_tip_text ?>" class="wpas-replies-middle-toolbar-item hint-bottom hint-anim"> 
		<?php
			$echoout = '';
			$echoout = $echoout . ' ' . '<' . $html_element_type . ' ' ;  		// opening tag such as <a> or <img>
			$echoout = $echoout . ' ' . 'name = ' . '"' . $name . '"' ; 		// name attribute
			$echoout = $echoout . ' ' . 'id = ' . '"' . $item_css_id . '"' ;	// css ID
			$echoout = $echoout . ' ' . 'class="link-primary wpas-link-reply wpas-middle-toolbar-links ' . $name . '"' ; // css class names
			$echoout = $echoout . ' ' . 'value = ' . '"' . $name . '"' ;		// value attribute
			
			if ( 'img' === $html_element_type ) {
				// img elements need the src attribute
				$echoout = $echoout . ' ' . 'src = ' . '"' . $image_url . '"' ;
			}
			
			if ( 'a' === $html_element_type ) {
				// add the href element
				$echoout = $echoout . ' ' . 'href = ' . '"' . $target_url . '"' ;
			}

			$echoout = $echoout. '>'; // closing bracket for the tag
			
			if ( 'a' === $html_element_type ) {
				// add the img element if we're using an "a" tag
				$echoout = $echoout . ' ' . '<img src = ' . $image_url . '>';
			}
			
			$echoout = $echoout. ' ' . '</' . $html_element_type . '>'; // closing tag such as </a> or </img>
			
			echo $echoout ;
		?>
	</span>
<?php }