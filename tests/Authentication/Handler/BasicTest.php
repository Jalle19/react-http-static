<?php

namespace Jalle19\ReactHttpStatic\Test\Authentication\Handler;

use Jalle19\ReactHttpStatic\Authentication\Handler\Basic as BasicAuthenticationHandler;
use React\Http\Request;
use React\Http\Response;

/**
 * Class BasicTest
 * @package   Jalle19\ReactHttpStatic\Test\Authentication\Handler
 * @copyright Copyright &copy; Sam Stenvall 2016-
 * @license   @license https://opensource.org/licenses/MIT
 */
class BasicTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     */
    public function testHandle()
    {
        $handler = new BasicAuthenticationHandler('realm', function ($username, $password) {
            return $username === 'user' && $password === 'password';
        });

        // Request without Authorization header
        $request = new Request('GET', '/');
        $this->assertFalse($handler->handle($request));

        // Non-basic authorization
        $request = new Request('GET', '/', [], '1.1', ['Authorization' => 'Unbasic foo']);
        $this->assertFalse($handler->handle($request));

        // Request with invalid Base64 encoded value
        $request = new Request('GET', '/', [], '1.1', ['Authorization' => 'Basic blabla']);
        $this->assertFalse($handler->handle($request));

        // Seemingly valid request, but wrong credentials
        $request = new Request('GET', '/', [], '1.1', ['Authorization' => 'Basic foo:bar']);
        $this->assertFalse($handler->handle($request));

        // All good request
        $request = new Request('GET', '/', [], '1.1', ['Authorization' => 'Basic ' . base64_encode('user:password')]);
        $this->assertTrue($handler->handle($request));
    }


    /**
     *
     */
    public function testRequireAuthentication()
    {
        $realm = 'realm';

        $conn = $this->getMock('React\Socket\ConnectionInterface');

        /* @var \PHPUnit_Framework_MockObject_MockObject|Response $response */
        $response = $this->getMockBuilder(Response::class)
                         ->setConstructorArgs([$conn])
                         ->setMethods(['writeHead', 'end'])
                         ->getMock();

        $response->expects($this->once())->method('writeHead')->with(401, [
            'WWW-Authenticate' => 'Basic realm="' . $realm . '"',
        ]);

        $response->expects($this->once())->method('end');

        $handler = new BasicAuthenticationHandler($realm, function () {

        });

        $handler->requireAuthentication($response);
    }

}
