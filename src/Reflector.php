<?php

namespace BetterReflection;

use Composer\Autoload\ClassLoader;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node;

class Reflector
{
    /**
     * @var ClassLoader
     */
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
     * Given an array of ReflectionClasses, try to find the class named in $className
     *
     * @param ReflectionClass[] $classes
     * @param string $className
     * @return ReflectionClass
     */
    private function findClassInArray($classes, $className)
    {
        foreach ($classes as $class) {
            if ($class->getName() == $className) {
                return $class;
            }
        }

        throw new \UnexpectedValueException(sprintf('Class "%s" could not be found to load', $className));
    }

    /**
     * Load an arbitrary string and attempt to read the specified class from it
     *
     * @param string $className
     * @param string $string
     * @return ReflectionClass
     */
    public function reflectClassFromString($className, $string)
    {
        $classes = $this->getClassesFromString($string);
        return $this->findClassInArray($classes, $className);
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
        $classes = $this->getClassesFromFile($filename);
        return $this->findClassInArray($classes, $className);
    }

    /**
     * Process and reflect all the classes found inside a namespace node
     *
     * @param Node\Stmt\Namespace_ $namespace
     * @param string
     * @return ReflectionClass[]
     */
    private function reflectClassesFromNamespace(Node\Stmt\Namespace_ $namespace, $filename)
    {
        $classes = [];
        foreach ($namespace->stmts as $node) {
            if (get_class($node) == Node\Stmt\Class_::class) {
                $classes[] = ReflectionClass::createFromNode($node, $namespace, $filename);
            }
        }
        return $classes;
    }

    /**
     * Reflect classes from an AST. If a namespace is found, also load all the classes found in the namespace
     *
     * @param Node[] $ast
     * @param string $filename
     * @return ReflectionClass[]
     */
    private function reflectClassesFromTree(array $ast, $filename)
    {
        $classes = [];
        foreach ($ast as $node) {
            switch (get_class($node)) {
                case Node\Stmt\Namespace_::class:
                    $classes = array_merge(
                        $classes,
                        $this->reflectClassesFromNamespace($node, $filename)
                    );
                    break;
                case Node\Stmt\Class_::class:
                    $classes[] = ReflectionClass::createFromNode($node, null, $filename);
                    break;
            }
        }
        return $classes;
    }

    /**
     * Get an array of classes found in an arbitrary string
     *
     * @param string $string
     * @param string|null $filename
     * @return ReflectionClass[]
     */
    public function getClassesFromString($string, $filename = null)
    {
        $parser = new Parser(new Lexer);
        $ast = $parser->parse($string);

        return $this->reflectClassesFromTree($ast, $filename);
    }

    /**
     * Get a list of the classes found in specified file file
     *
     * @param $filename
     * @return ReflectionClass[]
     */
    public function getClassesFromFile($filename)
    {
        $fileContent = file_get_contents($filename);
        return $this->getClassesFromString($fileContent, $filename);
    }

    /**
     * Compile an expression from a node into a value
     *
     * @param Node $node
     * @return mixed
     */
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
                } else if ($node->name->parts[0] == 'false') {
                    return false;
                } else if ($node->name->parts[0] == 'true') {
                    return true;
                } else {
                    throw new \LogicException('Other ConstFetch types are not implemented yet');
                }
                break;
            default:
                throw new \LogicException('Unable to compile expression');
        }
    }
}
