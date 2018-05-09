jQuery(document).ready(function ($) {
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
		jQuery( '.wpas-gdpr-pre-loader' ).show();
		jQuery( '.wpas-gdpr-loader-background').show();

		var data = {
			'action': 'wpas_gdpr_open_ticket',
			'security' : WPAS_GDPR.nonce,
			'data' 	: {
				'nonce'		: WPAS_GDPR.nonce,
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
					jQuery( '.wpas-gdpr-notice' ).addClass( 'success' ).html( '<p>' + response.message + '</p>' );
					jQuery( '.wpas-gdpr-form-table' ).remove();
				}else{
					jQuery( '.wpas-gdpr-notice' ).addClass( 'failure' ).html( '<p>' + response.message + '</p>' );
				}
			}
		);		
	});

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
				console.log(response);
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
				console.log(response);
			}
		);		
	});

	/**
	 * Set tab default
	 */
	jQuery( "#wpas-gdpr-tab-default" ).click();
	
	/**
	 * Initiate WP Editor when requesting right
	 * to be deleted data in GDPR popup
	 */
    wpas_init_editor( 'wpas-gdpr-ded-more-info', '' );
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
	    quicktags:		false,
	};
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

