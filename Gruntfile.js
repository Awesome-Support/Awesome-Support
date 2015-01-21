/* globals module, require */

module.exports = function(grunt) {

	'use strict';

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		jshint: {
			all: ['Gruntfile.js', 'assets/public/js/public.js']
		},

		/*
		Concatenate & Minify Javascript files
		@author: https://github.com/gruntjs/grunt-contrib-concat
		@author: https://github.com/gruntjs/grunt-contrib-uglify
		 */
		concat: {
			options: {
				separator: ';'
			},
			dist: {
				src: ['assets/public/vendor/*/*.js', 'assets/public/js/public.js'],
				dest: 'assets/public/js/public-dist.js'
			}
		},

		uglify: {
			global: {
				files: {
					'assets/public/js/public-dist.js': ['assets/public/js/public-dist.js']
				}
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
					'assets/admin/css/admin.css': 'assets/admin/less/admin.less'
				}
			}
		},

		/*
		Add vendor prefixes
		@author: https://github.com/nDmitry/grunt-autoprefixer
		 */
		autoprefixer: {
			single_file: {
				options: {
					cascade: false
				},
				src: 'assets/public/css/public.css'
			}
		},

		/*
		Combine Media Queries
		@author: https://github.com/frontendfriends/grunt-combine-mq
		@example: base file has 70 @media while output has only 12
		 */
		combine_mq: {
			new_filename: {
				options: {
					expand: false,
					beautify: false
				},
				src: 'assets/public/css/public.css',
				dest: 'assets/public/css/public.css'
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
					'assets/admin/css/admin.css': 'assets/admin/css/admin.css'
				},
				options: {
					report: 'min',
					keepSpecialComments: 0
				}
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
						'!.gitignore',
						'!apigen.neon',
						'!composer.json',
						'!composer.lock',
						'!tests/**',
						'!README.md',
						'!Gruntfile.js',
						'!package.json',
						'!*.sublime-workspace',
						'!*.sublime-project',
						'!awesome-support-<%= pkg.version %>.zip'
					]
				}]
			}
		},

		watch: {
			options: {
				livereload: {
					port: 9000
				}
			},
			js: {
				files: ['js/*.js'],
				tasks: ['concat', 'uglify']
			},
			css: {
				files: ['style.less'],
				tasks: ['less', 'autoprefixer', 'combine_mq', 'cssmin']
			}
		}

	});

	require('load-grunt-tasks')(grunt);

	grunt.registerTask('serve');
	grunt.registerTask('default', ['jshint', 'concat', 'uglify', 'less', 'autoprefixer', 'combine_mq', 'cssmin', 'watch']);
	grunt.registerTask('build', ['jshint', 'concat', 'uglify', 'less', 'autoprefixer', 'combine_mq', 'cssmin']);

};