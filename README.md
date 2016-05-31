# react-http-static

This is a very basic helper for creating static web servers using `react/http`. It serves files from a specified 
directory and can optionally be configured to require authentication.

## Requirements

* PHP >= 5.6

## Installation

```
composer require jalle19/react-http-static
```

## Usage

This example will serve the directory `/var/www` on port 8080. Additionally it will require clients to authenticate 
using the username `admin` and the password `admin`.

```php
<?php

require_once('vendor/autoload.php');

use Jalle19\ReactHttpStatic\Authentication\Handler\Basic as BasicAuthenticationHandler;
use React\Http\Server as HttpServer;
use React\Socket\Server as Socket;

// Create an event loop. You'll probably want to use your own application's loop instead of creating a new one.
$eventLoop = \React\EventLoop\Factory::create();

// Create the socket to use
$socket = new Socket($eventLoop);
$socket->listen(8080);

// Create the server itself
$httpServer      = new HttpServer($socket);
$staticWebServer = new Jalle19\ReactHttpStatic\StaticWebServer($httpServer, __DIR__ . '/tests/webroot');

// Apply our authentication handler
$handlerCallback = function ($username, $password) {
    return $username === 'admin' && $password === 'admin';
};

$staticWebServer->setAuthenticationHandler(new BasicAuthenticationHandler('our fancy realm', $handlerCallback));

// Start the loop
$eventLoop->run();
```

### Index handling

Requests for the root path (`/`) are mapped to `index.htm` or `index.html` by default, whichever is found first. You 
can override this behavior like this:

```php
$staticWebserver->setIndexFiles([
	'foo.html',
	'bar.html',
]);
```

### Authentication

You can write your own authentication handlers by implementing `Jalle19\ReactHttpStatic\Authentication\Handler\HandlerInterface`.

### Logging requests

You can log requests by passing a PSR-3 compatible logger to the server's constructor or by calling `setLogger()`. 

## License

MIT
