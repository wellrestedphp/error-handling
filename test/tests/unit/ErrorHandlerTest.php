<?php

namespace WellRESTed\HttpExceptions\Test\Unit;

use Prophecy\Argument;

/**
 * @covers WellRESTed\ErrorHandling\ErrorHandler
 * @uses WellRESTed\ErrorHandling\ErrorHandler
 */
class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $body;
    private $request;
    private $response;
    private $next;

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
        $this->next = function ($request, $response) {
            return $response;
        };
    }

    public function testCallsNextBeforeInspectingResponse()
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

        $updatedResponse = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $handler = $this->getMockForAbstractClass('WellRESTed\ErrorHandling\ErrorHandler');
        $handler->expects($this->any())
            ->method("provideResponseForError")
            ->will($this->returnValue($updatedResponse->reveal()));
        $response = $handler->__invoke($this->request->reveal(), $this->response->reveal(), $next);
        $this->assertSame($response, $updatedResponse->reveal());
    }

    public function testUpdatesResponseWithErrorStatusCode()
    {
        $updatedResponse = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $handler = $this->getMockForAbstractClass('WellRESTed\ErrorHandling\ErrorHandler');
        $handler->expects($this->any())
            ->method("provideResponseForError")
            ->will($this->returnValue($updatedResponse->reveal()));
        $response = $handler->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertSame($response, $updatedResponse->reveal());
    }

    public function testDoesNotUpdateResponseWithSuccessStatusCode()
    {
        $this->response->getStatusCode()->willReturn(304);
        $this->response->getReasonPhrase()->willReturn("Not Modified");

        $updatedResponse = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $handler = $this->getMockForAbstractClass('WellRESTed\ErrorHandling\ErrorHandler');
        $handler->expects($this->any())
            ->method("provideResponseForError")
            ->will($this->returnValue($updatedResponse->reveal()));
        $response = $handler->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertSame($response, $this->response->reveal());
    }

    public function testDoesNotUpdateResponseWithNonEmptyBody()
    {
        $this->body->getSize()->willReturn(1);

        $updatedResponse = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $handler = $this->getMockForAbstractClass('WellRESTed\ErrorHandling\ErrorHandler');
        $handler->expects($this->any())
            ->method("provideResponseForError")
            ->will($this->returnValue($updatedResponse->reveal()));
        $response = $handler->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertSame($response, $this->response->reveal());
    }
}
