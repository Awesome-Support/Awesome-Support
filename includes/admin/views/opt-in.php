<div class="wrap about-wrap">

	<h1><?php esc_html_e( 'Get Your Free Addon!', 'awesome-support' ); ?></h1>

	<div class="about-text"><?php esc_html_e( 'Wanna get more out of Awesome Support, but not yet ready to spend the cash? Get one free addon today!', 'awesome-support' ); ?></div>

	<div class="changelog">

		<div class="row">
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<div class="about-body">
					<img src="<?php echo WPAS_URL; ?>assets/admin/images/about-cf.png" alt="Improved Custom Fields">
				</div>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<div class="about-body">
					<h3>Satisfaction Survey</h3>
					<p><?php esc_attr_e( 'Are you looking to improve the quality of your support? Ask clients their opinion directly!', 'awesome-support' ); ?></p>
					<p><?php esc_attr_e( 'This extension allows you to automatically send out a survey to rate your agent’s performance on a ticket.  At its core, the add-on does something relatively straight-forward: When a ticket is closed, an email with a simple one-question survey request is sent out to the customer.', 'awesome-support' ); ?></p>
				</div>
			</div>
		</div>

		<h2><?php esc_html_e( 'How to Get Your Free Addon', 'awesome-support' ); ?></h2>

		<p><?php esc_attr_e( 'Getting your addon is dead simple: just subscribe to our newsletter hereafter and then you will get the free addon by e-mail. We will not spam you. We usually send out newsletters to talk about new major features in Awesome Support or when new addons are being released. That&#039;s it.', 'awesome-support' ); ?></p>

		<div id="wpas-mailchimp-signup-form-wrapper">
			<form action="<?php echo add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-optin' ), admin_url( 'edit.php' ) ); ?>" method="post" id="wpas-mailchimp-signup-form" name="wpas-mailchimp-signup-form">
				<table class="form-table">
					<tr>
						<td class="row-title"><label for="mce-FNAME">First Name</label> <input type="text" value="" name="FNAME" class="medium-text" id="mce-FNAME"></td>
						<td class="row-title">
							<label for="mce-EMAIL">Email Address</label>
							<input type="email" value="" name="EMAIL" class="regular-text required email" id="mce-EMAIL">
							<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button-secondary">
						</td>
					</tr>
				</table>
				<div style="position: absolute; left: -5000px;" aria-hidden="true">
					<input type="text" name="b_46ccfe899f0d2648a8b74454a_ad9db57f69" tabindex="-1" value="">
				</div>
				<div id="mce-responses" class="clear">
					<div class="wpas-alert-danger" id="wpas-mailchimp-signup-result-error" style="display:none;">Error</div>
					<div class="wpas-alert-success" id="wpas-mailchimp-signup-result-success" style="display:none; color: green;"><?php esc_html_e( 'Thanks for your subscription! You will need to confirm the double opt-in e-mail that you will receive in a coupe of minutes. After you confirmed it, you will receive the free addon directly in your inbox.', 'awesome-support' ); ?></div>
				</div>
			</form>
		</div>
	</div>

</div>