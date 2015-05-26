<?php

namespace WellRESTed\HttpExceptions\Test\Unit;

use Prophecy\Argument;
use WellRESTed\ErrorHandling\ErrorMessageProvider;

/**
 * @coversDefaultClass WellRESTed\ErrorHandling\ErrorMessageProvider
 * @uses WellRESTed\ErrorHandling\ErrorMessageProvider
 * @uses WellRESTed\Message\Stream
 */
class ErrorMessageProviderTest extends \PHPUnit_Framework_TestCase
{
    private $body;
    private $request;
    private $response;
    private $next;
    private $dispatcher;

    public function setUp()
    {
        parent::setUp();
        $this->body = $this->prophesize('Psr\Http\Message\StreamInterface');
        $this->body->isReadable()->willReturn(true);
        $this->body->getSize()->willReturn(0);
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->response->getStatusCode()->willReturn(400);
        $this->response->getReasonPhrase()->willReturn("Bad Request");
        $this->response->getBody()->willReturn($this->body->reveal());
        $this->response->withStatus(Argument::cetera())->willReturn($this->response->reveal());
        $this->response->withHeader(Argument::cetera())->willReturn($this->response->reveal());
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
        $this->next = function ($request, $response) {
            return $response;
        };
        $this->dispatcher = $this->prophesize('WellRESTed\Dispatching\DispatcherInterface');
        $this->dispatcher->dispatch(Argument::cetera())->will(function ($args) {
            list($middleware, $request, $response, $next) = $args;
            return $middleware($request, $response, $next);
        });
    }

    /**
     * @covers ::__invoke
     * @covers ::getResponse
     */
    public function testSetsBodyBasedOnStatusAndReasonPhrase()
    {
        $statusCode = 404;
        $reasonPhrase = "Not Found";

        $this->response->getStatusCode()->willReturn($statusCode);
        $this->response->getReasonPhrase()->willReturn($reasonPhrase);

        $errorMessageProvider = new ErrorMessageProvider();
        $errorMessageProvider($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->response->withBody(Argument::that(function ($body) use ($statusCode, $reasonPhrase) {
            return (string) $body === "$statusCode $reasonPhrase";
        }))->shouldHaveBeenCalled();
    }

    /**
     * @covers ::__invoke
     */
    public function testCallsNextBeforeInspectingResonse()
    {
        $statusCode = 404;
        $reasonPhrase = "Method Not Allowed";

        $next = function ($request, $response) use ($statusCode, $reasonPhrase) {
            $this->response->getStatusCode()->willReturn($statusCode);
            $this->response->getReasonPhrase()->willReturn($reasonPhrase);
            return $response;
        };
        $this->response->getStatusCode()->willReturn(200);
        $this->response->getReasonPhrase()->willReturn("OK");

        $errorMessageProvider = new ErrorMessageProvider();
        $errorMessageProvider($this->request->reveal(), $this->response->reveal(), $next);

        $this->response->withBody(Argument::that(function ($body) use ($statusCode, $reasonPhrase) {
            return (string) $body === "$statusCode $reasonPhrase";
        }))->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testSetsContentTypeToPlainText()
    {
        $provider = new ErrorMessageProvider();
        $provider($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->response->withHeader("Content-type", "text/plain")->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testDoesNotReplaceBodyWhenBodyIsAlreadySet()
    {
        $this->body->getSize()->willReturn(1);

        $errorMessageProvider = new ErrorMessageProvider();
        $errorMessageProvider($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->response->withBody(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testDoesNotReplaceBodyWhenStatusCodeIsNotAnErrorCode()
    {
        $this->response->getStatusCode()->willReturn(304);
        $this->response->getReasonPhrase()->willReturn("Not Modified");

        $errorMessageProvider = new ErrorMessageProvider();
        $errorMessageProvider($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->response->withBody(Argument::any())->shouldNotHaveBeenCalled();
    }
}
