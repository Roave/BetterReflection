<?php
namespace Asgrim;

use Composer\Autoload\ClassLoader;
use PhpParser\Parser;
use PhpParser\Lexer;

class Investigator
{
    private $classLoader;

    public function __construct(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }

    public function investigate($className)
    {
        $file = $this->classLoader->findFile($className);

        if (class_exists($file, false)) {
            throw new \LogicException(sprintf('Class "%s" is already loaded', $className));
        }

        $fileContent = file_get_contents($file);

        $parser = new Parser(new Lexer);
        $ast = $parser->parse($fileContent);

        return new ClassInfo();
    }
}
