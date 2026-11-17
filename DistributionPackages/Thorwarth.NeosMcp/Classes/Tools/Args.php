<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Tools;

use Neos\Flow\Annotations as Flow;
use Thorwarth\NeosMcp\Protocol\InvalidParamsException;

/**
 * Hilfsfunktionen zum typsicheren Auslesen von Tool-Argumenten
 */
#[Flow\Proxy(false)]
final class Args
{
    public static function str(array $args, string $key, string $default = ''): string
    {
        $value = $args[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        if (is_scalar($value)) {
            return trim((string)$value);
        }
        throw new InvalidParamsException(sprintf('Argument "%s" muss eine Zeichenkette sein.', $key));
    }

    public static function requireStr(array $args, string $key): string
    {
        $value = self::str($args, $key);
        if ($value === '') {
            throw new InvalidParamsException(sprintf('Argument "%s" ist erforderlich.', $key));
        }
        return $value;
    }

    public static function int(array $args, string $key, int $default, ?int $min = null, ?int $max = null): int
    {
        $value = $args[$key] ?? null;
        if ($value === null || $value === '') {
            $result = $default;
        } elseif (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) || is_float($value)) {
            $result = (int)$value;
        } else {
            throw new InvalidParamsException(sprintf('Argument "%s" muss eine ganze Zahl sein.', $key));
        }
        if ($min !== null) {
            $result = max($min, $result);
        }
        if ($max !== null) {
            $result = min($max, $result);
        }
        return $result;
    }

    public static function bool(array $args, string $key, bool $default): bool
    {
        $value = $args[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($filtered === null) {
            throw new InvalidParamsException(sprintf('Argument "%s" muss true oder false sein.', $key));
        }
        return $filtered;
    }

    /**
     * @return array<string, mixed>
     */
    public static function map(array $args, string $key): array
    {
        $value = $args[$key] ?? null;
        if ($value === null) {
            return [];
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }
        if (!is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new InvalidParamsException(sprintf('Argument "%s" muss ein JSON-Objekt sein.', $key));
        }
        return $value;
    }

    public static function enum(array $args, string $key, array $allowed, string $default): string
    {
        $value = self::str($args, $key, $default);
        if (!in_array($value, $allowed, true)) {
            throw new InvalidParamsException(sprintf('Argument "%s" muss einer der Werte %s sein.', $key, implode(', ', $allowed)));
        }
        return $value;
    }
}
