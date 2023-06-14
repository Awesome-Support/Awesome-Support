var fs = require('fs');
var gettext = require('gettext-parser');
var makepot = require('../lib/makepot');
var path = require('path');
var test = require('tap').test;

test('makepot default', function(t) {
  t.plan(3);

  makepot({
    cwd: path.resolve('tmp/makepot/basic-plugin')
  }).then(function() {
    var potFilename = path.resolve('tmp/makepot/basic-plugin/basic-plugin.pot');
    t.ok(fs.statSync(potFilename));

    var pot = gettext.po.parse(fs.readFileSync(potFilename, 'utf8'));
    var pluginName = 'Example Plugin';
    t.equal(pot.headers['project-id-version'], pluginName, 'the plugin name should be the project id in the pot file');
    t.equal(pot.translations[''][ pluginName ]['msgid'], pluginName, 'the plugin name should be included as a string in the pot file');
  });
});

test('makepot custom pot file', function(t) {
  t.plan(1);

  makepot({
    cwd: path.resolve('tmp/makepot/basic-plugin'),
    potFile: 'custom.pot'
  }).then(function() {
    var potFilename = path.resolve('tmp/makepot/basic-plugin/custom.pot');
    t.ok(fs.statSync(potFilename));
  });
});

test('makepot no changes', function(t) {
  t.plan(2);

  var potFilename = path.resolve('tmp/makepot/plugin-with-pot/plugin-with-pot.pot');
  var pot = gettext.po.parse(fs.readFileSync(potFilename, 'utf8'));
  var creationDate = pot.headers['pot-creation-date'];

  makepot({
    cwd: path.resolve('tmp/makepot/plugin-with-pot'),
    potComments: 'Copyright',
    potHeaders: {
      'x-generator': 'node-wp-i18n'
    },
    updateTimestamp: false
  }).then(function() {
    t.ok(fs.statSync(potFilename));

    var pot = gettext.po.parse(fs.readFileSync(potFilename, 'utf8'));
    t.equal(pot.headers['pot-creation-date'], creationDate, 'the creation date should not change');
  });
});

/**
 * @link https://github.com/cedaro/node-wp-i18n/issues/16
 */
test('makepot when working directory is not package root', function(t) {
  t.plan(3);

  makepot({
    cwd: path.resolve('tmp/makepot/nested-theme'),
    domainPath: '/languages',
    mainFile: 'subdir/style.css',
    potFile: 'nested-theme.pot',
    type: 'wp-theme'
  }).then(function() {
    var potFilename = path.resolve('tmp/makepot/nested-theme/languages/nested-theme.pot');
    t.ok(fs.statSync(potFilename));

    var pot = gettext.po.parse(fs.readFileSync(potFilename, 'utf8'));
    var themeName = 'Example Theme';
    t.equal(pot.headers['project-id-version'], themeName, 'the theme name should be the project id in the pot file');
    t.equal(pot.translations[''][ themeName ]['msgid'], themeName, 'the theme name should be included as a string in the pot file');
  });
});
