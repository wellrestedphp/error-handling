<?php

namespace WellRESTed\ErrorHandling;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Stream;

class TextErrorHandler extends ErrorHandler
{
    protected function provideResponseForError(ServerRequestInterface $request, ResponseInterface $response)
    {
        $message = $response->getStatusCode() . " " . $response->getReasonPhrase();
        return $response->withHeader("Content-type", "text/plain")
            ->withBody(new Stream($message));
    }
}
