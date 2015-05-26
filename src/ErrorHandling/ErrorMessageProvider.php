<?php

namespace WellRESTed\ErrorHandling;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Stream;
use WellRESTed\MiddlewareInterface;

/**
 * Checks for a response with an error status code and no body and provides a
 * body appropriate for the status code.
 */
class ErrorMessageProvider implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response = $next($request, $response);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            $body = $response->getBody();
            if (!$body->isReadable() || $body->getSize() === 0) {
                $response = $this->getResponse($request, $response);
            }
        }
        return $response;
    }

    protected function getResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $message = $response->getStatusCode() . " " . $response->getReasonPhrase();
        return $response->withHeader("Content-type", "text/plain")
            ->withBody(new Stream($message));
    }
}
