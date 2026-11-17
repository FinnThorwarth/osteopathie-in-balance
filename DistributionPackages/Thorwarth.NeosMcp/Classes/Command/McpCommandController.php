<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Security\Context as SecurityContext;
use Thorwarth\NeosMcp\Protocol\JsonRpcHandler;
use Thorwarth\NeosMcp\Tools\ToolRegistry;

/**
 * CLI-Werkzeuge für den MCP-Server
 */
class McpCommandController extends CommandController
{
    #[Flow\Inject]
    protected JsonRpcHandler $jsonRpcHandler;

    #[Flow\Inject]
    protected ToolRegistry $toolRegistry;

    #[Flow\Inject]
    protected SecurityContext $securityContext;

    /**
     * Erzeugt ein zufälliges Zugriffstoken für den MCP-Endpoint
     *
     * @param int $bytes Zufallsbytes (Token hat doppelt so viele Hex-Zeichen)
     */
    public function tokenCommand(int $bytes = 24): void
    {
        $token = bin2hex(random_bytes(max(16, $bytes)));
        $this->outputLine($token);
        $this->outputLine();
        $this->outputLine('In Configuration/Production/Settings.NeosMcp.yaml (nicht im Git) eintragen:');
        $this->outputLine();
        $this->outputLine('Thorwarth:');
        $this->outputLine('  NeosMcp:');
        $this->outputLine('    auth:');
        $this->outputLine('      tokens:');
        $this->outputLine('        claude-code: \'' . $token . '\'');
    }

    /**
     * Listet die verfügbaren MCP-Tools
     */
    public function toolsCommand(): void
    {
        foreach ($this->toolRegistry->getToolDefinitions() as $tool) {
            $this->outputLine('<b>%s</b> – %s', [$tool['name'], $tool['description']]);
        }
    }

    /**
     * Führt ein MCP-Tool direkt aus (zum Testen ohne HTTP)
     *
     * Beispiel: ./flow mcp:call get_node --arguments '{"path":"/"}'
     *
     * @param string $tool Tool-Name, z. B. neos_info
     * @param string $arguments Argumente als JSON-Objekt
     */
    public function callCommand(string $tool, string $arguments = '{}'): void
    {
        $decoded = json_decode($arguments, true);
        if (!is_array($decoded)) {
            $this->outputLine('<error>Argumente müssen ein JSON-Objekt sein.</error>');
            $this->quit(1);
        }
        $response = $this->securityContext->withoutAuthorizationChecks(
            fn () => $this->jsonRpcHandler->handle([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => ['name' => $tool, 'arguments' => $decoded],
            ], 'cli')
        );
        $this->outputLine((string)json_encode($response, JsonRpcHandler::JSON_FLAGS | JSON_PRETTY_PRINT));
        if (isset($response['error']) || !empty($response['result']['isError'])) {
            $this->quit(1);
        }
    }

    /**
     * MCP-Server über stdio (für lokale Nutzung, z. B. in .mcp.json: "command": "./flow", "args": ["mcp:serve"])
     *
     * Liest zeilenweise JSON-RPC-Nachrichten von STDIN und schreibt Antworten nach STDOUT.
     */
    public function serveCommand(): void
    {
        $stdin = fopen('php://stdin', 'r');
        $stdout = fopen('php://stdout', 'w');
        if ($stdin === false || $stdout === false) {
            $this->quit(1);
        }
        while (($line = fgets($stdin)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                $response = JsonRpcHandler::error(null, -32700, 'Parse error');
            } else {
                $response = $this->securityContext->withoutAuthorizationChecks(
                    fn () => $this->jsonRpcHandler->handle($decoded, 'stdio')
                );
            }
            if ($response !== null) {
                fwrite($stdout, json_encode($response, JsonRpcHandler::JSON_FLAGS) . "\n");
                fflush($stdout);
            }
        }
    }
}
