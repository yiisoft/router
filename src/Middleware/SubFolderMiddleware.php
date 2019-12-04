<?php

namespace Yiisoft\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Exception\BadUriPrefixException;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * This middleware supports routing when the project is placed in a subfolder relative to webroot
 */
final class SubFolderMiddleware implements MiddlewareInterface
{
    /** @var null|string */
    public $prefix;
    /** @var UrlGeneratorInterface */
    protected $uriGenerator;

    public function __construct(UrlGeneratorInterface $uriGenerator)
    {
        $this->uriGenerator = $uriGenerator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($this->prefix === null) {
            // automatically check that the project is in a subfolder
            // and uri contain a prefix
            $scriptName = $request->getServerParams()['SCRIPT_NAME'];
            if (strpos($scriptName, '/', 1) !== false) {
                $length = strrpos($scriptName, '/');
                $prefix = substr($scriptName, 0, $length);
                if (strpos($path, $prefix) === 0) {
                    $this->prefix = $prefix;
                    $this->uriGenerator->setUriPrefix($prefix);
                    $request = $request->withUri($uri->withPath(substr($path, $length)));
                }
            }
        } elseif ($this->prefix !== '') {
            if ($this->prefix[-1] === '/') {
                throw new BadUriPrefixException('Wrong URI prefix value');
            }
            $length = strlen($this->prefix);
            if (strpos($path, $this->prefix) !== 0) {
                throw new BadUriPrefixException('URI prefix does not match');
            }
            $this->uriGenerator->setUriPrefix($this->prefix);
            $request = $request->withUri($uri->withPath(substr($path, $length)));
        }

        return $handler->handle($request);
    }
}
