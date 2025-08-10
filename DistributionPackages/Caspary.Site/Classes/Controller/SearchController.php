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
            // TODO: Implement proper Neos 9 content repository search when API is stable
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
                ],
                [
                    'identifier' => '6',
                    'title' => 'Trauerbegleitung',
                    'uri' => '/leistungen/trauerbegleitung',
                    'teaser' => 'Wir begleiten Sie und Ihre Familie in der schweren Zeit der Trauer mit professioneller Unterstützung.'
                ],
                [
                    'identifier' => '7',
                    'title' => 'Vorsorge',
                    'uri' => '/vorsorge',
                    'teaser' => 'Mit einer Bestattungsvorsorge können Sie Ihre Wünsche festlegen und Ihre Angehörigen entlasten.'
                ]
            ];
            
            // Filter results based on search query
            $searchQuery = strtolower($q);
            foreach ($samplePages as $page) {
                // Search in all text fields
                $searchableContent = strtolower(
                    $page['title'] . ' ' . 
                    $page['teaser'] . ' ' . 
                    str_replace('/', ' ', $page['uri']) // Also search in URL segments
                );
                
                if (stripos($searchableContent, $searchQuery) !== false) {
                    $results[] = $page;
                }
            }
            
            // Sort results by relevance (title matches first)
            usort($results, function($a, $b) use ($searchQuery) {
                $aInTitle = stripos(strtolower($a['title']), $searchQuery) !== false ? 2 : 0;
                $bInTitle = stripos(strtolower($b['title']), $searchQuery) !== false ? 2 : 0;
                
                // Exact title match gets highest priority
                if (strtolower($a['title']) === $searchQuery) $aInTitle = 3;
                if (strtolower($b['title']) === $searchQuery) $bInTitle = 3;
                
                return $bInTitle - $aInTitle;
            });
        }
        
        $this->view->assign('value', ['results' => $results]);
    }
}