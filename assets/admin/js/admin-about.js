jQuery(document).ready(function ($) {

	/**
	 * Get Page Newsfeed
	 * https://developers.facebook.com/docs/graph-api/reference/v2.4/page/feed
	 * https://developers.facebook.com/tools/accesstoken/
	 */

	var pageId = '312003622332316',
		accessToken = '1059973257368113|x_ZhJSNE-sF4cWPH-iggQFeRa70',
		container = $('.wpas-fbpage-feed'),
		feedItems = '';

	if (sessionStorage.getItem('awesome_support_newsfeed')) {
		renderNewsfeed();
	} else {
		$.getJSON('https://graph.facebook.com/v2.2/' + pageId + '/posts?access_token=' + accessToken, function (json, textStatus) {
			sessionStorage.setItem('awesome_support_newsfeed', JSON.stringify(json));
			renderNewsfeed();
		});
	}

	function renderNewsfeed() {
		var json = $.parseJSON(sessionStorage.getItem('awesome_support_newsfeed'));

		$.each(json.data, function (i, val) {
			feedItems += '<p><strong>' + moment(val.created_time).fromNow() + '</strong><br>' + val.message + '</p>';
			return i < 2;
		});

		container.html('').append(feedItems).linkify({
			target: '_blank'
		});
	}

});