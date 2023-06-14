'use strict';

var semver = require('semver');
var grunt = require('grunt');

/*
  ======== A Handy Little Nodeunit Reference ========
  https://github.com/caolan/nodeunit

  Test methods:
    test.expect(numAssertions)
    test.done()
  Test assertions:
    test.ok(value, [message])
    test.equal(actual, expected, [message])
    test.notEqual(actual, expected, [message])
    test.deepEqual(actual, expected, [message])
    test.notDeepEqual(actual, expected, [message])
    test.strictEqual(actual, expected, [message])
    test.notStrictEqual(actual, expected, [message])
    test.throws(block, [error], [message])
    test.doesNotThrow(block, [error], [message])
    test.ifError(value)
*/

exports.version = {
  setUp: function(done) {
    done();
  },
  prefixOption: function(test) {
    var files = grunt.config('version.prefixOption.src');

    test.expect(files.length);

    files.forEach(function(file) {
      var content = grunt.file.read(file);
      var actual = /version['"]?\s*[:=] ['"](\d\.\d\.\d)/.exec(content);

      actual = actual && actual[1];

      test.equal(actual, '0.1.0', 'Updates the file with version.');
    });
    test.done();
  },
  patch: function(test) {
    var files = grunt.config('version.patch.src');

    test.expect(files.length);

    files.forEach(function(file) {
      var content = grunt.file.read(file);
      var actual = /version['"]?\s*[:=] ['"](\d\.\d\.\d)/.exec(content);

      actual = actual && actual[1];

      test.equal(actual, '0.1.1', 'Increments the version and updates the file.');
    });

    test.done();
  },
  prerelease: function(test) {

    var file = grunt.config('version.prerelease.src');
    var content = grunt.file.read(file);
    var actual = /version['"]?\s*[:=] ['"](\d\.\d\.\d[-+a-zA-Z0-9.]*)/.exec(content);

    actual = actual && actual[1];

    test.expect(1);
    test.equal(actual, '1.2.3-alpha.0', 'Increments the version and updates the file.');

    test.done();
  },
  minor: function(test) {
    var files = grunt.config('version.minor.src');

    test.expect(files.length);

    files.forEach(function(file) {
      var content = grunt.file.read(file);
      var actual = /version['"]?\s*[:=] ['"](\d\.\d\.\d)/.exec(content);

      actual = actual && actual[1];

      test.equal(actual, '1.3.0', 'Increments the minor version and updates the file.');
    });

    test.done();
  },
  minorwitharg: function(test) {
    test.expect(1);
    var pkg = grunt.file.readJSON('tmp/test-pkg-arg.json');

    test.equal(pkg.version, '1.3.0', 'Increments the minor version and updates the file.');
    test.done();
  },
  excludeFiles: function(test) {
    test.expect(4);
    var patchedFiles = [
      'tmp/exclude-some/123.js',
      'tmp/exclude-some/testing.js',
    ];
    var excludedFiles = [
      'tmp/exclude-some/no-123.js',
      'tmp/exclude-some/no-testing.js',
    ];

    patchedFiles.forEach(function(file) {
      var content = grunt.file.read(file);
      var actual = /version['"]?\s*[:=] ['"]([^'"]+)/.exec(content);

      actual = actual && actual[1];

      test.equal(actual, '1.2.4', 'Increments the version and updates the file.');
    });

    excludedFiles.forEach(function(file) {
      var content = grunt.file.read(file);
      var actual = /version['"]?\s*[:=] ['"]([^'"]+)/.exec(content);

      actual = actual && actual[1];

      test.equal(actual, '1.2.3', 'Ignores the file; version remains the same.');
    });

    test.done();
  },
  literal: function(test) {
    test.expect(2);
    var pkg = grunt.file.readJSON('tmp/test-package-v.json');

    test.equal(pkg.version, '3.2.1', 'Sets package version to literal value');
    test.equal(pkg.devDependencies['grunt-version'], '>=0.1.0', 'Does NOT increment grunt-version');
    test.done();
  },
  prereleaseBuild: function(test) {
    var files = grunt.config('version.prereleaseBuild.src');

    test.expect(files.length);

    files.forEach(function(file) {
      var content = grunt.file.read(file);
      var actual = /version['"]?\s*[:=] ['"]([^'"]+)/.exec(content);

      actual = actual && actual[1];

      test.equal(actual, '1.0.0-beta.2', 'Increments the version and updates the file.');
    });

    test.done();
  },
  flags: function(test) {
    var file = grunt.config('version.flags.src');
    var content = grunt.file.read(file);
    var actual = /vErSIoN = '(\d\.\d\.\d)/.exec(content);

    actual = actual && actual[1];

    test.expect(1);
    test.equal(actual, '1.2.4', 'Case insensitive version update and updates the file');

    test.done();
  },
};
