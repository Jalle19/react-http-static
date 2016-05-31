<?php

namespace Jalle19\ReactHttpStatic\Authentication\Handler;

use React\Http\Request;
use React\Http\Response;

/**
 * Class Basic
 * @package Jalle19\ReactHttpStatic\Authentication\Handler
 * @copyright Copyright &copy; Sam Stenvall 2016-
 * @license   @license https://opensource.org/licenses/MIT
 */
class Basic implements HandlerInterface
{

    /**
     * @var string
     */
    private $realm;

    /**
     * @var \Closure
     */
    private $implementation;


    /**
     * @param \Closure $implementation
     * @param string   $realm
     */
    public function __construct($realm, $implementation)
    {
        $this->realm          = $realm;
        $this->implementation = $implementation;
    }


    /**
     * @inheritdoc
     */
    public function handle(Request $request)
    {
        $headers = $request->getHeaders();

        if (array_key_exists('Authorization', $headers)) {
            $authorization = $headers['Authorization'];

            if (substr($authorization, 0, strlen('Basic ')) !== 'Basic ') {
                return false;
            }

            $authentication = base64_decode(substr($authorization, strlen('Basic ')));
            $parts          = explode(':', $authentication);

            if (count($parts) !== 2) {
                return false;
            }

            return $this->implementation->__invoke($parts[0], $parts[1]);
        }

        return false;
    }


    /**
     * @inheritdoc
     */
    public function requireAuthentication(Response $response)
    {
        $response->writeHead(401, [
            'WWW-Authenticate' => 'Basic realm="' . $this->realm . '"',
        ]);

        $response->end();
    }

}
