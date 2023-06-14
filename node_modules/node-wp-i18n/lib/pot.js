/**
 * node-wp-i18n
 * https://github.com/cedaro/node-wp-i18n
 *
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license MIT
 */

'use strict';

var _ = require('lodash');
var crypto = require('crypto');
var fs = require('fs');
var gettext = require('gettext-parser');
var util = require('./util');

module.exports = Pot;

/**
 * Fix POT file headers.
 *
 * Updates case-sensitive Poedit headers.
 *
 * @param {string} pot POT file contents.
 * @returns {string}
 */
function fixHeaders(contents) {
  contents = contents.replace(/x-poedit-keywordslist:/i, 'X-Poedit-KeywordsList:');
  contents = contents.replace(/x-poedit-searchpath-/ig, 'X-Poedit-SearchPath-');
  contents = contents.replace(/x-poedit-searchpathexcluded-/ig, 'X-Poedit-SearchPathExcluded-');
  contents = contents.replace(/x-poedit-sourcecharset:/i, 'X-Poedit-SourceCharset:');
  return contents;
}

function generateHash(content) {
  return crypto.createHash('md5').update(JSON.stringify(content)).digest('hex');
}

/**
 * Normalize Pot contents created by gettext-parser.
 *
 * This normalizes dynamic strings in a POT file in order to compare them and
 * determine if anything has changed.
 *
 * Headers are stored in two locations.
 *
 * @param {Object} pot Pot contents created by gettext-parser.
 * @returns {Object}
 */
function normalizeForComparison(pot) {
  var clone = _.cloneDeep(pot);

  if (!pot) {
    return pot;
  }

  // Normalize the content type case.
  clone.headers['content-type'] = clone.headers['content-type'].toLowerCase();

  // Blank out the dates.
  clone.headers['pot-creation-date'] = '';
  clone.headers['po-revision-date'] = '';

  // Blank out the headers in the translations object. These are used for
  // reference only and won't be compiled, so they shouldn't be used when
  // comparing POT objects.
  clone.translations['']['']['msgstr'] = '';

  return clone;
}

/**
 * Create a new Pot object.
 *
 * @class Pot
 */
function Pot(filename) {
  if (! (this instanceof Pot)) {
    return new Pot(filename);
  }

  this.isOpen = false;
  this.filename = filename;
  this.contents = '';
  this.initialDate = '';
  this.fingerprint = '';
}

/**
 * Whether the POT file exists.
 *
 * @returns {boolean}
 */
Pot.prototype.fileExists = function() {
  return util.fileExists(this.filename);
};

/**
 * Parse the POT file using gettext-parser.
 *
 * Initializes default properties to determine if the file has changed.
 *
 * Parsing the file removes duplicates, replacing the need for the msguniq binary.
 *
 * @param {string} Full path to the package root directory.
 * @returns {this}
 */
Pot.prototype.parse = function() {
  if (!this.isOpen) {
    this.contents = fs.readFileSync(this.filename, 'utf8');
    this.contents = gettext.po.parse(this.contents);
    this.initialDate = this.contents.headers['pot-creation-date'];
    this.fingerprint = generateHash(normalizeForComparison(this.contents));
    this.isOpen = true;
  }

  return this;
};

/**
 * Save the POT file.
 *
 * Writes the POT contents to a file.
 *
 * @returns {this}
 */
Pot.prototype.save = function() {
  var contents;

  if (this.isOpen) {
    contents = gettext.po.compile(this.contents).toString();
    contents = fixHeaders(contents);

    fs.writeFileSync(this.filename, contents);
    this.isOpen = false;
  }

  return this;
};

/**
 * Whether the contents have changed.
 *
 * @returns {boolean}
 */
Pot.prototype.hasChanged = function() {
  return generateHash(normalizeForComparison(this.contents)) !== this.fingerprint;
};

/**
 * Reset the creation date header.
 *
 * Useful when strings haven't changed in the package and don't want to commit
 * an unnecessary change to a repository.
 *
 * @returns {this}
 */
Pot.prototype.resetCreationDate = function() {
  this.contents.headers['pot-creation-date'] = this.initialDate;
  return this;
};

/**
 * Whether two POT files have the same content regardless of creation date header.
 *
 * @param {Pot}
 * @returns {boolean}
 */
Pot.prototype.sameAs = function(pot) {
  var fingerprint = generateHash(normalizeForComparison(this.contents));

  var compareHash = -1;
  if (pot.fileExists()) {
    compareHash = generateHash(normalizeForComparison(pot.contents));
  }

  return fingerprint === compareHash;
};

/**
 * Set the comment that shows at the beginning of the POT file.
 *
 * @param {string} Comment text.
 * @returns {this}
 */
Pot.prototype.setFileComment = function(comment) {
  if ('' === comment) {
    return this;
  }

  comment = comment.replace('{year}', new Date().getFullYear());
  this.contents.translations[''][''].comments.translator = comment;

  return this;
};

/**
 * Set a header value.
 *
 * Magically expands certain values to add Poedit headers.
 *
 * @param {string} name  Name of the header.
 * @param {string} value Value of the header.
 * @returns {this}
 */
Pot.prototype.setHeader = function(name, value) {
  var key = name.toLowerCase();

  var poedit = {
    'language': 'en',
    'plural-forms': 'nplurals=2; plural=(n != 1);',
    'x-poedit-country': 'United States',
    'x-poedit-sourcecharset': 'UTF-8',
    'x-poedit-keywordslist': true,
    'x-poedit-basepath': '../',
    'x-poedit-searchpath-0': '.',
    'x-poedit-bookmarks': '',
    'x-textdomain-support': 'yes'
  };

  // Add default Poedit headers.
  if ('poedit' === key && true === value) {
    var self = this;
    _.forOwn(poedit, function(value, name) {
      if (!_.has(self.contents.headers, name)) {
        self.setHeader(name, value);
      }
    });
    return this;
  }

  // Add the the Poedit keywordslist header.
  if ('x-poedit-keywordslist' === key && true === value) {
    value = '__;_e;_x:1,2c;_ex:1,2c;_n:1,2;_nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_attr__;esc_html__;esc_attr_e;esc_html_e;esc_attr_x:1,2c;esc_html_x:1,2c;';
  }

  this.contents.headers[ key ] = value;
  return this;
};

/**
 * Set multiple headers at once.
 *
 * @param {object} Headers object.
 * @returns {this}
 */
Pot.prototype.setHeaders = function(headers) {
  var self = this;

  _.forOwn(headers, function(value, name) {
    self.setHeader(name, value);
  });

  return this;
};
