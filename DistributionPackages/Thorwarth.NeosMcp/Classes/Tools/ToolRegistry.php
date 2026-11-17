<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Tools;

use Neos\Flow\Annotations as Flow;
use Thorwarth\NeosMcp\Protocol\InvalidParamsException;
use Thorwarth\NeosMcp\Service\ContentAccess;

/**
 * Verteilt Tool-Aufrufe an die Implementierungen
 *
 * @Flow\Scope("singleton")
 */
class ToolRegistry
{
    #[Flow\Inject]
    protected ReadTools $readTools;

    #[Flow\Inject]
    protected WriteTools $writeTools;

    #[Flow\Inject]
    protected WorkspaceTools $workspaceTools;

    #[Flow\Inject]
    protected ContentAccess $access;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getToolDefinitions(): array
    {
        return ToolDefinitions::all();
    }

    public function hasTool(string $name): bool
    {
        return in_array($name, ToolDefinitions::names(), true);
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function call(string $name, array $arguments): array
    {
        $this->access->selectDimension(Args::str($arguments, 'language'));
        try {
            return $this->dispatch($name, $arguments);
        } finally {
            $this->access->resetDimension();
        }
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function dispatch(string $name, array $arguments): array
    {
        return match ($name) {
            'neos_info' => $this->readTools->info($arguments),
            'list_node_types' => $this->readTools->listNodeTypes($arguments),
            'get_node_type' => $this->readTools->getNodeType($arguments),
            'get_page_tree' => $this->readTools->getPageTree($arguments),
            'get_node' => $this->readTools->getNode($arguments),
            'find_nodes' => $this->readTools->findNodes($arguments),
            'list_assets' => $this->readTools->listAssets($arguments),
            'workspace_status' => $this->workspaceTools->status($arguments),
            'set_properties' => $this->writeTools->setProperties($arguments),
            'create_node' => $this->writeTools->createNode($arguments),
            'move_node' => $this->writeTools->moveNode($arguments),
            'remove_node' => $this->writeTools->removeNode($arguments),
            'set_hidden' => $this->writeTools->setHidden($arguments),
            'translate_node' => $this->writeTools->translateNode($arguments),
            'publish' => $this->workspaceTools->publish($arguments),
            'discard' => $this->workspaceTools->discard($arguments),
            default => throw new InvalidParamsException(sprintf('Unbekanntes Tool "%s".', $name)),
        };
    }
}
