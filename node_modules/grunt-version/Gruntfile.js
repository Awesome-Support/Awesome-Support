/*
 * grunt-version
 * https://github.com/kswedberg/grunt-version
 *
 * Copyright (c) 2015 Karl Swedberg
 * Licensed under the MIT license.
 */

'use strict';

module.exports = function(grunt) {

  // Test targets to be merged into grunt.config.version
  var versionTests = require('./test/grunt.tests.config');

  // Project configuration.
  var gruntConfig = {
    pkg: grunt.file.readJSON('package.json'),
    eslint: {
      all: [
        'Gruntfile.js',
        'tasks/*.js',
        '<%= nodeunit.tests %>',
      ],

    },
    // Before generating any new files, remove any previously-created files.
    clean: {
      tests: ['tmp'],
    },
    copy: {
      tests: {
        files: [{
          cwd: 'test/fixtures/',
          src: ['**'],
          dest: 'tmp/',
          filter: 'isFile',
          expand: true,
        }],
      },
    },

    version: {
      options: {
        pkg: grunt.file.readJSON('test/fixtures/test-package.json'),
      },
      // Not for testing. Run with grunt version:v:[release]
      v: {
        options: {
          pkg: 'package.json',
        },
        src: [
          'package.json',
        ],
      },
      allFiles: {

      },
    },

    // Unit tests.
    nodeunit: {
      tests: ['test/version-test.js'],
      allFiles: ['test/version-all-test.js'],
    },

  };

  grunt.util._.extend(gruntConfig.version, versionTests);

  grunt.initConfig(gruntConfig);

  // Actually load this plugin's task(s).
  grunt.loadTasks('tasks');

  // These plugins provide necessary tasks.
  // grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-eslint');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-nodeunit');
  grunt.loadNpmTasks('grunt-contrib-copy');

  // Whenever the "test" task is run, first clean the "tmp" dir, then run this
  // plugin's task(s), then test the result.

  var testTasks = [
    'clean',
    'copy',
  ];
  var testTarget;

  for (var el in versionTests) {
    if (el === 'minorwitharg') {
      el += ':minor';
    }
    testTasks.push('version:' + el);
  }

  testTasks.push('nodeunit:tests');

  grunt.registerTask('testAll', 'test all targets using version::release', function() {
    var versionConfig = require('./test/fixtures/config-version-all.js');
    var tasks = [
      'clean',
      'copy',
      'version::minor',
      'nodeunit:allFiles',
    ];

    grunt.config.set('version', versionConfig);

    grunt.task.run(tasks);

  });

  testTasks.push('testAll');
  grunt.registerTask('test', testTasks);

  // By default, lint and run all tests.
  grunt.registerTask('default', ['eslint', 'test']);

};
