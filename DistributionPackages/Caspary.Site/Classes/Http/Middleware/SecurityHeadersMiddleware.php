<?php
declare(strict_types=1);

namespace Caspary\Site\Http\Middleware;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to add security headers to all HTTP responses
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\InjectConfiguration(path="securityHeaders", package="Caspary.Site")
     * @var array|null
     */
    protected ?array $securityHeaders = null;

    /**
     * Process the request and add security headers to the response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $response = $next->handle($request);

        // Default security headers
        $defaultHeaders = [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self';"
        ];

        // Merge default headers with configuration
        $headers = array_merge($defaultHeaders, $this->securityHeaders ?? []);

        // Add each security header to the response
        foreach ($headers as $headerName => $headerValue) {
            if ($headerValue !== false && $headerValue !== null) {
                $response = $response->withAddedHeader($headerName, $headerValue);
            }
        }

        return $response;
    }
}