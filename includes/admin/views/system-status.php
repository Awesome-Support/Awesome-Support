<p><?php _e( 'The system status is a built-in debugging tool. If you contacted the support and you\'re asked ot provide the system status, <strong>click the button below</strong> to copy your system report:', 'wpas' ); ?></p>

<div class="wpas-system-status">
	<textarea id="wpas-system-status-output" rows="10" style="display: none;"></textarea>
	<button id="wpas-system-status-generate" class="button-secondary"><?php _e( 'Copy Report', 'wpas' ); ?></button>
</div>

<table class="widefat wpas-system-status-table" id="wpas-system-status-wordpress">
	<thead>
		<tr>
			<th data-override="key" class="row-title">WordPress</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title"><label for="tablecell">Site URL</label></td>
			<td><?php echo site_url(); ?></td>
		</tr>
		<tr class="alternate">
			<td class="row-title"><label for="tablecell">Home URL</label></td>
			<td><?php echo home_url(); ?></td>
		</tr>
		<tr>
			<td class="row-title">WP Version</td>
			<td><?php bloginfo('version'); ?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">WP Multisite</td>
			<td><?php if ( is_multisite() ) echo __( 'Yes', 'wpas' ); else echo __( 'No', 'wpas' ); ?></td>
		</tr>
		<tr>
			<td class="row-title">WP Language</td>
			<td><?php echo get_locale(); ?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">WP Debug Mode</td>
			<td><?php if ( defined('WP_DEBUG') && WP_DEBUG ) _e( 'Yes', 'wpas' ); else _e( 'No', 'wpas' ); ?></td>
		</tr>
		<tr>
			<td class="row-title">WP Active Plugins</td>
			<td><?php echo count( (array) get_option( 'active_plugins' ) ); ?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">WP Max Upload Size</td>
			<td>
				<?php
				$wp_upload_max     = wp_max_upload_size();
				$server_upload_max = intval( str_replace( 'M', '', ini_get('upload_max_filesize') ) ) * 1024 * 1024;

				if ( $wp_upload_max <= $server_upload_max ) {
					echo size_format( $wp_upload_max );
				} else {
					echo '<span class="wpas-alert-danger">' . sprintf( __( '%s (The server only allows %s)', 'wpas' ), size_format( $wp_upload_max ), size_format( $server_upload_max ) ) . '</span>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td class="row-title">WP Memory Limit</td>
			<td><?php echo WP_MEMORY_LIMIT; ?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">WP Timezone</td>
			<td>
				<?php
				$timezone = get_option( 'timezone_string' );

				if ( empty( $timezone ) ) {
					echo '<span class="wpas-alert-danger">' . __( 'The timezone hasn\'t been set', 'wpas' ) . '</span>';
				} else {
					echo $timezone . ' (UTC' . wpas_get_offset_html5() . ')';
				}
				?>
			</td>
		</tr>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-server">
	<thead>
		<tr>
			<th data-override="key" class="row-title">Server</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title">PHP Version</td>
			<td><?php if ( function_exists( 'phpversion' ) ) echo esc_html( phpversion() ); ?></td>
		</tr>
		<tr class="alternate">
			<td class="row-title">Software</td>
			<td><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ); ?></td>
		</tr>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-settings">
	<thead>
		<tr>
			<th data-override="key" class="row-title">Settings</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title">Version</td>
			<td><?php echo WPAS_VERSION; ?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">DB Version</td>
			<td><?php echo WPAS_DB_VERSION; ?></td>
		</tr>
		<tr>
			<td class="row-title">Tickets Slug</td>
			<td><code><?php echo defined( 'WPAS_SLUG' ) ? sanitize_title( WPAS_SLUG ) : 'ticket'; ?></code></td>
		</tr>
		<tr class="alt">
			<td class="row-title">Products Slug</td>
			<td><code><?php echo defined( 'WPAS_PRODUCT_SLUG' ) ? WPAS_PRODUCT_SLUG : 'product'; ?></code></td>
		</tr>
		<tr>
			<td class="row-title">Multiple Products</td>
			<td><?php true === boolval( wpas_get_option( 'support_products' ) ) ? _e( 'Enabled', 'wpas' ) : _e( 'Disabled', 'wpas '); ?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">Registration Status</td>
			<td><?php true === boolval( wpas_get_option( 'allow_registrations' ) ) ? _e( 'Open', 'wpas' ) : _e( 'Closed', 'wpas '); ?></td>
		</tr>
		<tr>
			<td class="row-title">Registration Page</td>
			<td>
				<?php
				$login_page = wpas_get_option( 'login_page' );
				if ( empty( $login_page ) ) {
					_e( 'Default', 'wpas' );
				} else {
					echo get_permalink( $login_page ) . " (#$login_page)";
				}
				?>
			</td>
		</tr>
		<tr class="alt">
			<td class="row-title">Uploads Folder</td>
			<td>
				<?php
				if ( !is_dir( ABSPATH . 'wp-content/uploads/awesome-support' ) ) {
					if ( !is_writable( ABSPATH . 'wp-content/uploads' ) ) {
						echo '<span class="wpas-alert-danger">' . __( 'The upload folder doesn\'t exist and can\'t be created', 'wpas' ) . '</span>';
					} else {
						echo '<span class="wpas-alert-success">' . __( 'The upload folder doesn\'t exist but can be created', 'wpas' ) . '</span>';
					}
				} else {
					if ( !is_writable( ABSPATH . 'wp-content/uploads/awesome-support' ) ) {
						echo '<span class="wpas-alert-danger">' . __( 'The upload folder exists but isn\'t writable', 'wpas' ) . '</span>';
					} else {
						echo '<span class="wpas-alert-success">' . __( 'The upload folder exists and is writable', 'wpas' ) . '</span>';
					}
				}
				?>
			</td>
		</tr>
		<tr>
			<td class="row-title">Allowed File Types</td>
			<td>
				<?php
				$filetypes = apply_filters( 'wpas_attachments_filetypes', wpas_get_option( 'attachments_filetypes' ) );

				if ( empty( $filetypes ) ) {
					echo '<span class="wpas-alert-danger">' . _x( 'None', 'Allowed file types for attachments', 'wpas' ) . '</span>';
				} else {
					$filetypes = explode( ',', $filetypes );
					foreach ( $filetypes as $key => $type ) { $filetypes[$key] = "<code>.$type</code>"; }
					$filetypes = implode( ', ', $filetypes );
					echo $filetypes;
				}
				?>
			</td>
		</tr>
		<tr class="alt">
			<td class="row-title">WYSIWYG On Front</td>
			<td><?php true === boolval( wpas_get_option( 'frontend_wysiwyg_editor' ) ) ? _e( 'Yes', 'wpas' ) : _e( 'No', 'wpas '); ?></td>
		</tr>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-pages">
	<thead>
		<tr>
			<th data-override="key" class="row-title">Plugin Pages</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title">Ticket Submission</td>
			<?php $page_submit = wpas_get_option( 'ticket_submit' ); ?>
			<td><?php echo empty( $page_submit ) ? '<span class="wpas-alert-danger">Not set</span>' : "<span class='wpas-alert-success'>" . get_permalink( $page_submit ) . " (#$page_submit)</span>"; ?></td>
		</tr>
		<tr>
			<td class="row-title">Tickets List</td>
			<?php $page_list = wpas_get_option( 'ticket_list' ); ?>
			<td><?php echo empty( $page_list ) ? '<span class="wpas-alert-danger">Not set</span>' : "<span class='wpas-alert-success'>" . get_permalink( $page_list ) . " (#$page_list)</span>"; ?></td>
		</tr>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-email-notifications">
	<thead>
		<tr>
			<th data-override="key" class="row-title">E-Mail Notifications</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title">Sender Name</td>
			<td>
				<?php echo wpas_get_option( 'sender_name', get_bloginfo( 'name' ) ); ?>
			</td>
		</tr>
		<tr>
			<td class="row-title">Sender E-Mail</td>
			<td>
				<?php echo wpas_get_option( 'sender_email', get_bloginfo( 'admin_email' ) ); ?>
			</td>
		</tr>
		<tr>
			<td class="row-title">Reply-To E-Mail</td>
			<td>
				<?php echo wpas_get_option( 'reply_email', get_bloginfo( 'admin_email' ) ); ?>
			</td>
		</tr>
		<tr>
			<td class="row-title">Submission Confirmation</td>
			<td>
				<?php echo true === boolval( wpas_get_option( 'enable_confirmation' ) ) ? '<span class="wpas-alert-success">Enabled</span>' : '<span class="wpas-alert-danger">Disabled</span>'; ?>
			</td>
		</tr>
		<tr class="alt">
			<td class="row-title">New Assignment</td>
			<td>
				<?php echo true === boolval( wpas_get_option( 'enable_assignment' ) ) ? '<span class="wpas-alert-success">Enabled</span>' : '<span class="wpas-alert-danger">Disabled</span>'; ?>
			</td>
		</tr>
		<tr>
			<td class="row-title">New Agent Reply</td>
			<td>
				<?php echo true === boolval( wpas_get_option( 'enable_reply_agent' ) ) ? '<span class="wpas-alert-success">Enabled</span>' : '<span class="wpas-alert-danger">Disabled</span>'; ?>
			</td>
		</tr>
		<tr class="alt">
			<td class="row-title">New Client Reply</td>
			<td>
				<?php echo true === boolval( wpas_get_option( 'enable_reply_client' ) ) ? '<span class="wpas-alert-success">Enabled</span>' : '<span class="wpas-alert-danger">Disabled</span>'; ?>
			</td>
		</tr>
		<tr>
			<td class="row-title">Ticket Closed</td>
			<td>
				<?php echo true === boolval( wpas_get_option( 'enable_closed' ) ) ? '<span class="wpas-alert-success">Enabled</span>' : '<span class="wpas-alert-danger">Disabled</span>'; ?>
			</td>
		</tr>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-custom-fields">
	<thead>
		<tr>
			<th data-override="key" class="row-title">Custom Fields</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<?php
		global $wpas_cf;

		$fields = $wpas_cf->get_custom_fields();

		if ( empty( $fields ) ) { ?>
			<td colspan="2">None</td>	
		<?php } else {

			$cf_tr_class = 'alt';

			foreach ( $fields as $field_id => $field ) {

				$cf_tr_class                            = 'alt' === $cf_tr_class ? '' : 'alt';
				$values                                 = array();
				$attributes                             = array( __( 'Capability', 'wpas' ) => '<code>' . $field['args']['capability'] . '</code>' );
				$attributes[__( 'Core', 'wpas')]        = true === boolval( $field['args']['core'] ) ? __( 'Yes', 'wpas' ) : __( 'No', 'wpas' );
				$attributes[__( 'Required', 'wpas')]    = true === boolval( $field['args']['required'] ) ? __( 'Yes', 'wpas' ) : __( 'No', 'wpas' );
				$attributes[__( 'Logged', 'wpas')]      = true === boolval( $field['args']['log'] ) ? __( 'Yes', 'wpas' ) : __( 'No', 'wpas' );
				$attributes[__( 'Show Column', 'wpas')] = true === boolval( $field['args']['show_column'] ) ? __( 'Yes', 'wpas' ) : __( 'No', 'wpas' );

				if ( 'taxonomy' === $field['args']['callback'] ) {
					if ( true === boolval( $field['args']['taxo_std'] ) ) {
						$attributes[__( 'Taxonomy', 'wpas')] = __( 'Yes (standard)', 'wpas' );
					} else {
						$attributes[__( 'Taxonomy', 'wpas')] = __( 'Yes (custom)', 'wpas' );
					}
				} else {
					$attributes[__( 'Taxonomy', 'wpas')] = __( 'No', 'wpas' );
				}

				$attributes[__( 'Callback', 'wpas')] = '<code>' . $field['args']['callback'] . '</code>';

				foreach ( $attributes as $label => $value ) {
					array_push( $values,  "<strong>$label</strong>: $value" );
				}
				?>

				<tr <?php if ( !empty( $cf_tr_class ) ) echo "class='$cf_tr_class'"; ?>>
					<td class="row-title"><?php echo wpas_get_field_title( $field ); ?></td>
					<td><?php echo implode( ', ', $values ); ?></td>
				</tr>

			<?php }
		} ?>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-plugins">
	<thead>
		<tr>
			<th data-override="key" class="row-title">Plugins</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title">Installed</td>
			<td>
				<?php
				$active_plugins = (array) get_option( 'active_plugins', array() );

				if ( is_multisite() )
					$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

				$wp_plugins = array();

				foreach ( $active_plugins as $plugin ) {

					$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
					$dirname        = dirname( $plugin );
					$version_string = '';

					if ( ! empty( $plugin_data['Name'] ) ) {

					// link the plugin name to the plugin url if available
						$plugin_name = $plugin_data['Name'];
						if ( ! empty( $plugin_data['PluginURI'] ) ) {
							$plugin_name = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '" title="Visit plugin homepage">' . $plugin_name . '</a>';
						}

						$wp_plugins[] = $plugin_name . ' by ' . $plugin_data['Author'] . ' version ' . $plugin_data['Version'] . $version_string;

					}
				}

				if ( sizeof( $wp_plugins ) == 0 )
					echo '-';
				else
					echo implode( ', <br/>', $wp_plugins );
				?>
			</td>
		</tr>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-theme">
	<thead>
		<tr>
			<th data-override="key" class="row-title">Theme</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title">Theme Name:</td>
			<td><?php
				$active_theme = wp_get_theme();
				echo $active_theme->Name;
			?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">Theme Version:</td>
			<td><?php
				echo $active_theme->Version;
			?></td>
		</tr>
		<tr>
			<td class="row-title">Theme Author URL:</td>
			<td><?php
				echo $active_theme->{'Author URI'};
			?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">Is Child Theme:</td>
			<td><?php echo is_child_theme() ? __( 'Yes', 'wpas' ) : __( 'No', 'wpas' ); ?></td>
		</tr>
		<?php
		if( is_child_theme() ) :
			$parent_theme = wp_get_theme( $active_theme->Template );
		?>
		<tr>
			<td class="row-title">Parent Theme Name:</td>
			<td><?php echo $parent_theme->Name; ?></td>
		</tr>
		<tr class="alt">
			<td class="row-title">Parent Theme Version:</td>
			<td><?php echo  $parent_theme->Version; ?></td>
		</tr>
		<tr>
			<td class="row-title">Parent Theme Author URL:</td>
			<td><?php
				echo $parent_theme->{'Author URI'};
			?></td>
		</tr>
		<?php endif ?>
	</tbody>
