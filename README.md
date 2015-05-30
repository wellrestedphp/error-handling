Error Handling
==============

Provides classes to facilitate error handling with WellRESTed.

**ErrorHandler** and its subclasses provide human-readable default
responses for responses with error status codes. The package includes concrete 
class that are ready to be dropped into you project as well as an abstract base
class for creating completely custom error responses.

**Catcher** wraps a sequence of middleware in a try/catch block to allow
recovery from exceptions. Catcher also provides response status codes for 
[HttpExceptions](https://github.com/wellrestedphp/http-exceptions).

Install
-------

Add this package to your composer.json 

```json
{
    "require": {
        "wellrested/wellrested": "^3.0",
        "wellrested/error-handling": "^1.0"
    }
}
```

Error Handler
-------------

`ErrorHandler` and its concrete subclasses provide human-readable responses
for responses with status codes >= 400. To use, add an `ErrorHandler` subclass
to your `Server` **before** the middleware that may return an error response.

### Text- and HtmlErrorHandler

```php
// Create a server.
$server = new \WellRESTed\Server();

// Add an error handler BEFORE the router and anything else that you want to
// provide error responses for.
$server->add(new \WellRESTed\ErrorHandling\TextErrorHandler());

// Add a router or any other middleware.
// If any middleware added AFTER the error handler returns a response with a 
// status code >= 400, the error handler will provide a message body, headers,
// etc. to match.
$server->add($server->createRouter()
    ->register("GET", "/", 'MySite\RootEndpoint')
    /* ... register other routes ... */
    );
```

When the router fails to match a route (or middleware returns a response with
a `404` status code), the `TextErrorHandler` provides a plain/text response:

```
HTTP/1.1 404 Not Found
Server: nginx/1.4.6 (Ubuntu)
Date: Wed, 27 May 2015 11:47:09 GMT
Content-Type: text/plain; charset=utf-8
Content-Length: 13
Connection: keep-alive
X-Powered-By: PHP/5.5.9-1ubuntu4.9

404 Not Found
```

Or, if you're prefer HTML, use the `HtmlErrorHandler`.

```
HTTP/1.1 404 Not Found
Server: nginx/1.4.6 (Ubuntu)
Date: Wed, 27 May 2015 11:53:55 GMT
Content-Type: text/html; charset=utf-8
Content-Length: 22
Connection: keep-alive
X-Powered-By: PHP/5.5.9-1ubuntu4.9

<h1>404 Not Found</h1>
```

### Custom Error Handlers

To provide your own custom error responses, subclass 
`WellRESTed\ErrorHandling\ErrorHandler` and implement `provideResponseForError`.
This method expects a request and response as parameters and returns the updated
response. 

You do not need to check whether or not to handle the response; the 
`ErrorHandler` instance calls `provideResponseForError` only when:

- The middleware that follows it provides a response.
- That response has a status code >= 400.
- That response body is either not readable or has a size of 0.


Here's an example that provides a silly response for 404 errors, and normal
responses for all other errors.

```php
/**
 * Provides a silly response for 404 errors.
 */
class CustomErrorHandler extends \WellRESTed\ErrorHandling\ErrorHandler
{
    protected function provideResponseForError(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        // Set the contet-type.
        $response = $response->withHeader("Content-type", "text/html");
        // Check if this is a Not Found error.
        if ($response->getStatusCode() === 404) {
            // Set a silly message body.
            $message = "<h1>It's not here!</h1><p>I must have eaten it.</p>";
        } else {
            // Set the message body to the status code and reaspon phrase.
            $message = "<h1>" . $response->getStatusCode() . " " . $response->getReasonPhrase() . "</h1>";
        }
        // Return the response.
        return $response->withBody(new Stream($message));
    }
}
```

Catcher
-------

A Catcher instance wraps a sequence of middleware in a try/catch block to allow 
for recovery from exceptions.

Catcher may be used "out-of-the-box" to provide response status codes for 
[HttpExceptions](https://github.com/wellrestedphp/http-exceptions), or you can 
subclass it to respond to other Exceptions.

### Basic Usage

Add a `Catcher` to your server and add middleware to it using the `Catcher::add`
method. Be sure to pass a reference to a dispatcher (an instance implementing 
`WellRESTed\Dispatching\DispatcherInterface`) to the constructor. The `Server`
provides access to the dispatcher it uses via `Server::getDispatcher`.

```php
// Create a server.
$server = new \WellRESTed\Server();

// Create a catcher, providing the dispatcher used by the server.
$catcher = new \WellRESTed\ErrorHandling\Catcher($server->getDispatcher());

// Add the catcher to the server.
$server->add($catcher);

// Add middleware to the catcher. Any HttpException thrown by middleware
// contained in the catcher will be converted to a response with an 
// appropriate status code.
$catcher->add($server->createRouter()
    ->register("GET", "/", 'MySite\RootEndpoint')
    /* ... add other routes ... */
    );
```

When any middleware contained in the `Catcher` throws an `HttpException`, the
catcher will return a response with a status code matching the `HttpException`'s
code. For example, throwing a `ConflictException` results in a `409 Conflict`
status.

**NOTE:** When middleware throws an exception, the execution jumps directly to
the `Catcher` and does not work its way back down through the chain of 
middleware. This may be useful if you want to provide an immediate out for your
response.

### Custom Exceptions

To catch exceptions other than `HttpException`s, subclass `Catcher` and
implement the `getResponseForException` method.

```php
class CustomCatcher extends Catcher
{
    /**
     * @param Exception $e The exception caught by the instance
     * @param ServerRequestInterface $request The request passed into the
     *     instance's __invoke
     * @param ResponseInterface $response The response passed into the
     *     instance's __invoke
     * @return ResponseInterface|null $response with an updated status code
     */
    protected function getResponseForException(
        Exception $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        if ($e instanceof \Some\Custom\Exception) {
            // Do some logging or something...
            // Return a response.
            $response = $response
                ->withStatus(500)
                ->withBody(new \WellRESTed\Message\Stream(
                    "That's not supposed to happen."));
            return $response;
        }
        // Optionally, return null to re-throw exceptions you don't want the
        // instance to handle.
        return null;
    }
}
```

Catching and Handling
---------------------

`Catcher` and `ErrorHandler` make a great team. Add an `ErrorHandler` in front
of your `Catcher` to provide default responses for exceptions.

```php
// Create a server.
$server = new \WellRESTed\Server();

// Create a catcher, providing the dispatcher used by the server.
$catcher = new \WellRESTed\ErrorHandling\Catcher($server->getDispatcher());

// Add an error handler near the front of the server.
$server->add(new WellRESTed\ErrorHandling\TextErrorHandler());

// Add the catcher to the server AFTER the error handler. This allows the 
// error handler to react to the response returned by the catcher.
$server->add($catcher);

// Add middleware to the catcher. Any HttpException thrown by middleware
// contained in the catcher will be converted to a response with an
// appropriate status code. 
// 
// The catcher will return that response to the error handler, which will
// return a human-readable version of that response.
$catcher->add($server->createRouter()
    ->register("GET", "/", 'MySite\RootEndpoint')
    /* ... add other routes ... */
    );
```

Copyright and License
---------------------
Copyright © 2015 by PJ Dietz
Licensed under the [MIT license](http://opensource.org/licenses/MIT)
