<?php

namespace Jalle19\ReactHttpStatic\Authentication\Handler;

use React\Http\Request;
use React\Http\Response;

/**
 * Interface HandlerInterface
 * @package Jalle19\ReactHttpStatic\Authentication\Handler
 * @copyright Copyright &copy; Sam Stenvall 2016-
 * @license   @license https://opensource.org/licenses/MIT
 */
interface HandlerInterface
{

    /**
     * @param Request $request
     *
     * @return boolean
     */
    public function handle(Request $request);


    /**
     * @param Response $response
     */
    public function requireAuthentication(Response $response);

}
