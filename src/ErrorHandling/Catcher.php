<?php

namespace WellRESTed\ErrorHandling;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\DispatchStack;
use WellRESTed\HttpExceptions\HttpException;

class Catcher extends DispatchStack
{
    /**
     * Dispatch the contained middleware and return the returned response.
     *
     * When any middleware throws an exception, this instance catches the
     * exception and attempt to handle it gracefully.
     *
     * If the exception is an HttpException, the instances sends it to
     * getResponseForHttpException and returns the response.
     *
     * Otherwise, the instance pass the exception to getResponseForException
     * which may return a response, or may return null to indicate that the
     * instance should rethrow the exception.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws Exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        try {
            $response = parent::__invoke($request, $response, $next);
        } catch (HttpException $e) {
            $response = $this->getResponseForHttpException($e, $request, $response, $next);
        } catch (Exception $e) {
            $response = $this->getResponseForException($e, $request, $response, $next);
            if ($response === null) {
                throw $e;
            }
        }
        return $response;
    }

    /**
     * @param HttpException $e The exception caught by the instance
     * @param ServerRequestInterface $request The request passed into the
     *     instance's __invoke
     * @param ResponseInterface $response The response passed into the
     *     instance's __invoke
     * @return ResponseInterface $response with an updated status code
     */
    protected function getResponseForHttpException(
        HttpException $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        return $response->withStatus($e->getCode());
    }

    /**
     * @param Exception $e The exception caught by the instance
     * @param ServerRequestInterface $request The request passed into the
     *     instance's __invoke
     * @param ResponseInterface $response The response passed into the
     *     instance's __invoke
     * @return ResponseInterface|null $response with an updated status code.
     *     Return null to re-throw the exception.
     */
    protected function getResponseForException(
        Exception $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        return null;
    }
}
