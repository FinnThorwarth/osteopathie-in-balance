<?php
declare(strict_types=1);

namespace Thorwarth\BaseSite\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Verhindert HTTP 406 für maschinenlesbare Fassungen, die über den Neos-NodeController gerendert werden
 * (llms.txt, <Seite>.md, robots.txt, sitemap.xml, /.well-known/ard.json).
 *
 * Der NodeController unterstützt per Content-Negotiation nur "text/html". Schickt ein Client einen
 * passenden Accept-Header ohne Wildcard – z. B. ein KI-Agent "Accept: text/markdown" für eine .md-Seite
 * oder ein Crawler "Accept: text/plain" für robots.txt –, bricht Flow mit "406 Not Acceptable" ab.
 * Für diese Pfade bestimmt die Endung das Format; der Accept-Header wird daher neutralisiert. Den
 * tatsächlichen Content-Type setzt die jeweilige Fusion-Antwort (Neos.Fusion:Http.Message).
 */
class MachineReadableAcceptMiddleware implements MiddlewareInterface
{
    private const PATH_PATTERN = '~(\.md|/llms\.txt|/robots\.txt|/sitemap\.xml|/\.well-known/(ard|ai-catalog)\.json)$~';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if (preg_match(self::PATH_PATTERN, $request->getUri()->getPath()) === 1) {
            $request = $request->withHeader('Accept', '*/*');
        }
        return $next->handle($request);
    }
}
