(function ($) {
    "use strict";

    $(function () {

        /**
         * Function that will hide all replies except the last two on the page.
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