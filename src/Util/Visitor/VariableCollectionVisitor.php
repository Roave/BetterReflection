<?php

declare(strict_types=1);

namespace BetterReflection\Util\Visitor;

use PhpParser\NodeVisitorAbstract;
use BetterReflection\Reflection\ReflectionVariable;
use BetterReflection\TypesFinder\FindTypeFromAst;
use BetterReflection\Reflection\ReflectionType;
use BetterReflection\Reflection\ReflectionClass;
use PhpParser\Node;
use PhpParser\Node\Expr;
use BetterReflection\Reflector\Reflector;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types as DocType;
use phpDocumentor\Reflection\TypeResolver;
use PhpParser\Node\Name;
use phpDocumentor\Reflection\Type;

class VariableCollectionVisitor extends NodeVisitorAbstract
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var array
     */
    private $variables = [];

    /**
     * @var array
     */
    private $methodParamTypes = [];

    /**
     * Construct with the reflection class for the AST that we are traversing
     * and a reflector instance to resolve types from other classes.
     */
    public function __construct(ReflectionClass $reflection, Reflector $reflector, TypeResolver $typeResolver = null)
    {
        $this->reflector = $reflector;
        $this->reflection = $reflection;
        $this->typeResolver = $typeResolver ?: new TypeResolver();
    }

    /**
     * {@inheritdoc}
     *
     * Just in case this visitor is invoked again, reset the
     * variables before traversal starts.
     */
    public function beforeTraverse(array $nodes)
    {
        $this->variables = [];
    }

    /**
     * {@inheritdoc}
     *
     * Currently we care about two types of variables:
     *
     * 1. Parameters that are passed to class methods.
     * 2. Newly declared variables.
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->processClassMethod($node);

            return;
        }

        if ($node instanceof Expr\Assign) {
            $this->processAssign($node);

            return;
        }
    }

    /**
     * Return all the variables which were discovered in the AST.
     *
     * @return ReflectionVariable[]
     */
    public function getVariables()
    {
        return $this->variables;
    }

    private function processClassMethod(Node\Stmt\ClassMethod $node)
    {
        // reset when we enter the class method scope
        $this->methodParamTypes = [];

        $reflMethod = $this->reflection->getMethod($node->name);

        foreach ($node->params as $param) {
            $reflParam = $reflMethod->getParameter($param->name);

            // first use any type hint provided in the method signature.
            $type = $reflParam->getType(); /* @var ReflectionType */

            // if the type is null, then try and guess the type from the docblock.
            if ($reflMethod->getDocComment() && null === $type) {
                $types = $reflParam->getDocBlockTypes();

                // if multiple types are provided, then return the first.
                $type = $this->createReflectionTypeFromDocType(reset($types));
            }

            // if the type is still null, then assume it as a "mixed" type.
            $type = $type ?: $this->createReflectionTypeFromString('mixed');

            // make the parameter type available for later.
            $this->methodParamTypes[$param->name] = $type;

            // parameters count as available variables.
            $this->variables[] = ReflectionVariable::createFromName($param->name, $type, $param->getAttribute('startLine'));
        }
    }

    private function processAssign(Expr\Assign $node)
    {
        // assignment is not directly to a variable, so just ignore it
        // rather than trying to recreate state.
        if (false === $node->var instanceof Expr\Variable) {
            return;
        }

        $type = $this->typeFromExpression($node->expr);

        $this->variables[] = ReflectionVariable::createFromName($node->var->name, $type, $node->getAttribute('startLine'));
    }

    private function typeFromExpression(Node $expr): ReflectionType
    {
        if ($expr instanceof Expr\New_) {
            return $this->createTypeFromNameNode($expr->class);
        }

        if ($expr instanceof Expr\PropertyFetch) {
            $type = $this->typeFromExpression($expr->var);

            if ($type->getTypeObject() instanceof DocType\Object_) {
                $reflection = $this->reflector->reflect($type);
                $propertyRefl = $reflection->getProperty($expr->name);

                if ($propertyRefl->getDocComment()) {
                    $types = $propertyRefl->getDocBlockTypes();

                    return $this->createReflectionTypeFromDocType(reset($types));
                }
            }

            return $this->createReflectionTypeFromString('mixed');
        }

        if ($expr instanceof Expr\MethodCall) {
            $type = $this->typeFromExpression($expr->var);

            if ($type->getTypeObject() instanceof DocType\Object_) {
                $reflection = $this->reflector->reflect($type);
                $method = $reflection->getMethod($expr->name);

                return $method->getReturnType() ?: $this->createReflectionTypeFromString('mixed');
            }

            return $this->createReflectionTypeFromString('mixed');
        }

        if ($expr instanceof Expr\Variable) {
            if ($expr->name === 'this') {
                $type = $this->createReflectionTypeFromString($this->reflection->getName());

                return $type;
            }

            if (isset($this->methodParamTypes[$expr->name])) {
                return $this->methodParamTypes[$expr->name];
            }
        }

        // if this is a function call, try and instantiate the runtime native
        // \ReflectionFunction, if it is not internal then ignore it as we
        // cannot guarantee that we are running in the same process as the code
        // we are analyzing.
        //
        // TODO: This is no positive test case for this ... which PHP internal functions
        //       actually have a return type??
        if ($expr instanceof Expr\FuncCall) {
            $func = (string) $expr->name;
            $reflection = new \ReflectionFunction($func);

            // do not try and find out return type for non-internal functions
            if (false === $reflection->isInternal()) {
                return;
            }

            // in the case that no return type was provided, just return
            // "mixed".
            return $reflection->getReturnType() ?: $this->createUnknownReflectionType();
        }

        if ($expr instanceof Expr\ArrayDimFetch) {
            return $this->createUnknownReflectionType();
        }

        if ($expr instanceof Expr\Array_) {
            return $this->createReflectionTypeFromString('array');
        }

        if ($expr instanceof Node\Scalar) {
            switch (get_class($expr)) {
                case Node\Scalar\DNumber::class:
                    return $this->createReflectionTypeFromString('float');

                case Node\Scalar\LNumber::class:
                    return $this->createReflectionTypeFromString('integer');

                case Node\Scalar\String_::class:
                    return $this->createReflectionTypeFromString('string');

                case Node\Scalar\Encapsed::class:
                case Node\Scalar\MagicConst::class:
                case Node\Scalar\EncapsedStringPart::class:
                    // TODO: ???
                    return $this->createUnknownReflectionType();
                default:
                    throw new \RuntimeException(sprintf(
                        'Do not know scalar type "%s"', get_class($expr)
                    ));
            }
        }

        throw new \RuntimeException(sprintf(
            'Could not determine type from expression for node of type "%s"',
            get_class($expr)
        ));
    }

    /**
     * Create the a type relative to the current reflection class from
     * a php-parser name-node.
     */
    private function createTypeFromNameNode(Name $name)
    {
        $docType = (new FindTypeFromAst())->__invoke(
            $name,
            $this->reflection->getLocatedSource(),
            $this->reflection->getNamespaceName()
        );

        return $this->createReflectionTypeFromDocType($docType);
    }

    private function createReflectionTypeFromString(string $type = null)
    {
        $type = $type ?: 'mixed';

        return $this->createReflectionTypeFromDocType(
            $this->typeResolver->resolve($type)
        );
    }

    private function createReflectionTypeFromDocType(Type $type): ReflectionType
    {
        return ReflectionType::createFromType($type, false);
    }

    private function createUnknownReflectionType()
    {
        return $this->createReflectionTypeFromString('mixed', false);
    }
}