</table>
<table class="widefat wpas-system-status-table" id="wpas-system-status-templates">
	<thead>
		<tr>
			<th data-override="key" class="row-title">Templates</th>
			<th data-override="value"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="row-title">Overrides:</td>
			<td>
				<?php
				$theme_directory       = trailingslashit( get_template_directory() ) . 'awesome-support';
				$child_theme_directory = trailingslashit( get_stylesheet_directory() ) . 'awesome-support';
				$templates             = array(
					'details.php',
					'list.php',
					'registration.php',
					'submission.php'
				);

				if ( is_dir( $child_theme_directory ) ) {

					$overrides = wpas_check_templates_override( $child_theme_directory );

					if ( !empty( $overrides ) ) {
						echo '<ul>';
						foreach ( $overrides as $key => $override ) {
							echo "<li><code>$override</code></li>";
						}
						echo '</ul>';
					} else {
						echo 'There is no template override';
					}

				} elseif ( is_dir( $theme_directory ) ) {

					$overrides = wpas_check_templates_override( $theme_directory );

					if ( !empty( $overrides ) ) {
						echo '<ul>';
						foreach ( $overrides as $key => $override ) {
							echo "<li><code>$override</code></li>";
						}
						echo '</ul>';
					} else {
						echo 'There is no template override';
					}

				} else {
					echo 'There is no template override';
				}
				?>
			</td>
		</tr>
	</tbody>
</table>