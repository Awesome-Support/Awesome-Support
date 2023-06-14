# node-wp-i18n [![Build Status](https://travis-ci.org/cedaro/node-wp-i18n.png?branch=develop)](https://travis-ci.org/cedaro/node-wp-i18n)

> Internationalize WordPress plugins and themes.

WordPress has a robust suite of tools to help internationalize plugins and themes. This plugin brings the power of those existing tools to Node.js in order to make it easy for you to automate the i18n process and make your projects more accessible to an international audience.

If you're not familiar with i18n concepts, read the Internationalization entries in the [Plugin Developer Handbook](https://developer.wordpress.org/plugins/internationalization/) or [Theme Developer Handbook](https://developer.wordpress.org/themes/functionality/internationalization/).

node-wp-i18n started as the core of the [grunt-wp-i18n](https://github.com/cedaro/grunt-wp-i18n) plugin, but has been extracted and rewritten to be more useful as a standalone module and with other tools.


## Getting Started

`node-wp-i18n` includes a basic CLI tool to help generate POT file or add text domains to i18n functions in WordPress plugins or themes. Installing this module globally will allow you to access the `wpi18n` command:

```sh
npm install -g node-wp-i18n
```

Once installed, run this command from a plugin or theme to see the available options:

```sh
wpi18n -h
```

Running `wpi18n info` in a plugin or theme directory will show you information about that package.


### Requirements

* [PHP CLI](http://www.php.net/manual/en/features.commandline.introduction.php) must be in your system path.
