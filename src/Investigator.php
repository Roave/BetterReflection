<?php
namespace Asgrim;

use Composer\Autoload\ClassLoader;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\Node\Stmt;

class Investigator
{
    private $classLoader;

    public function __construct(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }

    /**
     * @param string $className
     * @return ReflectionClass
     */
    public function investigate($className)
    {
        $file = $this->classLoader->findFile($className);

        if (class_exists($file, false)) {
            throw new \LogicException(sprintf('Class "%s" is already loaded', $className));
        }

        $fileContent = file_get_contents($file);

        $parser = new Parser(new Lexer);
        $ast = $parser->parse($fileContent);

        $class = ReflectionClass::createFromNode($ast[0]->stmts[0], $ast[0]);
        return $class;
    }
}
