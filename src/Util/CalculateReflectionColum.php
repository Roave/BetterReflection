<?php
declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Node;
use Roave\BetterReflection\Util\Exception\InvalidNodePosition;
use Roave\BetterReflection\Util\Exception\NoNodePosition;

/**
 * @internal
 */
final class CalculateReflectionColum
{
    /**
     * @param string $source
     * @param \PhpParser\Node $node
     * @return int
     * @throws \Roave\BetterReflection\Util\Exception\InvalidNodePosition
     * @throws \Roave\BetterReflection\Util\Exception\NoNodePosition
     */
    public static function getStartColumn(string $source, Node $node) : int
    {
        if ( ! $node->hasAttribute('startFilePos')) {
            throw NoNodePosition::fromNode($node);
        }

        return self::calculateColumn($source, $node->getAttribute('startFilePos'));
    }

    /**
     * @param string $source
     * @param \PhpParser\Node $node
     * @return int
     * @throws \Roave\BetterReflection\Util\Exception\InvalidNodePosition
     * @throws \Roave\BetterReflection\Util\Exception\NoNodePosition
     */
    public static function getEndColumn(string $source, Node $node) : int
    {
        if ( ! $node->hasAttribute('endFilePos')) {
            throw NoNodePosition::fromNode($node);
        }

        return self::calculateColumn($source, $node->getAttribute('endFilePos'));
    }

    /**
     * @param string $source
     * @param int $position
     * @return int
     * @throws \Roave\BetterReflection\Util\Exception\InvalidNodePosition
     */
    private static function calculateColumn(string $source, int $position) : int
    {
        $sourceLength = \strlen($source);

        if ($position > $sourceLength) {
            throw InvalidNodePosition::fromPosition($position);
        }

        $lineStartPosition = \strrpos($source, "\n", $position - $sourceLength);
        if (false === $lineStartPosition) {
            return $position + 1;
        }

        return $position - $lineStartPosition;
    }
}
