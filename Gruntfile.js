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
					'!assets/public/js/public-dist.js'
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
					'themes/default/css/style.css': 'themes/default/less/style.less'
				}
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
					'themes/default/css/style.css': 'themes/default/css/style.css'
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
						'!node_modules/**',
						'!tests/**',
						'!.tx/**',
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
		}

	});

	require('load-grunt-tasks')(grunt);

	grunt.registerTask('default', ['jshint', 'uglify', 'less', 'autoprefixer', 'combine_mq', 'cssmin', 'watch']);
	grunt.registerTask('build', ['jshint', 'uglify', 'less', 'autoprefixer', 'combine_mq', 'cssmin']);
	
	grunt.registerTask('txpull', ['exec:txpull', 'potomo']);
	grunt.registerTask('txpush', ['makepot', 'exec:txpush']);


	grunt.registerTask('release', ['composer:install', 'build', 'compress']);
	grunt.registerTask('release_patch', ['version::patch', 'release']);
	grunt.registerTask('release_minor', ['version::minor', 'release']);
	grunt.registerTask('release_major', ['version::major', 'release']);

};