<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignments;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;
use Thorwarth\NeosMcp\Protocol\InvalidParamsException;

/**
 * Zentraler Zugriff auf das Content Repository für die MCP-Tools:
 * Workspace-Auflösung, Subgraph, Site-Node, Rebase, Node-Lookup.
 *
 * @Flow\Scope("singleton")
 */
class ContentAccess
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected WorkspaceService $workspaceService;

    #[Flow\Inject]
    protected WorkspacePublishingService $workspacePublishingService;

    #[Flow\InjectConfiguration(package: 'Thorwarth.NeosMcp')]
    protected array $settings = [];

    public function contentRepositoryId(): ContentRepositoryId
    {
        return ContentRepositoryId::fromString((string)($this->settings['contentRepository'] ?? 'default'));
    }

    public function contentRepository(): ContentRepository
    {
        return $this->contentRepositoryRegistry->get($this->contentRepositoryId());
    }

    public function nodeTypeManager(): NodeTypeManager
    {
        return $this->contentRepository()->getNodeTypeManager();
    }

    /**
     * Für den laufenden Tool-Aufruf gewählte Dimension (siehe selectDimension), sonst die konfigurierte Standard-Dimension
     */
    private ?DimensionSpacePoint $currentDimensionSpacePoint = null;

    public function dimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->currentDimensionSpacePoint ?? $this->defaultDimensionSpacePoint();
    }

    public function defaultDimensionSpacePoint(): DimensionSpacePoint
    {
        $coordinates = $this->settings['dimensionSpacePoint'] ?? [];
        return is_array($coordinates) && $coordinates !== []
            ? DimensionSpacePoint::fromArray($coordinates)
            : DimensionSpacePoint::createWithoutDimensions();
    }

    /**
     * Name der Dimension, die über das Tool-Argument "language" gesteuert wird
     */
    public function languageDimensionName(): string
    {
        return trim((string)($this->settings['languageDimension'] ?? 'language'));
    }

    /**
     * Wählt die Dimension für den laufenden Tool-Aufruf anhand des Arguments "language" (z. B. "de", "en").
     * Leer = Standard-Dimension. Mit resetDimension() wieder zurücksetzen.
     */
    public function selectDimension(?string $language): void
    {
        $this->currentDimensionSpacePoint = null;
        $language = trim((string)$language);
        if ($language === '') {
            return;
        }
        $this->currentDimensionSpacePoint = $this->dimensionSpacePointForLanguage($language);
    }

    public function resetDimension(): void
    {
        $this->currentDimensionSpacePoint = null;
    }

    /**
     * Standard-Dimension mit ersetzter Sprache; validiert gegen die erlaubten Dimension-Space-Points des Content Repository
     */
    public function dimensionSpacePointForLanguage(string $language): DimensionSpacePoint
    {
        $dimensionName = $this->languageDimensionName();
        $allowed = $this->contentRepository()->getVariationGraph()->getDimensionSpacePoints();
        $available = $this->availableLanguages();
        if ($available === []) {
            throw new InvalidParamsException('Diese Site hat keine Sprach-Dimension; "language" bitte weglassen.');
        }
        $coordinates = $this->defaultDimensionSpacePoint()->coordinates;
        $coordinates[$dimensionName] = $language;
        $dimensionSpacePoint = DimensionSpacePoint::fromArray($coordinates);
        if (!$allowed->contains($dimensionSpacePoint)) {
            throw new InvalidParamsException(sprintf('Unbekannte Sprache "%s". Verfügbar: %s', $language, implode(', ', $available)));
        }
        return $dimensionSpacePoint;
    }

    /**
     * @return array<int, string> Werte der Sprach-Dimension (leer, wenn es keine gibt)
     */
    public function availableLanguages(): array
    {
        $dimensionName = $this->languageDimensionName();
        if ($dimensionName === '') {
            return [];
        }
        foreach ($this->contentRepository()->getContentDimensionSource()->getContentDimensionsOrderedByPriority() as $dimension) {
            if ($dimension->id->value === $dimensionName) {
                return array_map('strval', array_keys($dimension->values->values));
            }
        }
        return [];
    }

    public function origin(): OriginDimensionSpacePoint
    {
        return OriginDimensionSpacePoint::fromDimensionSpacePoint($this->dimensionSpacePoint());
    }

    public function defaultWorkspaceName(): WorkspaceName
    {
        return WorkspaceName::fromString((string)($this->settings['workspace']['name'] ?? 'mcp'));
    }

    public function allowLiveWrites(): bool
    {
        return (bool)($this->settings['workspace']['allowLiveWrites'] ?? false);
    }

    /**
     * Löst den angeforderten Workspace auf (leer = konfigurierter Arbeits-Workspace).
     * Der Arbeits-Workspace wird bei Bedarf angelegt.
     */
    public function resolveWorkspace(?string $requested, bool $forWriting): WorkspaceName
    {
        $requested = trim((string)$requested);
        try {
            $workspaceName = $requested === '' ? $this->defaultWorkspaceName() : WorkspaceName::fromString($requested);
        } catch (\Throwable $e) {
            throw new InvalidParamsException(sprintf('Ungültiger Workspace-Name "%s".', $requested), 0, $e);
        }

        if ($forWriting && $workspaceName->isLive() && !$this->allowLiveWrites()) {
            throw new InvalidParamsException(sprintf(
                'Direktes Schreiben in den Live-Workspace ist deaktiviert. Bitte in den Workspace "%s" schreiben (Standard, "workspace" einfach weglassen) und anschließend mit "publish" veröffentlichen.',
                $this->defaultWorkspaceName()->value
            ));
        }

        $contentRepository = $this->contentRepository();
        if ($contentRepository->findWorkspaceByName($workspaceName) === null) {
            if ($workspaceName->equals($this->defaultWorkspaceName())) {
                $this->createDefaultWorkspace();
            } else {
                $existing = $contentRepository->findWorkspaces()->map(fn (Workspace $workspace) => $workspace->workspaceName->value);
                throw new InvalidParamsException(sprintf('Workspace "%s" existiert nicht. Vorhandene Workspaces: %s', $workspaceName->value, implode(', ', $existing)));
            }
        }
        return $workspaceName;
    }

    public function workspace(WorkspaceName $workspaceName): Workspace
    {
        $workspace = $this->contentRepository()->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            throw new \RuntimeException(sprintf('Workspace "%s" existiert nicht.', $workspaceName->value));
        }
        return $workspace;
    }

    /**
     * Subgraph des Workspaces. Deaktivierte (hidden) Nodes sind immer sichtbar, soft-removed Nodes nur auf Wunsch.
     */
    public function subgraph(WorkspaceName $workspaceName, bool $includeRemoved = false): ContentSubgraphInterface
    {
        return $this->subgraphFor($workspaceName, $this->dimensionSpacePoint(), $includeRemoved);
    }

    public function subgraphFor(WorkspaceName $workspaceName, DimensionSpacePoint $dimensionSpacePoint, bool $includeRemoved = false): ContentSubgraphInterface
    {
        $constraints = $includeRemoved
            ? VisibilityConstraints::createEmpty()
            : NeosVisibilityConstraints::excludeRemoved();
        return $this->contentRepository()
            ->getContentGraph($workspaceName)
            ->getSubgraph($dimensionSpacePoint, $constraints);
    }

    /**
     * Rebase, falls der Workspace hinter seinem Basis-Workspace zurückliegt.
     *
     * @return bool true, wenn ein Rebase stattgefunden hat
     */
    public function rebaseIfOutdated(WorkspaceName $workspaceName): bool
    {
        if ($workspaceName->isLive()) {
            return false;
        }
        $workspace = $this->workspace($workspaceName);
        if ($workspace->status !== WorkspaceStatus::OUTDATED) {
            return false;
        }
        try {
            $this->workspacePublishingService->rebaseWorkspace($this->contentRepositoryId(), $workspaceName, RebaseErrorHandlingStrategy::STRATEGY_FAIL);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf(
                'Workspace "%s" konnte nicht auf den aktuellen Stand von "%s" gebracht werden (Konflikt): %s. Konfliktbehaftete Änderungen lassen sich mit "discard" verwerfen.',
                $workspaceName->value,
                $workspace->baseWorkspaceName?->value ?? 'live',
                $e->getMessage()
            ), 0, $e);
        }
        return true;
    }

    public function findSiteNode(ContentSubgraphInterface $subgraph, ?string $siteNodeName = null): Node
    {
        $sitesRoot = $subgraph->findRootNodeByType(NodeTypeNameFactory::forSites());
        if ($sitesRoot === null) {
            throw new \RuntimeException('Kein Sites-Root-Node gefunden – ist das Content Repository eingerichtet (./flow cr:setup)?');
        }
        $siteNodeName = trim((string)($siteNodeName ?? ($this->settings['siteNodeName'] ?? '')));
        $siteNodes = $subgraph->findChildNodes($sitesRoot->aggregateId, FindChildNodesFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
        foreach ($siteNodes as $siteNode) {
            if ($siteNodeName === '' || $siteNode->name?->value === $siteNodeName) {
                return $siteNode;
            }
        }
        $language = $subgraph->getDimensionSpacePoint()->coordinates[$this->languageDimensionName()] ?? null;
        if ($language !== null && !$subgraph->getDimensionSpacePoint()->equals($this->defaultDimensionSpacePoint())) {
            throw new InvalidParamsException(sprintf(
                'In der Sprache "%s" gibt es noch keine Startseite. Zuerst eine Seite mit translate_node (targetLanguage: "%s") übersetzen; fehlende Elternseiten inkl. Startseite werden dabei mit angelegt.',
                $language,
                $language
            ));
        }
        throw new \RuntimeException($siteNodeName === '' ? 'Kein Site-Node gefunden.' : sprintf('Site-Node "%s" nicht gefunden.', $siteNodeName));
    }

    public function requireNode(ContentSubgraphInterface $subgraph, string $id, string $what = 'Node'): Node
    {
        $id = trim($id);
        try {
            $aggregateId = NodeAggregateId::fromString($id);
        } catch (\Throwable $e) {
            throw new InvalidParamsException(sprintf('Ungültige Node-ID "%s".', $id), 0, $e);
        }
        $node = $subgraph->findNodeById($aggregateId);
        if ($node === null) {
            $language = $subgraph->getDimensionSpacePoint()->coordinates[$this->languageDimensionName()] ?? null;
            throw new InvalidParamsException(sprintf(
                '%s "%s" wurde im Workspace "%s"%s nicht gefunden.%s',
                $what,
                $id,
                $subgraph->getWorkspaceName()->value,
                $language !== null ? sprintf(' (Sprache "%s")', $language) : '',
                $language !== null ? ' Falls er in einer anderen Sprache existiert: mit translate_node eine Übersetzung anlegen.' : ''
            ));
        }
        return $node;
    }

    public function requireNodeType(string|NodeTypeName $name): NodeType
    {
        $nodeType = null;
        try {
            $nodeType = $this->nodeTypeManager()->getNodeType($name);
        } catch (\Throwable) {
            // unbekannter Typ -> unten behandelt
        }
        if ($nodeType === null) {
            throw new InvalidParamsException(sprintf('NodeType "%s" ist nicht bekannt. Verfügbare Typen mit "list_node_types" abfragen.', is_string($name) ? $name : $name->value));
        }
        return $nodeType;
    }

    private function createDefaultWorkspace(): void
    {
        $assignments = WorkspaceRoleAssignments::create(
            WorkspaceRoleAssignment::createForGroup('Neos.Neos:AbstractEditor', WorkspaceRole::COLLABORATOR),
            WorkspaceRoleAssignment::createForGroup('Neos.Neos:Administrator', WorkspaceRole::MANAGER),
        );
        $this->workspaceService->createSharedWorkspace(
            $this->contentRepositoryId(),
            $this->defaultWorkspaceName(),
            WorkspaceTitle::fromString((string)($this->settings['workspace']['title'] ?? 'MCP')),
            WorkspaceDescription::fromString((string)($this->settings['workspace']['description'] ?? '')),
            WorkspaceName::forLive(),
            $assignments
        );
    }
}
