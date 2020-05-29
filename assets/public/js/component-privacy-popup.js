/* Handles the front-end logic for the privacy popup. */
/* Will NOT be included in the public-dist.js because of an exclusion in gruntfile.js */

jQuery(document).ready(function ($) {

	jQuery( ".privacy-container-template" ).on( "click", ".download-file-link", function(e) {	
		 jQuery(this).parent('p').remove();
	});

	jQuery( '.wpas-gdpr-pre-loader' ).hide();
	jQuery( '.wpas-gdpr-loader-background' ).hide();

	jQuery('.wpas-link-privacy').click(function(){
		jQuery(".privacy-container-template").show();
		jQuery(document).keyup(function(e) {
			if (e.keyCode == 27) jQuery(".privacy-container-template").hide();
		});

		jQuery(".privacy-container-template .hide-the-content").click(function(){
			jQuery(".privacy-container-template").hide();
		});
	});

	/**
	 * Ajax based ticket submission for "Right To Be Forgotten"
	 * in "Delete My Existing Data" from GDPR popup
	 */
	jQuery( "#wpas-gdpr-ded-submit" ).click( function(e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		jQuery( '.wpas-gdpr-pre-loader' ).show();
		jQuery( '.wpas-gdpr-loader-background').show();

		/**
		 * Get current tinyMCE content
		 * NOTE: We cannot get the content wpas_set_editor_content()
		 * on submission. This will be the workaround.
		 */
		var activeEditor_content = tinyMCE.activeEditor.getContent();
		jQuery( '#wpas-gdpr-ded-more-info' ).html( activeEditor_content );

		var data = {
			'action': 'wpas_gdpr_open_ticket',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
				'request_type': 'delete',
				'form-data'	: $( '#wpas-gdpr-rtbf-form' ).serialize()
			}
		};
		jQuery.post(
			WPAS_GDPR.ajax_url,
			data,
			function( response ) {
				jQuery( '.wpas-gdpr-pre-loader' ).hide();
				jQuery( '.wpas-gdpr-loader-background').hide();
				if( response.message && response.code === 200 ) {
					jQuery( '.wpas-gdpr-notice.delete-existing-data' ).addClass( 'success' ).html( '<p>' + response.message + '</p>' );
					jQuery( '.wpas-gdpr-form-table' ).remove();
				}else{
					jQuery( '.wpas-gdpr-notice.delete-existing-data' ).addClass( 'failure' ).html( '<p>' + response.message + '</p>' );
				}
			}
		);		
	});

	/**
	 * Ajax based ticket submission
	 * in "Export My Existing Data" from GDPR popup
	 */
	jQuery( "#wpas-gdpr-export-submit" ).click( function(e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		jQuery( '.wpas-gdpr-pre-loader' ).show();
		jQuery( '.wpas-gdpr-loader-background').show();

		/**
		 * Get current tinyMCE content
		 * NOTE: We cannot get the content wpas_set_editor_content()
		 * on submission. This will be the workaround.
		 */
		if( jQuery( '#wpas-gdpr-export-more-info' ).length > 0 ) {
			var activeEditor_content = tinyMCE.activeEditor.getContent();
			jQuery( '#wpas-gdpr-export-more-info' ).html( activeEditor_content );
		}
		var data = {
			'action': 'wpas_gdpr_open_ticket',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
				'request_type': 'export',
				'form-data'	: $( '#wpas-gdpr-rted-form' ).serialize()
			}
		};
		jQuery.post(
			WPAS_GDPR.ajax_url,
			data,
			function( response ) {
				jQuery( '.wpas-gdpr-pre-loader' ).hide();
				jQuery( '.wpas-gdpr-loader-background').hide();
				if( response.message && response.code === 200 ) {
					jQuery( '.wpas-gdpr-notice.export-existing-data' ).addClass( 'success' ).html( '<p>' + response.message + '</p>' );
					jQuery( '.wpas-gdpr-form-table' ).remove();
				}else{
					jQuery( '.wpas-gdpr-notice.export-existing-data' ).addClass( 'failure' ).html( '<p>' + response.message + '</p>' );
				}
			}
		);		
	});

	/**
	 * Ajax based Opted in button processing
	 * in "Add/Remove Consent" from GDPR popup
	 */
	jQuery( ".privacy-container-template" ).on( "click", ".wpas-gdpr-opt-in", function(e) {	
		e.preventDefault();
		var optin_handle = jQuery(this);
		jQuery( '.wpas-gdpr-pre-loader' ).show();
		jQuery( '.wpas-gdpr-loader-background').show();

		var data = {
			'action': 'wpas_gdpr_user_opt_in',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
				'gdpr-data'	: jQuery(this).data( 'gdpr' ),
				'gdpr-user'	: jQuery(this).data( 'user' ),
				'gdpr-optout'	: jQuery(this).data( 'optout-date' )
			}
		};
		
		jQuery.post(
			WPAS_GDPR.ajax_url,
			data,
			function( response ) {
				jQuery( '.wpas-gdpr-pre-loader' ).hide();
				jQuery( '.wpas-gdpr-loader-background').hide();
				if( undefined !== response.message.success ){
					if( undefined !== response.message.date ){
						optin_handle.parent('td').siblings('td:nth-child(3)').html(response.message.date);
					}
					if( undefined !== response.message.status ){
						optin_handle.parent('td').siblings('td:nth-child(2)').html(response.message.status);
					}
					if( undefined !== response.message.button ){
						optin_handle.parent('td').html( response.message.button );
					}

					jQuery( '.wpas-gdpr-notice.add-remove-consent' ).addClass( 'success' ).html( '<p>' + response.message.success + '</p>' );
				} else if( undefined !== response.message.error ){
					jQuery( '.wpas-gdpr-notice.add-remove-consent' ).addClass( 'failure' ).html( '<p>' + response.message.error + '</p>' );
				}
			}
		);		
	});

	/**
	 * Ajax based Opted out button processing
	 * in "Add/Remove Consent" from GDPR popup
	 */
	jQuery( ".privacy-container-template" ).on( "click", ".wpas-gdpr-opt-out", function(e) {
		e.preventDefault();
		var handle = jQuery(this);
		jQuery( '.wpas-gdpr-pre-loader' ).show();
		jQuery( '.wpas-gdpr-loader-background').show();

		var data = {
			'action': 'wpas_gdpr_user_opt_out',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
				'gdpr-data'	: jQuery(this).data( 'gdpr' ),
				'gdpr-user'	: jQuery(this).data( 'user' ),
				'gdpr-optin'	: jQuery(this).data( 'optin-date' )
			}
		};
		
		jQuery.post(
			WPAS_GDPR.ajax_url,
			data,
			function( response ) {
				jQuery( '.wpas-gdpr-pre-loader' ).hide();
				jQuery( '.wpas-gdpr-loader-background').hide();
				if( undefined !== response.message.success ){
					if( undefined !== response.message.date ){
						handle.parent('td').siblings('td:nth-child(4)').html( response.message.date );
					}
					if( undefined !== response.message.status ){
						handle.parent('td').siblings('td:nth-child(2)').html(response.message.status);
					}
					if( undefined !== response.message.button ){
						handle.parent('td').html( response.message.button );
					}
					jQuery( '.wpas-gdpr-notice.add-remove-consent' ).addClass( 'success' ).html( '<p>' + response.message.success + '</p>' );
				} else if( undefined !== response.message.error ){
					jQuery( '.wpas-gdpr-notice.add-remove-consent' ).addClass( 'failure' ).html( '<p>' + response.message.error + '</p>' );
				}
			}
		);		
    });
    
    /**
	 * Ajax based export data
	 */
	jQuery( "#wpas-gdpr-export-data-submit" ).click( function(e) {
		e.preventDefault();
		jQuery( '.wpas-gdpr-pre-loader' ).show();
		jQuery( '.wpas-gdpr-loader-background').show();

		var data = {
			'action': 'wpas_gdpr_export_data',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
				'gdpr-user'	: jQuery(this).data( 'user' )
			}
		};
		
		jQuery.post(
			WPAS_GDPR.ajax_url,
			data,
			function( response ) {
				jQuery( '.wpas-gdpr-pre-loader' ).hide();
				jQuery( '.wpas-gdpr-loader-background').hide();
				if( undefined !== response.message.success ){
					jQuery( '.export-data' ).addClass( 'success' ).html( response.message.success );
				} else if( undefined !== response.message.error  ) {
					jQuery( '.export-data' ).addClass( 'failure' ).html( response.message.error );
				}
			}
		);		
	});

	/**
	 * Set tab default
	 */
	jQuery( ".wpas-gdpr-tablinks" ).first().click();
	
	/**
	 * Initiate WP Editor when requesting right
	 * to be deleted data in GDPR popup
	 */
	jQuery( ".wpas-gdpr-tablinks" ).click( function(e) {
		if( jQuery(this).data( 'id' ) === 'delete-existing'  ) {
			/**
			 * If the Additional Information is set
			 */
			if( jQuery( '#wpas-gdpr-ded-more-info' ).length > 0 ) {
				wpas_init_editor( 'wpas-gdpr-ded-more-info', '' );
			}
		}
		if( jQuery(this).data( 'id' ) === 'export-existing'  ) {
			/**
			 * If the Additional Information is set
			 */
			if( jQuery( '#wpas-gdpr-export-more-info' ).length > 0 ) {
				wpas_init_editor( 'wpas-gdpr-export-more-info', '' );
			}
		}
	});
});

