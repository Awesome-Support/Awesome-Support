/*
 * grunt-version
 * https://github.com/kswedberg/grunt-version
 *
 * Copyright (c) 2015 Karl Swedberg
 * Licensed under the MIT license.
 */

'use strict';

module.exports = function(grunt) {

  var getVersion = (function getVersion() {

    var pkgs = [];

    return function(opts) {
      var pkg = opts.pkg;
      var i = pkgs.length - 1;

      // If pkg is the same string as previous target, and we're looping through targets,
      // make sure we're sticking with previous version so we don't keep incrementing subsequent targets
      if (typeof pkg === 'string') {
        if (pkgs[i] && pkg === pkgs[i].pkg && /^version::/.test(process.argv[2])) {
          pkg = pkgs[i];
        } else {
          pkg = grunt.file.readJSON(pkg, {encoding: opts.encoding});
        }
      }

      pkgs.push({
        // Store the pre-modified pkg, but the modified version
        pkg: opts.pkg,
        version: pkg.version,
      });

      return pkg.version;
    };
  })();

  // THE VERSION TASK
  grunt.registerMultiTask('version', 'Update version number in all the files.', function() {
    // Merge task-specific and/or target-specific options with these defaults.

    var options = this.options({
      prefix: '[^\\-]version[\'"]?\\s*[:=]\\s*[\'"]',
      replace: '[0-9a-zA-Z\\-_\\+\\.]+',
      pkg: 'package.json',
      release: '',
      flags: 'g',
      encoding: 'utf8',
    });

    var fileOptions = {encoding: options.encoding};

    var log = function log(type, info) {
      var msgs = {
        notFound: ['Pattern not found in file', 'Pattern: ' + info.pattern],
        skipped: ['File skipped.', 'Current version and new version are equal: ' + info.version],
        updated: [
          'File updated.',
          'Old version: ' + info.fileVersion + '. New version: ' + info.version + '.',
        ],
      };

      grunt.log.subhead(msgs[type][0]);
      grunt.log.writeln('Path: ' + info.filePath);
      grunt.log.writeln(msgs[type][1]);
    };

    var newVersion;
    var version = getVersion(options);
    var release = this.args && this.args[0] || options.release;
    var semver = require('semver');
    var bump = /major|minor|patch|prerelease/.test(release);
    var literal = semver.valid(release);

    if (bump && semver.valid(version)) {
      if (typeof options.prereleaseIdentifier !== 'undefined') {
        newVersion = semver.inc(version, release, options.prereleaseIdentifier);
      } else {
        newVersion = semver.inc(version, release);
      }
    } else if (literal) {
      newVersion = literal;
    } else if (release) {
      grunt.log.warn(release + ' must be a valid release name or semver version. Version will not be updated.');
    }

    version = newVersion || version;

    this.filesSrc.forEach(function(filepath) {
      // Warn if a source file/pattern was invalid.
      if (!grunt.file.exists(filepath)) {
        grunt.log.error('Source file "' + filepath + '" not found.');

        return '';
      }

      // Read file source.
      var fileInfo = {
        file: grunt.file.read(filepath, fileOptions),
        filePath: filepath,
        version: version,
        pattern: new RegExp('(' + options.prefix + ')(' + options.replace + ')', options.flags),
      };

      var newfile;
      var matches = fileInfo.pattern.exec(fileInfo.file);

      if (!matches) {
        log('notFound', fileInfo);
      } else {
        fileInfo.fileVersion = matches.pop();

        if (fileInfo.fileVersion === version) {
          log('skipped', fileInfo);
        } else {
          log('updated', fileInfo);
          newfile = fileInfo.file.replace(fileInfo.pattern, '$1' + version);
          grunt.file.write(filepath, newfile, fileOptions);
        }
      }
    });

  });

};
