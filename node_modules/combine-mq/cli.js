#! /usr/bin/env node

'use strict';

var task = require('./lib/combine-mq');


// These options will come from the Grunt task
// task.init({
// 	'src': 'test/examples/test.css',
// 	'dest': 'test/actual/combined.css'
// });


// Use the CLi whilst building the Node task
var program = require('commander');

program
.command('combine <src> <dest>')
.action(function (src, dest) {
	task.init({
		'src': src,
		'dest': dest
	});
});

program.parse(process.argv);
