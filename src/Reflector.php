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
     * Use the Class Loader to resolve a class to a file and load it
     *
     * @param string $className
     * @return ReflectionClass
     */
    public function reflect($className)
    {
        if ('\\' == $className[0]) {
            $className = substr($className, 1);
        }

        $filename = $this->classLoader->findFile($className);

        if (class_exists($filename, false)) {
            throw new \LogicException(sprintf('Class "%s" is already loaded', $className));
        }

        if (!$filename) {
            throw new \UnexpectedValueException(sprintf('Could not locate file to load "%s"', $className));
        }

        return $this->reflectClassFromFile($className, $filename);
    }

    /**
     * Load a file and attempt to read the specified class from the specified file
     *
     * @param string $className
     * @param string $filename
     * @return ReflectionClass
     */
    public function reflectClassFromFile($className, $filename)
    {
        $fileContent = file_get_contents($filename);

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

    public static function compileNodeExpression(Node $node)
    {
        $type = get_class($node);

        switch ($type) {
            case Node\Scalar\String_::class:
            case Node\Scalar\DNumber::class:
            case Node\Scalar\LNumber::class:
                return $node->value;
            case Node\Expr\Array_::class:
                return []; // @todo compile expression
            case Node\Expr\ConstFetch::class:
                if ($node->name->parts[0] == 'null') {
                    return null;
                } else {
                    throw new \LogicException('Other ConstFetch types are not implemented yet');
                }
                break;
            default:
                throw new \LogicException('Unable to compile expression');
        }
    }
}
