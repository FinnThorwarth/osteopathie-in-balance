<?php
namespace Caspary\Site\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class ImageHelper implements ProtectedContextAwareInterface
{
    /**
     * Calculate height from width and aspect ratio
     *
     * @param int $width
     * @param string $ratio Format: "width:height" (e.g., "16:9")
     * @return array
     */
    public function calculateDimensions(int $width, string $ratio): array
    {
        if (!$ratio) {
            return ['width' => $width, 'height' => null];
        }
        
        $parts = explode(':', $ratio);
        if (count($parts) !== 2) {
            return ['width' => $width, 'height' => null];
        }
        
        $ratioWidth = (float) $parts[0];
        $ratioHeight = (float) $parts[1];
        
        if ($ratioWidth <= 0 || $ratioHeight <= 0) {
            return ['width' => $width, 'height' => null];
        }
        
        $height = round($width * ($ratioHeight / $ratioWidth));
        
        return [
            'width' => $width,
            'height' => (int) $height
        ];
    }
    
    /**
     * Get aspect ratio as decimal
     *
     * @param string $ratio
     * @return float|null
     */
    public function getRatioDecimal(string $ratio): ?float
    {
        if (!$ratio) {
            return null;
        }
        
        $parts = explode(':', $ratio);
        if (count($parts) !== 2) {
            return null;
        }
        
        $width = (float) $parts[0];
        $height = (float) $parts[1];
        
        if ($width <= 0 || $height <= 0) {
            return null;
        }
        
        return $width / $height;
    }
    
    /**
     * Generate srcset string from variants
     *
     * @param array $variants
     * @param string|null $ratio
     * @return array
     */
    public function generateSrcsetData(array $variants, ?string $ratio = null): array
    {
        $srcsetData = [];
        
        foreach ($variants as $key => $variant) {
            $width = $variant['width'] ?? null;
            if (!$width) {
                continue;
            }
            
            $dimensions = $ratio ? $this->calculateDimensions($width, $ratio) : $variant;
            
            $srcsetData[$key] = [
                'width' => $dimensions['width'],
                'height' => $dimensions['height'] ?? null,
                'descriptor' => $width . 'w'
            ];
        }
        
        return $srcsetData;
    }
    
    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}