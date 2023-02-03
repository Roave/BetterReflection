<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Node;
use Roave\BetterReflection\Util\Exception\InvalidNodePosition;
use Roave\BetterReflection\Util\Exception\NoNodePosition;

use function strlen;
use function strrpos;

/** @internal */
final class CalculateReflectionColumn
{
    /**
     * @return positive-int
     *
     * @throws InvalidNodePosition
     * @throws NoNodePosition
     *
     * @psalm-pure
     */
    public static function getStartColumn(string $source, Node $node): int
    {
        if (! $node->hasAttribute('startFilePos')) {
            throw NoNodePosition::fromNode($node);
        }

        return self::calculateColumn($source, $node->getStartFilePos());
    }

    /**
     * @return positive-int
     *
     * @throws InvalidNodePosition
     * @throws NoNodePosition
     *
     * @psalm-pure
     */
    public static function getEndColumn(string $source, Node $node): int
    {
        if (! $node->hasAttribute('endFilePos')) {
            throw NoNodePosition::fromNode($node);
        }

        return self::calculateColumn($source, $node->getEndFilePos());
    }

    /**
     * @return positive-int
     *
     * @throws InvalidNodePosition
     *
     * @psalm-pure
     */
    private static function calculateColumn(string $source, int $position): int
    {
        $sourceLength = strlen($source);

        if ($position >= $sourceLength) {
            throw InvalidNodePosition::fromPosition($position);
        }

        $lineStartPosition = strrpos($source, "\n", $position - $sourceLength);

        /** @psalm-var positive-int */
        return $lineStartPosition === false ? $position + 1 : $position - $lineStartPosition;
    }
}
