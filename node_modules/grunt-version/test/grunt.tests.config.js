var grunt = require('grunt');

module.exports = {
  prefixOption: {
    options: {
      prefix: 'version[\'"]?( *=|:) *[\'"]',
    },
    src: ['tmp/testing.js', 'tmp/testingb.js'],
  },
  prerelease: {
    options: {
      release: 'prerelease',
      pkg: 'test/fixtures/test-pkg-pre.json'
    },
    src: 'tmp/test-pkg-pre.json'
  },
  patch: {
    options: {
      release: 'patch'
    },
    src: [
      'tmp/123.js',
      'tmp/456.js',
      'tmp/test-package.json'
    ]
  },
  minorwitharg: {
    options: {
      pkg: 'tmp/test-pkg-arg.json'
    },
    src: 'tmp/test-pkg-arg.json'
  },
  prereleaseBuild: {
    options: {
      release: 'prerelease',
      pkg: 'test/fixtures/test-pkg-prerelease_build.json'
    },
    src: [
      'tmp/test-pkg-prerelease_build.json',
      'tmp/test-prerelease_build.js'
    ]
  },
  minor: {
    options: {
      release: 'minor',
      pkg: 'test/fixtures/test-pkg-vc.json'
    },
    src: ['tmp/test-pkg-vc.json', 'tmp/testingc.js']
  },
  literal: {
    options: {
      release: '3.2.1',
      pkg: grunt.file.readJSON('test/fixtures/test-package-v.json')
    },
    src: [
      'tmp/test-package-v.json'
    ]
  },
  excludeFiles: {
    options: {
      pkg: grunt.file.readJSON('test/fixtures/test-package-v.json'),
      release: 'patch',
    },
    src: [
      'tmp/exclude-some/*.js',
      '!tmp/exclude-some/no-*.js'
    ]
  },
  flags: {
    options: {
      release: 'patch',
      pkg: 'tmp/test-pkg-insensitive.json',
      flags: 'i'
    },
    src: [
      'tmp/123-insensitive.js'
    ]
  }

};
