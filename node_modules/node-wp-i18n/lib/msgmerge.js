/**
 * node-wp-i18n
 * https://github.com/cedaro/node-wp-i18n
 *
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license MIT
 */

'use strict';

var glob = require('glob');
var path = require('path');
var Promise = require('bluebird');
var util = require('./util');

module.exports = {
  merge: mergeFiles,
  updatePoFiles: updatePoFiles
};

/**
 * Uses gettext msgmerge to merge a .pot file into a .po.
 *
 * @param {string} from File to merge from (generally a .pot file).
 * @param {string} to   File to merge to (generally a .po file).
 * @returns {Promise}
 */
function mergeFiles(from, to) {
  return util.execFile('msgmerge', [ '--update', '--backup=none', to, from ]);
}

/**
 * Set multiple headers at once.
 *
 * Magically expands certain values to add Poedit headers.
 *
 * @param {string} filename Full path to a POT file.
 * @param {string} pattern  Optional. Glob pattern of PO files to update.
 * @returns {Promise}
 */
function updatePoFiles(filename, pattern) {
  var merged = [];
  var searchPath = path.dirname(filename);

  pattern = pattern || '*.po';

  glob.sync(pattern, {
    cwd: path.dirname(filename)
  }).forEach(function(file) {
    var poFile = path.join(searchPath, file);
    merged.push(mergeFiles(filename, poFile));
  });

  return Promise.all(merged);
}
