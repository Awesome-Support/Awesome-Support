<?php 

$tools_tabs['status'] = array( 'name' => 'System Status' );
if( current_user_can( 'administrator' ) ) {
    $tools_tabs['logs'] = array( 'name' => 'Log Viewer' );
}
$tools_tabs['tools'] = array( 'name' => 'Cleanup' );
$tools_tabs = apply_filters( 'wpas_system_tabls', $tools_tabs );

/*$tools_tabs = apply_filters( 'wpas_system_tabls', array(
    'status' => array(
	'name' => 'System Status'
    ),
    'logs'  => array(
	'name' => 'Log Viewer'
    ),
    'tools'  => array(
	'name' => 'Cleanup'
    )
) );
*/

/* Remove some items if running in SAAS mode AND in a multi-site environment - do not allow the user to see  the LOG viewer and the SYSTEM STATUS screen! */
if ( defined( 'WPAS_SAAS' ) && true === WPAS_SAAS ) {
	if ( true === is_multisite() && 1 <> get_current_blog_id() && 0 <> get_current_blog_id() ) {
		unset($tools_tabs['logs']);
		unset($tools_tabs['status']);
	}
}


if(!empty( $tools_tabs )) {
?>


<div class="wrap">
	
	<h2 class="nav-tab-wrapper">
	<?php 
	
	$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $tools_tabs ) ) ? $_GET['tab'] : 'status';
	
	foreach($tools_tabs as $t_tab_key => $t_tab) {
		$link = add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-status', 'tab' => $t_tab_key ), admin_url( 'edit.php' ) );
		
		$active_class = ( $t_tab_key === $active_tab ) ?  'nav-tab-active' : '';
		
		echo '<a href="'.$link.'" class="nav-tab '.$active_class.'">'. __( $t_tab['name'], 'awesome-support' ) .'</a>';
	}
	?>
	
	</h2>

	<?php
	
	
	
	$tab_view = isset( $tools_tabs[ $active_tab ]['view_path'] ) ? $tools_tabs[ $active_tab ]['view_path'] : WPAS_PATH . 'includes/admin/views/system-'.$active_tab.'.php';
	require_once( $tab_view );
	
	?>

</div>

<?php
}
?>
