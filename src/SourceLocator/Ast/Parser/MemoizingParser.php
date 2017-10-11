<?php
declare(strict_types=1);

namespace Rector\BetterReflection\SourceLocator\Ast\Parser;

use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Parser;

/**
 * @internal
 */
final class MemoizingParser implements Parser
{
    /**
     * @var Node[][]|null[] indexed by source hash
     */
    private $sourceHashToAst = [];

    /**
     * @var Parser
     */
    private $wrappedParser;

    public function __construct(Parser $wrappedParser)
    {
        $this->wrappedParser = $wrappedParser;
    }

    /**
     * {@inheritDoc}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function parse(string $code, ?ErrorHandler $errorHandler = null) : ?array
    {
        // note: this code is mathematically buggy by default, as we are using a hash to identify
        //       cache entries. The string length is added to further reduce likeliness (although
        //       already imperceptible) of key collisions.
        //       In the "real world", this code will work just fine.
        $hash = \hash('sha256', $code) . ':' . \strlen($code);

        if (\array_key_exists($hash, $this->sourceHashToAst)) {
            return $this->sourceHashToAst[$hash];
        }

        return $this->sourceHashToAst[$hash] = $this->wrappedParser->parse($code, $errorHandler);
    }
}
