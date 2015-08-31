<?php

namespace BetterReflection\SourceLocator;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Reflection\ClassReflection;

/**
 * Function that generates a stub source from a given reflection instance.
 */
final class SourceStubber
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    /**
     * @var Standard
     */
    private $prettyPrinter;

    public function __construct()
    {
        $this->parser        = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->prettyPrinter = new Standard();
    }

    /**
     * @param ClassReflection $reflection
     *
     * @return string
     */
    public function __invoke(ClassReflection $reflection)
    {
        $stubCode   = ClassGenerator::fromReflection($reflection)->generate();
        $interface  = $reflection->isInterface();
        $trait      = $reflection->isTrait();

        if (! ($interface || $trait)) {
            return $stubCode;
        }

        return $this->prettyPrinter->prettyPrint(
            $this->replaceNodesRecursively($this->parser->parse('<?php ' . $stubCode), $interface)
        );
    }

    /**
     * @param \PhpParser\Node[] $statements
     * @param bool              $interfaceOrTrait (true => interface, false => trait)
     * @param bool              $trait
     *
     * @return \PhpParser\Node[]
     */
    private function replaceNodesRecursively(array $statements, $interfaceOrTrait)
    {
        foreach ($statements as $key => $statement) {
            if ($statement instanceof Class_) {
                $statements[$key] = $interfaceOrTrait
                    ? new Interface_(
                        $statement->name,
                        [
                            'extends' => $statement->implements,
                            'stmts'   => $statement->stmts,
                        ]
                    )
                    : new Trait_($statement->name, $statement->stmts);

                continue;
            }

            if (property_exists($statement, 'stmts')) {
                $statement->stmts = $this->replaceNodesRecursively($statement->stmts, $interfaceOrTrait);
            }
        }

        return $statements;
    }
}
