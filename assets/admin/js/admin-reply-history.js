(function ($) {
	"use strict";

	$(function () {
        $(".wpas-show-reply-history").click(function(e) {
            e.preventDefault();
            $(".pop").fadeIn("slow");
            /**
             * Display history using Ajax call
             */
          });
          
          $(".pop i").click(function() {
            $(".pop").fadeOut("fast");
          });
          
          //Thanks for Iphone titlebar fix http://coding.smashingmagazine.com/2013/05/02/truly-responsive-lightbox/
          
          var getIphoneWindowHeight = function() {
            // Get zoom level of mobile Safari
            // Such zoom detection might not work correctly on other platforms
            //
            var zoomLevel = document.documentElement.clientWidth / window.innerWidth;
          
            // window.innerHeight returns height of the visible area.
            // We multiply it by zoom and get our real height.
            return window.innerHeight * zoomLevel;
          };        
    });

}(jQuery));