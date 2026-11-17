<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Protocol;

use Neos\Flow\Annotations as Flow;

/**
 * Ungültige Tool-/Methoden-Parameter (JSON-RPC -32602)
 */
#[Flow\Proxy(false)]
class InvalidParamsException extends \InvalidArgumentException
{
}
