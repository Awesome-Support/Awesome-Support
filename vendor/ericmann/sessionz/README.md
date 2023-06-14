# PHP Sessionz [![Build Status][travis-image]][travis-url] [![Coverage Status][coveralls-image]][coveralls-url]

Sessionz is a PHP library for smarter session management in modular applications.

## Quick Start

Use [Composer](https://getcomposer.org/) to add `ericmann/sessionz` to your project. Then, after loading all of your dependencies, initialize the core session manager and add the handlers you need to your stack.

```
require __DIR__ . '/vendor/autoload.php';

EAMann\Sessionz\Manager::initialize()
    ->addHandler( new \EAMann\Sessionz\Handlers\DefaultHandler() )
    ->addHandler( new \EAMann\Sessionz\Handlers\EncryptionHandler( getenv('session_passkey') ) )
    ->addHandler( new \EAMann\Sessionz\Handlers\MemoryHandler() )

session_start();

```

The above example adds, in order:

- The default PHP session handler (which uses files for storage)
- An encryption middleware such that session data will be encrypted at rest on disk
- An in-memory cache to avoid round-trips to the filesystem on read

## How Handlers Work

The session manager maintains a list of registered "handlers" to which it passes requests from the PHP engine to:

- Read a session
- Write a session
- Create a session store
- Clean up (garbage collect) expired sessions
- Delete a session

Each handler must implement the `Handler` interface so the session manager knows how to work with them.

### Middleware Structure

The overall structure of the handler stack is identical to that of a middleware stack in a modern PHP application. You can read more about the general philosophy [on Slim's website](https://www.slimframework.com/docs/concepts/middleware.html#how-does-middleware-work).

In general, stack operations will flow from the outside-in, starting by invoking the appropriate operation on the most recently registered handler and walking down the stack to the oldest handler. Each handler has the option to halt execution and return data immediately, or can invoke the passed `$next` callback to continue operation.

Using the quick start example above:

- Requests start in the `MemoryHandler`
- If necessary, they then pass to the `EncryptionHandler`
- Requests always pass from encryption to the `DefaultHandler`
- If necessary, they then pass to the (hidden) `BaseHandler`
- Then everything returns because the base handler doesn't pass anything on

## Available Handlers

### `DefaultHandler`

The default session handler merely exposes PHP's default session implementation to our custom manager. Including this handler will provide otherwise standard PHP session functionality to the project as a whole, but this functionality can be extended by placing other stacks on top.

### `EncryptionHandler`

Sessions stored on disk (the default implementation) or in a separate storage system (Memcache, MySQL, or similar) should be encrypted _at rest_. This handler will automatically encrypt any information passing through it on write and decrypt data on read. It does not store data on its own.

This handler requires a symmetric encryption key when it's instantiated. This key should be an ASCII-safe string, 32 bytes in length. You can easily use [Defuse PHP Encryption](https://github.com/defuse/php-encryption) (a dependency of this library) to generate a new key:

```php
$rawKey = Defuse\Crypto\Key::createNewRandomKey();
$key = $rawKey->saveToAsciiSafeString();
```

### `MemoryHandler`

If the final storage system presented to the session manager is remote, reads and writes can take a non-trivial amount of time. Storing session data in memory helps to make the application more performant. Reads will stop at this layer in the stack if the session is found (i.e. the cache is hot) but will flow to the next layer if no session exists. When a session is found in a subsequent layer, this handler will update its cache to make the data available upon the next lookup.

Writes will update the cache and pass through to the next layer in the stack.

### Abstract handlers

The `BaseHandler` class is always instantiated and included at the root of the handler stack by default. This is so that, no matter what handlers you add in to the stack, the session manager will always return a standard, reliable set of information.

The `NoopHandler` class is provided for you to build additional middleware atop a standard interface that "passes through" to the next layer in the stack by default. The `EncryptionHandler`, for example, inherits from this class as it doesn't store or read data, but merely manipulates information before passing it along. Another implementation might be a logging interface to track when sessions are accessed/updated.

## Credits

The middleware implementation is inspired heavily by the request middleware stack presented by [the Slim Framework](https://www.slimframework.com/).

[travis-image]: https://travis-ci.org/ericmann/sessionz.svg?branch=master
[travis-url]: https://travis-ci.org/ericmann/sessionz
[coveralls-image]: https://coveralls.io/repos/github/ericmann/sessionz/badge.svg?branch=master
[coveralls-url]: https://coveralls.io/github/ericmann/sessionz?branch=master