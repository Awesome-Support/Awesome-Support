jQuery(document).ready(function ($) {

	/**
	 * Condition to prevent JavaScript error. It checks if:
	 * 1) Selector exists
	 * 2) jQuery Select2 is loaded
	 * 3) WordPress AJAX is enabled
	 */
	/**
	 * Select2 inserts placeholder values with escapeMarkup(). Its default
	 * returns non-strings unchanged, so a placeholder whose text is an array
	 * is later written as HTML. Always coerce to a string and escape it.
	 */
	function wpasEscapeSelect2Markup(markup) {
		var replaceMap = {
			'\\': '&#92;',
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#39;',
			'/': '&#47;'
		};

		if (markup === null || typeof markup === 'undefined') {
			return '';
		}

		if (typeof markup !== 'string') {
			markup = String(markup);
		}

		return markup.replace(/[&<>"'\/\\]/g, function (match) {
			return replaceMap[match];
		});
	}

	/*
	 * Only <select> elements rendered by the plugin. Ticket and reply HTML is
	 * inside these containers and must not be able to start this initializer.
	 */
	var selector = $('select.wpas-select2').not('.wpas-ticket-content select, .wpas-reply-content select, .wpas-main-ticket-message select');
	var condition = selector.length && $.fn.select2 && typeof ajaxurl !== 'undefined';
	if (condition) getUserList( selector );

	/**
	 * Get User List via AJAX
	 * https://select2.github.io/examples.html#data-ajax
	 */
        
        
        
        
	function getUserList( selector ) {
		selector.each(function (index, el) {
			var capability = $(el).attr('data-capability');
			if (capability) {
				$(el).select2({
					ajax: {
						url: ajaxurl,
						dataType: 'json',
						type: 'POST',
						delay: 250,
						data: function (params) {
							return {
								action: 'wpas_get_users',
								get_users_nonce: WPAS_get_users.get_users_nonce,
								cap: capability,
								q: params.term
							};
						},
						processResults: function (data, params) {
							return {
								results: $.map(data, function (obj) {
									return {
										id: obj.user_id,
										text: "#" +  obj.user_id + " " + obj.user_name + " (" +  obj.user_email + ")"
									};
								})
							};
						}
					},
					minimumInputLength: 3,
					escapeMarkup: wpasEscapeSelect2Markup
				});
			}
		});
	}
        
        window.getUserListSelect2 = getUserList;

});
