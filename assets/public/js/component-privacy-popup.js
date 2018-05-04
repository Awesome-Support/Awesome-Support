jQuery(document).ready(function ($) {
	jQuery('.wpas-link-privacy').click(function(){
		jQuery(".privacy-container-template").show();
		jQuery(document).keyup(function(e) {
			if (e.keyCode == 27) jQuery(".privacy-container-template").hide();
		});

		jQuery(".privacy-container-template .hide-the-content").click(function(){
			jQuery(".privacy-container-template").hide();
		});
	}); 
});
