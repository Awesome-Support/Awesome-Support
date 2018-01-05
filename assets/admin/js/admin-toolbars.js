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
*
* @TODO: At some point they should all be put into a class/object so that
* they're not all in the global scope!!!
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
 * @params integer  ttl			The amount of time the message will be displayed before automatically disppearing. Must be greater than zero.
 * 
 */
function wpas_add_toolbar_msg(imessage = '', successflag = true, ttl = 0 ) {
	
	(function($){
		
		var dismissibleText = '<button type="button" class="notice-dismiss" id="wpas-toolbar-dismissible"><span class="screen-reader-text">X</span></button>';  // Allows you to show the "X" that will dismiss the message shown.
		var msg = $('#wpas-tb01-msg-area .wpas_btn_msg')  // Get a jquery object handle to the message text inside the toolbar message area - should only be one!
		$(msg).find('p').html(dismissibleText + imessage); // find the paragraph area inside of it and add the text message to that paragraph
		$('#wpas-tb01-msg-area').show(); // show the message area
		
		if( successflag ) {
				msg.addClass('updated').removeClass('error');
		} else {
				msg.addClass('error').removeClass('updated');
		}

		/* If the dismissible text is clicked hide the entire message area */
		$('#wpas-toolbar-dismissible').click(function() {
                $('#wpas-tb01-msg-area').hide();
        });
		
		/* Automatically hide the message after a few seconds seconds */
		if ( ttl > 0 ) {
			setTimeout(function() {
			  $("#wpas-tb01-msg-area").hide();
			}, 1000 * ttl);
		}
		
	})(jQuery);		
	
}

/**
 * Function that will enable a "loading" spinner.  
 * It actually just disables/enables a class where 
 * CSS does the actual hard work.
 *
 * Visibility: GLOBAL
 *
  */
function wpas_add_toolbar_loading_spinner() {
	(function($){	
                
		var loader = $('<div class="spinner wpas_toolbar_loading_spinner"></div>');  // create the jQuery div object with the spinner class.
		var msgarea = $('#wpas-tb01-msg-area');  // Get a jquery object handle to all the toolbar message areas - should only be one though.		
		loader.css({visibility: 'visible'}).insertBefore(msgarea);
		
	})(jQuery);					
}

/**
 * Function that will disable the "loading" spinner.  
 * It actually just disables a class where 
 * CSS does the actual hard work.
 *
 * Visibility: GLOBAL
 *
  */
function wpas_remove_toolbar_loading_spinner() {
	(function($){	
                
		var loader = $('.wpas_toolbar_loading_spinner');  // Find the spinner class in the toolbar area...
		loader.remove();  // Remove it.
		
	})(jQuery);					
}