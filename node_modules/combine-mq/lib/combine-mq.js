/**
 *
 * @file Combine MQ Main
 * @version 0.1.0
 * @author {@link http://github.com/furzeface Daniel Furze}
 * @link https://github.com/frontendfriends/node-combine-mq
 *
 * Copyright (c) 2014 Building Blocks
 * Licensed under the MIT license.
 *
 */
 'use strict';
// Require all of the things.
var fs = require('fs'),
chalk = require('chalk'),
mkdirp = require('mkdirp'),
parseCss = require('css-parse'),
cssbeautify = require('cssbeautify'),
superb = require('superb'),
dammit = require('dammit');
/**
 * Task methods.
 * @namespace CombineMq
 */
 module.exports = {
	/**
	 * Initialises task.
	 * @function init
	 * @param {object} options
	 * @memberof CombineMq
	 * @todo Add option to log out combined queries.
	 */
	 init: function(options) {
		var self = this; // Scope resolution
		// Read file in
		fs.readFile(options.src, 'utf8', function(err, fileContents) {
			if (err) {
				// Write error messages
				console.log(chalk.red(dammit({
					NSFW: false
				})));
				console.log(chalk.red('(Could not open file: "' + self.src + '")'));
				// Exit out
				process.exit(1);
			} else {
				console.log(chalk.bold('\nCombining file: ' + self.src + '...'));
				// Moving on...
				self.parseCssString(fileContents ,options);
			}
		});
	},
	/**
	 * Parses the input CSS file.
	 * @function parseCssString
	 * @param {string} contents - CSS string.
	 * @memberof CombineMq
	 */
	 parseCssString: function(contents, options) {
		var self = this; // Scope resolution
		// Helpers
		// Process media queries
		var processImportURL = function(importURL) {
			var strCss = '';
			strCss += '@import ' + importURL.import + ';';
			return strCss;
		};
		// Process comments
		var processComment = function(comment) {
			var strCss = '/*' + comment.comment + '*/';
			return strCss;
		};
		// Process declaration
		var processDeclaration = function(declaration) {
			var strCss = declaration.property + ': ' + declaration.value + ';';
			return strCss;
		};
		// Check declarations type
		var commentOrDeclaration = function(declarations) {
			var strCss = '';
			if (declarations.type === 'declaration') {
				strCss += '\n\t' + processDeclaration(declarations);
			} else if (declarations.type === 'comment') {
				strCss += ' ' + processComment(declarations);
			}
			return strCss;
		};
		// Process normal CSS rule
		var processRule = function(rule) {
			var strCss = '';
			strCss += rule.selectors.join(',\n') + ' {';
			rule.declarations.forEach(function(rules) {
				strCss += commentOrDeclaration(rules);
			});
			strCss += '\n}\n\n';
			return strCss;
		};
		// Check rule type
		var commentOrRule = function(rule) {
			var strCss = '';
			//log(rule.type);
			if (rule.type === 'rule') {
				strCss += processRule(rule);
			} else if (rule.type === 'comment') {
				strCss += processComment(rule) + '\n\n';
			} else if (rule.type === 'media') {
				strCss += processMedia(rule);
			} else if (rule.type === 'keyframes') {
				strCss += processKeyframes(rule);
			}
			return strCss;
		};
		// Check keyframe type
		var commentOrKeyframe = function(frame) {
			var strCss = '';
			if (frame.type === 'keyframe') {
				strCss += frame.values.join(',') + ' {';
				frame.declarations.forEach(function(declaration) {
					strCss += commentOrDeclaration(declaration);
				});
				strCss += '\n}\n\n';
			} else if (frame.type === 'comment') {
				strCss += processComment(frame) + '\n\n';
			}
			return strCss;
		};
		// Process media queries
		var processMedia = function(media) {
			var strCss = '',
			query = media.rule;
			//log(JSON.stringify(media)+'\n\n');
			if (!query && media.media) {
				query = media.media;
			}
			if (query) {
				//log(JSON.stringify(media)+'\n\n');
				strCss += '@media ' + query + ' {\n\n';
				media.rules.forEach(function(rule) {
					strCss += commentOrRule(rule);
				});
				strCss += '}\n\n';
			}
			return strCss;
		};
		// Process keyframes
		var processKeyframes = function(key) {
			var strCss = '';
			strCss += '@' + (typeof key.vendor !== 'undefined' ? key.vendor : '') + 'keyframes ' + key.name + ' {\n\n';
			key.keyframes.forEach(function(keyframe) {
				strCss += commentOrKeyframe(keyframe);
			});
			strCss += '}\n\n';
			return strCss;
		};
		// Process document
		var processDocument = function(doc) {
			var strCss = '';
			strCss += '@' + (typeof doc.vendor !== 'undefined' ? doc.vendor : '') + 'document ' + doc.document + ' {\n\n';
			doc.rules.forEach(function(rule) {
				strCss += commentOrRule(rule);
			});
			strCss += '}\n\n';
			return strCss;
		};

		// Process charset
		var processCharset = function(charset) {
			return '@charset ' + charset.charset + ';\n\n';
		};

		// Process font-face
		var processFontface = function(fontface) {
			var strCss = '';
			strCss += '@font-face {\n';
			fontface.declarations.forEach(function (declaration) {
				strCss += declaration.property + ':' + declaration.value + ';\n';
			});

			strCss += '}\n\n';
			return strCss;
		};
		// Do the processing
		var cssJson = parseCss(contents);
		var strStyles = '';
		var processedCSS = {};
		processedCSS.importURL = [];
		processedCSS.base = [];
		processedCSS.media = [];
		processedCSS.media.all = [];
		processedCSS.media.minWidth = [];
		processedCSS.media.maxWidth = [];
		processedCSS.media.minHeight = [];
		processedCSS.media.maxHeight = [];
		processedCSS.media.print = [];
		processedCSS.media.blank = [];
		processedCSS.keyframes = [];
		processedCSS.charset = [];
		processedCSS.document = [];
		processedCSS.fontface = [];
		// For every rule in the stylesheet...
		cssJson.stylesheet.rules.forEach(function(rule) {
			// if the rule is a media query...
			if (rule.type === 'media') {
				// Create 'id' based on the query (stripped from spaces and dashes etc.)
				var strMedia = rule.media.replace(/[^A-Za-z0-9]/ig, '');
				// Create an array with all the media queries with the same 'id'
				var item = processedCSS.media.filter(function(element) {
					return (element.val === strMedia);
				});
				// If there are no media queries in the array, define details
				if (item.length < 1) {
					var mediaObj = {};
					mediaObj.sortVal = parseFloat(rule.media.match(/\d+/g));
					mediaObj.rule = rule.media;
					mediaObj.val = strMedia;
					mediaObj.rules = [];

					processedCSS.media.push(mediaObj);
				}
				// Compare the query to other queries
				var i = 0,
				matched = false;
				processedCSS.media.forEach(function(elm) {
					if (elm.val === strMedia) {
						matched = true;
					}
					if (!matched) {
						i++;
					}
				});
				// Push every merged query
				rule.rules.forEach(function(mediaRule) {
					if (mediaRule.type === 'rule' || 'comment') {
						processedCSS.media[i].rules.push(mediaRule);
					}
				});
			} else if (rule.type === 'keyframes') {
				processedCSS.keyframes.push(rule);
			} else if (rule.type === 'import') {
				processedCSS.importURL.push(rule);
			} else if (rule.type === 'document') {
				processedCSS.document.push(rule);
			} else if (rule.type === 'charset') {
				processedCSS.charset.push(rule);
			} else if (rule.type === 'rule' || 'comment') {
				processedCSS.base.push(rule);
			}
			if (rule.type === 'font-face') {
				processedCSS.fontface.push(rule);
			}
		});

		var i = 0;
		// Sort media queries by kind, this is needed to output them in the right order
		processedCSS.media.forEach(function(item) {
			// We need to store a reliable sort order as Array.prototype.sort
			// is not stable in the V8 engine for large arrays
			item.position = i;

			if (item.rule.match(/min-width/)) {
				processedCSS.media.minWidth.push(item);
			} else if (item.rule.match(/min-height/)) {
				processedCSS.media.minHeight.push(item);
			} else if (item.rule.match(/max-width/)) {
				processedCSS.media.maxWidth.push(item);
			} else if (item.rule.match(/max-height/)) {
				processedCSS.media.maxHeight.push(item);
			} else if (item.rule.match(/print/)) {
				processedCSS.media.print.push(item);
			} else if (item.rule.match(/all/)) {
				processedCSS.media.all.push(item);
			} else {
				processedCSS.media.blank.push(item);
			}

			i++;
		});
		// Function to determine sort order
		var determineSortOrder = function(a, b, isMax) {
			var sortValA = a.sortVal,
			sortValB = b.sortVal;
			isMax = typeof isMax !== 'undefined' ? isMax : false;
			// consider print for sorting if sortVals are equal
			if (sortValA === sortValB) {
				if (a.rule.match(/print/)) {
					// a contains print and should be sorted after b
					return 1;
				}
				if (b.rule.match(/print/)) {
					// b contains print and should be sorted after a
					return -1;
				}

				// Fall back to sorting by their position in the array
				return a.position - b.position;
			}
			// return descending sort order for max-(width|height) media queries
			if (isMax) {
				return sortValB - sortValA;
			}

			// return ascending sort order
			return sortValA - sortValB;
		};
		// Sort media.all queries ascending
		processedCSS.media.all.sort(function(a, b) {
			return determineSortOrder(a, b);
		});
		// Sort media.minWidth queries ascending
		processedCSS.media.minWidth.sort(function(a, b) {
			return determineSortOrder(a, b);
		});
		// Sort media.minHeight queries ascending
		processedCSS.media.minHeight.sort(function(a, b) {
			return determineSortOrder(a, b);
		});
		// Sort media.maxWidth queries descending
		processedCSS.media.maxWidth.sort(function(a, b) {
			return determineSortOrder(a, b, true);
		});
		// Sort media.maxHeight queries descending
		processedCSS.media.maxHeight.sort(function(a, b) {
			return determineSortOrder(a, b, true);
		});
		// Function to output charset CSS
		var outputCharset = function(charset){
			charset.forEach(function (charset) {
				strStyles += processCharset(charset);
			});
		};
		// Function to output base CSS
		var outputBase = function(base) {
			base.forEach(function(rule) {
				strStyles += commentOrRule(rule);
			});
		};
		// Function to import URL
		var outputImportURL = function(importURL) {
			importURL.forEach(function(item) {
				strStyles += processImportURL(item);
			});
		};
		// Function to output media queries
		var outputMedia = function(media) {
			media.forEach(function(item) {
				strStyles += processMedia(item);
			});
		};
		// Function to output keyframes
		var outputKeyFrames = function(keyframes) {
			keyframes.forEach(function(keyframe) {
				strStyles += processKeyframes(keyframe);
			});
		};
		// Function to output document
		var outputDocument = function(doc) {
			doc.forEach(function(doc) {
				strStyles += processDocument(doc);
			});
		};
		// Function to output fontface CSS
		var outputFontface = function(fontface){
			fontface.forEach(function (fontface) {
				strStyles += processFontface(fontface);
			});
		};

		// Check if charset was set
		if (processedCSS.charset.length !== 0){
			outputCharset(processedCSS.charset);
		}
		// Check if import URL was processed
		if (processedCSS.importURL.length !== 0) {
			outputImportURL(processedCSS.importURL);
		}
		// Check if base CSS was processed and print them
		if (processedCSS.base.length !== 0) {
			outputBase(processedCSS.base);
		}
		// Check if fontface was set
		if (processedCSS.fontface.length !== 0){
			outputFontface(processedCSS.fontface);
		}
		// Check if media queries were processed and print them in order
		if (processedCSS.media.length !== 0) {
			outputMedia(processedCSS.media.blank);
			outputMedia(processedCSS.media.all);
			outputMedia(processedCSS.media.minWidth);
			outputMedia(processedCSS.media.minHeight);
			outputMedia(processedCSS.media.maxWidth);
			outputMedia(processedCSS.media.maxHeight);
			outputMedia(processedCSS.media.print);
		}
		// Check if document was processed
		if (processedCSS.document.length !== 0) {
			outputDocument(processedCSS.document);
		}
		// Check if keyframes were processed and print them
		if (processedCSS.keyframes.length !== 0) {
			outputKeyFrames(processedCSS.keyframes);
		}

		// Beautify output if requested
	 	if (options.beautify) {
			strStyles = cssbeautify(strStyles, {
				autosemicolon: true,
				indent: '  '
			});
	 	}

		if (!options.dest) {
			return strStyles;
		}

		self.writeFile(strStyles, options);
	},
	/**
	 * Write combined CSS to a specified file, in a specified directory.
	 * @function writeFile
	 * @param {string} fileContents - combined CSS string.
	 * @memberof CombineMq
	 * @todo Strip file extension if it's in dest.
	 * @todo Extract file path from dest.
	 */
	 writeFile: function(fileContents, options) {
		// Create a dir
		mkdirp('tmp', function(err) {
			if (err) {
				throw err;
			} else {
				process.chdir('tmp');
				fs.writeFile(options.dest + '.css', fileContents, function(err) {
					if (err) {
						throw err;
					}
					// Write a success message
					var sup = superb();
					sup = sup.charAt(0).toUpperCase() + sup.slice(1) + '!';
					console.log(chalk.green(sup + ' CSS combined!\n'));
				});
			}
		});
	}
};
