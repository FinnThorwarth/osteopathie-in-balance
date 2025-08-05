<?php
namespace Caspary\Site\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;

/**
 * @Flow\Scope("singleton")
 */
class SearchController extends ActionController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * Search action for API endpoint
     * 
     * @param string $q Search query
     * @return void
     */
    public function searchAction(string $q = '')
    {
        $results = [];
        
        // For now, let's just return a simple test response
        if (strlen($q) >= 2) {
            $results[] = [
                'identifier' => 'test-1',
                'title' => 'Test Result for: ' . $q,
                'uri' => '/test-page',
                'teaser' => 'This is a test result for your search query.'
            ];
        }
        
        $this->view->assign('value', ['results' => $results]);
    }
}