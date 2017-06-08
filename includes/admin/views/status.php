<div class="wrap">

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-status', 'tab' => 'status' ), admin_url( 'edit.php' ) ); ?>" class="nav-tab <?php if ( !isset( $_GET['tab'] ) || 'status' === $_GET['tab'] ): ?> nav-tab-active<?php endif; ?>"><?php _e( 'System Status', 'awesome-support' ); ?></a>
		<a href="<?php echo add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-status', 'tab' => 'logs' ), admin_url( 'edit.php' ) ); ?>" class="nav-tab <?php if ( isset( $_GET['tab'] ) && 'logs' === $_GET['tab'] ): ?> nav-tab-active<?php endif; ?>"><?php _e( 'Logs', 'awesome-support' ); ?></a>
		<a href="<?php echo add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-status', 'tab' => 'tools' ), admin_url( 'edit.php' ) ); ?>" class="nav-tab <?php if ( isset( $_GET['tab'] ) && 'tools' === $_GET['tab'] ): ?> nav-tab-active<?php endif; ?>"><?php _e( 'Cleanup', 'awesome-support' ); ?></a>
	</h2>

	<?php
	if ( !isset( $_GET['tab'] ) ) {
		require_once( WPAS_PATH . 'includes/admin/views/system-status.php' );
	} else {
		switch( $_GET['tab'] ) {
			case 'tools':
				require_once( WPAS_PATH . 'includes/admin/views/system-tools.php' );
			break;

			case 'logs':
				require_once( WPAS_PATH . 'includes/admin/views/system-logs.php' );
			    break;

			default:
				require_once( WPAS_PATH . 'includes/admin/views/system-status.php' );
		}
	}
	?>

</div>