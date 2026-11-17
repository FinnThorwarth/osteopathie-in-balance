<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Tools;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Locale;
use Neos\Flow\I18n\Translator;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Thorwarth\NeosMcp\Protocol\InvalidParamsException;
use Thorwarth\NeosMcp\Protocol\JsonRpcHandler;
use Thorwarth\NeosMcp\Service\ContentAccess;
use Thorwarth\NeosMcp\Service\NodeSerializer;

/**
 * Lesende Tools
 *
 * @Flow\Scope("singleton")
 */
class ReadTools
{
    #[Flow\Inject]
    protected ContentAccess $access;

    #[Flow\Inject]
    protected NodeSerializer $serializer;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected AssetRepository $assetRepository;

    #[Flow\Inject]
    protected WorkspaceService $workspaceService;

    #[Flow\Inject]
    protected WorkspacePublishingService $workspacePublishingService;

    #[Flow\Inject]
    protected Translator $translator;

    public function info(array $args): array
    {
        $contentRepository = $this->access->contentRepository();
        $contentRepositoryId = $this->access->contentRepositoryId();
        $workingWorkspace = $this->access->resolveWorkspace(null, false);
        $liveSubgraph = $this->access->subgraph(WorkspaceName::forLive());

        $sites = [];
        foreach ($this->siteRepository->findAll() as $site) {
            /** @var Site $site */
            $siteNodeId = null;
            try {
                $siteNodeId = $this->access->findSiteNode($liveSubgraph, $site->getNodeName()->value)->aggregateId->value;
            } catch (\Throwable) {
                // Site ohne Node (z. B. noch nicht importiert)
            }
            $sites[] = [
                'name' => $site->getName(),
                'nodeName' => $site->getNodeName()->value,
                'siteNodeId' => $siteNodeId,
                'online' => $site->getState() === Site::STATE_ONLINE,
                'primaryDomain' => $site->getPrimaryDomain()?->getHostname(),
            ];
        }

        $workspaces = [];
        foreach ($contentRepository->findWorkspaces() as $workspace) {
            $metadata = null;
            $pending = null;
            try {
                $metadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspace->workspaceName);
            } catch (\Throwable) {
            }
            if (!$workspace->isRootWorkspace()) {
                try {
                    $pending = $this->workspacePublishingService->countPendingWorkspaceChanges($contentRepositoryId, $workspace->workspaceName);
                } catch (\Throwable) {
                }
            }
            $workspaces[] = [
                'name' => $workspace->workspaceName->value,
                'title' => $metadata?->title->value,
                'classification' => $metadata?->classification->value,
                'baseWorkspace' => $workspace->baseWorkspaceName?->value,
                'status' => $workspace->status->value,
                'pendingChanges' => $pending,
            ];
        }

        $documentTypes = 0;
        $contentTypes = 0;
        foreach ($this->access->nodeTypeManager()->getNodeTypes(false) as $nodeType) {
            if ($nodeType->isOfType(NodeTypeNameFactory::NAME_DOCUMENT)) {
                $documentTypes++;
            } elseif ($nodeType->isOfType(NodeTypeNameFactory::NAME_CONTENT)) {
                $contentTypes++;
            }
        }

