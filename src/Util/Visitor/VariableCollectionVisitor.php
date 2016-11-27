<?php

namespace BetterReflection\Util\Visitor;

use PhpParser\NodeVisitorAbstract;
use BetterReflection\Reflection\ReflectionVariable;
use BetterReflection\TypesFinder\FindTypeFromAst;
use BetterReflection\Reflection\ReflectionType;
use BetterReflection\Reflection\ReflectionClass;
use PhpParser\Node;
use PhpParser\Node\Expr;
use BetterReflection\Reflector\Reflector;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Types as DocType;

class VariableCollectionVisitor extends NodeVisitorAbstract
{
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
    public function __construct(ReflectionClass $reflection, Reflector $reflector)
    {
        $this->reflector = $reflector;
        $this->reflection = $reflection;
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
            $this->processAssignation($node);
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

        foreach ($node->params as $param) {
            $reflParam = $this->reflection->getMethod($node->name)->getParameter($param->name);
            $type = $reflParam->getType();

            // if the type is null, then try and guess the type from the docblock, if
            // multiple types are available, then return the first.
            if (null === $type) {
                $types = $reflParam->getDocBlockTypes();
                $type = count($types) ? ReflectionType::createFromType(reset($types), false) : null;
            }

            $this->methodParamTypes[$param->name] = $type;
            $this->variables[] = ReflectionVariable::createFromName($param->name, $type, $param->getAttribute('startLine'));
        }
    }

    private function processAssignation(Expr\Assign $node)
    {
        // assignment is not directly to a vaiable, so just ignore it
        // rather than trying to recreate state.
        if (false === $node->var instanceof Expr\Variable) {
            return;
        }

        $type = $this->typeFromExpression($node->expr);
        $this->variables[] = ReflectionVariable::createFromName($node->var->name, $type, $node->getAttribute('startLine'));
    }

    private function typeFromExpression(Node $expr)
    {
        $type = null;

        // new object, just get the type and we are good.
        if ($expr instanceof Expr\New_) {
            return $this->createType($expr->class);
        }

        // if this is a property fetch we must resolve the call
        // chain to determine the type.
        if ($expr instanceof Expr\PropertyFetch) {
            $exprs = $this->flattenFetchChain($expr);

            return $this->resolvePropertyFetchChain($exprs);
        }

        // if it is a method call then we recurse to resolve the type.
        if ($expr instanceof Expr\MethodCall) {
            $type = $this->typeFromExpression($expr->var);
            $reflection = $this->reflector->reflect($type);
            $method = $reflection->getMethod($expr->name);

            return $method->getReturnType();
        }

        if ($expr instanceof Expr\Variable) {
            if ($expr->name === 'this') {
                $type = ReflectionType::createFromType(new Object_(new Fqsen('\\'.$this->reflection->getName())), false);

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
            return $reflection->getReturnType() ?: ReflectionType::createFromType(new DocType\Mixed(), false);
        }

        if ($expr instanceof Expr\ArrayDimFetch) {
            return ReflectionType::createFromType(new DocType\Mixed(), false);
        }

        if ($expr instanceof Expr\Array_) {
            return ReflectionType::createFromType(new DocType\Array_(), false);
        }

        if ($expr instanceof Node\Scalar) {
            switch (get_class($expr)) {
                case Node\Scalar\DNumber::class:
                    return ReflectionType::createFromType(new DocType\Float_(), false);

                case Node\Scalar\LNumber::class:
                    return ReflectionType::createFromType(new DocType\Integer(), false);

                case Node\Scalar\String_::class:
                    return ReflectionType::createFromType(new DocType\String_(), false);

                case Node\Scalar\Encapsed::class:
                case Node\Scalar\MagicConst::class:
                case Node\Scalar\EncapsedStringPart::class:
                    // TODO: ???
                    return;
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

    private function resolvePropertyFetchChain(array $exprs)
    {
        $reflection = $this->reflection;

        foreach ($exprs as $expr) {
            // TODO: This seems very wrong ...
            if (false === $expr instanceof Expr\PropertyFetch) {
                continue;
            }

            // resolve the type of the property from its docblock
            $prop = $reflection->getProperty($expr->name);
            $types = $prop->getDocBlockTypes();
            $type = reset($types);

            // set the new reflection to the type of the property
            $reflection = $this->reflector->reflect($type->getFqsen());
        }

        return $reflection->getName();
    }

    private function flattenFetchChain(Expr\PropertyFetch $node)
    {
        $exprs = [];

        // if this the child of this node is also a property fetch node then
        // recurse, otherwise just add the child to the chain and return.
        if ($node->var instanceof Expr\PropertyFetch) {
            $exprs = array_merge($exprs, $this->flattenFetchChain($node->var));
        } else {
            $exprs[] = $node->var;
        }

        $exprs[] = $node;

        return $exprs;
    }

    private function createType($type)
    {
        $typeHint = (new FindTypeFromAst())->__invoke(
            $type,
            $this->reflection->getLocatedSource(),
            $this->reflection->getNamespaceName()
        );

        if (null === $typeHint) {
            return;
        }

        return ReflectionType::createFromType($typeHint, false);
    }
}
