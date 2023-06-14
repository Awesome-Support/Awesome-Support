var execFileSync = require('child_process').execFileSync;
var fs = require('fs');
var gettext = require('gettext-parser');
var path = require('path');
var test = require('tap').test;
var msgmerge = require('../lib/msgmerge');

var hasMsgMerge = (function() {
  try {
    execFileSync('msgmerge', ['--version']);
  } catch (ex) {
    return false;
  }
  return true;
})();

test('update po files', { skip: ! hasMsgMerge }, function(t) {
  t.plan(4);

  var potFilename = path.resolve('tmp/msgmerge/msgmerge.pot');
  msgmerge.updatePoFiles(potFilename)
    .then(function() {
      var en_GB = gettext.po.parse(fs.readFileSync('tmp/msgmerge/msgmerge-en_GB.po', 'utf8'));
      var nl_NL = gettext.po.parse(fs.readFileSync('tmp/msgmerge/msgmerge-nl_NL.po', 'utf8'));

      t.ok(en_GB.translations['']['Colors'], '"Colors" string should exist');
      t.ok(nl_NL.translations['']['Colors'], '"Colors" string should exist');

      t.equal(en_GB.translations['']['Colors']['comments']['flag'], 'fuzzy', 'a changed translation should be fuzzy after msgmerge');
      t.equal(nl_NL.translations['']['Colors']['comments']['flag'], 'fuzzy', 'a changed translation should be fuzzy after msgmerge');
    });
});
