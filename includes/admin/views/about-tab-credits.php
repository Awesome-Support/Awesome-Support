<div class="row row-wpas-more">	
	<div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
		<div class="about-body">
			<h2><?php echo esc_html__( 'Newsfeed', 'awesome-support' );?></h2>
			<div class="wpas-fbpage-feed"><?php echo esc_html__( 'Loading...', 'awesome-support' );?></div>
			<p><a class="button button-large button-primary" href="https://www.facebook.com/awesomesupport" target="_blank"><?php echo esc_html__( 'View more news', 'awesome-support' );?></a></p>
		</div>
	</div>
	<div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
		<div class="about-body">
			<h2><?php echo esc_html__( 'Contributing', 'awesome-support' );?></h2>
			<h4><?php echo esc_html__( 'Open Source', 'awesome-support' );?></h4>
			<p><?php echo __( 'The code is open source and <a href="https://github.com/ThemeAvenue/Awesome-Support" target="_blank">available on GitHub</a> for anyone to contribute. Even you.', 'awesome-support' );?></p>
			<h4><?php echo esc_html__( 'Translation Ready', 'awesome-support' );?></h4>
			<p><?php echo __( 'The plugin is fully localized. You can <a href="https://poeditor.com/join/project/P6HgfPnBt4" target="_blank">translate the plugin</a> in any language!', 'awesome-support' );?></p>
			<h4><?php echo esc_html__( 'Rate the plugin', 'awesome-support' );?></h4>
			<p><?php echo esc_html__( 'If you like the plugin, make sure to rate it on the WordPress Repository website. This is perhaps one of the best way to share the love for our plugin!', 'awesome-support' );?></p>
			<a href="https://wordpress.org/support/view/plugin-reviews/awesome-support?rate=5#postform" target="_blank"><?php echo esc_html__( 'Rate the plugin now!', 'awesome-support' );?> →</a>
		</div>
		
		<div class="about-body">
			<h2><?php echo esc_html__( 'Libraries', 'awesome-support' );?></h2>
			<h4><?php echo esc_html__( 'Open Source Libraries', 'awesome-support' );?></h4>
			<p><?php echo esc_html__( 'The following open source libraries were used in this plugin. We are very grateful to the authors for sharing their work so freely with us and the world!', 'awesome-support' );?></p>
			<a href="https://github.com/select2/select2" target="_blank">Select2</a><br />
			<a href="https://getcomposer.org/" target="_blank">Composer</a><br />
			<a href="https://github.com/ericmann/wp-session-manager" target="_blank">Eric Mann's wp-session manager</a><br />
			<a href="https://fooplugins.github.io/FooTable/" target="_blank">Footable</a><br />
			<a href="http://flexboxgrid.com/" target="_blank">Flexbox Grid</a><br />
			<a href="http://catc.github.io/simple-hint/" target="_blank">Simple Hint</a><br />
			<a href="https://github.com/gregjacobs/Autolinker.js/" target="_blank">Autolinker JS</a><br />
			<a href="https://noelboss.github.io/featherlight/" target="_blank">Featherlight</a><br />
			<a href="https://github.com/cloudfour/hideShowPassword" target="_blank">hideShowPasword</a><br />
		</div>		
		
	</div>
	<div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
		<div class="about-body">
			<h2><?php echo esc_html__( 'Extending the Possibilities', 'awesome-support' );?></h2>
			<p><?php echo esc_html__( 'Even though Awesome Support has a lot of built-in features, it is impossible to make everyone happy. This is why we have lots of addons to help you tailor your support system.', 'awesome-support' );?></p>
			<a href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'ticket', 'page' => 'wpas-addons' ), admin_url( 'edit.php' ) ) ); ?>" class="button button-large button-primary"><?php echo esc_html__( 'Browse extensions', 'awesome-support' );?></a>
		</div>
	</div>

</div>