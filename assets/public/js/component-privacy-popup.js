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
	jQuery( "#wpas-gdpr-tab-default" ).click();
});

function openGDPRTab(evt, tab) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("wpas-gdpr-tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(tab).style.display = "block";
    evt.currentTarget.className += " active";
}

