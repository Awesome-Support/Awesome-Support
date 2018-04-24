jQuery(document).ready(function(){
	jQuery('.as-setup-content .check_radio').click(function(){
		jQuery(this).next('input[type=radio').prop("checked", true);
	});
});