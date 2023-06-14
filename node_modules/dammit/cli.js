#! /usr/bin/env node

'use strict';

var argv = require('minimist')(process.argv.slice(2));
var pkg = require('./package.json');
var dammit = require('./lib/dammit');

function help() {
	console.log([
		pkg.description,
		'',
		'Example',
		'  $ dammit',
		'  gosh darn it'
	].join('\n'));
}

if (argv.help) {
	help();
	return;
}

if (argv.version) {
	console.log(pkg.version);
	return;
}

if (argv._.indexOf('NSFW') !== -1) {
	console.log(dammit({'NSFW': true}));
} else {
	console.log(dammit({'NSFW': false}));
}
