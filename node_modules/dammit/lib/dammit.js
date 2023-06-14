/*
 * dammit
 * https://github.com/furzeface/dammit
 *
 * Copyright (c) 2014 Daniel Furze
 * Licensed under the MIT license.
 */

 'use strict';

 var curses = require('../src/curses.json'),
 cursesNSFW = require('../src/cursesNSFW.json');
 var uniqueRandom = require('unique-random')(0, curses.length - 1),
 uniqueRandomNSFW = require('unique-random')(0, cursesNSFW.length - 1);

 module.exports = function (args) {
 	if (args.NSFW) {
 		return cursesNSFW[uniqueRandomNSFW()];
 	} else {
 		return curses[uniqueRandom()];
 	}
 };

 module.exports.curses = curses;
