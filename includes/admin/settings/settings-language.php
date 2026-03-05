<?php
add_filter( 'mumei_ayuda_plugin_settings', 'mumei_ayuda_core_settings_language', 95, 1 );
/**
 * Add plugin language settings and notices
 * 
 * @param  (array) $def Array of existing settings
 * @return (array)      Updated settings
 */
function mumei_ayuda_core_settings_language( $def ) {

	// translators: %s is the link to the documentation.
	$desc = __( 'Ayuda – Help Desk includes full and partial translations for many languages. These are automatically applied when you change the WordPress language settings.<br /><br /> Learn how you can add, change and update translation terms in this document: %s', 'ayuda-help-desk' );
	$settings = array(
		'Language' => array(
			'name'    => __( 'Language Options', 'ayuda-help-desk' ),
			'options' => array(
			
				array(
					'name'    => __( 'Language Notices', 'ayuda-help-desk' ),
					'id'      => 'language_notices',
					'type'    => 'note',
					'desc'    => sprintf( $desc, '<a href="https://getawesomesupport.com/documentation/ayuda-help-desk/translations/" target="_blank">' . 'Translations in Ayuda – Help Desk' . '</a>' ),					
				),				

			)
		),
	);

	return array_merge( $def, apply_filters('mumei_ayuda_settings_language', $settings )  );

}