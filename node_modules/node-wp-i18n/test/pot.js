var path = require('path');
var Pot = require('../lib/pot');
var test = require('tap').test;

test('pot instance', function(t) {
  t.plan(1);

  var pot = Pot();
  t.type(pot, 'Pot');
});

test('pot file does not exist', function(t) {
  t.plan(2);

  var filename = path.resolve('tmp/pot/fake.pot');
  var pot = new Pot(filename);
  t.type(pot, 'Pot');
  t.equal(pot.fileExists(), false);
});

test('pot file exists', function(t) {
  t.plan(1);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);
  t.equal(pot.fileExists(), true);
});

test('parse pot file', function(t) {
  t.plan(6);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);
  pot = pot.parse().parse();

  t.type(pot, 'Pot');
  t.equal(pot.isOpen, true);
  t.type(pot.contents, 'object');
  t.ok(pot.initialDate);
  t.ok(pot.fingerprint);
  t.equal(pot.hasChanged(), false);
});

test('has changed on post creation header update', function(t) {
  t.plan(1);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);

  pot.parse()
    .setHeader('pot-creation-date', '2003-04-01 14:12:34+00:00');

  t.equal(pot.hasChanged(), false);
});

test('compare pot file with non-existent pot file', function(t) {
  t.plan(1);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);
  var fake = new Pot(path.resolve('tmp/pot/fake.pot'));

  pot.parse();

  t.equal(pot.sameAs(fake), false);
});

test('compare same pot files with different creation date headers', function(t) {
  t.plan(1);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);
  var pot2 = new Pot(filename);

  pot.parse().setHeader('pot-creation-date', '2003-04-01 14:12:34+00:00');
  pot2.parse()

  t.equal(pot.sameAs(pot2), true);
});

test('reset pot creation date', function(t) {
  t.plan(1);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);

  pot.parse()
    .setHeader('pot-creation-date', '2003-04-01 14:12:34+00:00')
    .resetCreationDate();

  t.equal(pot.contents.headers['pot-creation-date'], '2014-03-20 19:54:59+00:00');
});

test('set pot file comment', function(t) {
  t.plan(2);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);
  var comment = 'a file comment';

  pot.parse()
    .setFileComment(comment);

  t.equal(pot.contents.translations[''][''].comments.translator, comment);

  pot.setFileComment('');
  t.equal(pot.contents.translations[''][''].comments.translator, comment);
});

test('set pot header', function(t) {
  t.plan(1);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);
  var key = 'report-msgid-bugs-to';
  var value = 'https://example.com';

  pot.parse()
    .setHeader(key, value);

  t.equal(pot.contents.headers[ key ], value);
});

test('set pot headers', function(t) {
  t.plan(2);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);

  pot.parse()
    .setHeaders({
      'last-translator': 'Firstus Lastus',
      'language-team': 'translate@example.com'
    });

  t.equal(pot.contents.headers['last-translator'], 'Firstus Lastus');
  t.equal(pot.contents.headers['language-team'], 'translate@example.com');
});

test('set poedit headers', function(t) {
  t.plan(9);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);

  pot.parse()
    .setHeader('poedit', true);

  var headers = pot.contents.headers;
  t.equal(headers['language'], 'en');
  t.equal(headers['plural-forms'], 'nplurals=2; plural=(n != 1);');
  t.equal(headers['x-poedit-country'], 'United States');
  t.equal(headers['x-poedit-sourcecharset'], 'UTF-8');
  t.equal(headers['x-poedit-basepath'], '../');
  t.equal(headers['x-poedit-searchpath-0'], '.');
  t.equal(headers['x-poedit-bookmarks'], '');
  t.equal(headers['x-textdomain-support'], 'yes');
  t.equal(headers['x-poedit-keywordslist'], '__;_e;_x:1,2c;_ex:1,2c;_n:1,2;_nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_attr__;esc_html__;esc_attr_e;esc_html_e;esc_attr_x:1,2c;esc_html_x:1,2c;');
});

test('set poedit headers without overriding existing header values', function(t) {
  t.plan(1);

  var filename = path.resolve('tmp/pot/basic.pot');
  var pot = new Pot(filename);

  pot.parse()
    .setHeader('x-poedit-country', 'Spain')
    .setHeader('poedit', true);

  var headers = pot.contents.headers;
  t.equal(headers['x-poedit-country'], 'Spain');
});

test('save pot file', function(t) {
  t.plan(2);

  var filename = path.resolve('tmp/pot/save.pot');
  var pot = new Pot(filename);
  var comment = 'a file comment';

  pot.parse()
    .setFileComment(comment)
    .save().save();

  t.equal(pot.isOpen, false);

  var pot = new Pot(filename);
  pot.parse();

  t.equal(pot.contents.translations[''][''].comments.translator, 'a file comment');
});
