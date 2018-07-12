<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_cronjob', 5, 1 );
/**
 * Add plugin core settings for fields and custom fields.
 *
 * @param  array $def Array of existing settings
 *
 * @return array      Updated settings
 */
function wpas_core_settings_cronjob( $def ) {

	$settings = array(
		'cronjob' => array(
			'name'    => __( 'Cron Job', 'awesome-support' ),
			'options' => array(
				array(
					'name' => __( 'Cron Job to Anonymize tickets after a certain days', 'awesome-support' ),
					'type' => 'heading',
				),
				array(
					'name'    => __( 'Cron Job', 'awesome-support' ),
					'id'      => 'cron_job',
					'type'    => 'checkbox',
					'desc'    => __( 'Enable Cron management.', 'awesome-support' ),
					'default' => false
				),	
				array(
					'name'    => __( 'Cron Job Trigger Time', 'awesome-support' ),
					'id'      => 'anonymize_cronjob_trigger_time',
					'type'    => 'number',
					'desc'    => __( 'Cron job Trigger time in minute', 'awesome-support' ),
					'max'	  => 10000,
					'default' => 1440
				),
				array(
					'name'    => __( 'Ticket Max Age', 'awesome-support' ),
					'id'      => 'anonymize_cronjob_max_age',
					'type'    => 'number',
					'desc'    => __( 'Cron job Max Age in days', 'awesome-support' ),
					'default' => false
				),

			),
	
		),
	);

	return array_merge( $def, $settings );

}
