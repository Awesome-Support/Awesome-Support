'use strict';
const gzipSize = require('gzip-size');
const prettyBytes = require('pretty-bytes');
const chalk = require('chalk');
const figures = require('figures');
const arrow = ' ' + figures.arrowRight + ' ';

const format = size => chalk.green(prettyBytes(size));

module.exports = function (max, min, useGzip = false) {
	const maxString = format(typeof max === 'number' ? max : max.length);
	const minString = format(typeof min === 'number' ? min : min.length);
	let returnValue = maxString + arrow + minString;

	if (useGzip && typeof min !== 'number') {
		returnValue += arrow + format(gzipSize.sync(min)) + chalk.gray(' (gzip)');
	}

	return returnValue;
};
