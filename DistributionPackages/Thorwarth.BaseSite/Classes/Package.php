<?php
declare(strict_types=1);

namespace Thorwarth\BaseSite;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Thorwarth\BaseSite\Service\AgentTrackingService;

/**
 * Hinweis: Flow liest die Package-Klasse beim Paket-Scan ein (automatisch bei "composer install",
 * sonst "./flow package:rescan").
 */
class Package extends BasePackage
{
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        // Matomo-Hits für Crawler/KI-Agenten erst senden, wenn die Antwort beim Client ist
        $dispatcher->connect(Bootstrap::class, 'finishedRuntimeRun', AgentTrackingService::class, 'flush', false);
    }
}
