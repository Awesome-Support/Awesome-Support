# dammit [![Build Status](https://secure.travis-ci.org/furzeface/dammit.png?branch=master)](http://travis-ci.org/furzeface/dammit) [![Stories in Ready](https://badge.waffle.io/furzeface/dammit.png?label=ready&title=Ready)](https://waffle.io/furzeface/dammit)

> Returns a random curse phrase. Use within tasks in failing scenarios. Go crazy with NSFW mode if you like.

## Getting Started

Install the module with: 

```sh
$ npm install dammit
```

Install with cli command:

```sh
$ npm install -g dammit
$ dammit --help
$ dammit --version
```

Use on the cli (pass in NSFW if you want):
```sh
$ dammit
$ dammit NSFW
```

## Examples
```js
var dammit = require('dammit');

dammit(); // 'Gosh darn it!'
dammit({'NSFW': true}); // '**** ***'
```

## Development
Link for local development:

```sh
$ npm link
```

Add phrases to `src/curses.json` or `src/cursesNSFW.json` if you're feeling naughty.

## Contributing
In lieu of a formal styleguide, take care to maintain the existing coding style. Add unit tests for any new or changed functionality. Lint and test your code using [Grunt](http://gruntjs.com).

```
$ grunt test
```

Commit messages loosely adhere to [these guidelines](https://github.com/angular/angular.js/blob/master/CONTRIBUTING.md#commit).

Versioning adheres to [Semver](http://semver.org).

## Release History
- 2014-08-13 v0.5.0 - Added NSFW Mode.
- 2014-08-13 v0.3.0
- 2014-08-13 v0.2.0 
- 2014-08-11 v0.1.0 - Initial release. 

Release with [grunt-bump](https://github.com/vojtajina/grunt-bump).

## License
Copyright (c) 2014 Daniel Furze. Licensed under the MIT license: http://danielfurze.mit-license.org
