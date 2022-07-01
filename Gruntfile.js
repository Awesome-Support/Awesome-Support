/* globals module, require */

module.exports = function (grunt) {

	'use strict';

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		jshint: {
			all: ['Gruntfile.js', 'assets/public/js/public.js']
		},

		/*
		Concatenate & Minify Javascript files
		@author: https://github.com/gruntjs/grunt-contrib-uglify
		 */
		uglify: {
			public: {
				options: {
					sourceMap: true
				},
				src: [
					'assets/public/vendor/**/*.js',
					'assets/public/js/*.js',
					'!assets/public/js/public-dist.js',
					'!assets/public/js/component-privacy-popup.js'
				],
				dest: 'assets/public/js/public-dist.js'
			}
		},

		/*
		Combine LESS files into CSS
		@author: https://github.com/gruntjs/grunt-contrib-less
		 */
		less: {
			production: {
				options: {
					ieCompat: false
				},
				files: {
					'assets/public/css/public.css': 'assets/public/less/public.less',
					'assets/admin/css/admin.css': 'assets/admin/less/admin.less',
					'themes/default/css/style.css': 'themes/default/less/style.less',
					'themes/default/css/overlay-subtle.css': 'themes/default/less/overlay-subtle.less',
					'themes/default/css/overlay-angle.css': 'themes/default/less/overlay-angle.less',
					'themes/default/css/overlay-dark.css': 'themes/default/less/overlay-dark.less',
					'themes/default/css/overlay-orange-blend.css': 'themes/default/less/overlay-orange-blend.less',
					'themes/default/css/overlay-royal-blend.css': 'themes/default/less/overlay-royal-blend.less',
					'themes/default/css/overlay-green-envy.css': 'themes/default/less/overlay-green-envy.less',
					'themes/default/css/overlay-plain-gray.css': 'themes/default/less/overlay-plain-gray.less',
					'themes/default/css/overlay-basic-blue.css': 'themes/default/less/overlay-basic-blue.less',
					'themes/default/css/overlay-basic-red.css': 'themes/default/less/overlay-basic-red.less',
					'themes/default/css/overlay-basic-green.css': 'themes/default/less/overlay-basic-green.less',
					'includes/rest-api/assets/admin/css/admin.css': 'includes/rest-api/assets/admin/less/admin.less'
				}
			}
		},

		/*
		Concatenate CSS files into one
		 */
		concat_css: {
    		options: {},
    		files: {
		  		src: [
			  		'assets/public/css/public.css',
			  		'assets/public/css/component_*.css'
		  		],
	      		dest: 'assets/public/css/public.css'
    		}
  		},

		/*
		Add vendor prefixes
		@author: https://github.com/nDmitry/grunt-autoprefixer
		 */
		autoprefixer: {
			options: {
				cascade: false
			},
			publicCSS: {
				src: 'assets/public/css/public.css'
			},
			themeCSS: {
				src: 'themes/default/css/style.css'
			}
		},

		/*
		Combine Media Queries
		@author: https://github.com/frontendfriends/grunt-combine-mq
		@example: base file has 70 @media while output has only 12
		 */
		combine_mq: {
			options: {
				expand: false,
				beautify: false
			},
			publicCSS: {
				src: 'assets/public/css/public.css',
				dest: 'assets/public/css/public.css'
			},
			themeCSS: {
				src: 'themes/default/css/style.css',
				dest: 'themes/default/css/style.css'
			}
		},

		/*
		Minify Stylehseets for production
		@author: https://github.com/gruntjs/grunt-contrib-cssmin
		 */
		cssmin: {
			minify: {
				files: {
					'assets/public/css/public.css': 'assets/public/css/public.css',
					'assets/admin/css/admin.css': 'assets/admin/css/admin.css',
					'themes/default/css/style.css': 'themes/default/css/style.css',
					'themes/default/css/overlay-subtle.css': 'themes/default/css/overlay-subtle.css',
					'themes/default/css/overlay-angle.css': 'themes/default/css/overlay-angle.css'
				},
				options: {
					report: 'min',
					keepSpecialComments: 0
				}
			}
		},

		/*
		Bump version number
		@author https://www.npmjs.com/package/grunt-version
		 */
		version: {
			pluginVersion: {
				options: {
					prefix: 'Version:\\s+'
				},
				src: [
					'awesome-support.php'
				]
			},
			pluginConstant: {
				options: {
					prefix: 'define\\(\\s*\'WPAS_VERSION\',\\s*\''
				},
				src: [
					'awesome-support.php'
				]
			},
			packageJson: {
				src: [
					'package.json'
				]
			}
		},

		/**
		Creates a clean zip archive for production
		@author https://github.com/gruntjs/grunt-contrib-compress
		 */
		compress: {
			main: {
				options: {
					archive: 'awesome-support-<%= pkg.version %>.zip',
					mode: 'zip'
				},
				files: [{
					src: [
						'*',
						'**',
						'!dist/**',
						'!node_modules/**',
						'!vendor-overrides/**',
						'!vendor/freemius/**',
						'!languages/*.po',
						'!tests/**',
						'!.tx/**',
						'!*.zip',
						'!.gitignore',
						'!.travis.yml',
						'!apigen.neon',
						'!composer.json',
						'!composer.lock',
						'!tests/**',
						'!logs/**',
						'!README.md',
						'!CONTRIBUTING.md',
						'!Gruntfile.js',
						'!package.json',
						'!*.sublime-workspace',
						'!*.sublime-project',
						'!awesome-support-<%= pkg.version %>.zip'
					]
				}]
			}
		},

		/**
		 Updates the translation catalog (not currently used but added here to eventually replace the transifix stuff below)
		 @author https://www.npmjs.com/package/grunt-pot
		 */
		pot: {
			  options:{
			  text_domain: 'awesome-support',
			  dest: 'languages/',
			  keywords: ['__','_e','_x:1,2c','_ex:1,2c','_n:1,2','_nx:1,2,4c','_n_noop:1,2','_nx_noop:1,2,3c','esc_attr__','esc_html__','esc_attr_e','esc_html_e','esc_attr_x:1,2c','esc_html_x:1,2c\n'], //functions to look for
			},
			files:{
			  src:  [ '**/*.php' ], //Parse all php files
			  expand: true,
			   }
		  },

		/**
		 Updates the translation catalog
		 @author https://www.npmjs.com/package/grunt-wp-i18n
		 */
		makepot: {
			target: {
				options: {
					domainPath: '/languages/',
					exclude: ['assets/.*', 'node_modules/.*', 'vendor/.*', 'tests/.*', 'includes/admin/views/system-status.php'],
					mainFile: 'awesome-support.php',
					potComments: 'Awesome Support',
					potFilename: 'awesome-support.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
						'report-msgid-bugs-to': 'https://github.com/ThemeAvenue/Awesome-Support/issues',
						'last-translator': 'Awesome Support (https://getawesomesupport.com/)',
						'language-team': 'Awesome Support <contact@getawesomesupport.com>',
						'language': 'en_US'
					},
					processPot: function (pot, options) {
						var translation,
							excluded_meta = [
								'Plugin Name of the plugin/theme',
								'Plugin URI of the plugin/theme',
								'Author of the plugin/theme',
								'Author URI of the plugin/theme'
							];
						for (translation in pot.translations['']) {
							if ('undefined' !== typeof pot.translations[''][translation].comments.extracted) {
								if (excluded_meta.indexOf(pot.translations[''][translation].comments.extracted) >= 0) {
									console.log('Excluded meta: ' + pot.translations[''][translation].comments.extracted);
									delete pot.translations[''][translation];
								}
							}
						}
						return pot;
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},

		/**
		 Convert PO files into MO files
		 @author https://www.npmjs.com/package/grunt-potomo
		 */
		potomo: {
			dist: {
				options: {
					poDel: true
				},
				files: [{
					expand: true,
					cwd: 'languages',
					src: ['*.po'],
					dest: 'languages',
					ext: '.mo',
					nonull: true
				}]
			}
		},

		/**
		 Run shell commands
		 @author https://github.com/jharding/grunt-exec
		 */
		exec: {
			txpull: { // Pull Transifex translation - grunt exec:txpull
				cmd: 'tx pull -a -f' // Change the percentage with --minimum-perc=yourvalue
			},
			txpush: { // Push pot to Transifex - grunt exec:txpush_s
				cmd: 'tx push -s'
			}
		},

		watch: {
			options: {
				livereload: {
					port: 9000
				}
			},
			js: {
				files: ['assets/**/*.js'],
				tasks: ['uglify']
			},
			css: {
				files: ['assets/**/*.less', 'assets/**/*.css', 'themes/**/*.less'],
				tasks: ['less', 'autoprefixer', 'combine_mq', 'cssmin']
			}
		},

		copy: {
		  vendoroverrides: {
			files: [
			  { src:"vendor-overrides/eric-mann-session-manager/ericmann/wp-session-manager/includes/deprecated.php", dest:"vendor/ericmann/wp-session-manager/includes/deprecated.php" },
			  { src:"vendor-overrides/eric-mann-session-manager/ericmann/wp-session-manager/wp-session-manager.php", dest:"vendor/ericmann/wp-session-manager/wp-session-manager.php" },
			  { src:"vendor-overrides/eric-mann-sessionz/Manager.php", dest:"vendor/ericmann/sessionz/php/Manager.php" }
			]
		  },
			copytodist: {
			files: [
			  { src:"awesome-support-<%= pkg.version %>.zip", dest:"dist/awesome-support.zip" }
			]
		  }
		}

	});

	require('load-grunt-tasks')(grunt);

	grunt.registerTask('default', ['jshint', 'copy:vendoroverrides', 'uglify', 'less', 'concat_css', 'autoprefixer', 'combine_mq', 'cssmin', 'watch']);
	grunt.registerTask('build'  , ['jshint', 'copy:vendoroverrides', 'uglify', 'less', 'concat_css', 'autoprefixer', 'combine_mq', 'cssmin']);

	grunt.registerTask('txpull', ['exec:txpull', 'potomo']);
	grunt.registerTask('txpush', ['makepot', 'exec:txpush']);

	grunt.registerTask('ugly', ['uglify'] );

	grunt.registerTask('release', ['composer:install --no-dev', 'build', 'compress', 'copy:copytodist']);
	grunt.registerTask('release_patch', ['version::patch', 'release']);
	grunt.registerTask('release_minor', ['version::minor', 'release']);
	grunt.registerTask('release_major', ['version::major', 'release']);

};
