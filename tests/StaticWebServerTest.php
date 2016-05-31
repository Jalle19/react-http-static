<?php

namespace Jalle19\ReactHttpStatic\Test;

use Jalle19\ReactHttpStatic\StaticWebServer;
use React\EventLoop\Factory;
use React\Http\Server as HttpServer;
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
            [[], '/bar/baz.css', $this->getWebroot() . '/bar/baz.css', 'text/css'],
            [[], '/foo.js', $this->getWebroot() . '/foo.js', 'application/javascript'],
            [[], '/plain.txt', $this->getWebroot() . '/plain.txt', 'text/plain'],
        ];
    }


    /**
     * @return string
     */
    private function getWebroot()
    {
        return realpath(__DIR__ . '/webroot');
    }

}
