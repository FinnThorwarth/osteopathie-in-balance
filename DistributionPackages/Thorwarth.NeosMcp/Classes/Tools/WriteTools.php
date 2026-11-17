<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Tools;

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;
use Neos\Neos\Utility\NodeUriPathSegmentGenerator;
use Thorwarth\NeosMcp\Protocol\InvalidParamsException;
use Thorwarth\NeosMcp\Service\ContentAccess;
use Thorwarth\NeosMcp\Service\NodeSerializer;
use Thorwarth\NeosMcp\Service\PropertyValueConverter;

/**
 * Schreibende Tools (Properties, Anlegen, Verschieben, Löschen, Ausblenden)
 *
 * @Flow\Scope("singleton")
 */
class WriteTools
{
    #[Flow\Inject]
    protected ContentAccess $access;

    #[Flow\Inject]
    protected NodeSerializer $serializer;

    #[Flow\Inject]
    protected PropertyValueConverter $converter;

    #[Flow\Inject]
    protected NodeUriPathSegmentGenerator $uriPathSegmentGenerator;

    public function setProperties(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), true);
        $this->access->rebaseIfOutdated($workspaceName);
        $subgraph = $this->access->subgraph($workspaceName);
        $node = $this->access->requireNode($subgraph, Args::requireStr($args, 'id'));
        $nodeType = $this->access->requireNodeType($node->nodeTypeName);

        $properties = Args::map($args, 'properties');
        $references = Args::map($args, 'references');
        if ($properties === [] && $references === []) {
            throw new InvalidParamsException('Keine Änderungen angegeben ("properties" und/oder "references").');
        }

        $contentRepository = $this->access->contentRepository();
        if ($properties !== []) {
            $values = $this->converter->convert($nodeType, $properties);
            $contentRepository->handle(SetNodeProperties::create(
                $workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray($values)
            ));
        }
        if ($references !== []) {
            $contentRepository->handle(SetNodeReferences::create(
                $workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                $this->converter->convertReferences($nodeType, $references)
            ));
        }

        $updated = $this->access->requireNode($this->access->subgraph($workspaceName), $node->aggregateId->value);
        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'node' => $this->serializer->full($updated, $this->access->subgraph($workspaceName)),
            'hint' => $this->publishHint($workspaceName),
        ];
    }

    public function createNode(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), true);
        $this->access->rebaseIfOutdated($workspaceName);
        $subgraph = $this->access->subgraph($workspaceName);
        $parent = $this->access->requireNode($subgraph, Args::requireStr($args, 'parentId'), 'Eltern-Node');
        $nodeType = $this->access->requireNodeType(Args::requireStr($args, 'nodeType'));
        if ($nodeType->isAbstract()) {
            throw new InvalidParamsException(sprintf('NodeType "%s" ist abstrakt und kann nicht angelegt werden.', $nodeType->name->value));
        }
        $targetParent = $this->resolveTargetParent($subgraph, $parent, $nodeType, Args::str($args, 'collection'));

        $properties = $this->converter->convert($nodeType, Args::map($args, 'properties'));
        if ($nodeType->isOfType(NodeTypeNameFactory::NAME_DOCUMENT)) {
            $title = trim((string)($properties['title'] ?? ''));
            if ($title === '') {
                throw new InvalidParamsException('Neue Dokumente benötigen die Property "title".');
            }
            $segment = trim((string)($properties['uriPathSegment'] ?? ''));
            if ($segment === '') {
                $segment = $this->uriPathSegmentGenerator->generateUriPathSegment(null, $title);
            }
            $properties['uriPathSegment'] = $this->uniqueUriPathSegment($subgraph, $targetParent, $segment);
        }
        $properties = array_merge($nodeType->getDefaultValuesForProperties(), $properties);

        $references = Args::map($args, 'references');
        [, $succeedingSiblingId] = $this->resolvePosition(
            $subgraph,
            $targetParent,
            Args::enum($args, 'position', ['start', 'end', 'before', 'after'], 'end'),
            Args::str($args, 'siblingId'),
            null
        );

        $nodeAggregateId = NodeAggregateId::create();
        $this->access->contentRepository()->handle(CreateNodeAggregateWithNode::create(
            $workspaceName,
            $nodeAggregateId,
            $nodeType->name,
            $this->access->origin(),
            $targetParent->aggregateId,
            $succeedingSiblingId,
            PropertyValuesToWrite::fromArray($properties),
            $references !== [] ? $this->converter->convertReferences($nodeType, $references) : null
        ));

        $freshSubgraph = $this->access->subgraph($workspaceName);
        $created = $this->access->requireNode($freshSubgraph, $nodeAggregateId->value);
        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'parentId' => $targetParent->aggregateId->value,
            'node' => $this->serializer->full($created, $freshSubgraph),
            'hint' => $this->publishHint($workspaceName),
        ];
    }

    public function moveNode(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), true);
        $this->access->rebaseIfOutdated($workspaceName);
        $subgraph = $this->access->subgraph($workspaceName);
        $node = $this->access->requireNode($subgraph, Args::requireStr($args, 'id'));
        if ($node->classification->isTethered()) {
            throw new InvalidParamsException('Feste Kind-Nodes (z. B. Collections) können nicht verschoben werden.');
        }
        $nodeType = $this->access->requireNodeType($node->nodeTypeName);
        $currentParent = $subgraph->findParentNode($node->aggregateId);
        if ($currentParent === null) {
            throw new InvalidParamsException('Der Node hat keinen Eltern-Node und kann nicht verschoben werden.');
        }

        $parentId = Args::str($args, 'parentId');
        $collection = Args::str($args, 'collection');
        $targetParent = $currentParent;
        if ($parentId !== '') {
            $targetParent = $this->resolveTargetParent($subgraph, $this->access->requireNode($subgraph, $parentId, 'Ziel-Eltern-Node'), $nodeType, $collection);
        } elseif ($collection !== '') {
            $targetParent = $this->resolveTargetParent($subgraph, $currentParent, $nodeType, $collection);
        }

        [$precedingSiblingId, $succeedingSiblingId] = $this->resolvePosition(
            $subgraph,
            $targetParent,
            Args::enum($args, 'position', ['start', 'end', 'before', 'after'], 'end'),
            Args::str($args, 'siblingId'),
            $node->aggregateId
        );
        $parentChanges = !$targetParent->aggregateId->equals($currentParent->aggregateId);
        if (!$parentChanges && $precedingSiblingId === null && $succeedingSiblingId === null) {
            $lastChild = $this->lastChildExcept($subgraph, $targetParent, $node->aggregateId);
            if ($lastChild === null) {
                return ['ok' => true, 'workspace' => $workspaceName->value, 'unchanged' => true, 'hint' => 'Der Node steht bereits an dieser Position.'];
            }
            $precedingSiblingId = $lastChild->aggregateId;
        }

        $this->access->contentRepository()->handle(MoveNodeAggregate::create(
            $workspaceName,
            $this->access->dimensionSpacePoint(),
            $node->aggregateId,
            RelationDistributionStrategy::STRATEGY_GATHER_ALL,
            $parentChanges ? $targetParent->aggregateId : null,
            $precedingSiblingId,
            $succeedingSiblingId
        ));

        $freshSubgraph = $this->access->subgraph($workspaceName);
        $moved = $this->access->requireNode($freshSubgraph, $node->aggregateId->value);
        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'parentId' => $targetParent->aggregateId->value,
            'node' => $this->serializer->summary($moved, $freshSubgraph),
            'hint' => $this->publishHint($workspaceName),
        ];
    }

    public function removeNode(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), true);
        $this->access->rebaseIfOutdated($workspaceName);
        $subgraph = $this->access->subgraph($workspaceName);
        $node = $this->access->requireNode($subgraph, Args::requireStr($args, 'id'));
        if ($node->classification->isTethered()) {
            throw new InvalidParamsException('Feste Kind-Nodes (z. B. Collections) können nicht gelöscht werden.');
        }
        if ($this->serializer->isSite($node)) {
            throw new InvalidParamsException('Der Site-Node kann nicht gelöscht werden.');
        }
        $summary = $this->serializer->summary($node, $subgraph);

        if ($workspaceName->isLive()) {
            $command = RemoveNodeAggregate::create(
                $workspaceName,
                $node->aggregateId,
                $this->access->dimensionSpacePoint(),
                NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS
            );
            $mode = 'hard';
        } else {
            $command = TagSubtree::create(
                $workspaceName,
                $node->aggregateId,
                $this->access->dimensionSpacePoint(),
                NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS,
                NeosSubtreeTag::removed()
            );
            $mode = 'soft';
        }
        $this->access->contentRepository()->handle($command);

        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'removed' => $summary,
            'mode' => $mode,
            'hint' => $mode === 'soft'
                ? 'Soft-Löschung: wird beim Veröffentlichen endgültig; mit "discard" (ggf. mit documentId) rückgängig zu machen.'
                : 'Der Node wurde endgültig aus dem Live-Workspace entfernt.',
        ];
    }

    public function setHidden(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), true);
        $this->access->rebaseIfOutdated($workspaceName);
        $subgraph = $this->access->subgraph($workspaceName);
        $node = $this->access->requireNode($subgraph, Args::requireStr($args, 'id'));
        if (!array_key_exists('hidden', $args)) {
            throw new InvalidParamsException('Argument "hidden" ist erforderlich.');
        }
        $hidden = Args::bool($args, 'hidden', false);
        $command = $hidden
            ? DisableNodeAggregate::create($workspaceName, $node->aggregateId, $this->access->dimensionSpacePoint(), NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS)
            : EnableNodeAggregate::create($workspaceName, $node->aggregateId, $this->access->dimensionSpacePoint(), NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS);
        $this->access->contentRepository()->handle($command);

        $freshSubgraph = $this->access->subgraph($workspaceName);
        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'node' => $this->serializer->summary($this->access->requireNode($freshSubgraph, $node->aggregateId->value), $freshSubgraph),
            'hint' => $this->publishHint($workspaceName),
        ];
    }

    /**
     * Legt Sprachvarianten an (Node, optional fehlende Eltern und bei Dokumenten den Inhaltsbaum)
     */
    public function translateNode(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), true);
        $this->access->rebaseIfOutdated($workspaceName);

        $sourceLanguage = Args::str($args, 'sourceLanguage');
        $sourceDimension = $sourceLanguage !== ''
            ? $this->access->dimensionSpacePointForLanguage($sourceLanguage)
            : $this->access->defaultDimensionSpacePoint();
        $targetLanguage = Args::requireStr($args, 'targetLanguage');
        $targetDimension = $this->access->dimensionSpacePointForLanguage($targetLanguage);
        if ($sourceDimension->equals($targetDimension)) {
            throw new InvalidParamsException('Quell- und Zielsprache sind identisch.');
        }
        $targetOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($targetDimension);

        $sourceSubgraph = $this->access->subgraphFor($workspaceName, $sourceDimension);
        $node = $this->access->requireNode($sourceSubgraph, Args::requireStr($args, 'id'));
        if ($node->classification->isRoot()) {
            throw new InvalidParamsException('Root-Nodes können nicht übersetzt werden.');
        }

        $toTranslate = [];
        if (Args::bool($args, 'includeParents', true)) {
            $ancestors = iterator_to_array($sourceSubgraph->findAncestorNodes($node->aggregateId, FindAncestorNodesFilter::create()));
            foreach (array_reverse($ancestors) as $ancestor) {
                if (!$ancestor->classification->isRoot()) {
                    $toTranslate[] = $ancestor;
                }
            }
        }
        $toTranslate[] = $node;
        if ($this->serializer->isDocument($node) && Args::bool($args, 'includeContent', true)) {
            $subtree = $sourceSubgraph->findSubtree(
                $node->aggregateId,
                FindSubtreeFilter::create(nodeTypes: '!' . NodeTypeNameFactory::NAME_DOCUMENT, maximumLevels: 50)
            );
            $collect = function (Subtree $subtree) use (&$collect, &$toTranslate): void {
                foreach ($subtree->children as $child) {
                    $toTranslate[] = $child->node;
                    $collect($child);
                }
            };
            if ($subtree !== null) {
                $collect($subtree);
            }
        }

        $contentRepository = $this->access->contentRepository();
        $contentGraph = $contentRepository->getContentGraph($workspaceName);
        $created = [];
        $existing = 0;
        foreach ($toTranslate as $candidate) {
            $aggregate = $contentGraph->findNodeAggregateById($candidate->aggregateId);
            if ($aggregate === null || $aggregate->coversDimensionSpacePoint($targetDimension)) {
                $existing++;
                continue;
            }
            $contentRepository->handle(CreateNodeVariant::create(
                $workspaceName,
                $candidate->aggregateId,
                $candidate->originDimensionSpacePoint,
                $targetOrigin
            ));
            $created[] = [
                'id' => $candidate->aggregateId->value,
                'nodeType' => $candidate->nodeTypeName->value,
                'label' => $this->serializer->label($candidate),
            ];
        }

        $targetSubgraph = $this->access->subgraphFor($workspaceName, $targetDimension);
        $translated = $this->access->requireNode($targetSubgraph, $node->aggregateId->value);
        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'sourceLanguage' => $sourceDimension->coordinates[$this->access->languageDimensionName()] ?? null,
            'targetLanguage' => $targetLanguage,
            'createdVariants' => count($created),
            'alreadyTranslated' => $existing,
            'created' => $created,
            'node' => $this->serializer->full($translated, $targetSubgraph),
            'hint' => sprintf(
                'Die Variante enthält noch die Texte der Quellsprache. Jetzt mit get_node und set_properties (jeweils language: "%s") übersetzen, bei Seiten auch title/uriPathSegment. %s',
                $targetLanguage,
                $this->publishHint($workspaceName)
            ),
        ];
    }

    /**
     * Tatsächlicher Eltern-Node: explizite Collection, sonst bei Dokument + Inhaltselement automatisch "main"
     * bzw. die erste Content-Collection des Dokuments.
     */
    private function resolveTargetParent(ContentSubgraphInterface $subgraph, Node $parent, NodeType $childType, string $collection): Node
    {
        if ($collection !== '') {
            try {
                $path = NodePath::fromString(trim($collection, '/'));
            } catch (\Throwable $e) {
                throw new InvalidParamsException(sprintf('Ungültiger Collection-Pfad "%s".', $collection), 0, $e);
            }
            $node = $subgraph->findNodeByPath($path, $parent->aggregateId);
            if ($node === null) {
                throw new InvalidParamsException(sprintf('Unterhalb von "%s" gibt es keine Collection "%s".', $this->serializer->label($parent), $collection));
            }
            return $node;
        }
        if ($childType->isOfType(NodeTypeNameFactory::NAME_DOCUMENT) || !$this->serializer->isDocument($parent)) {
            return $parent;
        }
        $main = $subgraph->findNodeByPath(NodePath::fromString('main'), $parent->aggregateId);
        if ($main !== null) {
            return $main;
        }
        $firstCollection = $subgraph->findChildNodes(
            $parent->aggregateId,
            FindChildNodesFilter::create(nodeTypes: NodeTypeNameFactory::NAME_CONTENT_COLLECTION)
        )->first();
        if ($firstCollection !== null) {
            return $firstCollection;
        }
        throw new InvalidParamsException(sprintf('Das Dokument "%s" hat keine Content-Collection (z. B. "main"); bitte "collection" angeben.', $this->serializer->label($parent)));
    }

    /**
     * @return array{0: ?NodeAggregateId, 1: ?NodeAggregateId} [preceding, succeeding]
     */
    private function resolvePosition(ContentSubgraphInterface $subgraph, Node $parent, string $position, string $siblingId, ?NodeAggregateId $movingNodeId): array
    {
        switch ($position) {
            case 'start':
                $children = $subgraph->findChildNodes($parent->aggregateId, FindChildNodesFilter::create(pagination: Pagination::fromLimitAndOffset(2, 0)));
                foreach ($children as $child) {
                    if ($movingNodeId === null || !$child->aggregateId->equals($movingNodeId)) {
                        return [null, $child->aggregateId];
                    }
                }
                return [null, null];
            case 'before':
            case 'after':
                if ($siblingId === '') {
                    throw new InvalidParamsException(sprintf('Für position "%s" ist "siblingId" erforderlich.', $position));
                }
                $sibling = $this->access->requireNode($subgraph, $siblingId, 'Geschwister-Node');
                $siblingParent = $subgraph->findParentNode($sibling->aggregateId);
                if ($siblingParent === null || !$siblingParent->aggregateId->equals($parent->aggregateId)) {
                    throw new InvalidParamsException('siblingId liegt nicht unterhalb des Ziel-Eltern-Nodes.');
                }
                if ($position === 'before') {
                    return [null, $sibling->aggregateId];
                }
                if ($movingNodeId !== null) {
                    return [$sibling->aggregateId, null];
                }
                $next = $subgraph->findSucceedingSiblingNodes($sibling->aggregateId, FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0)))->first();
                return [null, $next?->aggregateId];
            default:
                return [null, null];
        }
    }

    private function lastChildExcept(ContentSubgraphInterface $subgraph, Node $parent, NodeAggregateId $except): ?Node
    {
        $last = null;
        foreach ($subgraph->findChildNodes($parent->aggregateId, FindChildNodesFilter::create()) as $child) {
            if (!$child->aggregateId->equals($except)) {
                $last = $child;
            }
        }
        return $last;
    }

    private function uniqueUriPathSegment(ContentSubgraphInterface $subgraph, Node $parent, string $segment): string
    {
        $candidate = $segment;
        $counter = 1;
        while ($this->siblingWithSegmentExists($subgraph, $parent, $candidate)) {
            $counter++;
            $candidate = $segment . '-' . $counter;
        }
        return $candidate;
    }

    private function siblingWithSegmentExists(ContentSubgraphInterface $subgraph, Node $parent, string $segment): bool
    {
        $existing = $subgraph->findChildNodes(
            $parent->aggregateId,
            FindChildNodesFilter::create(
                nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT,
                propertyValue: PropertyValueEquals::create(PropertyName::fromString('uriPathSegment'), $segment, false)
            )
        );
        return !$existing->isEmpty();
    }

    private function publishHint(WorkspaceName $workspaceName): string
    {
        return $workspaceName->isLive()
            ? 'Die Änderung ist direkt live.'
            : sprintf('Die Änderung liegt im Workspace "%s" und wird erst mit "publish" veröffentlicht.', $workspaceName->value);
    }
}
