<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Reflection;

use Closure;
use Exception;
use phpDocumentor\Reflection\Type;
use PhpParser\Node;
use PhpParser\Node\Expr\Yield_ as YieldNode;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use PhpParser\PrettyPrinterAbstract;
use Reflector as CoreReflector;
use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Identifier\Identifier;
use Rector\BetterReflection\Identifier\IdentifierType;
use Rector\BetterReflection\Reflection\Exception\Uncloneable;
use Rector\BetterReflection\Reflector\Reflector;
use Rector\BetterReflection\SourceLocator\Located\LocatedSource;
use Rector\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Rector\BetterReflection\TypesFinder\FindReturnType;
use Rector\BetterReflection\Util\CalculateReflectionColum;
use Rector\BetterReflection\Util\GetFirstDocComment;
use Rector\BetterReflection\Util\Visitor\ReturnNodeVisitor;

abstract class ReflectionFunctionAbstract implements CoreReflector
{
    public const CLOSURE_NAME = '{closure}';

    /**
     * @var NamespaceNode
     */
    private $declaringNamespace;

    /**
     * @var LocatedSource
     */
    private $locatedSource;

    /**
     * @var Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure
     */
    private $node;

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var Parser
     */
    private static $parser;

    protected function __construct()
    {
    }

    public static function export() : void
    {
        throw new Exception('Unable to export statically');
    }

    /**
     * Populate the common elements of the function abstract.
     *
     * @param Reflector                                              $reflector
     * @param Node\Stmt\ClassMethod|Node\FunctionLike|Node\Stmt|Node $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     * @param LocatedSource                                          $locatedSource
     * @param NamespaceNode|null                                     $declaringNamespace
     *
     * @throws \Rector\BetterReflection\Reflection\Exception\InvalidAbstractFunctionNodeType
     */
    protected function populateFunctionAbstract(
        Reflector $reflector,
        Node\FunctionLike $node,
        LocatedSource $locatedSource,
        ?NamespaceNode $declaringNamespace = null
    ) : void {
        $this->reflector          = $reflector;
        $this->node               = $node;
        $this->locatedSource      = $locatedSource;
        $this->declaringNamespace = $declaringNamespace;

        $this->setNodeOptionalFlag();
    }

    /**
     * Get the AST node from which this function was created
     *
     * @return Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\FunctionLike
     */
    protected function getNode() : Node\FunctionLike
    {
        return $this->node;
    }

    /**
     * We must determine if params are optional or not ahead of time, but we
     * must do it in reverse...
     */
    private function setNodeOptionalFlag() : void
    {
        $overallOptionalFlag = true;
        $lastParamIndex      = (\count($this->node->params) - 1);
        for ($i = $lastParamIndex; $i >= 0; $i--) {
            $hasDefault = (null !== $this->node->params[$i]->default);

            // When we find the first parameter that does not have a default,
            // flip the flag as all params for this are no longer optional
            // EVEN if they have a default value
            if ( ! $hasDefault) {
                $overallOptionalFlag = false;
            }

            $this->node->params[$i]->isOptional = $overallOptionalFlag;
        }
    }

    /**
     * Get the "full" name of the function (e.g. for A\B\foo, this will return
     * "A\B\foo").
     *
     * @return string
     */
    public function getName() : string
    {
        if ( ! $this->inNamespace()) {
            return $this->getShortName();
        }

        return $this->getNamespaceName() . '\\' . $this->getShortName();
    }

    /**
     * Get the "short" name of the function (e.g. for A\B\foo, this will return
     * "foo").
     *
     * @return string
     */
    public function getShortName() : string
    {
        if ($this->node instanceof Node\Expr\Closure) {
            return self::CLOSURE_NAME;
        }

        return (string) $this->node->name;
    }

    /**
     * Get the "namespace" name of the function (e.g. for A\B\foo, this will
     * return "A\B").
     *
     * @return string
     */
    public function getNamespaceName() : string
    {
        if ( ! $this->inNamespace()) {
            return '';
        }

        return \implode('\\', $this->declaringNamespace->name->parts);
    }

    /**
     * Decide if this function is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace.
     *
     * @return bool
     */
    public function inNamespace() : bool
    {
        return null !== $this->declaringNamespace
            && null !== $this->declaringNamespace->name;
    }

    /**
     * Get the number of parameters for this class.
     *
     * @return int
     */
    public function getNumberOfParameters() : int
    {
        return \count($this->getParameters());
    }

    /**
     * Get the number of required parameters for this method.
     *
     * @return int
     */
    public function getNumberOfRequiredParameters() : int
    {
        return \count(\array_filter(
            $this->getParameters(),
            function (ReflectionParameter $p) : bool {
                return ! $p->isOptional();
            }
        ));
    }

