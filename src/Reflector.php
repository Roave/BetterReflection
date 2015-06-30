<?php

namespace BetterReflection;

use BetterReflection\SourceLocator\FilenameSourceLocator;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\SourceLocator;
use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\Node;
use BetterReflection\Reflection\ReflectionClass;

class Reflector
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    public function __construct(SourceLocator $sourceLocator)
    {
        $this->sourceLocator = $sourceLocator;
    }

    /**
     * Uses the SourceLocator given in the constructor to locate the $className
     * specified and returns the ReflectionClass
     *
     * @param $className
     * @return ReflectionClass
     */
    public function reflect($className)
    {
        if ('\\' == $className[0]) {
            $className = substr($className, 1);
        }

        if (class_exists($className, false)) {
            throw new \LogicException(sprintf(
                'Class "%s" is already loaded',
                $className
            ));
        }

        $locatedSource = $this->sourceLocator->locate($className);
        $class = $this->reflectClassFromLocatedSource($className, $locatedSource);

        if (class_exists($className, false)) {
            throw new \LogicException(sprintf(
                'Class "%s" was loaded whilst reflecting',
                $className
            ));
        }

        return $class;
    }

    /**
     * Given an array of ReflectionClasses, try to find the class $className
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

        throw new \UnexpectedValueException(sprintf(
            'Class "%s" could not be found to load',
            $className
        ));
    }

    /**
     * Read all the classes from a LocatedSource and find the specified class
     *
     * @param string $className
     * @param LocatedSource $locatedSource
     * @return ReflectionClass
     */
    private function reflectClassFromLocatedSource(
        $className,
        LocatedSource $locatedSource
    ) {
        $classes = $this->getClasses($locatedSource);
        return $this->findClassInArray($classes, $className);
    }

    /**
     * Process and reflect all the classes found inside a namespace node
     *
     * @param Node\Stmt\Namespace_ $namespace
     * @param string|null $filename
     * @return ReflectionClass[]
     */
    private function reflectClassesFromNamespace(
        Node\Stmt\Namespace_ $namespace,
        $filename
    ) {
        $classes = [];
        foreach ($namespace->stmts as $node) {
            if ($node instanceof Node\Stmt\Class_) {
                $classes[] = ReflectionClass::createFromNode(
                    $node,
                    $namespace,
                    $filename
                );
            }
        }
        return $classes;
    }

    /**
     * Reflect classes from an AST. If a namespace is found, also load all the
     * classes found in the namespace
     *
     * @param Node[] $ast
     * @param string|null $filename
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
                    $classes[] = ReflectionClass::createFromNode(
                        $node,
                        null,
                        $filename
                    );
                    break;
            }
        }
        return $classes;
    }

    /**
     * Get an array of classes found in a LocatedSource
     *
     * @param LocatedSource $locatedSource
     * @return ReflectionClass[]
     */
    private function getClasses(LocatedSource $locatedSource)
    {
        $parser = new Parser(new Lexer);
        $ast = $parser->parse($locatedSource->getSource());

        return $this->reflectClassesFromTree(
            $ast,
            $locatedSource->getFileName()
        );
    }

    /**
     * Return an array of ReflectionClass objects from the file.
     *
     * This requires a FilenameSourceLocator to be used, otherwise a
     * LogicException will be thrown.
     *
     * @throws \LogicException
     * @return Reflection\ReflectionClass[]
     */
    public function getClassesFromFile()
    {
        if (!$this->sourceLocator instanceof FilenameSourceLocator) {
            throw new \LogicException(
                'To fetch all classes from file, you must use a'
                . ' FilenameSourceLocator'
            );
        }

        return $this->getClasses($this->sourceLocator->locate('*'));
    }
}
