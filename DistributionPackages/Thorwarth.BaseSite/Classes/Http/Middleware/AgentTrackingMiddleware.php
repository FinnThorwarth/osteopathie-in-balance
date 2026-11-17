<?php
declare(strict_types=1);

namespace Thorwarth\BaseSite\Http\Middleware;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Thorwarth\BaseSite\Service\AgentTrackingService;

/**
 * Merkt Matomo-Hits für Abrufe ohne Browser vor (llms.txt, ARD-Manifest, sitemap.xml, APIs).
 *
 * Läuft nach der Site-Erkennung von Neos ("after detectSite"), damit der Service die Matomo-idSite
 * der aktuellen Site auflösen kann. Gesendet wird erst nach der Antwort, siehe AgentTrackingService.
 */
class AgentTrackingMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var AgentTrackingService
     */
    protected $agentTrackingService;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $response = $next->handle($request);

        try {
            $this->agentTrackingService->track($request, $response);
        } catch (\Throwable $exception) {
            // Tracking darf die Auslieferung nie beeinträchtigen
            $this->logger->notice(
                sprintf('Agent-Tracking übersprungen: %s', $exception->getMessage()),
                LogEnvironment::fromMethodName(__METHOD__)
            );
        }

        return $response;
    }
}
