/*
 * dammit
 * https://github.com/furzeface/dammit
 *
 * Copyright (c) 2014 Daniel Furze
 * Licensed under the MIT license.
 */

 'use strict';

 var dammit = require('../lib/dammit');

 exports.dammit = {
  setUp: function(done) {
    done();
  },
  safe: function(test) {
    test.expect(1);
    var curse = dammit({'NSFW': false});
    
    test.ok(curse.length > 0);
    test.done();
  },
  unsafe: function(test) {
    test.expect(1);

    var curse = dammit({'NSFW': true});

    test.ok(curse.length > 0);
    test.done();
  }
};
