<?php

namespace Yiisoft\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;

class SubFolderMiddleware implements MiddlewareInterface
{
    public const PARAM_URI_PREFIX = 'uri-prefix';
    public $prefix;
    public $prefixParamName = self::PARAM_URI_PREFIX;
    protected $uriGenerator;

    public function __construct(UrlGeneratorInterface $uriGenerator)
    {
        $this->uriGenerator = $uriGenerator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();

        if ($this->prefix === null) {
            // check if project in subfolder
            $scriptName = $request->getServerParams()['SCRIPT_NAME'];
            if (strpos($scriptName, '/', 1) !== false) {
                $length = strrpos($scriptName, '/');
                $prefix = substr($scriptName, 0, $length);
                $path = $uri->getPath();
                if (substr($path, 0, $length) === $prefix) {
                    $this->prefix = $prefix;
                    $this->uriGenerator->setUriPrefix($prefix);
                    $request = $request->withAttribute($this->prefixParamName, $prefix)
                                       ->withUri($uri->withPath(substr($path, $length)));
                }
            }
        } elseif (strlen($this->prefix)) {
            if ($this->prefix[-1] === '/') {
                throw new \Exception('Dad URI prefix value');
            }
            $length = strlen($this->prefix);
            $path = $uri->getPath();
            if (substr($path, 0, $length) === $this->prefix) {
                $this->uriGenerator->setUriPrefix($this->prefix);
                $request = $request->withAttribute($this->prefixParamName, $this->prefix)
                                   ->withUri($uri->withPath(substr($path, $length)));
            } else {
                throw new \Exception('URI prefix does not match');
            }
        }

        return $handler->handle($request);
    }
}
