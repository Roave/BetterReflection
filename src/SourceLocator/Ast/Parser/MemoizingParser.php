<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Parser;

use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Parser;

use function array_key_exists;
use function hash;
use function serialize;
use function sprintf;
use function strlen;
use function unserialize;

/** @internal */
final class MemoizingParser implements Parser
{
    /** @var array<string, string> indexed by source hash */
    private array $sourceHashToAst = [];

    public function __construct(private Parser $wrappedParser)
    {
    }

    public function parse(string $code, ErrorHandler|null $errorHandler = null): array|null
    {
        // note: this code is mathematically buggy by default, as we are using a hash to identify
        //       cache entries. The string length is added to further reduce likeliness (although
        //       already imperceptible) of key collisions.
        //       In the "real world", this code will work just fine.
        $hash = sprintf('%s:%d', hash('sha256', $code), strlen($code));

        if (array_key_exists($hash, $this->sourceHashToAst)) {
            /** @var Node\Stmt[]|null $ast */
            $ast = unserialize($this->sourceHashToAst[$hash]);

            return $ast;
        }

        $ast                          = $this->wrappedParser->parse($code, $errorHandler);
        $this->sourceHashToAst[$hash] = serialize($ast);

        return $ast;
    }
}
