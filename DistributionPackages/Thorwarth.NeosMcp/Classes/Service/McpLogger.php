<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Audit-Log für MCP-Aufrufe (Data/Logs/NeosMcp.log)
 *
 * @Flow\Scope("singleton")
 */
class McpLogger
{
    #[Flow\Inject]
    protected PsrLoggerFactoryInterface $loggerFactory;

    protected ?LoggerInterface $logger = null;

    public function info(string $message, array $context = []): void
    {
        $this->getLogger()->info($this->format($message, $context));
    }

    public function warning(string $message, array $context = []): void
    {
        $this->getLogger()->warning($this->format($message, $context));
    }

    public function error(string $message, array $context = []): void
    {
        $this->getLogger()->error($this->format($message, $context));
    }

    private function format(string $message, array $context): string
    {
        if ($context === []) {
            return $message;
        }
        $encoded = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (is_string($encoded) && strlen($encoded) > 2000) {
            $encoded = substr($encoded, 0, 2000) . '…';
        }
        return $message . ' ' . $encoded;
    }

    private function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = $this->loggerFactory->get('neosMcpLogger');
        }
        return $this->logger;
    }
}
