<?php 

function my_plugin_enqueue_scripts_and_styles() {
    wp_enqueue_style(
        'wpas-editor-email-template-tags-style',
        plugins_url( 'includes/wpas_editor_email_template_tags.css', __FILE__ ),
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'wpas-editor-email-template-tags-script',
        plugins_url( 'includes/wpas_editor_email_template_tags.js', __FILE__ ),
        array(),
        '1.0.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_scripts_and_styles' );

?>
<body>

	<?php
    echo '<div>';
    	
    	echo '<p id="instructions"></p>';
		echo '<div id="template_tags"></div>';
    echo '</div>';
	?>
</body>