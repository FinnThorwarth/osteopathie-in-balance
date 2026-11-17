<?php
namespace Thorwarth\BaseSite\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Cache-Busting für statische Package-Ressourcen.
 *
 * Liefert einen kurzen Hash des Dateiinhalts, der als Query-Parameter an die URL
 * gehängt wird. Ändert sich die Datei (z. B. neues Logo), ändert sich die URL und
 * Browser/CDN laden die neue Version statt der gecachten.
 *
 * Fusion: ${StaticResource.uri('Pkg', 'Public/x.svg') + '?v=' + Thorwarth.Asset.version('Pkg', 'Public/x.svg')}
 *
 * @Flow\Proxy(false)
 */
class AssetHelper implements ProtectedContextAwareInterface
{
    /**
     * @var array<string, string>
     */
    protected static array $cache = [];

    public function version(string $packageKey, string $pathAndFilename): string
    {
        $resourcePath = 'resource://' . $packageKey . '/' . ltrim($pathAndFilename, '/');
        if (!isset(self::$cache[$resourcePath])) {
            $hash = @md5_file($resourcePath);
            self::$cache[$resourcePath] = $hash !== false ? substr($hash, 0, 10) : '0';
        }
        return self::$cache[$resourcePath];
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
