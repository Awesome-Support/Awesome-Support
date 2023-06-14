# grunt-composer [![Build Status](https://travis-ci.org/voceconnect/grunt-composer.png?branch=master)](https://travis-ci.org/voceconnect/grunt-composer)


> A Grunt task wrapper for composer to allow custom tasks to be configured to run composer as needed.

## Getting Started

Installing the plugin:

```shell
npm install grunt-composer --save-dev
```

Loading the plugin via JavaScript:

```js
grunt.loadNpmTasks('grunt-composer');
```

### Options

#### options.cwd
Type: `String`
Default value: `.`

The directory in which to execute the composer command.


## Running Composer Commands

##### Commands

The first argument passed to the composer task becomes the command to run.  Arguments are passed to tasks in Grunt by separating them via a colon, ```:```.

```shell
grunt composer:update
```

Is equivalent to:

```shell
composer update
```

##### Command Options

Any arguments passed to the composer task after the command will get converted into options for the command.

```shell
grunt composer:install:no-dev
```

Is equivalent to:

```shell
composer install --no-dev
```
