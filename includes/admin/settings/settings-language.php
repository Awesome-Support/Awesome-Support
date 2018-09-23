<?php
add_filter( 'wpas_plugin_settings', 'wpas_core_settings_language', 95, 1 );
/**
 * Add plugin language settings and notices
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function wpas_core_settings_language( $def ) {

	$settings = array(
		'Language' => array(
			'name'    => __( 'Language Options', 'awesome-support' ),
			'options' => array(
			
				array(
					'name'    => __( 'Language Notices', 'awesome-support' ),
					'id'      => 'language_notices',
					'type'    => 'note',
					'desc'    => sprintf( __( 'Awesome Support includes full and partial translations for many languages. These are automatically applied when you change the WordPress language settings.<br /><br /> Learn how you can add, change and update translation terms in this document: %s', 'awesome-support' ), '<a href="https://getawesomesupport.com/documentation/awesome-support/translations/" target="_blank">' . 'Translations in Awesome Support' . '</a>' ),					
				),				

			)
		),
	);

	return array_merge( $def, apply_filters('wpas_settings_language', $settings )  );

}