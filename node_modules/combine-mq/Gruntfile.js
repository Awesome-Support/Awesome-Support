'use strict';

module.exports = function (grunt) {
  // Show elapsed time at the end
  require('time-grunt')(grunt);
  // Load all grunt tasks
  require('load-grunt-tasks')(grunt);

  // Project configuration
  grunt.initConfig({
  	config: {
  		docs: 'docs',
  		gruntfile: 'Gruntfile.js',
  		temp: 'tmp'
  	},


  	// Watchers
  	watch: {
  		gruntfile: {
  			files: '<%= jshint.gruntfile.src %>',
  			tasks: [
  			'jshint:gruntfile'
  			]
  		},
  		lib: {
  			files: '<%= jshint.lib.src %>',
  			tasks: [
  			'jshint:lib'
  			]
  		},
  		test: {
  			files: '<%= jshint.test.src %>',
  			tasks: [
  			'jshint:test',
  			'nodeunit'
  			]
  		}
  	},


  	// Housekeeping
		clean: {
			docs: [
				'<%= config.docs %>/'
			]
		},


  	// Tests
  	nodeunit: {
  		files: [
  		'test/**/*_test.js'
  		]
  	},


  	// Scripts
  	jshint: {
  		options: {
  			jshintrc: true,
  			reporter: require('jshint-stylish')
  		},
  		gruntfile: {
  			src: '<%= config.gruntfile %>'
  		},
  		lib: {
  			src: [
  			'lib/**/*.js'
  			]
  		},
  		test: {
  			src: [
  			'test/**/*.js'
  			]
  		}
  	},

  	jsdoc: {
  		all: {
  			src: [
  			'lib/**/*.js'
  			],
  			options: {
  				destination: 'docs',
  				template: 'node_modules/grunt-jsdoc/node_modules/ink-docstrap/template',
  				configure: '.jsdoc.conf.json'
  			}
  		}
  	}
  });

  // Default task
  grunt.registerTask('default', [
  	'dev'
  	]);

  // Dev task
  grunt.registerTask('dev', [
  	'jshint',
  	'nodeunit',
  	'watch'
  	]);

   // Dev task
   grunt.registerTask('docs', [
   	'clean:docs',
   	'jsdoc'
   	]);

  // Test task
  grunt.registerTask('test', [
  	'jshint',
  	'nodeunit'
  	]);
};
