/**
 * node-wp-i18n
 * https://github.com/cedaro/node-wp-i18n
 *
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license MIT
 */

'use strict';

var execFile = require('child_process').execFile;
var fs = require('fs');
var Promise = require('bluebird');
var spawn = require('child_process').spawn;

module.exports = {
  /**
   * Execute a file and return a promise.
   *
   * @param {string}   file Filename of the program to run.
   * @param {string[]} args List of string arguments.
   * @returns {Promise}
   */
  execFile: function(file, args) {
    return new Promise(function(resolve, reject) {
      execFile(file, args, function(error, stdout) {
        console.log(stdout);

        if (error) {
          reject(error);
        } else {
          resolve();
        }
      });
    });
  },

  /**
   * Whether a file exists.
   *
   * @param {string} filename Full path to a file.
   * @returns {boolean}
   */
  fileExists: function(filename) {
    try {
      var stat = fs.statSync(filename);
    } catch (ex) {
      return false;
    }

    return stat.isFile();
  },

  /**
   * Spawn a process and return a promise.
   *
   * @param {string}   file Filename of the program to run.
   * @param {string[]} args List of string arguments.
   * @returns {Promise}
   */
  spawn: function(file, args) {
    return new Promise(function(resolve, reject) {
      var child = spawn(file, args, { stdio: 'inherit' });
      child.on('error', reject);
      child.on('close', resolve);
    });
  }
};
