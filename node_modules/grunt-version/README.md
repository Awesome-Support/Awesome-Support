# grunt-version

[Grunt][grunt] task to handle versioning of a project.

## Getting Started
_Requires grunt >=0.4.2. If you haven't used [grunt][] before, be sure to check out the [Getting Started][] guide._

From the same directory as your project's [Gruntfile][Getting Started] and [package.json][], install this plugin by running the following command:

```bash
npm install grunt-version --save-dev
```

Once that's done, add this line to your project's Gruntfile:

```js
grunt.loadNpmTasks('grunt-version');
```

If the plugin has been installed correctly, running `grunt --help` at the command line should list the newly-installed plugin's task. In addition, the plugin should be listed in package.json as a `devDependency`, which ensures that it will be installed whenever the `npm install` command is run.

[grunt]: http://gruntjs.com/
[Getting Started]: https://github.com/gruntjs/grunt/blob/devel/docs/getting_started.md
[package.json]: https://npmjs.org/doc/json.html

## The "version" task

### Overview
In your project's Gruntfile, add a section named `version` to the data object passed into `grunt.initConfig()`.

```js
grunt.initConfig({
  version: {
    options: {
      // Task-specific options go here.
    },
    your_target: {
      // Target-specific file lists and/or options go here.
    },
  },
})
```

### Options

#### options.pkg
Type: `String|Object`
Default value: `'package.json'`

A string representing a package file's path relative to Gruntfile.js, or an object representing a parsed package file.

This package file is where your "canonical" version should be set, in a `"version"` property. The `grunt-version` plugin uses that version (either incremented by the `release` option or not) when it updates version info in other files.


#### options.prefix
Type: `String`
Default value: `'[^\\-]version[\'"]?\\s*[:=]\\s*[\'"]'`

A string value representing a regular expression to match text preceding the actual version within the file.

If you're following one of the popular documentation syntaxes in your js files, you might want to set the option like so:

```js
grunt.initConfig({
  version: {
    somejs: {
      options: {
        prefix: '@version\\s*'
      },
      src: ['js/*.js']
    },
  },
})
```

#### options.replace
Type: `String`
Default value: `'[0-9a-zA-Z\\-_\\+\\.]+'`

A string value representing a regular expression to match the version number (immediately following the `options.prefix` text).

#### options.flags
Type: `String`
Default value: `'g'`

A string value representing one or more regular expression flags (e.g. `'i'`, `'ig'`).

#### options.release
Type: `String`
Default value: `''`

A string value representing one of the **semver 2.x** release types (`'major'`, `'minor'`, `'patch'`, or `'prerelease'`) used to increment the value of the specified package version. See [node-semver](https://github.com/isaacs/node-semver) for more information about release incrementing. The value may also be a literal semver-valid release (for example, '1.3.2').

#### options.prereleaseIdentifier
Type: `String`
Default value: `''`

A string value representing a prefix for the prerelease version (e.g., `'dev'`,`'alpha'`,`'beta'`). Setting this value to `dev` would prerelease-increment a version of 1.2.3 to 1.2.3-dev.0 instead of 1.2.3-0.

#### options.encoding
Type: `String`
Default value: `'utf8'`

A string value representing the encoding to be used for reading and writing file contents.

### Usage Examples

#### Default Options
In this example, the default options are used to update the version in `src/testing.js` based on the `version` property set in a `package.json` file located in the same directory as your `Gruntfile.js`. So if the version property in `package.json` is `"0.1.2"`, and the `src/testing.js` file has the content `var version = '0';`, that content would change to `var version = '0.1.2';`

```js
grunt.initConfig({
  version: {
    // options: {},
    defaults: {
      src: ['src/testing.js']
    }
  }
})
```

#### Auto-incrementing based on task argument

It can be a hassle to add a grunt target for every release type you might want to use. Fortunately, you can avoid that. Simply provide at least one target that lists the files you want to update:

```js
grunt.initConfig({
  version: {
    project: {
      src: ['package.json', 'bower.json', 'myplugin.jquery.json']
    }
  }
});
```

Then, from the command line (designated by the `$`, so don't include that if you're copying the code below), you can bump the patch version, for example:

```bash
$  grunt version:project:patch
```

You can also skip the target name:

```bash
$  grunt version::minor
```

In this example, it bumps the minor version in the files listed within the "project" target, even though "project" is not identified explicitly between the two `:`. Note that if the version config includes more than one target, the example would update the files listed within *every* target.

#### Custom Options
In this example, custom options are used.

```js
grunt.initConfig({
  version: {
    options: {
      pkg: 'myplugin.jquery.json'
    },
    myplugin: {
      options: {
        prefix: 'var version\\s+=\\s+[\'"]'
      },
      src: ['src/testing.js', 'src/123.js']
    },
    myplugin_patch: {
      options: {
        release: 'patch'
      },
      src: ['myplugin.jquery.json', 'src/testing.js', 'src/123.js'],
    }
  }
});
```

## Contributing
In lieu of a formal styleguide, take care to maintain the existing coding style. Add unit tests for any new or changed functionality. Lint and test your code using [grunt][].