/**
 * Set content on WP Editor
 * 
 * @param {*} content 
 * @param {*} editor_id 
 * @param {*} textarea_id 
 */
function wpas_set_editor_content( content, editor_id, textarea_id ){
	if ( typeof editor_id == 'undefined' ) editor_id = wpActiveEditor;
  	if ( typeof textarea_id == 'undefined' ) textarea_id = editor_id;
  
	if ( jQuery('#wp-'+editor_id+'-wrap').hasClass('tmce-active') && tinyMCE.get(editor_id) ) {
	    return tinyMCE.get(editor_id).setContent(content);
	}else{
	    return jQuery('#'+textarea_id).val(content);
	}
}

/**
 * Initialize WP Editor through Javascript
 * 
 * @param {*} this_id 
 * @param {*} content 
 */
function wpas_init_editor( this_id, content ){
	/**
	 * Prepare the standard settings. Include the media buttons plus toolbars
	 */
	settings = {
	    mediaButtons:	false,
	    tinymce:	{
	        toolbar1: 'bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,strikethrough,hr,forecolor,pastetext,removeformat,codeformat,undo,redo'
	    },
	    quicktags:		true,
	};
	
	wp.editor.remove(this_id);
	/**
	 * Initialize editor
	*/
	wp.editor.initialize( this_id, settings );
	/**
	 * Set editor content. This function set the 
	 * editor content back to textarea as well
	*/
	wpas_set_editor_content( content, this_id );
}

/**
 * A function for switching tabs in GDPR pop up
 * 
 * @param {*} evt 
 * @param {*} tab 
 */
function wpas_gdpr_open_tab(evt, tab) {
    // Declare all variables!
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them!
    tabcontent = document.getElementsByClassName("wpas-gdpr-tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"!
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab!
    document.getElementById(tab).style.display = "block";
    evt.target.className += " active";
}
