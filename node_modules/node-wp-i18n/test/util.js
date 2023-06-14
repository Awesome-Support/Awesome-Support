var path = require('path');
var test = require('tap').test;
var util = require('../lib/util');

test('execute file', function(t) {
  t.plan(2);

  util.execFile('node', ['-v'])
    .then(t.pass)
    .catch(t.threw);

  util.execFile('unknown')
    .then(t.fail)
    .catch(t.pass);
});

test('file exists', function(t) {
  t.plan(1);

  var filename = path.resolve(__dirname, 'fixtures/pot/basic.pot');
  t.equal(util.fileExists(filename), true);
});

test('spawn a process', function(t) {
  t.plan(2);

  util.spawn('node', ['-v'])
    .then(t.pass)
    .catch(t.threw);

  util.spawn('unknown')
    .then(t.fail)
    .catch(t.pass);
});
