<div class="wrap about-wrap">

	<h1>Welcome to Awesome Support&nbsp;<?php echo WPAS_VERSION; ?></h1>

	<div class="about-text">Trusted by over 1000+ Happy Customers, Awesome Support is the most versatile WordPress support plugin.</div>

	<div class="changelog">

		<div class="row">
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<div class="about-body">
					<img src="<?php echo WPAS_URL; ?>assets/admin/images/about-cf.png" alt="Improved Custom Fields">
					<h3>Improved Custom Fields</h3>
					<p>This feature lets your tailor your support system to your needs, allowing you to <mark>add more fields to the ticket submission form</a>.</p>
					<p>Since version 3.1.2, custom fields become much more powerful. They are simpler to use, and many more field types are available: checkbox, email, number, radio, text, and much more. Find all the available field types in <a href="https://getawesomesupport.com/documentation/awesome-support/custom-fields/" target="_blank">the documentation</a>.</p>
				</div>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<div class="about-body">
					<img src="<?php echo WPAS_URL; ?>assets/admin/images/about-autoassign.png" alt="Auto-Assignment">
					<h3>Auto-Assignment</h3>
					<p>Awesome Support comes with an "intelligent" ticket assignment system. New tickets are automatically assigned to the agent with the least open tickets.</p>
					<p>But you can also <mark>enable/disable the auto-assignation for specific users</mark> (for instance a site administrator).</p>
				</div>
			</div>
		</div>

		<a class="wpas-bundle-link" href="https://getawesomesupport.com/addons/startup-bundle/" target="_blank">
			<img src="https://cdn.getawesomesupport.com/wp-content/uploads/2015/08/bundle-1140x200.png" alt="Startup Bundle">
		</a>

		<div class="row">
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<div class="about-body">
					<img src="<?php echo WPAS_URL; ?>assets/admin/images/about-multipleforms.png" alt="Multiple Submission Forms">
					<h3>Multiple Submission Forms</h3>
					<p>You can now create multiple submission forms for your users and pre-set values for each field. Need a for for your technical support? Create a new one and link it with the correct URL parameter to pre-populate the "type" field. More in the documentation.</p>
				</div>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<div class="about-body">
					<img src="<?php echo WPAS_URL; ?>assets/admin/images/about-translations.png" alt="Translated in many languages">
					<h3>Translated in many languages</h3>
					<p>Thanks to our contributors, the plugin is available in several languages. Currently the plugin is <mark>available in nearly 10 languages</mark>.</p>
					<p>To check out available translations, please visit <a href="https://www.transifex.com/projects/p/awesome-support/" target="_blank">our transifex project</a>.</p>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
				<div class="about-body">
					<h3>Newsfeed</h3>
					<div class="wpas-fbpage-feed">Loading...</div>
					<p><a class="button button-large button-primary" href="https://www.facebook.com/awesomesupport" target="_blank">View more news</a></p>
				</div>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
				<div class="about-body">
					<h3>Contributing</h3>
					<h4>Open Source</h4>
					<p>The code is open source and <a href="https://github.com/ThemeAvenue/Awesome-Support" target="_blank">available on GitHub</a> for anyone to contribute. Even you.</p>
					<h4>Translation Ready</h4>
					<p>The plugin is fully localized. You can <a href="https://www.transifex.com/projects/p/awesome-support/" target="_blank">translate the plugin</a> in any language!</p>
					<h4>Rate the plugin</h4>
					<p>If you like the plugin, make sure to rate it on the WordPres extend. This is perhaps one of the best way to share the love for our plugin <a href="https://wordpress.org/support/view/plugin-reviews/awesome-support" target="_blank">Rate the plugin →</a></p>
				</div>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
				<div class="about-body">
					<h3>Extending the Possibilities</h3>
					<p>Even though Awesome Support has a lot of built-in features, it is impossible to make everyone happy. This is why we have lots of addons to help you tailor your support system.</p>
					<a href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-addons' ), admin_url( 'edit.php' ) ) ); ?>" class="button button-large button-primary">Browse extensions</a>
					<p>Please also make sure to check <a href="#" target="_blank">our roadmap</a>.</p>
				</div>
			</div>
		</div>

	</div>

</div>

<script type="text/javascript" src="<?php echo WPAS_URL; ?>assets/admin/js/vendor/linkify.min.js"></script>
<script type="text/javascript" src="<?php echo WPAS_URL; ?>assets/admin/js/vendor/linkify-jquery.min.js"></script>
<script type="text/javascript" src="<?php echo WPAS_URL; ?>assets/admin/js/vendor/moment.min.js"></script>
<script type="text/javascript">
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
</script>