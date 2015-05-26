<?php

namespace WellRESTed\ErrorHandling;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\DispatchStack;
use WellRESTed\HttpExceptions\HttpException;

class Catcher extends DispatchStack
{
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

    protected function getResponseForHttpException(
        Exception $e,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ) {
        return $response->withStatus($e->getCode());
    }

    protected function getResponseForException(
        Exception $e,
        ServerRequestInterface $request,
        ResponseInterface $response,
        $next
    ) {
        return null;
    }
}
