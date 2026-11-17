<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Http;

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Context as SecurityContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Thorwarth\NeosMcp\Protocol\JsonRpcHandler;
use Thorwarth\NeosMcp\Service\McpLogger;

/**
 * MCP-Endpoint (Streamable HTTP, stateless): POST <endpointPath> mit JSON-RPC-Body.
 *
 * - Authentifizierung per "Authorization: Bearer <token>" (Tokens in den Settings)
 * - Origin-Prüfung gegen allowedOrigins (DNS-Rebinding-Schutz)
 * - GET/DELETE -> 405 (kein SSE-Stream, keine Sessions)
 */
class McpEndpointMiddleware implements MiddlewareInterface
{
    #[Flow\InjectConfiguration(package: 'Thorwarth.NeosMcp')]
    protected array $settings = [];

    #[Flow\Inject]
    protected JsonRpcHandler $jsonRpcHandler;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    #[Flow\Inject]
    protected McpLogger $logger;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $endpointPath = '/' . trim((string)($this->settings['endpointPath'] ?? '/api/mcp'), '/');
        if ($endpointPath === '/' || rtrim($request->getUri()->getPath(), '/') !== $endpointPath) {
            return $next->handle($request);
        }

        $method = strtoupper($request->getMethod());
        if ($method === 'OPTIONS') {
            return new Response(204, ['Allow' => 'POST, OPTIONS']);
        }
        if ($method !== 'POST') {
            return $this->jsonResponse(405, JsonRpcHandler::error(null, -32600, 'Method Not Allowed: Der MCP-Endpoint akzeptiert nur POST (kein SSE-Stream).'), ['Allow' => 'POST, OPTIONS']);
        }

        $clientIp = (string)($request->getAttribute(ServerRequestAttributes::CLIENT_IP) ?? ($request->getServerParams()['REMOTE_ADDR'] ?? ''));

        $origin = trim($request->getHeaderLine('Origin'));
        if ($origin !== '' && !$this->isOriginAllowed($origin)) {
            $this->logger->warning('Abgelehnt: Origin nicht erlaubt', ['ip' => $clientIp, 'origin' => $origin]);
            return $this->jsonResponse(403, JsonRpcHandler::error(null, -32600, 'Forbidden: Origin nicht erlaubt.'));
        }

        $tokens = $this->configuredTokens();
        if ($tokens === []) {
            $this->logger->warning('Abgelehnt: kein Token konfiguriert', ['ip' => $clientIp]);
            return $this->jsonResponse(503, JsonRpcHandler::error(null, -32000, 'MCP-Endpoint ist nicht konfiguriert (Thorwarth.NeosMcp.auth.tokens).'));
        }
        $client = $this->authenticate($request, $tokens);
        if ($client === null) {
            $this->logger->warning('Abgelehnt: ungültiges oder fehlendes Token', ['ip' => $clientIp]);
            return $this->jsonResponse(401, JsonRpcHandler::error(null, -32000, 'Unauthorized: gültiges Bearer-Token erforderlich.'), ['WWW-Authenticate' => 'Bearer realm="Neos MCP"']);
        }

        $protocolVersion = trim($request->getHeaderLine('MCP-Protocol-Version'));
        if ($protocolVersion !== '' && !JsonRpcHandler::supportsProtocolVersion($protocolVersion)) {
            return $this->jsonResponse(400, JsonRpcHandler::error(null, -32600, sprintf('Unsupported MCP-Protocol-Version "%s". Unterstützt: %s', $protocolVersion, implode(', ', JsonRpcHandler::PROTOCOL_VERSIONS))));
        }

        $body = (string)$request->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $this->jsonResponse(400, JsonRpcHandler::error(null, -32700, 'Parse error: Body muss gültiges JSON (JSON-RPC 2.0) sein.'));
        }

        // Security-Context mit dem aktuellen Request verknüpfen, damit Neos-Dienste (z. B. UserService) ihn
        // initialisieren können; Autorisierungsprüfungen des Content Repository sind für den Aufruf deaktiviert,
        // die Zugriffskontrolle erfolgt über das Token.
        if (!$this->securityContext->canBeInitialized()) {
            $this->securityContext->setRequest(ActionRequest::fromHttpRequest($request));
        }
        $response = $this->securityContext->withoutAuthorizationChecks(
            fn () => $this->jsonRpcHandler->handle($decoded, $client . '@' . $clientIp)
        );

        if ($response === null) {
            return new Response(202, ['Cache-Control' => 'no-store']);
        }
        return $this->jsonResponse(200, $response);
    }

    /**
     * @return array<string, string> label => token
     */
    private function configuredTokens(): array
    {
        $configured = $this->settings['auth']['tokens'] ?? [];
        if (is_string($configured)) {
            $configured = [$configured];
        }
        if (!is_array($configured)) {
            return [];
        }
        if (isset($this->settings['auth']['token']) && is_string($this->settings['auth']['token'])) {
            $configured['default'] = $this->settings['auth']['token'];
        }
        $tokens = [];
        foreach ($configured as $label => $token) {
            if (!is_string($token)) {
                continue;
            }
            $token = trim($token);
            if (strlen($token) < 20) {
                continue;
            }
            $tokens[is_int($label) ? 'token-' . $label : (string)$label] = $token;
        }
        return $tokens;
    }

    /**
     * @param array<string, string> $tokens
     * @return string|null Label des passenden Tokens
     */
    private function authenticate(ServerRequestInterface $request, array $tokens): ?string
    {
        $provided = $this->extractToken($request);
        if ($provided === null) {
            return null;
        }
        foreach ($tokens as $label => $token) {
            if (hash_equals($token, $provided)) {
                return $label;
            }
        }
        return null;
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        $serverParams = $request->getServerParams();
        $candidates = [
            $request->getHeaderLine('Authorization'),
            (string)($serverParams['HTTP_AUTHORIZATION'] ?? ''),
            (string)($serverParams['REDIRECT_HTTP_AUTHORIZATION'] ?? ''),
        ];
        foreach ($candidates as $candidate) {
            if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $candidate, $matches) === 1) {
                return $matches[1];
            }
        }
        $fallbackHeader = trim((string)($this->settings['auth']['fallbackHeader'] ?? ''));
        if ($fallbackHeader !== '' && $request->hasHeader($fallbackHeader)) {
            $value = trim($request->getHeaderLine($fallbackHeader));
            return $value !== '' ? $value : null;
        }
        return null;
    }

    private function isOriginAllowed(string $origin): bool
    {
        $normalizedOrigin = strtolower(rtrim($origin, '/'));
        foreach ((array)($this->settings['allowedOrigins'] ?? []) as $allowed) {
            if (is_string($allowed) && strtolower(rtrim(trim($allowed), '/')) === $normalizedOrigin) {
                return true;
            }
        }
        return false;
    }

    private function jsonResponse(int $status, array $data, array $headers = []): ResponseInterface
    {
        $body = json_encode($data, JsonRpcHandler::JSON_FLAGS);
        if ($body === false) {
            $body = '{"jsonrpc":"2.0","id":null,"error":{"code":-32603,"message":"Antwort konnte nicht serialisiert werden."}}';
            $status = 500;
        }
        return new Response($status, $headers + [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
        ], $body);
    }
}
