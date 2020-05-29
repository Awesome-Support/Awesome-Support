/* globals module, require */

module.exports = function (grunt) {

	'use strict';
	
	grunt.initConfig({
		pkg: grunt.file.readJSON('package-poeditor.json'),

		/*
		Pull poeditor files
		*/

		poeditor: {
			target2: {
			  download: {
				project_id: process.env.POEDITOR_PROJECT_ID,
				type: 'mo', 
				dest: 'languages/awesome-support-?.mo'
			  }
			},
			target3: {
			  download: {
				project_id: process.env.POEDITOR_PROJECT_ID,
				type: 'po', 
				dest: 'languages/awesome-support-?.po'
			  }
			},
			options: {
			  project_id: process.env.POEDITOR_PROJECT_ID,
			  // matching POEditor's language codes with yours 
			  // applies to uploads & downloads 
			  languages: {
				'ar'	: 'ar',
				'ca'	: 'ca',				
				'da'	: 'da_DK',
				'de'	: 'de_DE',
				'el'	: 'el',
				'en'	: 'en_GB',				
				'es'	: 'es_ES',
				'es-cl'	: 'es_CL',
				'es-co'	: 'es_CO',
				'es_mx'	: 'es_MX',				
				'fa'	: 'fa_IR',
				'fr'	: 'fr_FR',				
				'he'	: 'he_IL',
				'hr'	: 'hr',
				'hu'	: 'hu_HU',
				'it'	: 'it_IT',
				'ja'	: 'ja',
				'my'	: 'my_MM',
				'nb'	: 'nb_NO',				
				'nl'	: 'nl_NL',				
				'pl'	: 'pl_PL',
				'pt'	: 'pt_PT',
				'pt-br'	: 'pt_BR',
				'ro'	: 'ro_RO',
				'ru'	: 'ru_RU',
				'sw'	: 'sw',
				'sv'	: 'sv_SE',
				'tr'	: 'tr_TR',				
				'zn-CH'	: 'zn-CH'

			  },
			  api_token: process.env.POEDITOR_API_TOKEN
			}
		  }
		
	});

	// require('load-grunt-tasks')(grunt);
	grunt.loadNpmTasks('grunt-poeditor-at');
		
	grunt.registerTask('default', ['poeditor:target2']);

	grunt.registerTask('po_editor_pull', ['poeditor:target2','poeditor:target3']);

};