        return [
            'server' => ['name' => JsonRpcHandler::SERVER_NAME, 'version' => JsonRpcHandler::SERVER_VERSION],
            'contentRepository' => $contentRepositoryId->value,
            'dimensionSpacePoint' => $this->access->defaultDimensionSpacePoint()->coordinates,
            'languages' => $this->access->availableLanguages(),
            'workingWorkspace' => $workingWorkspace->value,
            'allowLiveWrites' => $this->access->allowLiveWrites(),
            'sites' => $sites,
            'workspaces' => $workspaces,
            'nodeTypes' => ['documentTypes' => $documentTypes, 'contentTypes' => $contentTypes],
            'hints' => [
                sprintf('Schreibzugriffe landen im Workspace "%s" und werden erst mit "publish" live.', $workingWorkspace->value),
                'Seitenstruktur mit get_page_tree, Seiteninhalt mit get_node (id oder path) lesen.',
                'Vor create_node/set_properties die erlaubten Properties mit get_node_type prüfen.',
                'Andere Sprachen über das Argument "language" lesen/schreiben; fehlende Übersetzungen mit translate_node anlegen.',
            ],
        ];
    }

    public function listNodeTypes(array $args): array
    {
        $kind = Args::enum($args, 'kind', ['all', 'document', 'content', 'collection'], 'all');
        $search = mb_strtolower(Args::str($args, 'search'));
        $items = [];
        foreach ($this->access->nodeTypeManager()->getNodeTypes(false) as $nodeType) {
            $nodeTypeKind = $this->kindOf($nodeType);
            if ($kind === 'all' ? $nodeTypeKind === 'other' : $nodeTypeKind !== $kind) {
                continue;
            }
            $label = $this->labelOf($nodeType);
            if ($search !== '' && !str_contains(mb_strtolower($nodeType->name->value . ' ' . $label), $search)) {
                continue;
            }
            $items[] = [
                'name' => $nodeType->name->value,
                'kind' => $nodeTypeKind,
                'label' => $label,
                'group' => $nodeType->getConfiguration('ui.group'),
                'properties' => $this->propertyNames($nodeType),
            ];
        }
        usort($items, fn (array $a, array $b) => [$a['kind'], $a['name']] <=> [$b['kind'], $b['name']]);
        return ['count' => count($items), 'nodeTypes' => $items];
    }

    public function getNodeType(array $args): array
    {
        $nodeType = $this->access->requireNodeType(Args::requireStr($args, 'nodeType'));
        $configuration = $nodeType->getFullConfiguration();

        $properties = [];
        foreach ($nodeType->getProperties() as $name => $propertyConfiguration) {
            $name = (string)$name;
            if (str_starts_with($name, '_') || !is_array($propertyConfiguration)) {
                continue;
            }
            $ui = is_array($propertyConfiguration['ui'] ?? null) ? $propertyConfiguration['ui'] : [];
            $inspector = is_array($ui['inspector'] ?? null) ? $ui['inspector'] : [];
            $editorOptions = is_array($inspector['editorOptions'] ?? null) ? $inspector['editorOptions'] : [];
            $entry = ['type' => (string)($propertyConfiguration['type'] ?? 'string')];
            if (isset($ui['label'])) {
                $entry['label'] = $this->translate((string)$ui['label'], $name);
            }
            if (isset($inspector['group'])) {
                $entry['group'] = $inspector['group'];
            }
            if (isset($inspector['editor'])) {
                $entry['editor'] = $inspector['editor'];
            }
            if (!empty($ui['inlineEditable'])) {
                $entry['richText'] = true;
            }
            if (array_key_exists('defaultValue', $propertyConfiguration)) {
                $entry['defaultValue'] = $propertyConfiguration['defaultValue'];
            }
            if (isset($editorOptions['values']) && is_array($editorOptions['values'])) {
                $entry['values'] = [];
                foreach ($editorOptions['values'] as $value => $valueConfiguration) {
                    $valueLabel = is_array($valueConfiguration) ? ($valueConfiguration['label'] ?? (string)$value) : (string)$valueConfiguration;
                    $entry['values'][] = [
                        'value' => $value,
                        'label' => $this->translate((string)$valueLabel, (string)$value),
                    ];
                }
            }
            if (isset($editorOptions['dataSourceIdentifier'])) {
                $entry['dataSource'] = $editorOptions['dataSourceIdentifier'];
            }
            if (isset($editorOptions['placeholder']) && !str_starts_with((string)$editorOptions['placeholder'], 'ClientEval:')) {
                $placeholder = $this->translate((string)$editorOptions['placeholder'], '');
                if ($placeholder !== '') {
                    $entry['placeholder'] = $placeholder;
                }
            }
            if (!empty($propertyConfiguration['validation']) && is_array($propertyConfiguration['validation'])) {
                $entry['validation'] = array_keys($propertyConfiguration['validation']);
            }
            $properties[$name] = $entry;
        }

        $references = [];
        foreach ($nodeType->getReferences() as $name => $referenceConfiguration) {
            $referenceConfiguration = is_array($referenceConfiguration) ? $referenceConfiguration : [];
            $references[(string)$name] = [
                'label' => isset($referenceConfiguration['ui']['label']) ? $this->translate((string)$referenceConfiguration['ui']['label'], (string)$name) : null,
                'maxItems' => $referenceConfiguration['constraints']['maxItems'] ?? null,
                'nodeTypes' => $referenceConfiguration['constraints']['nodeTypes'] ?? null,
            ];
        }

        $childNodes = [];
        foreach ((array)($configuration['childNodes'] ?? []) as $childName => $childConfiguration) {
            if (!is_array($childConfiguration) || empty($childConfiguration['type'])) {
                continue;
            }
            $childNodes[(string)$childName] = [
                'type' => $childConfiguration['type'],
                'allowedChildNodeTypes' => $this->allowedChildTypesInCollection($nodeType, (string)$childName),
            ];
        }

        return [
            'name' => $nodeType->name->value,
            'kind' => $this->kindOf($nodeType),
            'label' => $this->labelOf($nodeType),
            'abstract' => $nodeType->isAbstract(),
            'superTypes' => array_keys($nodeType->getDeclaredSuperTypes()),
            'properties' => $properties === [] ? new \stdClass() : $properties,
            'references' => $references === [] ? new \stdClass() : $references,
            'childNodes' => $childNodes === [] ? new \stdClass() : $childNodes,
            'allowedChildNodeTypes' => $this->allowedChildTypes($nodeType),
        ];
    }

    public function getPageTree(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), false);
        $subgraph = $this->access->subgraph($workspaceName, Args::bool($args, 'includeRemoved', false));
        $rootId = Args::str($args, 'rootId');
        $root = $rootId !== '' ? $this->access->requireNode($subgraph, $rootId, 'Dokument') : $this->access->findSiteNode($subgraph);
        if (!$this->serializer->isDocument($root)) {
            throw new InvalidParamsException('rootId muss ein Dokument sein.');
        }
        $depth = Args::int($args, 'depth', 3, 1, 10);
        $subtree = $subgraph->findSubtree(
            $root->aggregateId,
            FindSubtreeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT, maximumLevels: $depth)
        );
        if ($subtree === null) {
            throw new \RuntimeException('Seitenbaum konnte nicht geladen werden.');
        }
        return [
            'workspace' => $workspaceName->value,
            'depth' => $depth,
            'tree' => $this->serializer->documentTree($subtree, $this->serializer->documentPath($root, $subgraph)),
        ];
    }

    public function getNode(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), false);
        $subgraph = $this->access->subgraph($workspaceName, Args::bool($args, 'includeRemoved', false));
        $id = Args::str($args, 'id');
        $path = Args::str($args, 'path');
        if ($id !== '') {
            $node = $this->access->requireNode($subgraph, $id);
        } elseif ($path !== '') {
            $node = $this->resolvePath($subgraph, $path);
        } else {
            throw new InvalidParamsException('Entweder "id" oder "path" angeben.');
        }

        $data = $this->serializer->full($node, $subgraph);
        $data['workspace'] = $workspaceName->value;

        if ($this->serializer->isDocument($node) && Args::bool($args, 'includeContent', true)) {
            $depth = Args::int($args, 'depth', 8, 1, 20);
            $subtree = $subgraph->findSubtree(
                $node->aggregateId,
                FindSubtreeFilter::create(nodeTypes: '!' . NodeTypeNameFactory::NAME_DOCUMENT, maximumLevels: $depth)
            );
            $content = [];
            foreach ($subtree?->children ?? [] as $child) {
                $content[] = $this->serializer->contentTree($child, $subgraph);
            }
            $data['content'] = $content;

            $ownPath = $data['path'] ?? '/';
            $childDocuments = [];
            foreach ($subgraph->findChildNodes($node->aggregateId, FindChildNodesFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT)) as $childDocument) {
                $childDocuments[] = [
                    'id' => $childDocument->aggregateId->value,
                    'title' => (string)$childDocument->getProperty('title'),
                    'path' => ($ownPath === '/' ? '' : $ownPath) . '/' . (string)$childDocument->getProperty('uriPathSegment'),
                    'nodeType' => $childDocument->nodeTypeName->value,
                ] + $this->serializer->flags($childDocument);
            }
            $data['childDocuments'] = $childDocuments;
        }
        return $data;
    }

    public function findNodes(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), false);
        $subgraph = $this->access->subgraph($workspaceName);
        $searchTerm = Args::str($args, 'searchTerm');
        $nodeTypeFilter = Args::str($args, 'nodeType');
        if ($searchTerm === '' && $nodeTypeFilter === '') {
            throw new InvalidParamsException('Mindestens "searchTerm" oder "nodeType" angeben.');
        }
        $limit = Args::int($args, 'limit', 25, 1, 200);
        $rootId = Args::str($args, 'rootId');
        $root = $rootId !== '' ? $this->access->requireNode($subgraph, $rootId) : $this->access->findSiteNode($subgraph);

        $filter = FindDescendantNodesFilter::create(
            nodeTypes: $nodeTypeFilter !== '' ? $nodeTypeFilter : null,
            searchTerm: $searchTerm !== '' ? $searchTerm : null,
            pagination: Pagination::fromLimitAndOffset($limit, 0)
        );
        $items = [];
        foreach ($subgraph->findDescendantNodes($root->aggregateId, $filter) as $node) {
            $item = $this->serializer->summary($node, $subgraph);
            if (!$this->serializer->isDocument($node)) {
                $document = $this->serializer->closestDocument($node, $subgraph);
                if ($document !== null) {
                    $item['document'] = $this->serializer->documentRef($document, $subgraph);
                }
            }
            if ($searchTerm !== '') {
                $item['matches'] = $this->matchingSnippets($node, $searchTerm);
            }
            $items[] = $item;
        }
        return [
            'workspace' => $workspaceName->value,
            'count' => count($items),
            'limit' => $limit,
            'items' => $items,
        ];
    }

    public function listAssets(array $args): array
    {
        $search = Args::str($args, 'search');
        $limit = Args::int($args, 'limit', 25, 1, 100);
        $query = $this->assetRepository->findBySearchTermOrTags($search, [], null)->getQuery();
        $query->setLimit($limit);
        $items = [];
        foreach ($query->execute() as $asset) {
            if (!$asset instanceof AssetInterface || $asset instanceof AssetVariantInterface) {
                continue;
            }
            $items[] = $this->serializer->asset($asset);
        }
        return ['count' => count($items), 'limit' => $limit, 'items' => $items];
    }

    private function resolvePath(ContentSubgraphInterface $subgraph, string $path): Node
    {
        $node = $this->access->findSiteNode($subgraph);
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn (string $segment) => $segment !== ''));
        foreach ($segments as $segment) {
            $child = $subgraph->findChildNodes(
                $node->aggregateId,
                FindChildNodesFilter::create(
                    nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT,
                    propertyValue: PropertyValueEquals::create(PropertyName::fromString('uriPathSegment'), $segment, true)
                )
            )->first();
            if ($child === null) {
                throw new InvalidParamsException(sprintf('Kein Dokument für Pfad "%s" gefunden (Segment "%s" unterhalb von "%s" fehlt).', $path, $segment, $this->serializer->label($node)));
            }
            $node = $child;
        }
        return $node;
    }

    /**
     * @return array<int, array{property: string, snippet: string}>
     */
    private function matchingSnippets(Node $node, string $searchTerm): array
    {
        $snippets = [];
        foreach ($this->serializer->properties($node) as $name => $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
            $position = mb_stripos($text, $searchTerm);
            if ($position === false) {
                continue;
            }
            $start = max(0, $position - 60);
            $snippet = mb_substr($text, $start, mb_strlen($searchTerm) + 120);
            $snippets[] = [
                'property' => (string)$name,
                'snippet' => ($start > 0 ? '…' : '') . $snippet . (mb_strlen($text) > $start + mb_strlen($snippet) ? '…' : ''),
            ];
            if (count($snippets) >= 3) {
                break;
            }
        }
        return $snippets;
    }

    private function kindOf(NodeType $nodeType): string
    {
        if ($nodeType->isOfType(NodeTypeNameFactory::NAME_DOCUMENT)) {
            return 'document';
        }
        if ($nodeType->isOfType(NodeTypeNameFactory::NAME_CONTENT_COLLECTION)) {
            return 'collection';
        }
        if ($nodeType->isOfType(NodeTypeNameFactory::NAME_CONTENT)) {
            return 'content';
        }
        return 'other';
    }

    private function labelOf(NodeType $nodeType): string
    {
        $localName = substr($nodeType->name->value, (int)strrpos($nodeType->name->value, ':') + 1);
        return $this->translate(trim((string)$nodeType->getConfiguration('ui.label')), str_replace('.', ' ', $localName));
    }

    /**
     * Übersetzt Neos-Label-IDs der Form "Package.Key:Source.Name:id" (Deutsch, Fallback Englisch);
     * sonstige Texte werden unverändert zurückgegeben, leere/unauflösbare durch $fallback ersetzt.
     */
    private function translate(string $label, string $fallback): string
    {
        $label = trim($label);
        if ($label === '' || $label === 'i18n') {
            return $fallback;
        }
        if (preg_match('/^([A-Za-z0-9]+(?:\.[A-Za-z0-9]+)+):([A-Za-z0-9.\/]+):([A-Za-z0-9._-]+)$/', $label, $matches) !== 1) {
            return $label;
        }
        [, $packageKey, $sourceName, $id] = $matches;
        $sourceName = str_replace('.', '/', $sourceName);
        foreach (['de', 'en'] as $language) {
            try {
                $translation = $this->translator->translateById($id, [], null, new Locale($language), $sourceName, $packageKey);
            } catch (\Throwable) {
                $translation = null;
            }
            if (is_string($translation) && $translation !== '' && $translation !== $id) {
                return $translation;
            }
        }
        return $fallback !== '' ? $fallback : $label;
    }

    /**
     * @return array<int, string>
     */
    private function propertyNames(NodeType $nodeType): array
    {
        return array_values(array_filter(
            array_map('strval', array_keys($nodeType->getProperties())),
            fn (string $name) => !str_starts_with($name, '_')
        ));
    }

    /**
     * @return array<int, string>
     */
    private function allowedChildTypes(NodeType $parent): array
    {
        $allowed = [];
        foreach ($this->access->nodeTypeManager()->getNodeTypes(false) as $candidate) {
            if ($this->kindOf($candidate) === 'other') {
                continue;
            }
            try {
                if ($parent->allowsChildNodeType($candidate)) {
                    $allowed[] = $candidate->name->value;
                }
            } catch (\Throwable) {
            }
        }
        sort($allowed);
        return $allowed;
    }

    /**
     * @return array<int, string>
     */
    private function allowedChildTypesInCollection(NodeType $parent, string $childName): array
    {
        $nodeTypeManager = $this->access->nodeTypeManager();
        $allowed = [];
        try {
            $nodeName = NodeName::fromString($childName);
        } catch (\Throwable) {
            return [];
        }
        foreach ($nodeTypeManager->getNodeTypes(false) as $candidate) {
            if ($this->kindOf($candidate) === 'other') {
                continue;
            }
            try {
                if ($nodeTypeManager->isNodeTypeAllowedAsChildToTetheredNode($parent->name, $nodeName, $candidate->name)) {
                    $allowed[] = $candidate->name->value;
                }
            } catch (\Throwable) {
            }
        }
        sort($allowed);
        return $allowed;
    }
}
