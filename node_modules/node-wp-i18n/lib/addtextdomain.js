/**
 * node-wp-i18n
 * https://github.com/cedaro/node-wp-i18n
 *
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license MIT
 */

'use strict';

var _ = require('lodash');
var fs = require('fs');
var path = require('path');
var Promise = require('bluebird');
var tmp = require('tmp');
var util = require('./util');
var WPPackage = require('./package');

var toolsPath = path.resolve(__dirname, '../bin/php/');

/**
 * Add a text domain to gettext functions in PHP files.
 *
 * @param {Array} files   List of files.
 * @param {Array} options
 * @returns {Promise}
 */
module.exports = function(files, options) {
  options = _.merge({
    cwd: process.cwd(),
    dryRun: false,
    textdomain: '',
    updateDomains: []
  }, options);

  options.cwd = path.resolve(process.cwd(), options.cwd);
  var wpPackage = new WPPackage(options.cwd);

  if ('' === options.textdomain) {
    options.textdomain = wpPackage.getHeader('Text Domain');
  }

  if (true === options.updateDomains) {
    options.updateDomains = ['all'];
  }

  var args = {
    'dry-run': options.dryRun,
    files: files.map(function(file) {
      return path.resolve(options.cwd, file);
    }),
    textdomain: options.textdomain,
    'update-domains': options.updateDomains
  };

  var argsFile = tmp.tmpNameSync({ prefix: 'arguments-', postfix: '.json' });
  fs.writeFileSync(argsFile, JSON.stringify(args));

  return util.spawn('php', [
    path.resolve(toolsPath, 'node-add-textdomain.php'),
    argsFile
  ]).finally(function() {
    fs.unlinkSync(argsFile);
  });
};
