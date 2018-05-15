jQuery(document).ready(function ($) {

    /**
	 * Ajax based Opted in button processing
	 * in "Add/Remove Consent" from GDPR popup
	 */
	jQuery( ".wpas-gdpr-opt-in" ).click( function(e) {
        e.preventDefault();
        
		var data = {
			'action': 'wpas_gdpr_user_opt_in',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
				'gdpr-data'	: jQuery(this).data( 'gdpr' ),
				'gdpr-user'	: jQuery(this).data( 'user' )
			}
		};
		
		jQuery.post(
			WPAS_GDPR.ajax_url,
			data,
			function( response ) {
				alert( response.message );
			}
		);		
	});

	/**
	 * Ajax based Opted out button processing
	 * in "Add/Remove Consent" from GDPR popup
	 */
	jQuery( ".wpas-gdpr-opt-out" ).click( function(e) {
		e.preventDefault();
		var data = {
			'action': 'wpas_gdpr_user_opt_out',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
				'gdpr-data'	: jQuery(this).data( 'gdpr' ),
				'gdpr-user'	: jQuery(this).data( 'user' )
			}
		};
		
		jQuery.post(
			WPAS_GDPR.ajax_url,
			data,
			function( response ) {
				alert( response.message );
			}
		);		
    });
    
});