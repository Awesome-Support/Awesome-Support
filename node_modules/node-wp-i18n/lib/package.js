/**
 * node-wp-i18n
 * https://github.com/cedaro/node-wp-i18n
 *
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license MIT
 */

'use strict';

var fs = require('fs');
var glob = require('glob');
var path = require('path');
var Pot = require('./pot');
var util = require('./util');

module.exports = WPPackage;

/**
 * Guess the wpPackage slug.
 *
 * See MakePOT::guess_plugin_slug() in makepot.php
 *
 * @returns {string}
 */
function guessSlug(wpPackage) {
  var directory = wpPackage.getPath();
  var slug = path.basename(directory);
  var slug2 = path.basename(path.dirname(directory));

  if ('trunk' === slug || 'src' === slug) {
    slug = slug2;
  } else if (-1 !== ['branches', 'tags'].indexOf(slug2)) {
    slug = path.basename(path.dirname(path.dirname(directory)));
  }

  return slug;
}

/**
 * Discover the main package file.
 *
 * For themes, the main file will be style.css. The main file for plugins
 * contains the plugin headers.
 *
 * @param {WPPackage} wpPackage Package object.
 * @returns {string}
 */
function findMainFile(wpPackage) {
  if (wpPackage.isType('wp-theme')) {
    return 'style.css';
  }

  var found = '';
  var pluginFile = guessSlug(wpPackage) + '.php';
  var filename = wpPackage.getPath(pluginFile);

  // Check if the main file exists.
  if (util.fileExists(filename) && wpPackage.getHeader('Plugin Name', filename)) {
    return pluginFile;
  }

  // Search for plugin headers in php files in the main directory.
  glob.sync('*.php', {
    cwd: wpPackage.getPath()
  }).forEach(function(file) {
    var filename = wpPackage.getPath(file);

    if (wpPackage.getHeader('Plugin Name', filename)) {
      found = file;
    }
  });

  return found;
}

/**
 * Create a new package.
 *
 * @class WPPackage
 */
function WPPackage(directory, type) {
  if (!(this instanceof WPPackage)) {
    return new WPPackage(directory, type);
  }

  this.directory = null;
  this.domainPath = null;
  this.mainFile = null;
  this.potFile = null;
  this.type = 'wp-plugin';

  this.initialize(directory, type);
}

/**
 * Initialize the package.
 *
 * @param {string} directory Full path to the package root directory.
 * @param {string} type      Optional. Package type.
 * @returns {this}
 */
WPPackage.prototype.initialize = function(directory, type) {
  return this.setDirectory(directory)
    .setType(type)
    .setMainFile(findMainFile(this))
    .setDomainPath(this.getHeader('Domain Path'))
    .setPotFile(this.getHeader('Text Domain') + '.pot');
};

/**
 * Set the package directory.
 *
 * @param {string} directory Full path to the package root directory.
 * @returns {this}
 */
WPPackage.prototype.setDirectory = function(directory) {
  this.directory = directory;

  if (!path.isAbsolute(directory)) {
    this.directory = path.resolve(process.cwd(), directory);
  }

  return this;
};

/**
 * Retrieve the domain path.
 *
 * @returns {string} Relative directory to the language files from the package directory.
 */
WPPackage.prototype.getDomainPath = function() {
  return this.domainPath.replace(/^(\/|\\)/, ''); // Strip leading slashes.
};

/**
 * Set the domain path.
 *
 * @param {string} domainPath Relative directory to the language files from the package directory.
 * @returns {this}
 */
WPPackage.prototype.setDomainPath = function(domainPath) {
  this.domainPath = domainPath;
  return this;
};

/**
 * Get the value of a plugin or theme header.
 *
 * @param {string} name Name of the header.
 * @param {string} filename Optional. Absolute path to the main file.
 * @returns {string}
 */
WPPackage.prototype.getHeader = function(name, filename) {
  if ('undefined' === typeof filename) {
    filename = this.getPath(this.getMainFile());
  } else if (!path.isAbsolute(filename)) {
    filename = path.resolve(this.getPath(), filename);
  }

  if (filename && util.fileExists(filename)) {
    var pattern = new RegExp(name + ':(.*)$', 'mi');
    var matches = fs.readFileSync(filename, {encoding: 'utf-8'}).match(pattern);

    if (matches) {
      return matches.pop().trim();
    }
  }

  if ('Text Domain' === name) {
    return guessSlug(this);
  }

  return '';
};

/**
 * Retrieve the main file.
 *
 * The main file contains the packager headers.
 *
 * @returns {string} Name of the main file relative to the package directory.
 */
WPPackage.prototype.getMainFile = function() {
  return this.mainFile;
};

/**
 * Set the main file.
 *
 * @param {string} mainFile Name of the main file relative to the package directory.
 * @returns {this}
 */
WPPackage.prototype.setMainFile = function(mainFile) {
  this.mainFile = mainFile;
  return this;
};

/**
 * Retrieve the full path to the package or a file within it.
 *
 * @param {string} file Optional. Name of a file relative to the package directory.
 * @returns {string} Full path to the package directory a file within it.
 */
WPPackage.prototype.getPath = function(file) {
  if ('undefined' === typeof file) {
    return this.directory;
  }

  return path.join(this.directory, file);
};

/**
 * Retrieve the package POT object.
 *
 * @returns {Pot}
 */
WPPackage.prototype.getPot = function() {
  return new Pot(this.getPotFilename());
};

/**
 * Retrieve the name of the POT file.
 *
 * @returns {string}
 */
WPPackage.prototype.getPotFile = function() {
  return this.potFile;
};

/**
 * Set the name of the POT file.
 *
 * @param {string} potFile Name of the pot file.
 * @returns {this}
 */
WPPackage.prototype.setPotFile = function(potFile) {
  this.potFile = potFile;
  return this;
};

/**
 * Retrieve the full path to the POT file.
 *
 * @returns {string}
 */
WPPackage.prototype.getPotFilename = function() {
  return path.join(this.getPath(), this.getDomainPath(), this.potFile);
};

/**
 * Retrieve the package slug.
 *
 * @returns {string}
 */
WPPackage.prototype.getSlug = function() {
  return guessSlug(this);
};

/**
 * Retrieve the package type.
 *
 * @returns {string}
 */
WPPackage.prototype.getType = function() {
  return this.type;
};

/**
 * Whether a package is a certain type.
 *
 * @param {string} type Package type.
 * @returns {boolean}}
 */
WPPackage.prototype.isType = function(type) {
  return this.getType() === type;
};

/**
 * Set the package type.
 *
 * @param {string} type Optional. Defaults to 'wp-plugin' if the package doesn't have a style.css file.
 * @returns {this}
 */
WPPackage.prototype.setType = function(type) {
  if ('wp-theme' === type) {
    this.type = 'wp-theme';
  } else if (('undefined' === typeof type || '' === type) && util.fileExists(this.getPath('style.css'))) {
    this.type = 'wp-theme';
  } else {
    this.type = 'wp-plugin';
  }

  return this;
};
