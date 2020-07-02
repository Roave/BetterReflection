<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Ast\Parser;

use PhpParser\ErrorHandler;
use PhpParser\JsonDecoder;
use PhpParser\Parser;

use function array_key_exists;
use function hash;
use function json_encode;
use function strlen;

use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_PRESERVE_ZERO_FRACTION;

/**
 * @internal
 */
final class MemoizingParser implements Parser
{
    /** @var string[] indexed by source hash */
    private array $sourceHashToAst = [];

    private Parser $wrappedParser;

    private JsonDecoder $jsonDecoder;

    public function __construct(Parser $wrappedParser)
    {
        $this->wrappedParser = $wrappedParser;
        $this->jsonDecoder   = new JsonDecoder();
    }

    /**
     * {@inheritDoc}
     */
    public function parse(string $code, ?ErrorHandler $errorHandler = null): ?array
    {
        // note: this code is mathematically buggy by default, as we are using a hash to identify
        //       cache entries. The string length is added to further reduce likeliness (although
        //       already imperceptible) of key collisions.
        //       In the "real world", this code will work just fine.
        $hash = hash('sha256', $code) . ':' . strlen($code);

        if (array_key_exists($hash, $this->sourceHashToAst)) {
            return $this->jsonDecoder->decode($this->sourceHashToAst[$hash]);
        }

        $ast                          = $this->wrappedParser->parse($code, $errorHandler);
        $this->sourceHashToAst[$hash] = json_encode($ast, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        return $ast;
    }
}
