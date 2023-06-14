/**
 * node-wp-i18n
 * https://github.com/cedaro/node-wp-i18n
 *
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license MIT
 */

'use strict';

var _ = require('lodash');
var mkdirp = require('mkdirp');
var msgMerge = require('./msgmerge');
var path = require('path');
var Promise = require('bluebird');
var util = require('./util');
var WPPackage = require('./package');

var toolsPath = path.resolve(__dirname, '../bin/php/');

/**
 * Create a POT file.
 *
 * @param {Array} options
 * @returns {Promise}
 */
module.exports = function(options) {
  options = _.merge({
    cwd: process.cwd(),
    domainPath: '',
    exclude: [],
    include: [],
    mainFile: '',
    potComments: '',
    potFile: '',
    potHeaders: {},
    processPot: null,
    type: '',
    updateTimestamp: true,
    updatePoFiles: false
  }, options);

  var wpPackage = new WPPackage(options.cwd, options.type);

  if ('' !== options.mainFile) {
    wpPackage.setMainFile(options.mainFile);
  }

  if ('' !== options.domainPath) {
    wpPackage.setDomainPath(options.domainPath);
  }

  if ('' !== options.potFile) {
    wpPackage.setPotFile(options.potFile);
  }

  // Create the domain path directory if it doesn't exist.
  mkdirp.sync(wpPackage.getPath(wpPackage.getDomainPath()));

  // Exclude the node_modules directory by default.
  options.exclude.push('node_modules/.*');

  var originalPot = wpPackage.getPot();

  if (originalPot.fileExists()) {
    originalPot.parse();
  }

  return util.execFile('php', [
    path.resolve(toolsPath, 'node-makepot.php'),
    wpPackage.getType(),
    wpPackage.getPath(),
    wpPackage.getPotFilename(),
    wpPackage.getSlug(),
    wpPackage.getMainFile(),
    options.exclude.join(','),
    options.include.join(',')
  ])
  .then(function() {
    var pot = wpPackage.getPot();

    if (pot.fileExists()) {
      pot.parse()
        .setFileComment(options.potComments)
        .setHeaders(options.potHeaders);

      // Allow the POT file to be modified with a callback.
      if ('function' === typeof options.processPot) {
        pot.contents = options.processPot.call(pot, pot.contents, options);
      }

      // Determine if the creation date is the only thing that changed.
      if (!options.updateTimestamp && pot.sameAs(originalPot)) {
        pot.setHeader('pot-creation-date', originalPot.initialDate);
      }

      pot.save();
    }

    return Promise.resolve(wpPackage);
  })
  .then(function maybeUpdatePoFiles(wpPackage) {
    if (options.updatePoFiles) {
      return msgMerge
        .updatePoFiles(wpPackage.getPotFilename())
        .return(wpPackage);
    }

    return Promise.resolve(wpPackage);
  })
  .catch(function(error) {
    console.log(error);
  });
};
