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
        
        if (strlen($q) >= 2) {
            // For now, let's return sample data
            // TODO: Implement proper Neos 9 content repository search
            $samplePages = [
                [
                    'identifier' => '1',
                    'title' => 'Erdbestattung',
                    'uri' => '/leistungen/erdbestattung',
                    'teaser' => 'Die Erdbestattung ist eine traditionelle Form der Bestattung, bei der der Verstorbene in einem Sarg in der Erde beigesetzt wird.'
                ],
                [
                    'identifier' => '2',
                    'title' => 'Feuerbestattung',
                    'uri' => '/leistungen/feuerbestattung',
                    'teaser' => 'Bei der Feuerbestattung wird der Verstorbene in einem Krematorium eingeäschert. Die Asche wird anschließend in einer Urne beigesetzt.'
                ],
                [
                    'identifier' => '3',
                    'title' => 'Seebestattung',
                    'uri' => '/leistungen/seebestattung',
                    'teaser' => 'Die Seebestattung ist eine besondere Form der Bestattung, bei der die Asche des Verstorbenen dem Meer übergeben wird.'
                ],
                [
                    'identifier' => '4',
                    'title' => 'Über uns',
                    'uri' => '/ueber-uns',
                    'teaser' => 'Caspary Bestattungen - Ihr vertrauensvoller Partner in schweren Zeiten. Wir begleiten Sie mit Respekt und Würde.'
                ],
                [
                    'identifier' => '5',
                    'title' => 'Kontakt',
                    'uri' => '/kontakt',
                    'teaser' => 'Nehmen Sie Kontakt mit uns auf. Wir sind rund um die Uhr für Sie erreichbar.'
                ]
            ];
            
            // Filter results based on search query
            foreach ($samplePages as $page) {
                $searchableContent = strtolower($page['title'] . ' ' . $page['teaser']);
                if (stripos($searchableContent, strtolower($q)) !== false) {
                    $results[] = $page;
                }
            }
        }
        
        $this->view->assign('value', ['results' => $results]);
    }
}