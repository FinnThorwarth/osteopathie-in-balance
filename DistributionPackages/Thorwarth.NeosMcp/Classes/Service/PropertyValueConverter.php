<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Service;

use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesForName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Thorwarth\NeosMcp\Protocol\InvalidParamsException;

/**
 * Wandelt JSON-Eingaben anhand der NodeType-Definition in Property-Werte für das Content Repository um
 *
 * @Flow\Scope("singleton")
 */
class PropertyValueConverter
{
    #[Flow\Inject]
    protected AssetRepository $assetRepository;

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function convert(NodeType $nodeType, array $input): array
    {
        $result = [];
        foreach ($input as $name => $value) {
            $name = (string)$name;
            if (!$nodeType->hasProperty($name)) {
                if ($nodeType->hasReference($name)) {
                    throw new InvalidParamsException(sprintf('"%s" ist eine Referenz, keine Property – bitte über "references" setzen.', $name));
                }
                $available = array_values(array_filter(array_keys($nodeType->getProperties()), fn ($property) => !str_starts_with((string)$property, '_')));
                throw new InvalidParamsException(sprintf('Unbekannte Property "%s" für NodeType %s. Verfügbar: %s', $name, $nodeType->name->value, implode(', ', $available)));
            }
            $result[$name] = $this->convertValue($nodeType->getPropertyType($name), $value, $name);
        }
        return $result;
    }

    public function convertValue(string $type, mixed $value, string $name): mixed
    {
        if ($value === null) {
            return null;
        }
        $type = ltrim(trim($type), '\\');
        if (preg_match('/^array<(.+)>$/', $type, $matches) === 1) {
            if (!is_array($value)) {
                throw new InvalidParamsException(sprintf('Property "%s" erwartet eine Liste (%s).', $name, $type));
            }
            return array_values(array_map(fn ($item) => $this->convertValue($matches[1], $item, $name), $value));
        }
        switch (strtolower($type)) {
            case 'string':
                if (is_array($value)) {
                    throw new InvalidParamsException(sprintf('Property "%s" erwartet eine Zeichenkette.', $name));
                }
                return is_bool($value) ? ($value ? '1' : '') : (string)$value;
            case 'int':
            case 'integer':
                if (!is_numeric($value)) {
                    throw new InvalidParamsException(sprintf('Property "%s" erwartet eine ganze Zahl.', $name));
                }
                return (int)$value;
            case 'float':
            case 'double':
                if (!is_numeric($value)) {
                    throw new InvalidParamsException(sprintf('Property "%s" erwartet eine Zahl.', $name));
                }
                return (float)$value;
            case 'bool':
            case 'boolean':
                $boolean = is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($boolean === null) {
                    throw new InvalidParamsException(sprintf('Property "%s" erwartet true oder false.', $name));
                }
                return $boolean;
            case 'array':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
                if (!is_array($value)) {
                    throw new InvalidParamsException(sprintf('Property "%s" erwartet ein Array.', $name));
                }
                return $value;
            case 'datetime':
            case 'datetimeimmutable':
            case 'datetimeinterface':
                try {
                    return new \DateTimeImmutable(is_scalar($value) ? (string)$value : '');
                } catch (\Throwable $e) {
                    throw new InvalidParamsException(sprintf('Property "%s" erwartet ein Datum (ISO 8601, z. B. 2026-10-29T10:00:00+01:00).', $name), 0, $e);
                }
        }
        if (is_a($type, AssetInterface::class, true) || is_a($type, ImageInterface::class, true)) {
            return $this->resolveAsset($value, $name);
        }
        return $value;
    }

    /**
     * Referenzen: { "referenceName": ["node-id", ...] }
     */
    public function convertReferences(NodeType $nodeType, array $references): NodeReferencesToWrite
    {
        $result = NodeReferencesToWrite::createEmpty();
        foreach ($references as $name => $targets) {
            $name = (string)$name;
            if (!$nodeType->hasReference($name)) {
                $available = array_keys($nodeType->getReferences());
                throw new InvalidParamsException(sprintf('Unbekannte Referenz "%s" für NodeType %s. Verfügbar: %s', $name, $nodeType->name->value, $available === [] ? '(keine)' : implode(', ', $available)));
            }
            if ($targets === null || $targets === '') {
                $targets = [];
            } elseif (is_string($targets)) {
                $targets = [$targets];
            }
            if (!is_array($targets)) {
                throw new InvalidParamsException(sprintf('Referenz "%s" erwartet eine Liste von Node-IDs.', $name));
            }
            $ids = [];
            foreach ($targets as $target) {
                $id = is_array($target) ? ($target['id'] ?? null) : $target;
                if (!is_string($id) || trim($id) === '') {
                    throw new InvalidParamsException(sprintf('Referenz "%s": ungültiges Ziel.', $name));
                }
                $ids[] = trim($id);
            }
            try {
                $result = $result->withReference(NodeReferencesForName::fromTargets(ReferenceName::fromString($name), NodeAggregateIds::fromArray($ids)));
            } catch (\Throwable $e) {
                throw new InvalidParamsException(sprintf('Referenz "%s": %s', $name, $e->getMessage()), 0, $e);
            }
        }
        return $result;
    }

    private function resolveAsset(mixed $value, string $name): AssetInterface
    {
        $identifier = is_array($value) ? ($value['asset'] ?? $value['identifier'] ?? $value['id'] ?? null) : $value;
        if (!is_string($identifier) || trim($identifier) === '') {
            throw new InvalidParamsException(sprintf('Property "%s" erwartet eine Asset-ID (siehe list_assets) oder null.', $name));
        }
        $asset = $this->assetRepository->findByIdentifier(trim($identifier));
        if (!$asset instanceof AssetInterface) {
            throw new InvalidParamsException(sprintf('Asset "%s" für Property "%s" wurde nicht gefunden.', $identifier, $name));
        }
        return $asset;
    }
}
