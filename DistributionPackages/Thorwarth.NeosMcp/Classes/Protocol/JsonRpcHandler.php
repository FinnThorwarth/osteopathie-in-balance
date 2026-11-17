<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Protocol;

use Neos\Flow\Annotations as Flow;
use Thorwarth\NeosMcp\Service\McpLogger;
use Thorwarth\NeosMcp\Tools\ToolRegistry;

/**
 * MCP-Protokoll (JSON-RPC 2.0): initialize, ping, tools/list, tools/call.
 * Transportunabhängig – wird von der HTTP-Middleware und dem stdio-Kommando genutzt.
 *
 * @Flow\Scope("singleton")
 */
class JsonRpcHandler
{
    public const SERVER_NAME = 'neos-mcp';
    public const SERVER_VERSION = '1.0.0';

    /** Unterstützte Protokollversionen, neueste zuerst */
    public const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    public const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

    #[Flow\Inject]
    protected ToolRegistry $toolRegistry;

    #[Flow\Inject]
    protected McpLogger $logger;

    #[Flow\InjectConfiguration(package: 'Thorwarth.NeosMcp')]
    protected array $settings = [];

    public static function supportsProtocolVersion(string $version): bool
    {
        return in_array($version, self::PROTOCOL_VERSIONS, true);
    }

    /**
     * Verarbeitet eine einzelne Nachricht oder einen Batch.
     *
     * @return array<mixed>|null null = keine Antwort (Notification)
     */
    public function handle(array $message, string $client): ?array
    {
        if (array_is_list($message)) {
            if ($message === []) {
                return self::error(null, -32600, 'Invalid Request: leerer Batch');
            }
            $responses = [];
            foreach ($message as $single) {
                $response = is_array($single) ? $this->handleSingle($single, $client) : self::error(null, -32600, 'Invalid Request');
                if ($response !== null) {
                    $responses[] = $response;
                }
            }
            return $responses === [] ? null : $responses;
        }
        return $this->handleSingle($message, $client);
    }

    private function handleSingle(array $message, string $client): ?array
    {
        $id = $message['id'] ?? null;
        $hasId = array_key_exists('id', $message) && $message['id'] !== null;
        $method = $message['method'] ?? null;
        if (($message['jsonrpc'] ?? null) !== '2.0' || !is_string($method) || $method === '') {
            return self::error($id, -32600, 'Invalid Request');
        }
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        if (!$hasId) {
            // Notifications (notifications/initialized, notifications/cancelled, ...) erfordern keine Antwort
            return null;
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize($params, $client),
                'ping' => new \stdClass(),
                'tools/list' => ['tools' => $this->toolRegistry->getToolDefinitions()],
                'tools/call' => $this->callTool($params, $client),
                default => null,
            };
        } catch (InvalidParamsException $exception) {
            return self::error($id, -32602, $exception->getMessage());
        } catch (\Throwable $throwable) {
            $this->logger->error('Interner Fehler', ['client' => $client, 'method' => $method, 'error' => $throwable->getMessage()]);
            return self::error($id, -32603, 'Internal error: ' . $throwable->getMessage());
        }
        if ($result === null) {
            return self::error($id, -32601, 'Method not found: ' . $method);
        }
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function initialize(array $params, string $client): array
    {
        $requestedVersion = (string)($params['protocolVersion'] ?? '');
        $version = self::supportsProtocolVersion($requestedVersion) ? $requestedVersion : self::PROTOCOL_VERSIONS[0];
        $clientInfo = is_array($params['clientInfo'] ?? null) ? $params['clientInfo'] : [];
        $this->logger->info('initialize', [
            'client' => $client,
            'clientName' => $clientInfo['name'] ?? null,
            'clientVersion' => $clientInfo['version'] ?? null,
            'requestedProtocolVersion' => $requestedVersion,
            'protocolVersion' => $version,
        ]);
        $result = [
            'protocolVersion' => $version,
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => self::SERVER_NAME, 'title' => 'Neos CMS Content', 'version' => self::SERVER_VERSION],
        ];
        $instructions = trim((string)($this->settings['instructions'] ?? ''));
        if ($instructions !== '') {
            $result['instructions'] = $instructions;
        }
        return $result;
    }

    private function callTool(array $params, string $client): array
    {
        $name = $params['name'] ?? null;
        if (!is_string($name) || !$this->toolRegistry->hasTool($name)) {
            throw new InvalidParamsException(sprintf('Unknown tool: %s', is_string($name) ? $name : '(fehlt)'));
        }
        $arguments = $params['arguments'] ?? [];
        if (!is_array($arguments)) {
            throw new InvalidParamsException('Tool-Argumente müssen ein JSON-Objekt sein.');
        }

        $start = microtime(true);
        try {
            $result = $this->toolRegistry->call($name, $arguments);
        } catch (\Throwable $throwable) {
            $this->logger->warning('tools/call fehlgeschlagen', [
                'client' => $client,
                'tool' => $name,
                'arguments' => $arguments,
                'error' => $throwable->getMessage(),
                'ms' => (int)round((microtime(true) - $start) * 1000),
            ]);
            return [
                'content' => [['type' => 'text', 'text' => 'Fehler: ' . $throwable->getMessage()]],
                'isError' => true,
            ];
        }
        $this->logger->info('tools/call', [
            'client' => $client,
            'tool' => $name,
            'arguments' => $arguments,
            'ms' => (int)round((microtime(true) - $start) * 1000),
        ]);
        $text = json_encode($result, self::JSON_FLAGS);
        if ($text === false) {
            $text = '{"ok":true}';
        }
        return [
            'content' => [['type' => 'text', 'text' => $text]],
            'structuredContent' => $result === [] ? new \stdClass() : $result,
            'isError' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
