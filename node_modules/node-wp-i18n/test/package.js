var path = require('path');
var test = require('tap').test;
var WPPackage = require('../lib/package');

test('plugin package instance', function(t) {
  t.plan(7);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var type = 'wp-plugin';
  var wpPackage = WPPackage(directory, type);
  t.type(wpPackage, 'WPPackage');


  var wpPackage = new WPPackage(directory, type);
  t.type(wpPackage, 'WPPackage');
  t.equal(wpPackage.getType(), type);
  t.equal(wpPackage.isType(type), true);
  t.equal(wpPackage.isType('wp-theme'), false);
  t.equal(wpPackage.getDomainPath(), '');
  t.equal(wpPackage.getPotFile(), 'basic-plugin.pot');
});

test('theme package instance', function(t) {
  t.plan(6);

  var directory = path.resolve('tmp/packages/themes/basic-theme');
  var type = 'wp-theme';
  var wpPackage = new WPPackage(directory, type);

  t.type(wpPackage, 'WPPackage');
  t.equal(wpPackage.getType(), type);
  t.equal(wpPackage.isType(type), true);
  t.equal(wpPackage.isType('wp-plugin'), false);
  t.equal(wpPackage.getDomainPath(), '');
  t.equal(wpPackage.getPotFile(), 'basic-theme.pot');
});

test('determine package type without type parameter', function(t) {
  t.plan(3);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var wpPackage = new WPPackage(directory);
  t.equal('wp-plugin', wpPackage.getType());

  var directory = path.resolve('tmp/packages/plugins/different-slug');
  var wpPackage = new WPPackage(directory);
  t.equal('wp-plugin', wpPackage.getType());

  var directory = path.resolve('tmp/packages/themes/basic-theme');
  var wpPackage = new WPPackage(directory);
  t.equal('wp-theme', wpPackage.getType());
});

test('set package directory', function(t) {
  t.plan(2);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.directory, directory);

  var newDirectory = 'tmp/packages/plugins/basic-plugin2';
  wpPackage.setDirectory(newDirectory);
  t.equal(wpPackage.directory, path.resolve(newDirectory));
});

test('set package domain path', function(t) {
  t.plan(1);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var wpPackage = new WPPackage(directory);
  wpPackage.setDomainPath('/languages');
  t.equal(wpPackage.getDomainPath(), 'languages');
});

test('set main package file', function(t) {
  t.plan(1);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var mainFile = 'basic-plugin.php';
  var wpPackage = new WPPackage(directory);
  wpPackage.setMainFile(mainFile);
  t.equal(wpPackage.getMainFile(), mainFile);
});

test('get package path', function(t) {
  t.plan(2);

  var directory = path.resolve('tmp/packages/themes/basic-theme');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.getPath(), directory);
  t.equal(wpPackage.getPath('style.css'), path.resolve(directory, 'style.css'));
});

test('set package pot file', function(t) {
  t.plan(1);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var potFile = 'basic-plugin.pot';
  var wpPackage = new WPPackage(directory);
  wpPackage.setPotFile(potFile);
  t.equal(wpPackage.getPotFile(), potFile);
});

test('get package pot filename', function(t) {
  t.plan(2);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var potFile = 'basic-plugin.pot';
  var wpPackage = new WPPackage(directory);
  wpPackage.setPotFile(potFile);
  t.equal(wpPackage.getPotFilename(), path.resolve(directory, potFile));

  wpPackage.setDomainPath('/languages');
  t.equal(wpPackage.getPotFilename(), path.resolve(directory, 'languages', potFile));
});

test('get package pot', function(t) {
  t.plan(1);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var wpPackage = new WPPackage(directory);
  t.type(wpPackage.getPot(), 'Pot');
});

test('get package header', function(t) {
  t.plan(5);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.getHeader('Text Domain'), 'basic-plugin');
  t.equal(wpPackage.getHeader('Plugin Name', 'basic-plugin.php'), 'Example Plugin');

  var directory = path.resolve('tmp/packages/plugins/plugin-headers');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.getHeader('Plugin Name'), 'Example Plugin');
  t.equal(wpPackage.getHeader('Domain Path'), '/languages');
  t.equal(wpPackage.getHeader('Text Domain'), 'example-plugin');
});

test('get package text domain without header', function(t) {
  t.plan(4);

  var directory = path.resolve('tmp/packages/plugins/basic-plugin');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.getHeader('Text Domain'), 'basic-plugin');

  // Packages default to the `wp-plugin` type.
  var directory = path.resolve('tmp/packages/plugins/invalid-plugin');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.getHeader('Text Domain'), 'invalid-plugin');

  var directory = path.resolve('tmp/packages/themes/nested-theme/src');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.getHeader('Text Domain'), 'nested-theme');

  var directory = path.resolve('tmp/packages/themes/svn-theme/tags/1.0.0');
  var wpPackage = new WPPackage(directory);
  t.equal(wpPackage.getHeader('Text Domain'), 'svn-theme');
});
