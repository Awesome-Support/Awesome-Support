module.exports = {
  options: {
    pkg: 'test/fixtures/test-package.json'
  },
  a: {
    src: [
      'tmp/123.js',
      'tmp/456.js',
      'tmp/test-package.json'
    ]
  },
  b: {
    src: ['tmp/test-pkg-arg.json']
  },
  c: {
    src: ['tmp/test-pkg-pre.json']
  },
  d: {
    src: ['tmp/testing.js']
  }
};
