'use strict';

var grunt = require('grunt');

exports.combine_mq = {
	setUp: function(done) {
		// setup here if necessary
		done();
	},
	default_options: function (test) {
		test.expect(1);

		var expected = grunt.file.read('test/expected/test.css'),
		actual = grunt.file.read('test/actual/test.css');

		test.equal(expected, actual, 'should combine matching media queries using combine-mq');

		test.done();
	},
	custom_options: function (test) {
		test.expect(1);

		var expected = grunt.file.read('test/expected/custom_options.css'),
		actual = grunt.file.read('test/actual/custom_options.css');

		test.equal(expected, actual, 'should combine matching media queries using combine-mq, and use custom options');

		test.done();
	}
};
