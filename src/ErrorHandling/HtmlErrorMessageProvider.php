<?php

namespace WellRESTed\ErrorHandling;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Stream;

class HtmlErrorMessageProvider extends ErrorMessageProvider
{
    protected function getResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $message = "<h1>" . $response->getStatusCode() . " " . $response->getReasonPhrase() . "</h1>";
        return $response->withHeader("Content-type", "text/html")
            ->withBody(new Stream($message));
    }
}
