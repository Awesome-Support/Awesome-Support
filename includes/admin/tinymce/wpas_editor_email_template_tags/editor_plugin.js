(function() {
    tinymce.create('tinymce.plugins.wpas_editor_email_template_tags', {
		
        // Initialize plugin
        init : function(ed, url) {
 			
			// Add button
			ed.addButton('wpas_editor_email_template_tags', {
				
                title : ed.getLang('wpas_editor_langs.button_title'),
                cmd : 'wpas_template_tags',
				image: url + '/images/brackets_icon.png'
            });
			
			// Add button command and open window manager
			ed.addCommand('wpas_template_tags', function() {
				
                var winW = 630, winH = 460;
				if (document.body && document.body.offsetWidth) {
					winW = document.body.offsetWidth;
					winH = document.body.offsetHeight;
				}
				if (document.compatMode=='CSS1Compat' &&
					document.documentElement &&
					document.documentElement.offsetWidth ) {
					winW = document.documentElement.offsetWidth;
					winH = document.documentElement.offsetHeight;
				}
				if (window.innerWidth && window.innerHeight) {
					winW = window.innerWidth;
					winH = window.innerHeight;
				}
				
				ed.windowManager.open({
						
					title: ed.getLang('wpas_editor_langs.window_title'),
					width: winW*.95,
					height: winH*.95,
					url: url + '/wpas_editor_email_template_tags.php'
				}, {
					// Pass variables to window
					wpas_editor_js_vars: wpas_editor_js_vars
				})
            });
        },
 
        
        createControl : function(n, cm) {
			
            return null;
        },
 
        
        getInfo : function() {
			
            return {
                longname : ed.getLang('wpas_editor_langs.plugin_long_name'),
                author : 'Josh Lobe for Awesome Support',
                authorurl : 'http://getawesomesupport.com',
                version : "0.1"
            };
        }
    });
 
    // Register plugin
    tinymce.PluginManager.add( 'wpas_editor_email_template_tags', tinymce.plugins.wpas_editor_email_template_tags );
})();