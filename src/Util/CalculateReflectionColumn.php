<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Node;
use Roave\BetterReflection\Util\Exception\InvalidNodePosition;
use Roave\BetterReflection\Util\Exception\NoNodePosition;

use function strlen;
use function strrpos;

/**
 * @internal
 */
final class CalculateReflectionColumn
{
    /**
     * @throws InvalidNodePosition
     * @throws NoNodePosition
     */
    public static function getStartColumn(string $source, Node $node): int
    {
        if (! $node->hasAttribute('startFilePos')) {
            throw NoNodePosition::fromNode($node);
        }

        return self::calculateColumn($source, $node->getStartFilePos());
    }

    /**
     * @throws InvalidNodePosition
     * @throws NoNodePosition
     */
    public static function getEndColumn(string $source, Node $node): int
    {
        if (! $node->hasAttribute('endFilePos')) {
            throw NoNodePosition::fromNode($node);
        }

        return self::calculateColumn($source, $node->getEndFilePos());
    }

    /**
     * @throws InvalidNodePosition
     */
    private static function calculateColumn(string $source, int $position): int
    {
        $sourceLength = strlen($source);

        if ($position > $sourceLength) {
            throw InvalidNodePosition::fromPosition($position);
        }

        $lineStartPosition = strrpos($source, "\n", $position - $sourceLength);
        if ($lineStartPosition === false) {
            return $position + 1;
        }

        return $position - $lineStartPosition;
    }
}
