Error Handling
==============

Provides classes to facilitate error handling with WellRESTed.

**ErrorHandler** and its subclasses provide more human-readable default
responses for responses with error status codes. The package includes concrete 
class that are ready to be dropped in to you project as well as an abstract base
class for creating completely custom error responses.

**Catcher** wraps a sequence of middleware in a try/catch block to allow for
recovery from exceptions. Catcher also provides response status codes for 
thrown [HttpExceptions](https://github.com/wellrestedphp/http-exceptions).

Install
-------

Add the entries for Error Handling and its dependencies to your composer.json 

```json
{
    "require": {
        "wellrested/wellrested": "^3.0",
        "wellrested/http-exceptions": "^1.0",
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
$server = new \WellRESTed\Server();
$server->add(new WellRESTed\ErrorHandling\TextErrorHandler());
$server->add(/* ... Add router and other middleware AFTER ... */);
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

- It receives a response back from the middleware that follows it (i.e., it 
    calls `$next` and receives a response).
- The response has a status code >= 400
- The response body is either not readable or has a size of 0

```php
/**
 * Provide a silly response for 404 errors.
 */
class CustomErrorHandler extends \WellRESTed\ErrorHandling\ErrorHandler
{
    protected function provideResponseForError(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        $response = $response->withHeader("Content-type", "text/html");
        if ($response->getStatusCode() === 404) {
            $message = "<h1>It's not here!</h1><p>I must have eaten it.</p>";
        } else {
            $message = "<h1>" . $response->getStatusCode() . " " . $response->getReasonPhrase() . "</h1>";
        }
        return $response->withBody(new Stream($message));
    }
}
```
