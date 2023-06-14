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
  allFiles: function(test) {
    var files = [];
    var versionConfig = require('./fixtures/config-version-all.js');
    Object.keys(versionConfig).forEach(function(target) {
      if (target !== 'options') {
        files = files.concat( versionConfig[target].src);
      }
    });

    test.expect(files.length);

    files.forEach(function(file) {
      var content = grunt.file.read(file);
      var actual = /version['"]?\s*[:=] ['"](\d\.\d\.\d)/.exec(content);
      actual = actual && actual[1];

      test.equal(actual, '0.2.0', 'Updates the file with version.');
    });
    test.done();
  }
};
