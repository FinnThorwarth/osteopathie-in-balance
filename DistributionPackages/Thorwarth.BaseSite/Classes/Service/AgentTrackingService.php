<?php
declare(strict_types=1);

namespace Thorwarth\BaseSite\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Serverseitiges Matomo-Tracking für Abrufe ohne Browser (Crawler, KI-Agenten).
 *
 * llms.txt, das ARD-Manifest, sitemap.xml und die JSON-APIs enthalten kein JavaScript – das
 * Matomo-Snippet aus Flowpack.Neos.Matomo läuft dort nie. Diese Klasse meldet solche Abrufe
 * über die Matomo-HTTP-Tracking-API (matomo.php) als Seitenaufruf mit dem hierarchischen
 * Seitentitel "<titlePrefix>/<Ressource>/<Agent>". Im Matomo-Report "Seitentitel" ist damit
 * ablesbar, welcher Agent welche Ressource wie oft abruft; der Report "Seiten" zeigt die URLs.
 *
 * Ablauf: Die AgentTrackingMiddleware ruft track() auf, der Hit wird dabei nur vorgemerkt.
 * Gesendet wird in flush(), angebunden an das Flow-Signal Bootstrap::finishedRuntimeRun
 * (siehe Package.php) – also erst, nachdem die Antwort an den Client gegangen ist. Unter
 * PHP-FPM wird die Verbindung zuvor mit fastcgi_finish_request() abgeschlossen, damit kein
 * Crawler auf Matomo wartet. Fehler beim Senden werden nur protokolliert.
 *
 * Konfiguration: Thorwarth.BaseSite.agentTracking (Settings.yaml); Matomo-Host, Protokoll
 * und idSite kommen aus Flowpack.Neos.Matomo.
 *
 * @Flow\Scope("singleton")
 */
class AgentTrackingService
{
    /**
     * @Flow\InjectConfiguration(path="agentTracking", package="Thorwarth.BaseSite")
     * @var array
     */
    protected array $settings = [];

    /**
     * @Flow\InjectConfiguration(package="Flowpack.Neos.Matomo")
     * @var array
     */
    protected array $matomoSettings = [];

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Vorgemerkte Tracking-Requests (Parameter für matomo.php), gesendet in flush()
     *
     * @var array<int, array<string, string>>
     */
    private array $queue = [];

    private bool $shutdownFlushRegistered = false;

