<?php
namespace Asgrim;

use Composer\Autoload\ClassLoader;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node;

class Reflector
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
    public function reflect($className)
    {
        if ('\\' == $className[0]) {
            $className = substr($className, 1);
        }

        $file = $this->classLoader->findFile($className);

        if (class_exists($file, false)) {
            throw new \LogicException(sprintf('Class "%s" is already loaded', $className));
        }

        $fileContent = file_get_contents($file);

        $parser = new Parser(new Lexer);
        $ast = $parser->parse($fileContent);

        $classes = $this->reflectClassesFromTree($ast);

        foreach ($classes as $class) {
            if ($class->getName() == $className) {
                return $class;
            }
        }

        throw new \UnexpectedValueException(sprintf('Class "%s" could not be found to load', $className));
    }

    /**
     * @param Node\Stmt\Namespace_ $namespace
     * @return ReflectionClass[]
     */
    private function reflectClassesFromNamespace(Node\Stmt\Namespace_ $namespace)
    {
        $classes = [];
        foreach ($namespace->stmts as $node) {
            if (get_class($node) == Node\Stmt\Class_::class) {
                $classes[] = ReflectionClass::createFromNode($node, $namespace);
            }
        }
        return $classes;
    }

    /**
     * @param Node[] $ast
     * @return ReflectionClass[]
     */
    private function reflectClassesFromTree(array $ast)
    {
        $classes = [];
        foreach ($ast as $node) {
            switch (get_class($node)) {
                case Node\Stmt\Namespace_::class:
                    $classes = array_merge(
                        $classes,
                        $this->reflectClassesFromNamespace($node)
                    );
                    break;
                case Node\Stmt\Class_::class:
                    $classes[] = ReflectionClass::createFromNode($node);
                    break;
            }
        }
        return $classes;
    }
}
