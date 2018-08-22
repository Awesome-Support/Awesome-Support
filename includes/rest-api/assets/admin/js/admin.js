/* global wpasAPI, console, wp */
(function($,wpasAPI){
	var $appPassSection           = $( '#wpas-api-section' ),
		$newAppPassForm           = $appPassSection.find( '.create-wpas-api-password' ),
		$newAppPassField          = $newAppPassForm.find( '.input' ),
		$newAppPassButton         = $newAppPassForm.find( '.button' ),
		$appPassTwrapper          = $appPassSection.find( '.wpas-api-list-table-wrapper' ),
		$appPassTbody             = $appPassSection.find( 'tbody' ),
		$appPassTrNoItems         = $appPassTbody.find( '.no-items' ),
		$removeAllBtn             = $( '#revoke-all-wpas-api-passwords' ),
		tmplNewAppPass            = wp.template( 'new-wpas-api-password' ),
		tmplAppPassRow            = wp.template( 'wpas-api-password-row' ),
		tmplNotice                = wp.template( 'wpas-api-password-notice' ),
		testBasicAuthUser         = Math.random().toString( 36 ).replace( /[^a-z]+/g, '' ),
		testBasicAuthPassword     = Math.random().toString( 36 ).replace( /[^a-z]+/g, '' );

	$.ajax( {
		url:        wpasAPI.root + wpasAPI.namespace + '/test-basic-authorization-header',
		method:     'POST',
		beforeSend: function( xhr ) {
			xhr.setRequestHeader( 'Authorization', 'Basic ' + btoa( testBasicAuthUser + ':' + testBasicAuthPassword ) );
		},
		error:      function( jqXHR ) {
			if ( 404 === jqXHR.status ) {
				$newAppPassForm.before( tmplNotice( {
					type:    'error',
					message: wpasAPI.text.no_credentials
				} ) );
			}
		}
	} ).done( function( response ) {
		if ( response.PHP_AUTH_USER === testBasicAuthUser && response.PHP_AUTH_PW === testBasicAuthPassword ) {
			// Save the success in SessionStorage or the like, so we don't do it on every page load?
		} else {
			$newAppPassForm.before( tmplNotice( {
				type:    'error',
				message: wpasAPI.text.no_credentials
			} ) );
		}
	} );

	$newAppPassButton.click( function( e ) {
		e.preventDefault();
		var name = $newAppPassField.val();

		if ( 0 === name.length ) {
			$newAppPassField.focus();
			return;
		}

		$newAppPassField.prop( 'disabled', true );
		$newAppPassButton.prop( 'disabled', true );

		$.ajax( {
			url:        wpasAPI.root + wpasAPI.namespace + '/users/' + wpasAPI.user_id + '/passwords',
			method:     'POST',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpasAPI.nonce );
			},
			data:       {
				name : name
			}
		} ).done( function( response ) {
			$newAppPassField.prop( 'disabled', false ).val('');
			$newAppPassButton.prop( 'disabled', false );

			$newAppPassForm.after( tmplNewAppPass( {
				name:     name,
				password: response.password
			} ) );

			delete response.password;

			$appPassTbody.prepend( tmplAppPassRow( response ) );

			$appPassTwrapper.show();
			$appPassTrNoItems.remove();
		} );
	});

	$appPassTbody.on( 'click', '.delete', function( e ) {
		e.preventDefault();
		var $tr  = $( e.target ).closest( 'tr' ),
			slug = $tr.data( 'slug' );

		$.ajax( {
			url:        wpasAPI.root + wpasAPI.namespace + '/users/' + wpasAPI.user_id + '/passwords/' + slug,
			method:     'DELETE',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpasAPI.nonce );
			}
		} ).done( function ( response ) {
			if ( response.deleted ) {
				if ( 0 === $tr.siblings().length ) {
					$appPassTwrapper.hide();
				}
				$tr.remove();
			}
		} );
	});

	$removeAllBtn.on( 'click', function( e ) {
		e.preventDefault();

		$.ajax( {
			url:        wpasAPI.root + wpasAPI.namespace + '/users/' + wpasAPI.user_id + '/passwords',
			method:     'DELETE',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpasAPI.nonce );
			}
		} ).done( function( response ) {
			if ( response.deleted ) {
				$appPassTbody.children().remove();
				$appPassSection.children( '.new-wpas-api-password' ).remove();
				$appPassTwrapper.hide();
			}
		} );
	});

	$( document ).on( 'click', '.wpas-api-password-modal-dismiss', function( e ) {
		e.preventDefault();

		$('.new-wpas-api-password.notification-dialog-wrap').hide();
	});

	// If there are no items, don't display the table yet.  If there are, show it.
	if ( 0 === $appPassTbody.children( 'tr' ).not( $appPassTrNoItems ).length ) {
		$appPassTwrapper.hide();
	}
})( jQuery, wpasAPI );