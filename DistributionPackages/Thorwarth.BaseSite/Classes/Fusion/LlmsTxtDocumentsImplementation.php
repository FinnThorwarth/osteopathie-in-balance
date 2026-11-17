<?php

declare(strict_types=1);

namespace Thorwarth\BaseSite\Fusion;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Sammelt den indexierbaren Dokumentbaum unterhalb eines Startpunkts (normalerweise der Site-Node)
 * für die dynamische llms.txt (https://llmstxt.org).
 *
 * Ergebnis:
 *
 *   [
 *     'site' => ['node' => Node, 'title' => string, 'description' => string],
 *     'entries' => [
 *       [
 *         'node' => Node,
 *         'title' => string,
 *         'sectionHeading' => string, // Titel, eindeutig gemacht (z. B. "Leistungen (leistungen)")
 *         'description' => string,
 *         'indexable' => bool,   // false = noindex / Canonical woanders (Unterseiten können trotzdem gelistet werden)
 *         'optional' => bool,    // im Menü versteckte Hauptseiten (Impressum, Datenschutz, …) -> "## Optional"
 *         'descendants' => list<array{node: Node, title: string, description: string, depth: int}>,
 *       ],
 *       ...
 *     ]
 *   ]
 *
 * `entries` sind die direkten Dokument-Kinder des Startpunkts, `descendants` die flache, in
 * Baumreihenfolge sortierte Liste ihrer indexierbaren Unterseiten. Die Sichtbarkeitsregeln
 * entsprechen der XML-Sitemap von Neos.Seo (noindex, canonical); zusätzlich werden ganze
 * Teilbäume der konfigurierten `excludedNodeTypes` übersprungen.
 */
class LlmsTxtDocumentsImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $result = null;

    private ?ContentSubgraphInterface $subgraph = null;

    private ?NodeTypeManager $nodeTypeManager = null;

    /**
     * @var list<string>
     */
    private array $excludedNodeTypes = [];

    /**
     * @var list<string>
     */
    private array $descriptionProperties = [];

    private int $descriptionMaxLength = 200;

    /**
     * @return array<string, mixed>
     */
    public function evaluate(): array
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $startingPoint = $this->fusionValue('startingPoint');
        if (!$startingPoint instanceof Node) {
            return $this->result = ['site' => null, 'entries' => []];
        }

        $this->excludedNodeTypes = array_values(array_filter((array)($this->fusionValue('excludedNodeTypes') ?? [])));
        $this->descriptionProperties = array_values(array_filter((array)($this->fusionValue('descriptionProperties') ?? [])));
        $this->descriptionMaxLength = max(40, (int)($this->fusionValue('descriptionMaxLength') ?: 200));

        $this->subgraph = $this->contentRepositoryRegistry->subgraphForNode($startingPoint);
        $this->nodeTypeManager = $this->contentRepositoryRegistry->get($startingPoint->contentRepositoryId)->getNodeTypeManager();

        $documentNodeTypeNames = NodeTypeNames::fromArray(array_map(
            fn(NodeType $nodeType): NodeTypeName => $nodeType->name,
            $this->nodeTypeManager->getSubNodeTypes('Neos.Neos:Document', false)
        ));
        $subtree = $this->subgraph->findSubtree(
            $startingPoint->aggregateId,
            FindSubtreeFilter::create(nodeTypes: NodeTypeCriteria::create($documentNodeTypeNames, NodeTypeNames::createEmpty()))
        );
        if ($subtree === null) {
            return $this->result = ['site' => null, 'entries' => []];
        }

        $entries = [];
        $usedHeadings = [];
        foreach ($subtree->children as $childSubtree) {
            $node = $childSubtree->node;
            if ($this->isExcludedNodeType($node)) {
                continue;
            }
            $descendants = [];
            foreach ($childSubtree->children as $grandChildSubtree) {
                $this->collectDescendants($descendants, $grandChildSubtree, 2);
            }
            $indexable = $this->isIndexable($node);
            if (!$indexable && $descendants === []) {
                continue;
            }
            $title = $this->resolveTitle($node);
            // Zwei Hauptseiten mit gleichem Titel ergäben zwei identische "## …"-Sektionen
            $sectionHeading = $title;
            if (isset($usedHeadings[mb_strtolower($sectionHeading)])) {
                $sectionHeading .= ' (' . ($node->getProperty('uriPathSegment') ?: $node->aggregateId->value) . ')';
            }
            $usedHeadings[mb_strtolower($sectionHeading)] = true;
            $entries[] = [
                'node' => $node,
                'title' => $title,
                'sectionHeading' => $sectionHeading,
                'description' => $this->resolveDescription($node),
                'indexable' => $indexable,
                'optional' => $node->getProperty('hiddenInMenu') === true,
                'descendants' => $descendants,
            ];
        }

        return $this->result = [
            'site' => [
                'node' => $startingPoint,
                'title' => $this->resolveTitle($startingPoint),
                'description' => $this->resolveDescription($startingPoint),
            ],
            'entries' => $entries,
        ];
    }

    /**
     * @param list<array<string, mixed>> $descendants
     */
    private function collectDescendants(array &$descendants, Subtree $subtree, int $depth): void
    {
        $node = $subtree->node;
        if ($this->isExcludedNodeType($node)) {
            return;
        }
        if ($this->isIndexable($node)) {
            $descendants[] = [
                'node' => $node,
                'title' => $this->resolveTitle($node),
                'description' => $this->resolveDescription($node),
                'depth' => $depth,
            ];
        }
        foreach ($subtree->children as $childSubtree) {
            $this->collectDescendants($descendants, $childSubtree, $depth + 1);
        }
    }

    private function isExcludedNodeType(Node $node): bool
    {
        $nodeType = $this->nodeTypeManager->getNodeType($node->nodeTypeName);
        if ($nodeType === null) {
            return true;
        }
        foreach ($this->excludedNodeTypes as $excludedNodeType) {
            if ($nodeType->isOfType($excludedNodeType)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gleiche Regeln wie die Sitemap von Neos.Seo: kein noindex, kein Canonical auf eine andere Seite.
     */
    private function isIndexable(Node $node): bool
    {
        $nodeType = $this->nodeTypeManager->getNodeType($node->nodeTypeName);
        if ($nodeType === null || $nodeType->isOfType('Neos.Seo:NoindexMixin')) {
            return false;
        }
        if ($node->getProperty('metaRobotsNoindex') === true) {
            return false;
        }
        $canonicalLink = (string)($node->getProperty('canonicalLink') ?? '');
        return $canonicalLink === '' || substr($canonicalLink, 7) === $node->aggregateId->value;
    }

    private function resolveTitle(Node $node): string
    {
        $title = '';
        if ($node->nodeTypeName->value === 'Thorwarth.BaseSite:Document.Author') {
            $title = (string)($node->getProperty('fullName') ?? '');
        }
        if ($title === '') {
            $title = (string)($node->getProperty('title') ?? '');
        }
        if ($title === '') {
            $title = (string)($node->name?->value ?? $node->aggregateId->value);
        }
        // Markdown-Link nicht zerstören
        return str_replace(['[', ']'], ['(', ')'], $this->toPlainText($title));
    }

    /**
     * Reihenfolge: metaDescription -> Auszug (Journal) -> Position (Autor) -> erster Inhaltstext.
     */
    private function resolveDescription(Node $node): string
    {
        $metaDescription = $this->toPlainText((string)($node->getProperty('metaDescription') ?? ''));
        if ($metaDescription !== '') {
            return $metaDescription;
        }

        $excerpt = $this->toPlainText((string)($node->getProperty('excerpt') ?? ''));
        if ($excerpt !== '') {
            return $this->crop($excerpt);
        }

        if ($node->nodeTypeName->value === 'Thorwarth.BaseSite:Document.Author') {
            $jobTitle = $this->toPlainText((string)($node->getProperty('jobTitle') ?? ''));
            $bio = $this->toPlainText((string)($node->getProperty('bio') ?? ''));
            $description = trim($jobTitle . ($jobTitle !== '' && $bio !== '' ? ' – ' : '') . $bio);
            if ($description !== '') {
                return $this->crop($description);
            }
        }

        return $this->crop($this->findFirstContentText($node));
    }

    /**
     * Durchsucht die eigenen Inhalte des Dokuments (Content-Collections und deren Inhalte, keine
     * Unterseiten) in Render-Reihenfolge und liefert den ersten aussagekräftigen Text aus einer der
     * konfigurierten Properties. Kurze Texte (z. B. Taglines) nur, wenn es nichts Längeres gibt.
     */
    private function findFirstContentText(Node $document): string
    {
        if ($this->descriptionProperties === []) {
            return '';
        }

        $subtree = $this->subgraph->findSubtree(
            $document->aggregateId,
            FindSubtreeFilter::create(nodeTypes: 'Neos.Neos:ContentCollection,Neos.Neos:Content')
        );
        if ($subtree === null) {
            return '';
        }

        $shortFallback = '';
        foreach ($subtree->children as $childSubtree) {
            $text = $this->findTextInSubtree($childSubtree, $shortFallback);
            if ($text !== '') {
                return $text;
            }
        }

        return $shortFallback;
    }

    private function findTextInSubtree(Subtree $subtree, string &$shortFallback): string
    {
        $node = $subtree->node;
        foreach ($this->descriptionProperties as $propertyName) {
            if (!$node->hasProperty($propertyName)) {
                continue;
            }
            $text = $this->toPlainText((string)($node->getProperty($propertyName) ?? ''));
            if ($text === '') {
                continue;
            }
            if (mb_strlen($text) >= 40) {
                return $text;
            }
            if ($shortFallback === '') {
                $shortFallback = $text;
            }
        }
        foreach ($subtree->children as $childSubtree) {
            $text = $this->findTextInSubtree($childSubtree, $shortFallback);
            if ($text !== '') {
                return $text;
            }
        }
        return '';
    }

    private function toPlainText(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\s\x{00A0}]+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function crop(string $text): string
    {
        if (mb_strlen($text) <= $this->descriptionMaxLength) {
            return $text;
        }
        $cropped = mb_substr($text, 0, $this->descriptionMaxLength);
        $lastSpace = mb_strrpos($cropped, ' ');
        if ($lastSpace !== false && $lastSpace > (int)($this->descriptionMaxLength * 0.6)) {
            $cropped = mb_substr($cropped, 0, $lastSpace);
        }
        return rtrim($cropped, " ,;:-–") . ' …';
    }
}
