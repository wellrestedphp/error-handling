<?php

namespace WellRESTed\ErrorHandling;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Stream;

class HtmlErrorHandler extends ErrorHandler
{
    protected function provideResponseForError(ServerRequestInterface $request, ResponseInterface $response)
    {
        $message = "<h1>" . $response->getStatusCode() . " " . $response->getReasonPhrase() . "</h1>";
        return $response->withHeader("Content-type", "text/html")
            ->withBody(new Stream($message));
    }
}