    /**
     * Merkt einen Hit vor, falls der Request eine konfigurierte Ressource betrifft.
     *
     * @return string|null Bezeichnung der Ressource, wenn ein Hit vorgemerkt wurde
     */
    public function track(ServerRequestInterface $request, ResponseInterface $response): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true) || $response->getStatusCode() >= 400) {
            return null;
        }

        $resource = $this->matchResource($request->getUri()->getPath());
        if ($resource === null || $this->isOwnFrontendRequest($request)) {
            return null;
        }

        $idSite = $this->resolveSiteId($request);
        if ($idSite === null) {
            return null;
        }

        $userAgent = $request->getHeaderLine('User-Agent');
        $agent = $this->detectAgent($userAgent);

        $parameters = [
            'idsite' => $idSite,
            'rec' => '1',
            'apiv' => '1',
            'url' => (string)$request->getUri(),
            'action_name' => $this->buildPageTitle($resource, $agent),
            'ua' => $userAgent,
            // Matomo verwirft Hits bekannter Bots, sofern nicht ausdrücklich erwünscht
            'bots' => '1',
            'send_image' => '0',
            'rand' => (string)random_int(1, PHP_INT_MAX),
        ];

        $referer = $request->getHeaderLine('Referer');
        if ($referer !== '') {
            $parameters['urlref'] = $referer;
        }
        $language = $request->getHeaderLine('Accept-Language');
        if ($language !== '') {
            $parameters['lang'] = $language;
        }

        $dimensionId = (int)($this->settings['agentDimensionId'] ?? 0);
        if ($dimensionId > 0) {
            $parameters['dimension' . $dimensionId] = $agent;
        }

        // Die IP des Agenten akzeptiert Matomo nur mit token_auth (Schreibrecht); ohne Token
        // zählt es alle Hits unter der IP des Webservers.
        $tokenAuth = trim((string)($this->settings['tokenAuth'] ?? ''));
        $clientIp = $this->resolveClientIp($request);
        if ($tokenAuth !== '' && $clientIp !== null) {
            $parameters['token_auth'] = $tokenAuth;
            $parameters['cip'] = $clientIp;
        }

        $this->queue[] = $parameters;

        if (!$this->shutdownFlushRegistered) {
            // Sicherheitsnetz: Ist das Signal aus Package.php nicht verbunden (Package-Klasse noch nicht
            // eingescannt), sendet der PHP-Shutdown. Nach dem Signal ist die Warteschlange leer, der
            // zweite Aufruf tut dann nichts.
            register_shutdown_function([$this, 'flush']);
            $this->shutdownFlushRegistered = true;
        }

        return $resource;
    }

    /**
     * Sendet die vorgemerkten Hits an Matomo. Slot für Bootstrap::finishedRuntimeRun (Package.php).
     */
    public function flush(): void
    {
        if ($this->queue === []) {
            return;
        }
        $hits = $this->queue;
        $this->queue = [];

        // Unter PHP-FPM die Antwort an den Client abschließen, bevor Matomo gerufen wird
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        foreach ($hits as $parameters) {
            try {
                $this->send($parameters);
                $this->log('debug', sprintf('Agent-Tracking: Hit an Matomo gesendet (%s)', $parameters['action_name']));
            } catch (\Throwable $exception) {
                $this->log('notice', sprintf('Agent-Tracking: Hit konnte nicht an Matomo gesendet werden – %s', $exception->getMessage()));
            }
        }
    }

    private function log(string $level, string $message): void
    {
        try {
            $this->logger->{$level}($message, LogEnvironment::fromMethodName(__METHOD__));
        } catch (\Throwable $exception) {
            // Im PHP-Shutdown (Fallback) kann das Log bereits geschlossen sein
        }
    }

    public function isEnabled(): bool
    {
        return (bool)($this->settings['enabled'] ?? false) && trim((string)($this->matomoSettings['host'] ?? '')) !== '';
    }

    /**
     * Ordnet einen Request-Pfad (mit oder ohne führenden Schrägstrich) einer konfigurierten
     * Ressource zu und liefert deren Bezeichnung.
     */
    public function matchResource(string $path): ?string
    {
        $path = ltrim($path, '/');
        foreach ($this->settings['resources'] ?? [] as $label => $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }
            if (preg_match('~' . $pattern . '~', $path) === 1) {
                return (string)$label;
            }
        }
        return null;
    }

    /**
     * Leitet aus dem User-Agent einen lesbaren Agentennamen ab (Liste aus den Settings, sonst
     * das erste Produkt-Token der Kennung).
     */
    public function detectAgent(string $userAgent): string
    {
        $userAgent = trim($userAgent);
        if ($userAgent === '') {
            return 'Ohne User-Agent';
        }
        foreach ($this->settings['agents'] ?? [] as $needle => $label) {
            $needle = (string)$needle;
            if ($needle !== '' && stripos($userAgent, $needle) !== false) {
                return (string)$label;
            }
        }
        // "Mozilla/5.0 (…)" sagt nichts über den Aufrufer aus; andere Produkt-Tokens schon ("curl/8.4.0")
        if (preg_match('/^([A-Za-z][A-Za-z0-9_.-]*)/', $userAgent, $matches) === 1 && strcasecmp($matches[1], 'Mozilla') !== 0) {
            return 'Sonstige: ' . $matches[1];
        }
        return 'Sonstige: Browser-Kennung';
    }

    /**
     * Abrufe aus dem eigenen Frontend (fetch() der Vue-Komponenten, WebMCP-Tools im Browser)
     * gehören zu einem Besuch, den das Matomo-Snippet bereits zählt.
     */
    private function isOwnFrontendRequest(ServerRequestInterface $request): bool
    {
        $fetchSite = strtolower($request->getHeaderLine('Sec-Fetch-Site'));
        if ($fetchSite === 'same-origin' || $fetchSite === 'same-site') {
            return true;
        }
        $referer = $request->getHeaderLine('Referer');
        if ($referer === '') {
            return false;
        }
        $refererHost = parse_url($referer, PHP_URL_HOST);
        return is_string($refererHost) && strcasecmp($refererHost, $request->getUri()->getHost()) === 0;
    }

    /**
     * idSite aus den eigenen Settings, sonst die der aktuellen Site aus Flowpack.Neos.Matomo
     * (dort je Site-Node-Name konfiguriert, analog zum Tracking-Code im Frontend).
     */
    private function resolveSiteId(ServerRequestInterface $request): ?string
    {
        $idSite = trim((string)($this->settings['idSite'] ?? ''));
        if ($idSite !== '') {
            return $idSite;
        }

        $configured = $this->matomoSettings['idSite'] ?? '';
        if (is_array($configured)) {
            $siteNodeName = null;
            try {
                $siteNodeName = SiteDetectionResult::fromRequest($request)->siteNodeName->value;
            } catch (\Throwable $exception) {
                // Keine Site erkannt (z. B. Request außerhalb des Neos-Frontends) – erste konfigurierte ID nehmen
            }
            $configured = ($siteNodeName !== null && array_key_exists($siteNodeName, $configured))
                ? $configured[$siteNodeName]
                : (reset($configured) ?: '');
        }

        $idSite = trim((string)$configured);
        return $idSite === '' ? null : $idSite;
    }

    private function resolveClientIp(ServerRequestInterface $request): ?string
    {
        $clientIp = $request->getAttribute(ServerRequestAttributes::CLIENT_IP) ?? ($request->getServerParams()['REMOTE_ADDR'] ?? null);
        return is_string($clientIp) && $clientIp !== '' ? $clientIp : null;
    }

    private function buildPageTitle(string $resource, string $agent): string
    {
        $prefix = trim((string)($this->settings['titlePrefix'] ?? 'KI-Agenten'));
        $segments = array_filter([$prefix, $resource, $agent], static fn(string $segment): bool => $segment !== '');
        // "/" trennt in Matomo die Ebenen des Seitentitels
        return implode('/', array_map(static fn(string $segment): string => str_replace('/', '-', $segment), $segments));
    }

    private function endpoint(): string
    {
        $protocol = trim((string)($this->matomoSettings['protocol'] ?? 'https')) ?: 'https';
        $host = rtrim(trim((string)($this->matomoSettings['host'] ?? '')), '/');
        $file = (($this->matomoSettings['system'] ?? 'matomo') === 'piwik') ? 'piwik.php' : 'matomo.php';
        return sprintf('%s://%s/%s', $protocol, $host, $file);
    }

    /**
     * POST an die Tracking-API; kurze Timeouts, keine Weiterleitungen.
     *
     * @param array<string, string> $parameters
     */
    private function send(array $parameters): void
    {
        $endpoint = $this->endpoint();
        $body = http_build_query($parameters, '', '&');
        $connectTimeoutMs = max(100, (int)($this->settings['connectTimeoutMs'] ?? 1000));
        $timeoutMs = max($connectTimeoutMs, (int)($this->settings['timeoutMs'] ?? 2000));

        if (function_exists('curl_init')) {
            $curl = curl_init($endpoint);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_USERAGENT => 'Thorwarth.BaseSite AgentTracking',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT_MS => $connectTimeoutMs,
                CURLOPT_TIMEOUT_MS => $timeoutMs,
            ]);
            $result = curl_exec($curl);
            $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($result === false) {
                throw new \RuntimeException(sprintf('%s nicht erreichbar: %s', $endpoint, $error), 1761750001);
            }
            if ($statusCode >= 400) {
                throw new \RuntimeException(sprintf('%s antwortete mit HTTP %d', $endpoint, $statusCode), 1761750002);
            }
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: Thorwarth.BaseSite AgentTracking\r\n",
                'content' => $body,
                'timeout' => $timeoutMs / 1000,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);
        $result = @file_get_contents($endpoint, false, $context);
        if ($result === false) {
            throw new \RuntimeException(sprintf('%s nicht erreichbar', $endpoint), 1761750003);
        }
        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) === 1 && (int)$matches[1] >= 400) {
            throw new \RuntimeException(sprintf('%s antwortete mit HTTP %d', $endpoint, (int)$matches[1]), 1761750004);
        }
    }
}
