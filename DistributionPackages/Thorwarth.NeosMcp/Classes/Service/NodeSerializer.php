<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Service;

use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\AssetVariantInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;

/**
 * Wandelt Nodes, Properties und Assets in JSON-fähige Arrays um
 *
 * @Flow\Scope("singleton")
 */
class NodeSerializer
{
    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected ContentAccess $contentAccess;

    public function nodeType(Node $node): ?NodeType
    {
        try {
            return $this->contentAccess->nodeTypeManager()->getNodeType($node->nodeTypeName);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isDocument(Node $node): bool
    {
        return $this->nodeType($node)?->isOfType(NodeTypeNameFactory::NAME_DOCUMENT) ?? false;
    }

    public function isSite(Node $node): bool
    {
        return $this->nodeType($node)?->isOfType(NodeTypeNameFactory::NAME_SITE) ?? false;
    }

    public function isCollection(Node $node): bool
    {
        return $this->nodeType($node)?->isOfType(NodeTypeNameFactory::NAME_CONTENT_COLLECTION) ?? false;
    }

    public function kind(Node $node): string
    {
        if ($this->isDocument($node)) {
            return 'document';
        }
        if ($this->isCollection($node)) {
            return 'collection';
        }
        return 'content';
    }

    public function label(Node $node): string
    {
        try {
            $label = trim($this->nodeLabelGenerator->getLabel($node));
        } catch (\Throwable) {
            $label = '';
        }
        return $label !== '' ? $label : $node->nodeTypeName->value;
    }

    /**
     * Kurzdarstellung eines Nodes (ID, Typ, Label, bei Dokumenten Titel und Pfad)
     */
    public function summary(Node $node, ContentSubgraphInterface $subgraph, bool $withPath = true): array
    {
        $kind = $this->kind($node);
        $data = [
            'id' => $node->aggregateId->value,
            'nodeType' => $node->nodeTypeName->value,
            'kind' => $kind,
            'label' => $this->label($node),
        ];
        if ($node->classification->isTethered() && $node->name !== null) {
            $data['name'] = $node->name->value;
        }
        if ($kind === 'document') {
            $data['title'] = (string)$node->getProperty('title');
            $data['uriPathSegment'] = (string)$node->getProperty('uriPathSegment');
            if ($withPath) {
                $data['path'] = $this->documentPath($node, $subgraph);
            }
        }
        return $data + $this->flags($node);
    }

    /**
     * Vollständige Darstellung inkl. Properties, Referenzen, Elternteil und zugehörigem Dokument
     */
    public function full(Node $node, ContentSubgraphInterface $subgraph): array
    {
        $data = $this->summary($node, $subgraph);
        $data['properties'] = $this->properties($node);
        $references = $this->references($node, $subgraph);
        if ($references !== []) {
            $data['references'] = $references;
        }
        $parent = $subgraph->findParentNode($node->aggregateId);
        if ($parent !== null && !$this->isSite($node)) {
            $data['parentId'] = $parent->aggregateId->value;
        }
        if (!$this->isDocument($node)) {
            $document = $this->closestDocument($node, $subgraph);
            if ($document !== null) {
                $data['document'] = $this->documentRef($document, $subgraph);
            }
        }
        $lastModified = $node->timestamps->lastModified ?? $node->timestamps->created;
        $data['lastModified'] = $lastModified->format(DATE_ATOM);
        return $data;
    }

    public function documentRef(Node $document, ContentSubgraphInterface $subgraph): array
    {
        return [
            'id' => $document->aggregateId->value,
            'title' => (string)$document->getProperty('title'),
            'path' => $this->documentPath($document, $subgraph),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function properties(Node $node): array
    {
        $result = [];
        foreach ($node->properties->serialized() as $name => $_) {
            try {
                $result[$name] = $this->value($node->properties->offsetGet($name));
            } catch (\Throwable $e) {
                $result[$name] = ['__error' => 'Property konnte nicht gelesen werden: ' . $e->getMessage()];
            }
        }
        return $result;
    }

    public function value(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->value($item);
            }
            return $result;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if ($value instanceof AssetInterface) {
            return $this->asset($value);
        }
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if ($value instanceof \JsonSerializable) {
            return $this->value($value->jsonSerialize());
        }
        if ($value instanceof \Stringable) {
            return (string)$value;
        }
        if ($value instanceof \Traversable) {
            return $this->value(iterator_to_array($value));
        }
        return ['__class' => get_class($value)];
    }

    public function asset(AssetInterface $asset): array
    {
        $resource = $asset->getResource();
        $data = [
            'asset' => $this->persistenceManager->getIdentifierByObject($asset),
            'title' => (string)$asset->getTitle(),
            'filename' => $resource !== null ? $resource->getFilename() : null,
            'mediaType' => $asset->getMediaType(),
        ];
        if ($asset instanceof ImageInterface) {
            $data['width'] = $asset->getWidth();
            $data['height'] = $asset->getHeight();
        }
        if ($asset instanceof AssetVariantInterface && method_exists($asset, 'getOriginalAsset')) {
            $original = $asset->getOriginalAsset();
            if ($original !== null) {
                $data['variantOf'] = $this->persistenceManager->getIdentifierByObject($original);
            }
        }
        return $data;
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public function references(Node $node, ContentSubgraphInterface $subgraph): array
    {
        $nodeType = $this->nodeType($node);
        if ($nodeType === null || $nodeType->getReferences() === []) {
            return [];
        }
        $result = [];
        try {
            foreach ($subgraph->findReferences($node->aggregateId, FindReferencesFilter::create()) as $reference) {
                $result[$reference->name->value][] = [
                    'id' => $reference->node->aggregateId->value,
                    'nodeType' => $reference->node->nodeTypeName->value,
                    'label' => $this->label($reference->node),
                ];
            }
        } catch (\Throwable) {
            // Referenzen sind optional
        }
        return $result;
    }

    public function closestDocument(Node $node, ContentSubgraphInterface $subgraph): ?Node
    {
        return $subgraph->findClosestNode(
            $node->aggregateId,
            FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT)
        );
    }

    /**
     * URL-Pfad eines Dokuments aus den uriPathSegments bis zur Site ("/" für die Startseite)
     */
    public function documentPath(Node $documentNode, ContentSubgraphInterface $subgraph): string
    {
        if ($this->isSite($documentNode)) {
            return '/';
        }
        $segments = [(string)$documentNode->getProperty('uriPathSegment')];
        $ancestors = $subgraph->findAncestorNodes(
            $documentNode->aggregateId,
            FindAncestorNodesFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT)
        );
        foreach ($ancestors as $ancestor) {
            if ($this->isSite($ancestor)) {
                break;
            }
            $segments[] = (string)$ancestor->getProperty('uriPathSegment');
        }
        return '/' . implode('/', array_reverse($segments));
    }

    /**
     * Dokumentbaum (nur Document-Nodes) mit Pfaden
     */
    public function documentTree(Subtree $subtree, string $ownPath): array
    {
        $node = $subtree->node;
        $data = [
            'id' => $node->aggregateId->value,
            'title' => (string)$node->getProperty('title'),
            'path' => $ownPath,
            'nodeType' => $node->nodeTypeName->value,
        ] + $this->flags($node);
        $children = [];
        foreach ($subtree->children as $child) {
            $segment = (string)$child->node->getProperty('uriPathSegment');
            $childPath = ($ownPath === '/' ? '' : $ownPath) . '/' . $segment;
            $children[] = $this->documentTree($child, $childPath);
        }
        if ($children !== []) {
            $data['children'] = $children;
        }
        return $data;
    }

    /**
     * Inhaltsbaum (Collections und Content-Elemente) inkl. Properties
     */
    public function contentTree(Subtree $subtree, ContentSubgraphInterface $subgraph): array
    {
        $node = $subtree->node;
        $kind = $this->kind($node);
        $data = [
            'id' => $node->aggregateId->value,
            'nodeType' => $node->nodeTypeName->value,
            'kind' => $kind,
        ];
        if ($node->classification->isTethered() && $node->name !== null) {
            $data['name'] = $node->name->value;
        }
        if ($kind !== 'collection') {
            $data['label'] = $this->label($node);
        }
        $data += $this->flags($node);
        $properties = $this->properties($node);
        if ($properties !== []) {
            $data['properties'] = $properties;
        }
        $references = $this->references($node, $subgraph);
        if ($references !== []) {
            $data['references'] = $references;
        }
        $children = [];
        foreach ($subtree->children as $child) {
            $children[] = $this->contentTree($child, $subgraph);
        }
        if ($children !== []) {
            $data['children'] = $children;
        }
        return $data;
    }

    /**
     * @return array<string, bool>
     */
    public function flags(Node $node): array
    {
        $flags = [];
        if ($node->tags->contain(SubtreeTag::disabled())) {
            $flags['hidden'] = true;
            if (!$node->tags->withoutInherited()->contain(SubtreeTag::disabled())) {
                $flags['hiddenByParent'] = true;
            }
        }
        if ($node->tags->contain(NeosSubtreeTag::removed())) {
            $flags['removed'] = true;
        }
        return $flags;
    }
}