    /**
     * Get an array list of the parameters for this method signature, as an
     * array of ReflectionParameter instances.
     *
     * @return ReflectionParameter[]
     */
    public function getParameters() : array
    {
        $parameters = [];

        foreach ($this->node->params as $paramIndex => $paramNode) {
            $parameters[] = ReflectionParameter::createFromNode(
                $this->reflector,
                $paramNode,
                $this->declaringNamespace,
                $this,
                $paramIndex
            );
        }

        return $parameters;
    }

    /**
     * Get a single parameter by name. Returns null if parameter not found for
     * the function.
     *
     * @param string $parameterName
     * @return ReflectionParameter|null
     */
    public function getParameter(string $parameterName) : ?ReflectionParameter
    {
        foreach ($this->getParameters() as $parameter) {
            if ($parameter->getName() === $parameterName) {
                return $parameter;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getDocComment() : string
    {
        return GetFirstDocComment::forNode($this->node);
    }

    /**
     * @return string|null
     */
    public function getFileName() : ?string
    {
        return $this->locatedSource->getFileName();
    }

    /**
     * @return LocatedSource
     */
    public function getLocatedSource() : LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Is this function a closure?
     *
     * @return bool
     */
    public function isClosure() : bool
    {
        return $this->node instanceof Node\Expr\Closure;
    }

    /**
     * Is this function deprecated?
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function.
     *
     * @see https://github.com/Roave/BetterReflection/issues/38
     * @return bool
     */
    public function isDeprecated() : bool
    {
        return false;
    }

    /**
     * Is this an internal function?
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function.
     *
     * @see https://github.com/Roave/BetterReflection/issues/38
     * @return bool
     */
    public function isInternal() : bool
    {
        return false;
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     *
     * @return bool
     */
    public function isUserDefined() : bool
    {
        return ! $this->isInternal();
    }

    /**
     * Check if the function has a variadic parameter.
     *
     * @return bool
     */
    public function isVariadic() : bool
    {
        $parameters = $this->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively search an array of statements (PhpParser nodes) to find if a
     * yield expression exists anywhere (thus indicating this is a generator).
     *
     * @param \PhpParser\Node $node
     * @return bool
     */
    private function nodeIsOrContainsYield(Node $node) : bool
    {
        if ($node instanceof YieldNode) {
            return true;
        }

        foreach ($node as $nodeProperty) {
            if ($nodeProperty instanceof Node && $this->nodeIsOrContainsYield($nodeProperty)) {
                return true;
            }

            if (\is_array($nodeProperty)) {
                foreach ($nodeProperty as $nodePropertyArrayItem) {
                    if ($nodePropertyArrayItem instanceof Node && $this->nodeIsOrContainsYield($nodePropertyArrayItem)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if this function can be used as a generator (i.e. contains the
     * "yield" keyword).
     *
     * @return bool
     */
    public function isGenerator() : bool
    {
        if (null === $this->node) {
            return false;
        }

        return $this->nodeIsOrContainsYield($this->node);
    }

    /**
     * Get the line number that this function starts on.
     *
     * @return int
     */
    public function getStartLine() : int
    {
        return (int) $this->node->getAttribute('startLine', -1);
    }

    /**
     * Get the line number that this function ends on.
     *
     * @return int
     */
    public function getEndLine() : int
    {
        return (int) $this->node->getAttribute('endLine', -1);
    }

    public function getStartColumn() : int
    {
        return CalculateReflectionColum::getStartColumn($this->locatedSource->getSource(), $this->node);
    }

    public function getEndColumn() : int
    {
        return CalculateReflectionColum::getEndColumn($this->locatedSource->getSource(), $this->node);
    }

    /**
     * Is this function declared as a reference.
     *
     * @return bool
     */
    public function returnsReference() : bool
    {
        return (bool) $this->node->byRef;
    }

    /**
     * Get the return types defined in the DocBlocks. This returns an array because
     * the parameter may have multiple (compound) types specified (for example
     * when you type hint pipe-separated "string|null", in which case this
     * would return an array of Type objects, one for string, one for null.
     *
     * @return Type[]
     */
    public function getDocBlockReturnTypes() : array
    {
        return  (new FindReturnType())->__invoke($this, $this->declaringNamespace);
    }

    /**
     * Get the return type declaration (only for PHP 7+ code)
     *
     * @return ReflectionType|null
     */
    public function getReturnType() : ?ReflectionType
    {
        $returnType = $this->node->getReturnType();

        if (null === $returnType) {
            return null;
        }

        if ($returnType instanceof NullableType) {
            return ReflectionType::createFromType((string) $returnType->type, true);
        }

        return ReflectionType::createFromType((string) $returnType, false);
    }

    /**
     * Do we have a return type declaration (only for PHP 7+ code)
     *
     * @return bool
     */
    public function hasReturnType() : bool
    {
        return null !== $this->getReturnType();
    }

    /**
     * Set the return type declaration.
     *
     * @param string $newReturnType
     */
    public function setReturnType(string $newReturnType) : void
    {
        $this->node->returnType = new Node\Name($newReturnType);
    }

    /**
     * Remove the return type declaration completely.
     */
    public function removeReturnType() : void
    {
        $this->node->returnType = null;
    }

    /**
     * @throws Uncloneable
     */
    public function __clone()
    {
        throw Uncloneable::fromClass(__CLASS__);
    }

    /**
     * Retrieves the body of this function as AST nodes
     *
     * @return Node[]
     */
    public function getBodyAst() : array
    {
        return $this->node->stmts;
    }

    /**
     * Retrieves the body of this function as code.
     *
     * If a PrettyPrinter is provided as a paramter, it will be used, otherwise
     * a default will be used.
     *
     * Note that the formatting of the code may not be the same as the original
     * function. If specific formatting is required, you should provide your
     * own implementation of a PrettyPrinter to unparse the AST.
     *
     * @param PrettyPrinterAbstract|null $printer
     * @return string
     */
    public function getBodyCode(?PrettyPrinterAbstract $printer = null) : string
    {
        if (null === $printer) {
            $printer = new StandardPrettyPrinter();
        }

        return $printer->prettyPrint($this->getBodyAst());
    }

    /**
     * Fetch the AST for this method or function.
     *
     * @return Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\FunctionLike
     */
    public function getAst() : Node\FunctionLike
    {
        return $this->node;
    }

    /**
     * Override the method or function's body of statements with an entirely new
     * body of statements within the reflection.
     *
     * @example
     * $reflectionFunction->setBodyFromClosure(function () { return true; });
     *
     * @param \Closure $newBody
     *
     * @throws \Rector\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure
     * @throws \Rector\BetterReflection\Identifier\Exception\InvalidIdentifierName
     */
    public function setBodyFromClosure(Closure $newBody) : void
    {
        /** @var self $closureReflection */
        $closureReflection = (new ClosureSourceLocator($newBody, $this->loadStaticParser()))->locateIdentifier(
            $this->reflector,
            new Identifier(self::CLOSURE_NAME, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );

        $this->node->stmts = $closureReflection->getNode()->stmts;
    }

    /**
     * Override the method or function's body of statements with an entirely new
     * body of statements within the reflection.
     *
     * @example
     * $reflectionFunction->setBodyFromString('return true;');
     *
     * @param string $newBody
     */
    public function setBodyFromString(string $newBody) : void
    {
        $this->node->stmts = $this->loadStaticParser()->parse('<?php ' . $newBody);
    }

    /**
     * Override the method or function's body of statements with an entirely new
     * body of statements within the reflection.
     *
     * @example
     * // $ast should be an array of Nodes
     * $reflectionFunction->setBodyFromAst($ast);
     *
     * @param Node[] $nodes
     */
    public function setBodyFromAst(array $nodes) : void
    {
        // This slightly confusing code simply type-checks the $sourceLocators
        // array by unpacking them and splatting them in the closure.
        $validator = function (Node ...$node) : array {
            return $node;
        };
        $this->node->stmts = $validator(...$nodes);
    }

    /**
     * Add a new parameter to the method/function.
     *
     * @param string $parameterName
     */
    public function addParameter(string $parameterName) : void
    {
        $this->node->params[] = new ParamNode($parameterName);
    }

    /**
     * Remove a parameter from the method/function.
     *
     * @param string $parameterName
     * @return void
     */
    public function removeParameter(string $parameterName) : void
    {
        $lowerName = \strtolower($parameterName);

        foreach ($this->node->params as $key => $paramNode) {
            if (\strtolower($paramNode->name) === $lowerName) {
                unset($this->node->params[$key]);
            }
        }
    }

    /**
     * Fetch an array of all return statements found within this function.
     *
     * Note that return statements within smaller scopes contained (e.g. anonymous classes, closures) are not returned
     * here as they are not within the immediate scope.
     *
     * @return Node\Stmt\Return_[]
     */
    public function getReturnStatementsAst() : array
    {
        $visitor = new ReturnNodeVisitor();

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);

        $traverser->traverse($this->node->getStmts());

        return $visitor->getReturnNodes();
    }

    final private function loadStaticParser() : Parser
    {
        return self::$parser ?? self::$parser = (new BetterReflection())->phpParser();
    }
}
