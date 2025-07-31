<?php
namespace Caspary\Site\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\ContentRepository\Domain\Service\NodeSearchServiceInterface;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * @Flow\Scope("singleton")
 */
class SearchController extends ActionController
{
    /**
     * @Flow\Inject
     * @var NodeSearchServiceInterface
     */
    protected $nodeSearchService;

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
            try {
                // Get the current context
                $context = $this->getContentContext();
                
                // Get the site node
                $siteNode = $context->getCurrentSiteNode();
                
                if ($siteNode) {
                    // Create a FlowQuery to search documents
                    $flowQuery = new FlowQuery([$siteNode]);
                    
                    // Find all document nodes that are not hidden
                    $documentNodes = $flowQuery
                        ->find('[instanceof Neos.Neos:Document][_hidden != true]')
                        ->get();
                    
                    // Filter nodes by search term
                    foreach ($documentNodes as $node) {
                        $title = $node->getProperty('title') ?? '';
                        $teaser = $node->getProperty('teaser') ?? '';
                        $content = '';
                        
                        // Get text content from the node
                        $mainContent = $node->getNode('main');
                        if ($mainContent) {
                            $textNodes = (new FlowQuery([$mainContent]))
                                ->find('[instanceof Neos.NodeTypes:Text]')
                                ->get();
                            
                            foreach ($textNodes as $textNode) {
                                $content .= ' ' . strip_tags($textNode->getProperty('text') ?? '');
                            }
                        }
                        
                        // Simple search in title, teaser and content
                        $searchContent = strtolower($title . ' ' . $teaser . ' ' . $content);
                        if (stripos($searchContent, $q) !== false) {
                            $results[] = [
                                'identifier' => $node->getIdentifier(),
                                'title' => $title,
                                'uri' => $this->getNodeUri($node),
                                'teaser' => $teaser ?: substr(strip_tags($content), 0, 200)
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error and return empty results
                $this->logger->error('Search error: ' . $e->getMessage());
            }
        }
        
        $this->view->assign('value', ['results' => $results]);
    }
    
    /**
     * Get content context
     * 
     * @return ContentContext
     */
    protected function getContentContext()
    {
        $contextFactory = $this->objectManager->get(\Neos\Neos\Domain\Service\ContentContextFactory::class);
        return $contextFactory->create([
            'workspaceName' => 'live',
            'currentDateTime' => new \DateTime(),
            'dimensions' => [],
            'targetDimensions' => [],
            'invisibleContentShown' => false,
            'removedContentShown' => false,
            'inaccessibleContentShown' => false
        ]);
    }
    
    /**
     * Get node URI
     * 
     * @param \Neos\ContentRepository\Domain\Model\NodeInterface $node
     * @return string
     */
    protected function getNodeUri($node)
    {
        try {
            $nodeUriBuilder = $this->controllerContext
                ->getUriBuilder()
                ->reset()
                ->setFormat('html')
                ->setCreateAbsoluteUri(false);
                
            return $nodeUriBuilder->uriFor(
                'show',
                ['node' => $node],
                'Frontend\Node',
                'Neos.Neos'
            );
        } catch (\Exception $e) {
            return '#';
        }
    }
}