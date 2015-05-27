<?php

namespace WellRESTed\ErrorHandling;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\MiddlewareInterface;

/**
 * Checks for a response with an error status code and no body and provides a
 * body appropriate for the status code.
 *
 * This middleware calls $next and updates the response returned.
 */
abstract class ErrorHandler implements MiddlewareInterface
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
                $response = $this->provideResponseForError($request, $response);
            }
        }
        return $response;
    }

    /**
     * Return an appropriate response based on the passed response status code.
     *
     * Implementations MUST NOT return a response with a different status code.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    abstract protected function provideResponseForError(ServerRequestInterface $request, ResponseInterface $response);
}
