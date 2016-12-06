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
use BetterReflection\NodeCompiler\CompilerContext;
use PhpParser\Node\FunctionLike;
use BetterReflection\Reflection\ReflectionMethod;

/**
 * This collection will traverse an AST and collect all of the variables and
 * attempt to determine their types.
 */
class VariableCollectionVisitor extends NodeVisitorAbstract
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var CompilerContext
     */
    private $context;

    /**
     * @var array
     */
    private $variables = [];

    /**
     * @var array
     */
    private $methodParamTypes = [];

    /**
     * @var array
     */
    private $scopes = [];

    public function __construct(CompilerContext $context, TypeResolver $typeResolver = null)
    {
        $this->context = $context;
        $this->typeResolver = $typeResolver ?: new TypeResolver();
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

    /**
     * {@inheritdoc}
     *
     * Reset the state.
     */
    public function beforeTraverse(array $nodes)
    {
        $this->variables = [];
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof FunctionLike) {
            $this->scopes[] = $this->processFunctionLike($node);

            return;
        }

        if ($node instanceof Expr\Assign) {
            $this->processAssign($node);

            return;
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof FunctionLike) {
            array_pop($this->scopes);
            
            return;
        }
    }

    private function processFunctionLike(FunctionLike $node)
    {
        // reset when we enter the class method scope
        $this->methodParamTypes = [];

        $methodReflection = $this->context->getSelf()->getMethod($node->name);

        foreach ($node->getParams() as $param) {

            $reflectionType = $this->reflectionTypeFromParam($param, $methodReflection);

            // make the parameter type available for later.
            $this->methodParamTypes[$param->name] = $reflectionType;

            // parameters count as available variables.
            // TODO: Test
            $this->variables[] = ReflectionVariable::createFromParamAndType($param, $reflectionType, $methodReflection);
        }

        return $methodReflection;
    }

    private function processAssign(Expr\Assign $node)
    {
        // assignment is not directly to a variable, so just ignore it
        // rather than trying to recreate state.
        if (false === $node->var instanceof Expr\Variable) {
            return;
        }

        $type = $this->typeFromNode($node->expr);

        $this->variables[] = ReflectionVariable::createFromVariableAndType($node->var, $type, $this->getCurrentScope());
    }

    private function typeFromNode(Node $expr): ReflectionType
    {
        if ($expr instanceof Expr\New_) {
            return $this->reflectionTypeFromNameNode($expr->class);
        }

        if ($expr instanceof Expr\PropertyFetch) {
            return $this->reflectionTypeFromPropertyFetch($expr);
        }

        if ($expr instanceof Expr\MethodCall) {
            return $this->reflectionTypeFromMethodCall($expr);
        }

        if ($expr instanceof Expr\Variable) {
            return $this->reflectionTypeFromVariable($expr);
        }

        if ($expr instanceof Expr\FuncCall) {
            return $this->reflectionTypeFromFunctionCall($expr);
        }

        if ($expr instanceof Expr\ArrayDimFetch) {
            return $this->reflectionTypeForUnknown();
        }

        if ($expr instanceof Expr\Array_) {
            return $this->reflectionTypeFromString('array');
        }

        if ($expr instanceof Node\Scalar) {
            switch (get_class($expr)) {
                case Node\Scalar\DNumber::class:
                    return $this->reflectionTypeFromString('float');

                case Node\Scalar\LNumber::class:
                    return $this->reflectionTypeFromString('integer');

                case Node\Scalar\String_::class:
                    return $this->reflectionTypeFromString('string');

                case Node\Scalar\Encapsed::class:
                case Node\Scalar\MagicConst::class:
                case Node\Scalar\EncapsedStringPart::class:
                    // TODO: ???
                    return $this->reflectionTypeForUnknown();
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

    private function reflectionTypeFromParam(Node\Param $expr, ReflectionMethod $reflectionMethod)
    {
        $reflectionParam = $reflectionMethod->getParameter($expr->name);

        // first use any type hint provided in the method signature.
        $reflectionType = $reflectionParam->getType();

        // if the type is null, then try and guess the type from the docblock.
        if (null === $reflectionType && $reflectionMethod->getDocComment()) {
            $docTypes = $reflectionParam->getDocBlockTypes();

            // if multiple types are provided, then return the first.
            $reflectionType = $this->reflectionTypeFromDocType(reset($docTypes));
        }

        // if the type is still null, then assume it as a "mixed" type.
        return $reflectionType ?: $this->reflectionTypeForUnknown();
    }

    private function reflectionTypeFromPropertyFetch(Expr\PropertyFetch $expr): ReflectionType
    {
        $type = $this->typeFromNode($expr->var);

        if (false === $type->isBuiltin()) {
            $reflection = $this->context->getReflector()->reflect($type);

            // TODO: what if reflection does not have property?
            $propertyRefl = $reflection->getProperty($expr->name);

            if ($propertyRefl->getDocComment()) {
                $types = $propertyRefl->getDocBlockTypes();

                return $this->reflectionTypeFromDocType(reset($types));
            }
        }

        return $this->reflectionTypeForUnknown();
    }

    private function reflectionTypeFromMethodCall(Expr\MethodCall $expr): ReflectionType
    {
        $type = $this->typeFromNode($expr->var);

        if (false === $type->isBuiltin()) {
            $reflection = $this->context->getReflector()->reflect($type);
            $method = $reflection->getMethod($expr->name);

            return $method->getReturnType() ?: $this->reflectionTypeForUnknown();
        }

        return $this->reflectionTypeForUnknown();
    }

    private function reflectionTypeFromVariable(Expr\Variable $expr): ReflectionType
    {
        if ($this->context->hasSelf() && $expr->name === 'this') {
            $type = $this->reflectionTypeFromString($this->context->getSelf()->getName());

            return $type;
        }

        if (isset($this->methodParamTypes[$expr->name])) {
            return $this->methodParamTypes[$expr->name];
        }
    }

    /**
     * if this is a function call, try and instantiate the runtime native
     * \ReflectionFunction, if it is not internal then ignore it as we
     * cannot guarantee that we are running in the same process as the code
     * we are analyzing.
     *
     * TODO: This is no positive test case for this ... which PHP internal functions
     *       actually have a return type??
     */
    private function reflectionTypeFromFunctionCall(Expr\FuncCall $expr): ReflectionType
    {
        $func = (string) $expr->name;
        $reflection = new \ReflectionFunction($func);

        // do not try and find out return type for non-internal functions
        if (false === $reflection->isInternal()) {
            return;
        }

        // in the case that no return type was provided, just return
        // "mixed".
        return $reflection->getReturnType() ?: $this->reflectionTypeForUnknown();
    }

    /**
     * Create the a type relative to the current reflection class from
     * a php-parser name-node.
     */
    private function reflectionTypeFromNameNode(Name $name)
    {
        $docType = (new FindTypeFromAst())->__invoke(
            $name,
            $this->context->getSelf()->getLocatedSource(),
            $this->context->getSelf()->getNamespaceName()
        );

        return $this->reflectionTypeFromDocType($docType);
    }

    private function reflectionTypeFromString(string $type = null)
    {
        $type = $type ?: 'mixed';

        return $this->reflectionTypeFromDocType(
            $this->typeResolver->resolve($type)
        );
    }

    private function reflectionTypeFromDocType(Type $type): ReflectionType
    {
        return ReflectionType::createFromType($type, false);
    }

    private function reflectionTypeForUnknown()
    {
        return $this->reflectionTypeFromString('mixed', false);
    }

    private function getCurrentScope()
    {
        if (empty($this->scopes)) {
            return null;
        }

        return end($this->scopes);
    }
}
