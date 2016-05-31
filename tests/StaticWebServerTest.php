<?php

namespace Jalle19\ReactHttpStatic\Test;

use Jalle19\ReactHttpStatic\Authentication\Handler\HandlerInterface;
use Jalle19\ReactHttpStatic\StaticWebServer;
use React\EventLoop\Factory;
use React\Http\Request;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Socket\ConnectionInterface;
use React\Socket\Server as SocketServer;

/**
 * Class StaticWebServerTest
 * @package   Jalle19\ReactHttpStatic\Test
 * @copyright Copyright &copy; Sam Stenvall 2016-
 * @license   @license https://opensource.org/licenses/MIT
 */
class StaticWebServerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var StaticWebServer
     */
    private $staticWebServer;


    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $httpServer            = new HttpServer(new SocketServer(Factory::create()));
        $this->staticWebServer = new StaticWebServer($httpServer, $this->getWebroot());
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidWebroot()
    {
        $httpServer            = new HttpServer(new SocketServer(Factory::create()));
        $this->staticWebServer = new StaticWebServer($httpServer, 'completely invalid');
    }


    /**
     *
     */
    public function testHandleRequestWithAuthentication()
    {
        // Test that authentication handlers are called properly

        /* @var \PHPUnit_Framework_MockObject_MockObject|HandlerInterface $authenticationHandler */
        $authenticationHandler = $this->getMockBuilder(HandlerInterface::class)
                                      ->setMethods(['handle', 'requireAuthentication'])
                                      ->getMock();

        $authenticationHandler->expects($this->once())->method('handle')->willReturn(false);
        $authenticationHandler->expects($this->once())->method('requireAuthentication');

        $this->staticWebServer->setAuthenticationHandler($authenticationHandler);
        $this->staticWebServer->handleRequest(new Request('GET', '/'), new Response($this->getMockedConnection()));
    }


    /**
     *
     */
    public function testHandleRequestUnreadableFile()
    {
        // Create an unreadable file
        $this->staticWebServer->setWebroot(sys_get_temp_dir());
        $filename = 'unreadable';
        $filePath = $this->staticWebServer->getWebroot() . '/' . $filename;
        @unlink($filePath);
        touch($filePath);
        chmod($filePath, 000);

        // Mock a response
        /* @var \PHPUnit_Framework_MockObject_MockObject|Response $response */
        $response = $this->getMockBuilder(Response::class)
                         ->setConstructorArgs([$this->getMockedConnection()])
                         ->setMethods(['writeHead', 'end'])
                         ->getMock();

        $response->expects($this->once())->method('writeHead')->with(403, ['Content-Type' => 'text/plain']);
        $this->staticWebServer->handleRequest(new Request('GET', '/' . $filename), $response);
    }


    /**
     *
     */
    public function testHandleRequestNonExistingFile()
    {
        $this->staticWebServer->setWebroot(sys_get_temp_dir());
        $filename = 'something that probably does not exist';

        // Mock a response
        /* @var \PHPUnit_Framework_MockObject_MockObject|Response $response */
        $response = $this->getMockBuilder(Response::class)
                         ->setConstructorArgs([$this->getMockedConnection()])
                         ->setMethods(['writeHead', 'end'])
                         ->getMock();

        $response->expects($this->once())->method('writeHead')->with(404, ['Content-Type' => 'text/plain']);
        $this->staticWebServer->handleRequest(new Request('GET', '/' . $filename), $response);
    }


    /**
     *
     */
    public function testHandleRequest()
    {
        $this->staticWebServer->setWebroot(__DIR__ . '/../resources/webroot');

        // Mock a response
        /* @var \PHPUnit_Framework_MockObject_MockObject|Response $response */
        $response = $this->getMockBuilder(Response::class)
                         ->setConstructorArgs([$this->getMockedConnection()])
                         ->setMethods(['writeHead', 'end'])
                         ->getMock();

        $response->expects($this->once())->method('writeHead')->with(200, ['Content-Type' => 'text/html']);
        $this->staticWebServer->handleRequest(new Request('GET', '/index.htm'), $response);
    }


    /**
     * @dataProvider dataProvider
     *
     * @param array $indexFiles
     * @param       $requestPath
     * @param       $resolvedPath
     */
    public function testResolvePath(array $indexFiles, $requestPath, $resolvedPath)
    {
        if (!empty($indexFiles)) {
            $this->staticWebServer->setIndexFiles($indexFiles);
        }

        $this->assertEquals($resolvedPath, $this->staticWebServer->resolvePath($requestPath));
    }


    /**
     * @dataProvider dataProvider
     *
     * @param array $indexFiles
     * @param       $requestPath
     * @param       $resolvedPath
     * @param       $contentType
     */
    public function testGetContentType(array $indexFiles, $requestPath, $resolvedPath, $contentType)
    {
        $this->assertEquals($contentType, $this->staticWebServer->getContentType($resolvedPath));
    }


    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [['foo.htm', 'foo.html'], '/', $this->getWebroot() . '/foo.html', 'text/html'],
            [['index.htm', 'index.html'], '/', $this->getWebroot() . '/index.htm', 'text/html'],
            [['super invalid'], '/', false, 'text/plain'],
            [[], '/bar/baz.css', $this->getWebroot() . '/bar/baz.css', 'text/css'],
            [[], '/foo.js', $this->getWebroot() . '/foo.js', 'application/javascript'],
            [[], '/plain.txt', $this->getWebroot() . '/plain.txt', 'text/plain'],
            [[], '/noextension', $this->getWebroot() . '/noextension', 'text/plain'],
        ];
    }


    /**
     * @return string
     */
    private function getWebroot()
    {
        return realpath(__DIR__ . '/../resources/webroot');
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConnectionInterface
     */
    private function getMockedConnection()
    {
        return $this->getMock('React\Socket\ConnectionInterface');
    }

}
