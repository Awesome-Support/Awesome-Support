/*
 * grunt-combine-mq
 * https://github.com/frontendfriends/grunt-combine-mq
 *
 * Copyright (c) 2014 Building Blocks
 * Licensed under the MIT license.
 */

'use strict';

module.exports = function (grunt) {
  grunt.registerMultiTask('combine_mq', 'Grunt wrapper for node-combine-mq', function() {
    var combineMq = require('combine-mq');

    var options = this.options({
    	beautify: true
    });

    this.files.forEach(function (file, next) {
      var src = file.src[0],
      dest = file.dest,
      processed;

      if (!grunt.file.exists(src)) {
        grunt.log.warn('Source file "' + src + '" not found.');

        return next();
      }

      processed = combineMq.parseCssString(grunt.file.read(src), options);

      grunt.file.write(file.dest, processed);
      grunt.log.writeln('File "' + file.dest + '" created.');
    });
  });
};
