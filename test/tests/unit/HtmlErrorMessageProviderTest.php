<?php

namespace WellRESTed\HttpExceptions\Test\Unit;

use Prophecy\Argument;
use WellRESTed\ErrorHandling\HtmlErrorMessageProvider;

/**
 * @coversDefaultClass WellRESTed\ErrorHandling\HtmlErrorMessageProvider
 * @uses WellRESTed\ErrorHandling\ErrorMessageProvider
 */
class HtmlErrorMessageProviderTest extends \PHPUnit_Framework_TestCase
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
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
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
     * @covers ::getResponse
     */
    public function testSetsBodyBasedOnStatusAndReasonPhrase()
    {
        $statusCode = 404;
        $reasonPhrase = "Not Found";

        $this->response->getStatusCode()->willReturn($statusCode);
        $this->response->getReasonPhrase()->willReturn($reasonPhrase);

        $provider = new HtmlErrorMessageProvider();
        $provider($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->response->withBody(Argument::that(function ($body) use ($statusCode, $reasonPhrase) {
            return (string) $body === "<h1>$statusCode $reasonPhrase</h1>";
        }))->shouldHaveBeenCalled();
    }

    /**
     * @covers ::getResponse
     */
    public function testSetsContentTypeToHtml()
    {
        $provider = new HtmlErrorMessageProvider();
        $provider($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->response->withHeader("Content-type", "text/html")->shouldHaveBeenCalled();
    }
}
