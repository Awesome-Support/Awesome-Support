(function ($) {
    "use strict";

    $(function () {

        /**
         * Function that will hide all replies except the last two on the page.
		 *
		 * This is called by a click event from a button on the ticket details page toolbar
		 *
         */		 
		function hideReplies() {	
			var rows = $('.wpas-ticket-reply');  // Get all the reply rows
			var otherrows = $('.wpas-table-replies .wpas-table-row').not('.wpas-ticket-reply');  // Get all other rows

			/* Hide Regular Reply Rows */
			var rowCount = rows.length;  // Count of strictly the replies (not notes or log records)
			if ( rowCount > 2 ) {
				rows = rows.slice(0,rowCount-1);
				rows.toggle();	
				otherrows.toggle();
			}
			
			/* Hide other rows but only if the existing replies is less than 2. If the number of replies was more than 2 rows would already be hidden above. */
			if ( rowCount < 2 && otherrows.length > 0 ) {
				otherrows.toggle();
			}
			
			/* Go to top of table of replies */
			var topTarget = $('.wpas-table-replies');
			$('html, body').animate({ scrollTop: topTarget.offset().top }, 500); 			
		}
		
		var lnkToggleRepliesTop 	= $('#wpas-collapse-replies-top');  	// Get a handle to the TOGGLE REPLIES link at the top of the reply list
		var lnkToggleRepliesBottom 	= $('#wpas-collapse-replies-bottom');  	// Get a handle to the TOGGLE REPLIES link at the bottom of the reply list
		lnkToggleRepliesTop.click( function() { hideReplies(); } ) ;  		// When its clicked, call our hideReplies function above.	
		lnkToggleRepliesBottom.click( function() { hideReplies(); } ) ;  	// When its clicked, call our hideReplies function above.

        /**
         * Function that will hide the TOGGLE REPLES links/buttons if the number of reply rows is 3 or less.
         */		 
		function hideToggleReplyLinks() {	
			var rows = $('.wpas-table-replies .wpas-table-row');  // Get all the reply rows
			if ( rows.length <= 3 ) {
				$('#wpas-collapse-replies-top').hide();
				$('#wpas-collapse-replies-bottom').hide();
			}
		}
		hideToggleReplyLinks();
		

		 
    });

}(jQuery));

/***********************************************************************
* All functions below this are GLOBAL in scope so that other WPAS 
* Javascript modules can call them!
***********************************************************************/

/**
 * Function that will hide the success and message areas on the toolbar
 *
 * Visibility: GLOBAL
 *
 */
function wpas_toggle_ToolBar_Message_Area() {
	(function($){
		
		var msgarea = $('#wpas-tb01-msg-area');  // Get a jquery object handle to all the toolbar message areas - should only be one though.
		msgarea.toggle();
		
	})(jQuery);		
}
wpas_toggle_ToolBar_Message_Area(); // make sure the area is initially hidden.

/**
 * Function that will write a message to the message area of the toolbar
 *
 * Visibility: GLOBAL
 *
 * @params string 	imessage 	The message to show in the bar
 * @params boolean	successflag Whether to decorate the text with a green or red indicator
 * 
 */
function wpas_add_toolbar_msg(imessage = '', successflag = true) {
	
	(function($){
		
		var msg = $('#wpas-tb01-msg-area .wpas_btn_msg')  // Get a jquery object handle to the message text inside the toolbar message area - should only be one!
		$(msg).find('p').html(imessage); // find the empty paragraph area inside of it and add the text message to that paragraph
		$('#wpas-tb01-msg-area').show(); // show the message area
		
		if( successflag ) {
				msg.addClass('updated').removeClass('error');
		} else {
				msg.addClass('error').removeClass('updated');
		}
		
	})(jQuery);		
	
}		 
