<?php

namespace WellRESTed\HttpExceptions\Test\Unit;

use Exception;
use Prophecy\Argument;
use WellRESTed\HttpExceptions\HttpException;
use WellRESTed\ErrorHandling\Catcher;

/**
 * @coversDefaultClass WellRESTed\ErrorHandling\Catcher
 */
class CatcherTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $next;
    private $dispatcher;

    public function setUp()
    {
        parent::setUp();
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
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
     * @covers ::getResponseForHttpException
     */
    public function testHttpExceptionSetsResponseStatusToExceptionCode()
    {
        $code = 404;
        $message = "404 Not Found";

        $catcher = new Catcher($this->dispatcher->reveal());
        $catcher->add(function ($request, $response, $next) use ($message, $code) {
            throw new HttpException($message, $code);
        });
        $catcher($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->response->withStatus($code)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::__invoke
     * @covers ::getResponseForException
     * @expectedException \Exception
     */
    public function testDoesNotCatchOtherExceptionsByDefault()
    {
        $catcher = new Catcher($this->dispatcher->reveal());
        $catcher->add(function ($request, $response, $next) {
            throw new \Exception("Rethrow me!");
        });
        $catcher($this->request->reveal(), $this->response->reveal(), $this->next);
    }

    /**
     * @coversNothing
     */
    public function testReturnResponseProvidedBySubclass()
    {
        $subclassResponse = $this->prophesize('Psr\Http\Message\ResponseInterface')->reveal();

        $catcher = $this->getMockBuilder('WellRESTed\ErrorHandling\Catcher')
            ->setConstructorArgs([$this->dispatcher->reveal()])
            ->setMethods(["getResponseForException"])
            ->getMock();
        $catcher->expects($this->any())
            ->method("getResponseForException")
            ->will($this->returnValue($subclassResponse));
        $catcher->add(function ($request, $response, $next) {
            throw new \Exception("Rethrow me!");
        });

        $response = $catcher->__invoke($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertSame($subclassResponse, $response);
    }
}
