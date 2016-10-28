<?php

namespace Jalle19\ReactHttpStatic;

use Dflydev\ApacheMimeTypes\Parser as ContentTypeParser;
use Dflydev\ApacheMimeTypes\PhpRepository;
use Jalle19\ReactHttpStatic\Authentication\Handler\HandlerInterface;
use Psr\Log\LoggerInterface;
use React\Http\Server;
use React\Http\Request;
use React\Http\Response;

/**
 * Class StaticWebServer
 * @package   Jalle19\ReactHttpStatic
 * @copyright Copyright &copy; Sam Stenvall 2016-
 * @license   @license https://opensource.org/licenses/MIT
 */
class StaticWebServer
{

    /**
     * @var Server
     */
    private $httpServer;

    /**
     * @var string the absolute path to the directory where files are served from
     */
    private $webroot;

    /**
     * @var HandlerInterface
     */
    private $authenticationHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContentTypeParser
     */
    private $contentTypeParser;

    /**
     * @var array
     */
    private $indexFiles = [
        'index.htm',
        'index.html',
    ];


    /**
     * StaticWebServer constructor.
     *
     * @param Server               $httpServer
     * @param string               $webroot
     * @param LoggerInterface|null $logger
     */
    public function __construct(Server $httpServer, $webroot, LoggerInterface $logger = null)
    {
        if (!file_exists($webroot)) {
            throw new \InvalidArgumentException('The specified webroot path does not exist');
        }

        $this->httpServer = $httpServer;
        $this->webroot    = $webroot;
        $this->logger     = $logger;

        // Attach the request handler
        $this->httpServer->on('request', [$this, 'handleRequest']);

        // Configure the content type parser
        $this->contentTypeParser = new ContentTypeParser();
    }


    /**
     * @return string
     */
    public function getWebroot()
    {
        return $this->webroot;
    }


    /**
     * @param string $webroot
     *
     * @return StaticWebServer
     */
    public function setWebroot($webroot)
    {
        $this->webroot = $webroot;

        return $this;
    }


    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }


    /**
     * @param LoggerInterface $logger
     *
     * @return StaticWebServer
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }


    /**
     * @return array
     */
    public function getIndexFiles()
    {
        return $this->indexFiles;
    }


    /**
     * @param array $indexFiles
     *
     * @return StaticWebServer
     */
    public function setIndexFiles($indexFiles)
    {
        $this->indexFiles = $indexFiles;

        return $this;
    }


    /**
     * @param HandlerInterface|null $authenticationHandler
     *
     * @return StaticWebServer
     */
    public function setAuthenticationHandler($authenticationHandler)
    {
        $this->authenticationHandler = $authenticationHandler;

        return $this;
    }


    /**
     * @param Request  $request
     * @param Response $response
     */
    public function handleRequest(Request $request, Response $response)
    {
        $requestPath = $request->getPath();
        $filePath    = $this->resolvePath($requestPath);

        if ($this->logger !== null) {
            $this->logger->debug('Got HTTP request (request path: {requestPath}, resolved path: {resolvedPath})', [
                'requestPath'  => $requestPath,
                'resolvedPath' => $filePath,
            ]);
        }

        if ($this->authenticationHandler instanceof HandlerInterface) {
            if (!$this->authenticationHandler->handle($request)) {
                if ($this->logger !== null) {
                    $this->logger->warning('Client failed authentication');
                }

                $this->authenticationHandler->requireAuthentication($response);

                return;
            }
        }

        if (file_exists($filePath)) {
            if (is_readable($filePath)) {
                $response->writeHead(200, [
                    'Content-Type' => $this->getContentType($filePath),
                ]);

                $response->end(file_get_contents($filePath));
            } else {
                if ($this->logger !== null) {
                    $this->logger->error('HTTP request failed, file unreadable ({filePath})', [
                        'filePath' => $filePath,
                    ]);    
                }
                
                $response->writeHead(403, ['Content-Type' => 'text/plain']);
                $response->end("Forbidden\n");
            }
        } else {
            if ($this->logger !== null) {
                $this->logger->error('HTTP request failed, file not found ({filePath})', [
                    'filePath' => $filePath,
                ]);    
            }
            
            $response->writeHead(404, ['Content-Type' => 'text/plain']);
            $response->end("Not found\n");
        }
    }


    /**
     * @param string $requestPath
     *
     * @return bool|string
     */
    public function resolvePath($requestPath)
    {
        $filePath = $this->webroot . $requestPath;

        if ($requestPath === '/') {
            foreach ($this->indexFiles as $indexFile) {
                $indexPath = $filePath . $indexFile;

                if (file_exists($indexPath)) {
                    return $indexPath;
                }
            }

            return false;
        }

        return $filePath;
    }


    /**
     * @param string $filePath
     *
     * @return string
     */
    public function getContentType($filePath)
    {
        $pathInfo = pathinfo($filePath);

        if (!isset($pathInfo['extension'])) {
            $extension = '';
        } else {
            $extension = $pathInfo['extension'];
        }

        $repository = new PhpRepository();
        $type = $repository->findType($extension);

        if ($type === null) {
            $type = 'text/plain';
        }

        return $type;
    }

}
