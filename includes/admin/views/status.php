<?php

$tools_tabs['status'] = array( 'name' => __('System Status', 'ayuda-help-desk') );
if( current_user_can( 'administrator' ) ) {
    $tools_tabs['logs'] = array( 'name' => __('Log Viewer', 'ayuda-help-desk') );
}
$tools_tabs['tools'] = array( 'name' => __('Cleanup', 'ayuda-help-desk') );
$tools_tabs = apply_filters( 'mumei_ayuda_system_tabls', $tools_tabs );

/*$tools_tabs = apply_filters( 'mumei_ayuda_system_tabls', array(
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
if ( defined( 'MUMEI_AYUDA_SAAS' ) && true === MUMEI_AYUDA_SAAS ) {
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

	$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $tools_tabs ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'status';

	foreach($tools_tabs as $t_tab_key => $t_tab) {
		$link = add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-status', 'tab' => $t_tab_key ), admin_url( 'edit.php' ) );

		$active_class = ( $t_tab_key === $active_tab ) ?  'nav-tab-active' : '';

		echo '<a href="' . esc_url( $link ) . '" class="nav-tab ' . esc_attr( $active_class ) . '">'. esc_html( $t_tab['name'] ) .'</a>';
	}
	?>

	</h2>

	<?php



	$tab_view = isset( $tools_tabs[ $active_tab ]['view_path'] ) ? $tools_tabs[ $active_tab ]['view_path'] : MUMEI_AYUDA_PATH . 'includes/admin/views/system-'.$active_tab.'.php';
	require_once( $tab_view );

	?>

</div>

<?php
}
?>
