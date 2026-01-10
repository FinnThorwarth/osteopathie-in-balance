<?php

namespace Thorwarth\BaseSite\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * @Flow\Scope("singleton")
 */
class SearchController extends ActionController
{
    protected $defaultViewObjectName = JsonView::class;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var NodeUriBuilderFactory
     */
    protected $nodeUriBuilderFactory;

    /**
     * Search action for API endpoint
     *
     * @param string $q Search query
     * @return void
     */
    public function searchAction(string $q = '')
    {
        $results = [];

        if (strlen($q) >= 2) {
            $results = $this->performSearch($q);
        }

        $this->view->assign('value', ['results' => $results]);
    }

    /**
     * Perform search - uses SimpleSearch if available, otherwise fallback
     *
     * @param string $searchTerm
     * @return array
     */
    protected function performSearch(string $searchTerm): array
    {
        $queryBuilderClass = 'Neos\\ContentRepository\\Search\\Search\\QueryBuilderInterface';

        if ($this->objectManager->isRegistered($queryBuilderClass)) {
            try {
                return $this->performSimpleSearch($searchTerm, $queryBuilderClass);
            } catch (\Exception $e) {
                // Fallback if SimpleSearch fails
            }
        }

        return $this->getSampleResults($searchTerm);
    }

    /**
     * Perform fulltext search using SimpleSearch
     *
     * @param string $searchTerm
     * @param string $queryBuilderClass
     * @return array
     */
    protected function performSimpleSearch(string $searchTerm, string $queryBuilderClass): array
    {
        $results = [];

        $contentRepository = $this->contentRepositoryRegistry->get(
            ContentRepositoryId::fromString('default')
        );

        $contentGraph = $contentRepository->getContentGraph(WorkspaceName::forLive());
        $dimensionSpacePoint = DimensionSpacePoint::fromArray(['language' => 'de']);

        $rootNodeAggregate = $contentGraph->findRootNodeAggregateByType(
            NodeTypeNameFactory::forSite()
        );

        if ($rootNodeAggregate === null) {
            return [];
        }

        $siteNode = $rootNodeAggregate->getNodes()->first();

        if ($siteNode === null) {
            return [];
        }

        $queryBuilder = $this->objectManager->get($queryBuilderClass);
        $searchResults = $queryBuilder
            ->query($siteNode)
            ->fulltext($searchTerm)
            ->nodeType('Neos.Neos:Document')
            ->execute();

        $nodeUriBuilder = $this->nodeUriBuilderFactory->forRequest($this->request->getHttpRequest());

        foreach ($searchResults as $node) {
            if (!$node instanceof Node) {
                continue;
            }

            $title = $node->getProperty('title') ?? '';
            $teaser = $this->extractTeaser($node);

            $nodeAddress = NodeAddress::create(
                $contentRepository->id,
                WorkspaceName::forLive(),
                $dimensionSpacePoint,
                $node->aggregateId
            );

            try {
                $uri = (string)$nodeUriBuilder->uriFor($nodeAddress);
            } catch (\Exception $e) {
                $uri = '';
            }

            $results[] = [
                'identifier' => (string)$node->aggregateId,
                'title' => $title,
                'uri' => $uri,
                'teaser' => $teaser
            ];
        }

        return $results;
    }

    /**
     * Extract teaser text from node
     *
     * @param Node $node
     * @return string
     */
    protected function extractTeaser(Node $node): string
    {
        $teaser = $node->getProperty('metaDescription')
            ?? $node->getProperty('teaser')
            ?? $node->getProperty('abstract')
            ?? '';

        if (empty($teaser)) {
            $title = $node->getProperty('title') ?? '';
            $teaser = $title ? "Weitere Informationen zu: {$title}" : '';
        }

        $teaser = strip_tags($teaser);
        if (strlen($teaser) > 200) {
            $teaser = substr($teaser, 0, 197) . '...';
        }

        return $teaser;
    }

    /**
     * Get sample results as fallback
     *
     * @param string $q
     * @return array
     */
    protected function getSampleResults(string $q): array
    {
        $samplePages = [];

        $results = [];
        $searchQuery = strtolower($q);

        foreach ($samplePages as $page) {
            $searchableContent = strtolower($page['title'] . ' ' . $page['teaser'] . ' ' . str_replace('/', ' ', $page['uri']));

            if (stripos($searchableContent, $searchQuery) !== false) {
                $results[] = $page;
            }
        }

        return $results;
    }
}
